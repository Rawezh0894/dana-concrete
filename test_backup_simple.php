<?php
// Simple backup test script
header('Content-Type: application/json');

try {
    require_once 'config/db_conected.php';
    
    $host = env('DB_HOST', 'localhost');
    $username = env('DB_USERNAME', 'dana_user');
    $password = env('DB_PASSWORD', 'Rawezh.Jaza@0894');
    $database = env('DB_DATABASE', 'dana_concrete_db');
    
    // Test backup directory
    $backup_dir = '../../backups/';
    if (!file_exists($backup_dir)) {
        mkdir($backup_dir, 0755, true);
    }
    
    $test_results = [
        'backup_directory_exists' => file_exists($backup_dir),
        'backup_directory_writable' => is_writable($backup_dir),
        'backup_directory_path' => realpath($backup_dir),
    ];
    
    // Test database connection
    $pdo = new PDO("mysql:host={$host};dbname={$database};charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Test table count
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $test_results['tables_count'] = count($tables);
    $test_results['tables'] = $tables;
    
    // Test creating a simple backup file
    $test_filename = "test_backup_" . date('Y-m-d_H-i-s') . ".sql";
    $test_path = $backup_dir . $test_filename;
    
    $test_content = "-- Test backup file\n";
    $test_content .= "-- Created: " . date('Y-m-d H:i:s') . "\n";
    $test_content .= "-- Database: {$database}\n";
    $test_content .= "-- Tables: " . implode(', ', $tables) . "\n";
    
    $bytes_written = file_put_contents($test_path, $test_content);
    $test_results['test_file_created'] = $bytes_written !== false;
    $test_results['test_file_size'] = $bytes_written;
    $test_results['test_file_exists'] = file_exists($test_path);
    $test_results['test_file_readable'] = is_readable($test_path);
    
    if (file_exists($test_path)) {
        $test_results['test_file_content'] = file_get_contents($test_path);
        // Clean up test file
        unlink($test_path);
    }
    
    // Test mysqldump if available
    $mysqldump_paths = ['mysqldump', '/usr/bin/mysqldump', '/usr/local/bin/mysqldump'];
    $mysqldump_found = false;
    
    foreach ($mysqldump_paths as $path) {
        if ($path === 'mysqldump') {
            $output = [];
            $return_code = 0;
            exec("mysqldump --version 2>&1", $output, $return_code);
            if ($return_code === 0) {
                $mysqldump_found = true;
                $test_results['mysqldump_path'] = $path;
                $test_results['mysqldump_version'] = implode(' ', $output);
                break;
            }
        } else {
            if (file_exists($path)) {
                $mysqldump_found = true;
                $test_results['mysqldump_path'] = $path;
                break;
            }
        }
    }
    
    $test_results['mysqldump_available'] = $mysqldump_found;
    
    // Test actual mysqldump command
    if ($mysqldump_found) {
        $test_backup_file = $backup_dir . "mysqldump_test_" . date('Y-m-d_H-i-s') . ".sql";
        $command = "mysqldump --host={$host} --user={$username} --password={$password} --single-transaction {$database} > " . escapeshellarg($test_backup_file) . " 2>&1";
        
        $output = [];
        $return_code = 0;
        exec($command, $output, $return_code);
        
        $test_results['mysqldump_command'] = $command;
        $test_results['mysqldump_return_code'] = $return_code;
        $test_results['mysqldump_output'] = $output;
        $test_results['mysqldump_file_created'] = file_exists($test_backup_file);
        
        if (file_exists($test_backup_file)) {
            $test_results['mysqldump_file_size'] = filesize($test_backup_file);
            $test_results['mysqldump_file_content_preview'] = substr(file_get_contents($test_backup_file), 0, 500);
            unlink($test_backup_file); // Clean up
        }
    }
    
    echo json_encode([
        'success' => true,
        'test_results' => $test_results
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ], JSON_PRETTY_PRINT);
}
?>
