<?php
session_start();
require_once '../../config/db_conected.php';
header('Content-Type: application/json');
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'manager'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}
$search = $_GET['search'] ?? '';
$type = $_GET['type'] ?? '';
$seen = $_GET['seen'] ?? '';
$date_filter = $_GET['date_filter'] ?? '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = isset($_GET['limit']) ? max(1, intval($_GET['limit'])) : 5;
$offset = ($page - 1) * $limit;
$where = [];
$params = [];
if ($search !== '') {
    $where[] = '(n.description LIKE ? OR n.table_name LIKE ? OR n.action LIKE ? OR u.username LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($type !== '') {
    $where[] = 'n.action = ?';
    $params[] = $type;
}
if ($seen !== '') {
    $where[] = 'n.seen = ?';
    $params[] = $seen;
}
if ($date_filter === 'today') {
    $where[] = 'DATE(n.created_at) = CURDATE()';
}
if ($date_filter === 'yesterday') {
    $where[] = 'DATE(n.created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)';
}
$where_sql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
// Get total count
$count_sql = "SELECT COUNT(*) FROM notifications n LEFT JOIN users u ON n.user_id = u.id $where_sql";
$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$total = $stmt->fetchColumn();
// Get paginated notifications
$sql = "SELECT n.*, u.username FROM notifications n LEFT JOIN users u ON n.user_id = u.id $where_sql ORDER BY n.created_at DESC LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Kurdish action translation
$action_ku = ['insert'=>'زیادکردن','update'=>'نوێکردنەوە','delete'=>'سڕینەوە'];
$notifications = array_map(function($row) use ($action_ku) {
    $row['action_ku'] = $action_ku[$row['action']] ?? $row['action'];
    return $row;
}, $rows);
echo json_encode([
    'success' => true,
    'notifications' => $notifications,
    'total' => intval($total),
    'page' => $page,
    'limit' => $limit
]); 