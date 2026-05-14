<?php
session_start();
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id']) || !hasPermission('view_cash_box')) {
    echo json_encode(['success' => false, 'error' => 'دەستپێگەیشتن قەدەغەیە']);
    exit;
}

// Handle both GET and POST requests
$request_data = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $_GET;

$from = $request_data['from'] ?? null;
$to = $request_data['to'] ?? null;
$search = isset($request_data['search']) ? trim((string) $request_data['search']) : '';

// Pagination parameters
$page = isset($request_data['page']) ? max(1, intval($request_data['page'])) : 1;
$limit = isset($request_data['limit']) ? max(10, min(500, intval($request_data['limit']))) : 10;
$offset = ($page - 1) * $limit;

$where = [];
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
    // Get total count
    $count_sql = "SELECT COUNT(*) as total FROM cash_box cb $whereSql";
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total_records = $count_stmt->fetchColumn();
    
    // Get paginated data
    $sql = "SELECT cb.*, u.username as created_by_username
            FROM cash_box cb
            LEFT JOIN users u ON cb.created_by = u.id
            $whereSql
            ORDER BY cb.date DESC, cb.id DESC
            LIMIT ? OFFSET ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array_merge($params, [$limit, $offset]));
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate pagination info
    $total_pages = ceil($total_records / $limit);
    
    echo json_encode([
        'success' => true, 
        'data' => $rows,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $total_pages,
            'total_records' => $total_records,
            'per_page' => $limit,
            'has_next' => $page < $total_pages,
            'has_prev' => $page > 1
        ]
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
