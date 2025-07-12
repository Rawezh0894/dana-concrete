<?php
session_start();
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'msg' => 'سێشن نییە!']);
    exit;
}

$customer_id = $_GET['customer_id'] ?? null;
if (!$customer_id) {
    echo json_encode(['success' => false, 'msg' => 'ناسنامەی کڕیار پێویستە!']);
    exit;
}

try {
    $stmt = $pdo->prepare('SELECT COALESCE(SUM(remaining_amount), 0) as total_remaining FROM sales WHERE customer_id = ? AND payment_type = "قەرز"');
    $stmt->execute([$customer_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($row) {
        echo json_encode(['success' => true, 'debt_usd' => floatval($row['total_remaining'] ?? 0)]);
    } else {
        echo json_encode(['success' => false, 'msg' => 'کڕیار نەدۆزرایەوە!']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'msg' => 'هەڵە لە بەدەستهێنانی زانیاری!']);
}
?> 