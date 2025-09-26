<?php
// Fix backup directory permissions
header('Content-Type: application/json');

try {
    $backup_dir = '../../backups/';
    
    $results = [
        'backup_directory' => $backup_dir,
        'absolute_path' => realpath($backup_dir) ?: 'Directory does not exist',
        'current_permissions' => file_exists($backup_dir) ? substr(sprintf('%o', fileperms($backup_dir)), -4) : 'N/A',
        'is_writable' => is_writable($backup_dir),
        'owner' => file_exists($backup_dir) ? (function_exists('posix_getpwuid') ? posix_getpwuid(fileowner($backup_dir))['name'] : 'Unknown') : 'N/A',
        'group' => file_exists($backup_dir) ? (function_exists('posix_getgrgid') ? posix_getgrgid(filegroup($backup_dir))['name'] : 'Unknown') : 'N/A',
    ];
    
    // Try to create directory if it doesn't exist
    if (!file_exists($backup_dir)) {
        $results['creating_directory'] = true;
        if (mkdir($backup_dir, 0755, true)) {
            $results['directory_created'] = true;
            $results['new_permissions'] = substr(sprintf('%o', fileperms($backup_dir)), -4);
        } else {
            $results['directory_created'] = false;
            $results['error'] = 'Failed to create directory';
        }
    }
    
    // Try to make it writable
    if (file_exists($backup_dir) && !is_writable($backup_dir)) {
        $results['attempting_to_fix_permissions'] = true;
        
        // Try chmod
        if (chmod($backup_dir, 0755)) {
            $results['chmod_success'] = true;
            $results['new_permissions'] = substr(sprintf('%o', fileperms($backup_dir)), -4);
        } else {
            $results['chmod_success'] = false;
        }
    }
    
    // Test file creation
    $test_file = $backup_dir . 'permission_test_' . time() . '.txt';
    $test_content = 'Test file created at ' . date('Y-m-d H:i:s');
    
    $bytes_written = file_put_contents($test_file, $test_content);
    $results['test_file_creation'] = [
        'attempted' => true,
        'success' => $bytes_written !== false,
        'bytes_written' => $bytes_written,
        'file_exists' => file_exists($test_file),
        'file_readable' => file_exists($test_file) ? is_readable($test_file) : false,
    ];
    
    // Clean up test file
    if (file_exists($test_file)) {
        unlink($test_file);
        $results['test_file_cleaned'] = true;
    }
    
    // Get system information
    $results['system_info'] = [
        'php_user' => get_current_user(),
        'web_server_user' => function_exists('posix_getpwuid') ? posix_getpwuid(posix_geteuid())['name'] : 'Unknown',
        'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown',
        'script_path' => __FILE__,
        'current_working_directory' => getcwd(),
    ];
    
    echo json_encode([
        'success' => true,
        'results' => $results
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ], JSON_PRETTY_PRINT);
}
?>
