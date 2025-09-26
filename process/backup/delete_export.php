<?php
session_start();
require_once '../../config/db_conected.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'دەبێت سەرەتا چوونەژوورەوە بکەیت']);
    exit;
}

// Get request data
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';
$file_path = $input['file_path'] ?? '';

if ($action === 'delete_export') {
    if (!empty($file_path) && file_exists($file_path)) {
        unlink($file_path);
        echo json_encode(['success' => true, 'message' => 'فایلەکە سڕایەوە']);
    } else {
        echo json_encode(['success' => false, 'message' => 'فایلەکە نەدۆزرایەوە']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'کرداری نەدراوە']);
}
?>
