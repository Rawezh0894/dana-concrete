<?php
session_start();
require_once '../../config/db_conected.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'دەبێت سەرەتا چوونەژوورەوە بکەیت']);
    exit;
}

// Get database configuration
$host = env('DB_HOST', 'localhost');
$database = env('DB_NAME', 'dana_concrete_db');

// Use root user for restore operations (most reliable)
$username = 'root';
$password = '';

// Log the configuration being used
error_log("Restore using: host=$host, user=$username, database=$database");

// Get request data
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';
$filename = $input['filename'] ?? '';

if ($action !== 'restore_backup') {
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
    $backup_path = $backup_dir . $filename;
    
    // Check if backup file exists
    if (!file_exists($backup_path)) {
        throw new Exception('فایلەکەی باک ئەپ نەدۆزرایەوە');
    }
    
    // Validate file extension
    if (pathinfo($filename, PATHINFO_EXTENSION) !== 'sql') {
        throw new Exception('فایلەکە پێویستە فایلێکی SQL بێت');
    }
    
    // Create a backup of current database before restoration
    $current_backup_filename = "pre_restore_backup_{$database}_" . date('Y-m-d_H-i-s') . ".sql";
    $current_backup_path = $backup_dir . $current_backup_filename;
    
    // Create current database backup
    $backup_command = "C:\\xampp\\mysql\\bin\\mysqldump.exe";
    if (file_exists($backup_command)) {
        $backup_params = [
            '--host=' . escapeshellarg($host),
            '--user=' . escapeshellarg($username),
            '--password=' . escapeshellarg($password),
            '--single-transaction',
            '--routines',
            '--triggers',
            '--add-drop-database',
            '--add-drop-table',
            '--create-options',
            '--disable-keys',
            '--extended-insert',
            '--quick',
            '--lock-tables=false',
            '--set-charset',
            '--default-character-set=utf8mb4',
            escapeshellarg($database)
        ];
        
        $backup_full_command = $backup_command . ' ' . implode(' ', $backup_params) . ' > ' . escapeshellarg($current_backup_path);
        exec($backup_full_command . ' 2>&1', $backup_output, $backup_return_code);
        
        if ($backup_return_code === 0 && file_exists($current_backup_path) && filesize($current_backup_path) > 0) {
            error_log("Pre-restore backup created: {$current_backup_filename}");
        }
    }
    
    // Build mysql command for restoration
    // Try multiple possible paths for mysql
    $possible_paths = [
        "C:\\xampp\\mysql\\bin\\mysql.exe",
        "C:\\xampp\\mysql\\bin\\mysql",
        "mysql.exe",
        "mysql"
    ];
    
    $mysql_command = null;
    foreach ($possible_paths as $path) {
        if (file_exists($path) || $path === "mysql.exe" || $path === "mysql") {
            // Test if command works
            $test_output = [];
            $test_return = 0;
            exec($path . ' --version 2>&1', $test_output, $test_return);
            
            if ($test_return === 0) {
                $mysql_command = $path;
                break;
            }
        }
    }
    
    if (!$mysql_command) {
        error_log("mysql not found. Tried paths: " . implode(', ', $possible_paths));
        throw new Exception('mysql نەدۆزرایەوە. تکایە دڵنیابە کە MySQL/MariaDB دامەزراوە و لە PATH-دا هەیە');
    }
    
    // Log the command being used
    error_log("Using mysql command: " . $mysql_command);
    
    // Build restoration command
    $restore_params = [
        '--host=' . escapeshellarg($host),
        '--user=' . escapeshellarg($username),
        '--password=' . escapeshellarg($password),
        '--default-character-set=utf8mb4',
        escapeshellarg($database)
    ];
    
    $restore_command = $mysql_command . ' ' . implode(' ', $restore_params) . ' < ' . escapeshellarg($backup_path);
    
    // Log the full command for debugging
    error_log("Full restore command: " . $restore_command);
    
    // Execute restoration command
    $output = [];
    $return_code = 0;
    exec($restore_command . ' 2>&1', $output, $return_code);
    
    // Log output for debugging
    error_log("Restore command output: " . implode("\n", $output));
    error_log("Restore return code: " . $return_code);
    
    if ($return_code !== 0) {
        $error_message = 'هەڵە لە گەڕاندنەوەی داتابەیس: ' . implode(' ', $output);
        error_log("Restore command failed: " . $error_message);
        throw new Exception($error_message);
    }
    
    // Verify restoration by checking if database has tables
    try {
        $pdo = new PDO("mysql:host={$host};dbname={$database};charset=utf8mb4", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (empty($tables)) {
            throw new Exception('داتابەیسەکە بەتاڵە دوای گەڕاندنەوە');
        }
        
    } catch (PDOException $e) {
        throw new Exception('نەتوانرا داتابەیسەکە پشکنین بکرێت: ' . $e->getMessage());
    }
    
    // Log successful restoration
    error_log("Database restored from backup: {$filename}");
    
    echo json_encode([
        'success' => true,
        'message' => 'داتابەیس بە سەرکەوتوویی گەڕێندرایەوە',
        'restored_from' => $filename,
        'pre_backup' => $current_backup_filename,
        'tables_count' => count($tables)
    ]);
    
} catch (Exception $e) {
    error_log("Backup restoration error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
