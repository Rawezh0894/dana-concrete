<?php
session_start();
require_once '../../config/db_conected.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'کاربر لاگین نەکراوە']);
    exit;
}

// Check if location ID is provided
if (!isset($_POST['id']) || empty($_POST['id'])) {
    echo json_encode(['success' => false, 'message' => 'ئایدی شوێنەکە نەدراوە']);
    exit;
}

$location_id = (int)$_POST['id'];

try {
    // Check if location exists
    $stmt = $pdo->prepare("SELECT name FROM locations WHERE id = ?");
    $stmt->execute([$location_id]);
    $location = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$location) {
        echo json_encode(['success' => false, 'message' => 'شوێنەکە نەدۆزرایەوە']);
        exit;
    }
    
    // Check if location is being used in purchases
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM purchases WHERE location = ?");
    $stmt->execute([$location['name']]);
    $usage = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($usage['count'] > 0) {
        echo json_encode(['success' => false, 'message' => 'ناتوانرێت شوێنەکە بسڕیتەوە چونکە لە کڕینەکاندا بەکاردەهێنرێت']);
        exit;
    }
    
    // Delete the location
    $stmt = $pdo->prepare("DELETE FROM locations WHERE id = ?");
    $result = $stmt->execute([$location_id]);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'شوێنەکە بە سەرکەوتوویی سڕایەوە']);
    } else {
        echo json_encode(['success' => false, 'message' => 'هەڵەیەک ڕوویدا لە سڕینەوەی شوێنەکە']);
    }
    
} catch (PDOException $e) {
    error_log("Database error in delete_location.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'هەڵەیەک ڕوویدا لە داتابەیس: ' . $e->getMessage()]);
}
?>
