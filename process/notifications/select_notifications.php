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

// Get notifications
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

// Get all notifications
$sql = "SELECT n.*, u.username FROM notifications n LEFT JOIN users u ON n.user_id = u.id $where_sql ORDER BY n.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get notes
$notes_where = [];
$notes_params = [];

if ($search !== '') {
    $notes_where[] = '(c.name LIKE ? OR n.location LIKE ? OR n.recipient LIKE ?)';
    $notes_params[] = "%$search%";
    $notes_params[] = "%$search%";
    $notes_params[] = "%$search%";
}

if ($seen !== '') {
    $notes_where[] = 'n.is_read = ?';
    $notes_params[] = $seen;
}

if ($date_filter === 'today') {
    $notes_where[] = 'n.date = CURDATE()';
}
if ($date_filter === 'yesterday') {
    $notes_where[] = 'n.date = DATE_SUB(CURDATE(), INTERVAL 1 DAY)';
}

$notes_where_sql = $notes_where ? ('WHERE ' . implode(' AND ', $notes_where)) : '';

$notes_sql = "SELECT 
    n.*,
    c.name AS customer_name,
    f.name AS formula_name,
    mc.name AS mixer_car_name,
    md.name AS mixer_driver_name,
    pc.name AS pump_car_name,
    pd.name AS pump_driver_name
FROM notes n
LEFT JOIN customers c ON n.customer_id = c.id
LEFT JOIN concrete_formulas f ON n.formula_id = f.id
LEFT JOIN cars mc ON n.mixer_car_id = mc.id
LEFT JOIN employees md ON n.mixer_driver_id = md.id
LEFT JOIN cars pc ON n.pump_car_id = pc.id
LEFT JOIN employees pd ON n.pump_driver_id = pd.id
$notes_where_sql
ORDER BY n.date DESC, n.time DESC";

$stmt = $pdo->prepare($notes_sql);
$stmt->execute($notes_params);
$notes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Convert notes to notification format
$notes_as_notifications = array_map(function($note) {
    return [
        'id' => 'note_' . $note['id'],
        'action' => 'insert',
        'action_ku' => 'زیادکردن',
        'table_name' => 'notes',
        'description' => "تێبینی بۆ کڕیار: {$note['customer_name']} - شوێن: {$note['location']} - بڕ: {$note['meter_amount']} م³",
        'record_id' => $note['id'],
        'username' => 'سیستەم',
        'created_at' => $note['date'] . ' ' . $note['time'],
        'seen' => $note['is_read'],
        'old_values' => null,
        'new_values' => json_encode([
            'customer_name' => $note['customer_name'],
            'location' => $note['location'],
            'recipient' => $note['recipient'],
            'meter_amount' => $note['meter_amount'],
            'formula_name' => $note['formula_name'],
            'mixer_car_name' => $note['mixer_car_name'],
            'mixer_driver_name' => $note['mixer_driver_name'],
            'pump_car_name' => $note['pump_car_name'],
            'pump_driver_name' => $note['pump_driver_name']
        ], JSON_UNESCAPED_UNICODE),
        'additional_info' => json_encode([
            'date' => $note['date'],
            'time' => $note['time'],
            'formula_id' => $note['formula_id'],
            'mixer_car_id' => $note['mixer_car_id'],
            'mixer_driver_id' => $note['mixer_driver_id'],
            'pump_car_id' => $note['pump_car_id'],
            'pump_driver_id' => $note['pump_driver_id']
        ], JSON_UNESCAPED_UNICODE),
        'ip_address' => null,
        'is_note' => true
    ];
}, $notes);

// Combine notifications and notes
$all_notifications = array_merge($notes_as_notifications, $notifications);

// Sort by created_at (most recent first)
usort($all_notifications, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});

// Kurdish action translation for regular notifications
$action_ku = ['insert'=>'زیادکردن','update'=>'نوێکردنەوە','delete'=>'سڕینەوە'];
$all_notifications = array_map(function($row) use ($action_ku) {
    if (!isset($row['action_ku'])) {
        $row['action_ku'] = $action_ku[$row['action']] ?? $row['action'];
    }
    return $row;
}, $all_notifications);

echo json_encode([
    'success' => true,
    'notifications' => $all_notifications,
    'total' => count($all_notifications)
]); 