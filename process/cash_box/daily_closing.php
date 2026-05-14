<?php
session_start();
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id']) || !hasPermission('view_cash_box')) {
    echo json_encode(['success' => false, 'error' => 'دەستپێگەیشتن قەدەغەیە']);
    exit;
}

$from   = $_GET['from']   ?? null;
$to     = $_GET['to']     ?? null;
$search = isset($_GET['search']) ? trim((string) $_GET['search']) : '';

$where  = [];
$params = [];
if ($from) {
    $where[]  = 'date >= ?';
    $params[] = $from;
}
if ($to) {
    $where[]  = 'date <= ?';
    $params[] = $to;
}
if ($search !== '') {
    $where[]  = '(note LIKE ? OR CAST(date AS CHAR) LIKE ?)';
    $like     = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

try {
    // Daily inflow / outflow totals per date
    $daily_stmt = $pdo->prepare("
        SELECT
            date,
            COALESCE(SUM(CASE WHEN type='deposit'  AND currency='دۆلار' THEN amount_usd ELSE 0 END), 0) AS inflow_usd,
            COALESCE(SUM(CASE WHEN type='withdraw' AND currency='دۆلار' THEN amount_usd ELSE 0 END), 0) AS outflow_usd,
            COALESCE(SUM(CASE WHEN type='deposit'  AND currency='دینار' THEN amount_iqd ELSE 0 END), 0) AS inflow_iqd,
            COALESCE(SUM(CASE WHEN type='withdraw' AND currency='دینار' THEN amount_iqd ELSE 0 END), 0) AS outflow_iqd,
            COUNT(*) AS tx_count
        FROM cash_box
        $whereSql
        GROUP BY date
        ORDER BY date ASC
    ");
    $daily_stmt->execute($params);
    $daily_rows = $daily_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Opening balance = cumulative net BEFORE the filter start date
    $opening_usd = 0.0;
    $opening_iqd = 0.0;
    if ($from) {
        $open_stmt = $pdo->prepare("
            SELECT
                COALESCE(SUM(CASE WHEN type='deposit'  AND currency='دۆلار' THEN  amount_usd
                               WHEN type='withdraw' AND currency='دۆلار' THEN -amount_usd ELSE 0 END), 0) AS bal_usd,
                COALESCE(SUM(CASE WHEN type='deposit'  AND currency='دینار' THEN  amount_iqd
                               WHEN type='withdraw' AND currency='دینار' THEN -amount_iqd ELSE 0 END), 0) AS bal_iqd
            FROM cash_box WHERE date < ?
        ");
        $open_stmt->execute([$from]);
        $opening   = $open_stmt->fetch(PDO::FETCH_ASSOC);
        $opening_usd = (float) $opening['bal_usd'];
        $opening_iqd = (float) $opening['bal_iqd'];
    }

    // Compute running closing balance for each day
    $running_usd = $opening_usd;
    $running_iqd = $opening_iqd;
    $result      = [];

    foreach ($daily_rows as $row) {
        $running_usd += (float) $row['inflow_usd'] - (float) $row['outflow_usd'];
        $running_iqd += (float) $row['inflow_iqd'] - (float) $row['outflow_iqd'];

        $result[] = [
            'date'         => $row['date'],
            'inflow_usd'   => (float) $row['inflow_usd'],
            'outflow_usd'  => (float) $row['outflow_usd'],
            'inflow_iqd'   => (float) $row['inflow_iqd'],
            'outflow_iqd'  => (float) $row['outflow_iqd'],
            'tx_count'     => (int)   $row['tx_count'],
            'closing_usd'  => round($running_usd, 2),
            'closing_iqd'  => round($running_iqd, 0),
        ];
    }

    // Return newest-first for display
    $result = array_reverse($result);

    echo json_encode([
        'success'      => true,
        'data'         => $result,
        'opening_usd'  => $opening_usd,
        'opening_iqd'  => $opening_iqd,
    ]);
} catch (\Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
