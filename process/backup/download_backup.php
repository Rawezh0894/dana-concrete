<?php
session_start();
require_once '../../config/db_conected.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'دەبێت سەرەتا چوونەژوورەوە بکەیت']);
    exit;
}

// Get filename from query parameter
$filename = $_GET['file'] ?? '';

if (empty($filename)) {
    http_response_code(400);
    echo 'ناوی فایلەکە پێویستە';
    exit;
}

// Security check - only allow .sql files
if (pathinfo($filename, PATHINFO_EXTENSION) !== 'sql') {
    http_response_code(400);
    echo 'تەنها فایلەکانی SQL ڕێگەپێدراون';
    exit;
}

// Security check - prevent directory traversal
if (strpos($filename, '..') !== false || strpos($filename, '/') !== false || strpos($filename, '\\') !== false) {
    http_response_code(400);
    echo 'ناوی فایلەکە نادروستە';
    exit;
}

try {
    // Set backup directory
    $backup_dir = '../../backups/';
    $file_path = $backup_dir . $filename;
    
    // Check if file exists
    if (!file_exists($file_path)) {
        http_response_code(404);
        echo 'فایلەکە نەدۆزرایەوە';
        exit;
    }
    
    // Normalize collations for compatibility before download
    sanitizeBackupCollations($file_path);

    // Get file info
    $file_size = filesize($file_path);
    $file_mime = 'application/sql';
    
    // Set headers for file download
    header('Content-Type: ' . $file_mime);
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . $file_size);
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
    
    // Clear any output buffer
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    // Read and output file
    $handle = fopen($file_path, 'rb');
    if ($handle === false) {
        throw new Exception('نەتوانرا فایلەکە کرانەوە');
    }
    
    // Stream file in chunks to handle large files
    $chunk_size = 8192; // 8KB chunks
    while (!feof($handle)) {
        $chunk = fread($handle, $chunk_size);
        if ($chunk === false) {
            break;
        }
        echo $chunk;
        flush();
    }
    
    fclose($handle);
    
    // Log download
    error_log("Backup file downloaded: {$filename} (" . formatFileSize($file_size) . ")");
    
} catch (Exception $e) {
    error_log("Download error: " . $e->getMessage());
    http_response_code(500);
    echo 'هەڵەیەک ڕوویدا لە داونلۆدکردنی فایلەکە';
}

function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, 2) . ' ' . $units[$pow];
}

function sanitizeBackupCollations($file_path) {
    if (!is_readable($file_path) || !is_writable($file_path)) {
        return;
    }

    $content = file_get_contents($file_path);
    if ($content === false || $content === '') {
        return;
    }

    $replacements = [
        'utf8mb4_0900_ai_ci' => 'utf8mb4_unicode_ci',
        'utf8mb4_0900_as_cs' => 'utf8mb4_unicode_ci',
        'utf8mb4_0900_as_ci' => 'utf8mb4_unicode_ci',
        'utf8mb4_0900_bin'   => 'utf8mb4_bin',
        'utf8mb4_0900_general_ci' => 'utf8mb4_unicode_ci'
    ];

    $updated = str_replace(array_keys($replacements), array_values($replacements), $content);
    $updated = preg_replace('/SET NAMES utf8mb4 COLLATE utf8mb4_0900_[^;]+;/', 'SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;', $updated);

    if ($updated !== null) {
        file_put_contents($file_path, $updated);
    }
}
?>
