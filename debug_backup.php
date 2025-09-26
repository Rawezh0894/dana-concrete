<?php
// Debug endpoint for backup system
header('Content-Type: application/json');

try {
    require_once 'config/db_conected.php';
    
    $debug_info = [
        'timestamp' => date('Y-m-d H:i:s'),
        'php_version' => PHP_VERSION,
        'os' => PHP_OS,
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
        'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown',
        'script_path' => __FILE__,
        'current_directory' => getcwd(),
        'backup_directory' => realpath('../../backups/'),
        'backup_directory_exists' => file_exists('../../backups/'),
        'backup_directory_writable' => is_writable('../../backups/'),
    ];
    
    // Test database connection
    try {
        $host = env('DB_HOST', 'localhost');
        $username = env('DB_USERNAME', 'dana_user');
        $password = env('DB_PASSWORD', 'Rawezh.Jaza@0894');
        $database = env('DB_DATABASE', 'dana_concrete_db');
        
        $pdo = new PDO("mysql:host={$host};dbname={$database};charset=utf8mb4", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $debug_info['database'] = [
            'host' => $host,
            'database' => $database,
            'username' => $username,
            'connection_status' => 'success',
            'tables_count' => $pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = '{$database}'")->fetchColumn()
        ];
        
    } catch (Exception $e) {
        $debug_info['database'] = [
            'connection_status' => 'failed',
            'error' => $e->getMessage()
        ];
    }
    
    // Test MySQL tools
    $mysql_tools = [];
    $possible_paths = [
        "C:\\xampp\\mysql\\bin\\mysqldump.exe",
        "mysqldump",
        "/usr/bin/mysqldump",
        "/usr/local/bin/mysqldump",
        "/opt/mysql/bin/mysqldump",
        "C:\\Program Files\\MySQL\\MySQL Server 8.0\\bin\\mysqldump.exe",
        "C:\\Program Files (x86)\\MySQL\\MySQL Server 8.0\\bin\\mysqldump.exe"
    ];
    
    foreach ($possible_paths as $path) {
        if ($path === 'mysqldump') {
            $output = [];
            $return_code = 0;
            exec("mysqldump --version 2>&1", $output, $return_code);
            $mysql_tools[$path] = [
                'exists' => $return_code === 0,
                'version' => $return_code === 0 ? implode(' ', $output) : 'Not found'
            ];
        } else {
            $mysql_tools[$path] = [
                'exists' => file_exists($path),
                'version' => file_exists($path) ? 'Found' : 'Not found'
            ];
        }
    }
    
    $debug_info['mysql_tools'] = $mysql_tools;
    
    // Test file permissions
    $test_file = '../../backups/debug_test.txt';
    $debug_info['file_permissions'] = [
        'can_create_file' => file_put_contents($test_file, 'test') !== false,
        'can_read_file' => file_exists($test_file) && is_readable($test_file),
        'can_delete_file' => unlink($test_file)
    ];
    
    // Test exec function
    $debug_info['exec_function'] = [
        'available' => function_exists('exec'),
        'disabled_functions' => ini_get('disable_functions'),
        'safe_mode' => ini_get('safe_mode')
    ];
    
    echo json_encode([
        'success' => true,
        'debug_info' => $debug_info
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ], JSON_PRETTY_PRINT);
}
?>
