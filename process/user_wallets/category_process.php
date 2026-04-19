<?php
session_start();
require_once '../../config/db_conected.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? '';

try {
    if ($action === 'add') {
        $name = $_POST['name'] ?? '';
        $type = $_POST['type'] ?? 'BOTH';
        
        if (empty($name)) throw new Exception("ناو پێویستە");
        
        $stmt = $pdo->prepare("INSERT INTO transaction_categories (name, type) VALUES (?, ?)");
        $stmt->execute([$name, $type]);
        
        echo json_encode(['success' => true, 'message' => 'جۆرەکە بە سەرکەوتوویی زیادکرا']);
    } 
    elseif ($action === 'edit') {
        $id = intval($_POST['id'] ?? 0);
        $name = $_POST['name'] ?? '';
        $type = $_POST['type'] ?? 'BOTH';
        
        if (empty($name)) throw new Exception("ناو پێویستە");
        
        $stmt = $pdo->prepare("UPDATE transaction_categories SET name = ?, type = ? WHERE id = ?");
        $stmt->execute([$name, $type, $id]);
        
        echo json_encode(['success' => true, 'message' => 'جۆرەکە نوێکرایەوە']);
    } 
    elseif ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        
        // چککردن کە ئایا ئەم جۆرە بەکارهاتووە لە ناو مامەڵەکاندا
        $check = $pdo->prepare("SELECT COUNT(*) FROM transactions WHERE category_id = ?");
        $check->execute([$id]);
        if ($check->fetchColumn() > 0) {
            throw new Exception("ناتوانرێت ئەم جۆرە بسڕدرێتەوە چونکە لە ناو مامەڵەکاندا بەکارهاتووە");
        }
        
        $stmt = $pdo->prepare("DELETE FROM transaction_categories WHERE id = ?");
        $stmt->execute([$id]);
        
        echo json_encode(['success' => true, 'message' => 'جۆرەکە بە سەرکەوتوویی سڕدرایەوە']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
