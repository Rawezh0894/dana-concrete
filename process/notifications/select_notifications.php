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
$seen = $_GET['seen'] ?? ''; // Empty by default to show all notifications (both read and unread)
$date_filter = $_GET['date_filter'] ?? '';

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

// Get all notifications (no pagination - handled by TableController)
$sql = "SELECT n.*, u.username FROM notifications n LEFT JOIN users u ON n.user_id = u.id $where_sql ORDER BY n.created_at DESC";
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
    'total' => count($notifications)
]); 