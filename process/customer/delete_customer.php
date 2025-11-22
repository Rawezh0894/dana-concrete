<?php
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';
if (!hasPermission('delete_customer')) {
    echo json_encode(['success' => false, 'message' => 'ڕێگەت پێنەدراوە!']);
    exit;
}
header('Content-Type: application/json; charset=utf-8');

if (empty($_POST['id']) || !is_numeric($_POST['id'])) {
    echo json_encode(['success' => false, 'message' => 'ناسنامەی هەڵە']);
    exit;
}
$id = (int)$_POST['id'];

// Get customer name and check if is_recipient
$customerStmt = $pdo->prepare('SELECT name, is_recipient FROM customers WHERE id = ?');
$customerStmt->execute([$id]);
$customer = $customerStmt->fetch(PDO::FETCH_ASSOC);

if (!$customer) {
    echo json_encode(['success' => false, 'message' => 'کڕیار نەدۆزرایەوە']);
    exit;
}

$customerName = $customer['name'];
$isRecipient = $customer['is_recipient'] == 1;

// Prevent delete if customer has payments
$hasPayments = $pdo->prepare('SELECT COUNT(*) FROM customer_debt_payments WHERE customer_id = ?');
$hasPayments->execute([$id]);
if ($hasPayments->fetchColumn() > 0) {
    echo json_encode(['success' => false, 'message' => 'ناتوانرێت کڕیار بسڕدرێت چونکە پارەدان بۆ تۆمارکراوە']);
    exit;
}

// Check sales: by customer_id OR by recipient name (if customer is also recipient)
if ($isRecipient) {
    $hasSales = $pdo->prepare('SELECT COUNT(*) FROM sales WHERE customer_id = ? OR recipient = ?');
    $hasSales->execute([$id, $customerName]);
} else {
    $hasSales = $pdo->prepare('SELECT COUNT(*) FROM sales WHERE customer_id = ?');
    $hasSales->execute([$id]);
}
if ($hasSales->fetchColumn() > 0) {
    echo json_encode(['success' => false, 'message' => 'ناتوانرێت کڕیار بسڕدرێت چونکە پسووڵەی فرۆشتن بۆ تۆمارکراوە']);
    exit;
}

// Check concrete receipts: by customer_id OR by receiver_name (if customer is also recipient)
if ($isRecipient) {
    $hasConcreteReceipts = $pdo->prepare('SELECT COUNT(*) FROM concrete_receipts WHERE customer_id = ? OR receiver_name = ?');
    $hasConcreteReceipts->execute([$id, $customerName]);
} else {
    $hasConcreteReceipts = $pdo->prepare('SELECT COUNT(*) FROM concrete_receipts WHERE customer_id = ?');
    $hasConcreteReceipts->execute([$id]);
}
if ($hasConcreteReceipts->fetchColumn() > 0) {
    echo json_encode(['success' => false, 'message' => 'ناتوانرێت کڕیار بسڕدرێت چونکە پسووڵەی کۆنکرێت بۆ تۆمارکراوە']);
    exit;
}

// Check notes: by customer_id OR by recipient name (if customer is also recipient)
if ($isRecipient) {
    $hasNotes = $pdo->prepare('SELECT COUNT(*) FROM notes WHERE customer_id = ? OR recipient = ?');
    $hasNotes->execute([$id, $customerName]);
} else {
    $hasNotes = $pdo->prepare('SELECT COUNT(*) FROM notes WHERE customer_id = ?');
    $hasNotes->execute([$id]);
}
if ($hasNotes->fetchColumn() > 0) {
    echo json_encode(['success' => false, 'message' => 'ناتوانرێت کڕیار بسڕدرێت چونکە تێبینی بۆ تۆمارکراوە']);
    exit;
}

try {
    $stmt = $pdo->prepare('DELETE FROM customers WHERE id = ?');
    $stmt->execute([$id]);
    if ($stmt->rowCount()) {
        echo json_encode(['success' => true, 'message' => 'کڕیار سڕایەوە']);
    } else {
        echo json_encode(['success' => false, 'message' => 'کڕیار نەدۆزرایەوە']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'هەڵەیەک ڕووی دا: ' . $e->getMessage()]);
}
