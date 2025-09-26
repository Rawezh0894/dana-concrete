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
$username = env('DB_USERNAME', 'dana_user');
$password = env('DB_PASSWORD', 'Rawezh.Jaza@0894');
$database = env('DB_DATABASE', 'dana_concrete_db');

// $host = env('DB_HOST', 'localhost');
// $username = env('DB_USERNAME', 'root');
// $password = env('DB_PASSWORD', '');
// $database = env('DB_DATABASE', 'dana_concrete_db');

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

function validateBackupFile($file_path) {
    // Read first few lines of the backup file
    $handle = fopen($file_path, 'r');
    if (!$handle) {
        return false;
    }
    
    $first_line = fgets($handle);
    fclose($handle);
    
    // Check if the first line contains mysqldump warnings or errors
    if (strpos($first_line, 'mysqldump:') !== false || 
        strpos($first_line, 'Warning:') !== false ||
        strpos($first_line, 'Error:') !== false) {
        return false;
    }
    
    // Check if it starts with proper SQL comment or SET statement
    $valid_starts = ['--', '/*!', 'SET ', 'DROP ', 'CREATE '];
    foreach ($valid_starts as $start) {
        if (strpos(trim($first_line), $start) === 0) {
            return true;
        }
    }
    
    return false;
}

function findMysqldumpPath() {
    // Common paths for mysqldump on different systems
    $possible_paths = [
        // Linux/Unix paths
        '/usr/bin/mysqldump',
        '/usr/local/bin/mysqldump',
        '/opt/mysql/bin/mysqldump',
        '/usr/local/mysql/bin/mysqldump',
        // Windows XAMPP path (for local development)
        'C:\\xampp\\mysql\\bin\\mysqldump.exe',
        // Try to find in PATH
        'mysqldump'
    ];
    
    foreach ($possible_paths as $path) {
        // For Windows XAMPP, check if file exists
        if (strpos($path, 'C:\\') === 0) {
            if (file_exists($path)) {
                return $path;
            }
        } else {
            // For Linux/Unix, check if command is executable
            $output = [];
            $return_code = 0;
            exec("which " . escapeshellarg($path) . " 2>/dev/null", $output, $return_code);
            
            if ($return_code === 0 && !empty($output)) {
                return trim($output[0]);
            }
            
            // Also try direct execution test
            exec($path . " --version 2>/dev/null", $output, $return_code);
            if ($return_code === 0) {
                return $path;
            }
        }
    }
    
    return false;
}

try {
    // Create backup filename with timestamp
    $timestamp = date('Y-m-d_H-i-s');
    $backup_filename = "backup_{$database}_{$timestamp}.sql";
    $backup_path = $backup_dir . $backup_filename;
    
    // Build mysqldump command - detect system and find mysqldump
    $command = findMysqldumpPath();
    
    // Check if mysqldump exists
    if (!$command) {
        // Log debug information
        error_log("mysqldump not found. Searched paths: " . implode(', ', [
            '/usr/bin/mysqldump',
            '/usr/local/bin/mysqldump', 
            '/opt/mysql/bin/mysqldump',
            '/usr/local/mysql/bin/mysqldump',
            'mysqldump'
        ]));
        throw new Exception('mysqldump نەدۆزرایەوە لە شوێنی چاوەڕوانکراو. تکایە دڵنیابە کە MySQL نصبکراوە');
    }
    
    // Log the mysqldump path being used
    error_log("Using mysqldump at: " . $command);
    
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
        '--no-tablespaces',
        '--skip-comments',
        '--skip-add-locks',
        '--skip-disable-keys',
        escapeshellarg($database)
    ];
    
    // Build the full command with proper error redirection
    $full_command = $command . ' ' . implode(' ', $params) . ' > ' . escapeshellarg($backup_path) . ' 2>/dev/null';
    
    // Execute backup command
    $output = [];
    $return_code = 0;
    exec($full_command, $output, $return_code);
    
    if ($return_code !== 0) {
        throw new Exception('هەڵە لە دروستکردنی باک ئەپ: ' . implode(' ', $output));
    }
    
    // Check if backup file was created and has content
    if (!file_exists($backup_path) || filesize($backup_path) === 0) {
        throw new Exception('فایلەکەی باک ئەپ دروست نەبوو یان بەتاڵە');
    }
    
    // Validate backup file content
    if (!validateBackupFile($backup_path)) {
        unlink($backup_path); // Remove invalid backup file
        throw new Exception('فایلەکەی باک ئەپ نادروستە - لەوانەیە هەڵەیەک لە دروستکردنیدا هەبێت');
    }
    
    // Log backup creation
    error_log("Database backup created: {$backup_filename} (" . formatFileSize(filesize($backup_path)) . ")");
    
    // Update auto backup schedule if needed
    updateAutoBackupSchedule();
    
    echo json_encode([
        'success' => true,
        'message' => 'باک ئەپ بە سەرکەوتوویی دروستکرا',
        'filename' => $backup_filename,
        'size' => filesize($backup_path),
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
