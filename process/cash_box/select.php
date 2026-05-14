<?php
session_start();
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id']) || !hasPermission('view_cash_box')) {
    echo json_encode(['success' => false, 'error' => 'دەستپێگەیشتن قەدەغەیە']);
    exit;
}

$request_data = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $_GET;

$from   = $request_data['from']  ?? null;
$to     = $request_data['to']    ?? null;
$search = isset($request_data['search']) ? trim((string) $request_data['search']) : '';

$page   = isset($request_data['page'])  ? max(1, intval($request_data['page']))               : 1;
$limit  = isset($request_data['limit']) ? max(10, min(500, intval($request_data['limit'])))   : 10;
$offset = ($page - 1) * $limit;

$where  = [];
$params = [];
if ($from) {
    $where[] = 'cb.date >= ?';
    $params[] = $from;
}
if ($to) {
    $where[] = 'cb.date <= ?';
    $params[] = $to;
}
if ($search !== '') {
    $where[] = '(cb.note LIKE ? OR CAST(cb.date AS CHAR) LIKE ?)';
    $like = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

try {
    $count_sql  = "SELECT COUNT(*) as total FROM cash_box cb $whereSql";
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total_records = $count_stmt->fetchColumn();

    // Running balance using window functions (MySQL 8+).
    // Separate tracking for USD-currency and IQD-currency transactions.
    $innerSql = "
        SELECT cb.*, u.username AS created_by_username,
            SUM(CASE
                WHEN cb.type='deposit'  AND cb.currency='دۆلار' THEN  cb.amount_usd
                WHEN cb.type='withdraw' AND cb.currency='دۆلار' THEN -(cb.amount_usd)
                ELSE 0
            END) OVER (ORDER BY cb.date ASC, cb.id ASC) AS running_bal_usd,
            SUM(CASE
                WHEN cb.type='deposit'  AND cb.currency='دینار' THEN  cb.amount_iqd
                WHEN cb.type='withdraw' AND cb.currency='دینار' THEN -(cb.amount_iqd)
                ELSE 0
            END) OVER (ORDER BY cb.date ASC, cb.id ASC) AS running_bal_iqd
        FROM cash_box cb
        LEFT JOIN users u ON cb.created_by = u.id
        $whereSql
    ";

    $sql  = "SELECT * FROM ($innerSql) t ORDER BY t.date DESC, t.id DESC LIMIT ? OFFSET ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array_merge($params, [$limit, $offset]));
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (\PDOException $winErr) {
    // Fallback for MySQL < 8 (no window function support)
    $sql  = "SELECT cb.*, u.username AS created_by_username,
                    NULL AS running_bal_usd, NULL AS running_bal_iqd
             FROM cash_box cb
             LEFT JOIN users u ON cb.created_by = u.id
             $whereSql
             ORDER BY cb.date DESC, cb.id DESC
             LIMIT ? OFFSET ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array_merge($params, [$limit, $offset]));
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$total_pages = (int) ceil($total_records / $limit);

echo json_encode([
    'success' => true,
    'data'    => $rows,
    'pagination' => [
        'current_page'  => $page,
        'total_pages'   => $total_pages,
        'total_records' => $total_records,
        'per_page'      => $limit,
        'has_next'      => $page < $total_pages,
        'has_prev'      => $page > 1,
    ],
]);
