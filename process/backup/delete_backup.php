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
$filename = $input['filename'] ?? '';

if ($action !== 'delete_backup') {
    echo json_encode(['success' => false, 'message' => 'کرداری نەدراوە']);
    exit;
}

if (empty($filename)) {
    echo json_encode(['success' => false, 'message' => 'ناوی فایلەکە پێویستە']);
    exit;
}

try {
    // Set backup directory
    $backup_dir = '../../backups/';
    $file_path = $backup_dir . $filename;
    
    // Security check - only allow .sql files
    if (pathinfo($filename, PATHINFO_EXTENSION) !== 'sql') {
        throw new Exception('تەنها فایلەکانی SQL ڕێگەپێدراون');
    }
    
    // Security check - prevent directory traversal
    if (strpos($filename, '..') !== false || strpos($filename, '/') !== false || strpos($filename, '\\') !== false) {
        throw new Exception('ناوی فایلەکە نادروستە');
    }
    
    // Check if file exists
    if (!file_exists($file_path)) {
        throw new Exception('فایلەکە نەدۆزرایەوە');
    }
    
    // Get file info before deletion
    $file_size = filesize($file_path);
    
    // Delete the file
    if (!unlink($file_path)) {
        throw new Exception('نەتوانرا فایلەکە بسڕێتەوە');
    }
    
    // Log deletion
    error_log("Backup file deleted: {$filename} (" . formatFileSize($file_size) . ")");
    
    echo json_encode([
        'success' => true,
        'message' => 'باک ئەپ بە سەرکەوتوویی سڕایەوە',
        'deleted_file' => $filename,
        'file_size' => $file_size
    ]);
    
} catch (Exception $e) {
    error_log("Backup deletion error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, 2) . ' ' . $units[$pow];
}
?>
