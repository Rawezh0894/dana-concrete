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

// Use root user for backup operations (most reliable)
$username = 'root';
$password = '';

// Log the configuration being used
error_log("Backup using: host=$host, user=$username, database=$database");

// Set backup directory
$backup_dir = '../../backups/';
if (!file_exists($backup_dir)) {
    mkdir($backup_dir, 0755, true);
}

// Get request data
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

if ($action !== 'create_backup') {
    echo json_encode(['success' => false, 'message' => 'کرداری نەدراوە']);
    exit;
}

try {
    // Create backup filename with timestamp
    $timestamp = date('Y-m-d_H-i-s');
    $backup_filename = "backup_{$database}_{$timestamp}.sql";
    $backup_path = $backup_dir . $backup_filename;
    
    // Try multiple possible paths for mysqldump
    $possible_paths = [
        "C:\\xampp\\mysql\\bin\\mysqldump.exe",
        "C:\\xampp\\mysql\\bin\\mysqldump",
        "mysqldump.exe",
        "mysqldump"
    ];
    
    $command = null;
    foreach ($possible_paths as $path) {
        if (file_exists($path) || $path === "mysqldump.exe" || $path === "mysqldump") {
            // Test if command works
            $test_output = [];
            $test_return = 0;
            exec($path . ' --version 2>&1', $test_output, $test_return);
            
            if ($test_return === 0) {
                $command = $path;
                break;
            }
        }
    }
    
    if (!$command) {
        // Log all possible paths for debugging
        error_log("mysqldump not found. Tried paths: " . implode(', ', $possible_paths));
        error_log("PHP exec function available: " . (function_exists('exec') ? 'Yes' : 'No'));
        error_log("PHP shell_exec function available: " . (function_exists('shell_exec') ? 'Yes' : 'No'));
        error_log("PHP system function available: " . (function_exists('system') ? 'Yes' : 'No'));
        
        throw new Exception('mysqldump نەدۆزرایەوە. تکایە دڵنیابە کە MySQL/MariaDB دامەزراوە و لە PATH-دا هەیە');
    }
    
    // Log the command being used
    error_log("Using mysqldump command: " . $command);
    
    // Build command parameters
    $params = [
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
    
    $full_command = $command . ' ' . implode(' ', $params) . ' > ' . escapeshellarg($backup_path);
    
    // Log the full command for debugging
    error_log("Full backup command: " . $full_command);
    
    // Execute backup command
    $output = [];
    $return_code = 0;
    exec($full_command . ' 2>&1', $output, $return_code);
    
    // Log output for debugging
    error_log("Command output: " . implode("\n", $output));
    error_log("Return code: " . $return_code);
    
    if ($return_code !== 0) {
        $error_message = 'هەڵە لە دروستکردنی باک ئەپ: ' . implode(' ', $output);
        error_log("Backup command failed: " . $error_message);
        throw new Exception($error_message);
    }
    
    // Check if backup file was created and has content
    if (!file_exists($backup_path)) {
        throw new Exception('فایلەکەی باک ئەپ دروست نەبوو');
    }
    
    $file_size = filesize($backup_path);
    if ($file_size === 0) {
        throw new Exception('فایلەکەی باک ئەپ بەتاڵە');
    }
    
    // Log backup creation
    error_log("Database backup created: {$backup_filename} (" . formatFileSize($file_size) . ")");
    
    // Update auto backup schedule if needed
    updateAutoBackupSchedule();
    
    echo json_encode([
        'success' => true,
        'message' => 'باک ئەپ بە سەرکەوتوویی دروستکرا',
        'filename' => $backup_filename,
        'size' => $file_size,
        'path' => $backup_path
    ]);
    
} catch (Exception $e) {
    error_log("Backup creation error: " . $e->getMessage());
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

function updateAutoBackupSchedule() {
    try {
        // Create a simple cron-like scheduler using a JSON file
        $schedule_file = '../../backups/auto_backup_schedule.json';
        
        if (file_exists($schedule_file)) {
            $schedule = json_decode(file_get_contents($schedule_file), true);
        } else {
            $schedule = [
                'enabled' => true,
                'interval_hours' => 24,
                'last_backup' => time(),
                'next_backup' => time() + (24 * 3600)
            ];
        }
        
        // Update last backup time
        $schedule['last_backup'] = time();
        $schedule['next_backup'] = time() + ($schedule['interval_hours'] * 3600);
        
        file_put_contents($schedule_file, json_encode($schedule, JSON_PRETTY_PRINT));
        
    } catch (Exception $e) {
        error_log("Error updating auto backup schedule: " . $e->getMessage());
    }
}
?>
