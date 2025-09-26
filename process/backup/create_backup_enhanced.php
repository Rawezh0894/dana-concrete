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
    // Log backup attempt
    error_log("=== BACKUP CREATION STARTED ===");
    error_log("Host: {$host}");
    error_log("Database: {$database}");
    error_log("Username: {$username}");
    error_log("Backup directory: {$backup_dir}");
    
    // Create backup filename with timestamp
    $timestamp = date('Y-m-d_H-i-s');
    $backup_filename = "backup_{$database}_{$timestamp}.sql";
    $backup_path = $backup_dir . $backup_filename;
    
    error_log("Backup filename: {$backup_filename}");
    error_log("Backup path: {$backup_path}");
    
    // Try mysqldump first
    $mysqldump_success = false;
    
    // Build mysqldump command - try different paths
    $possible_paths = [
        "C:\\xampp\\mysql\\bin\\mysqldump.exe",  // XAMPP Windows
        "mysqldump",                              // System PATH
        "/usr/bin/mysqldump",                     // Linux standard
        "/usr/local/bin/mysqldump",               // Linux alternative
        "/opt/mysql/bin/mysqldump",               // Custom MySQL installation
        "C:\\Program Files\\MySQL\\MySQL Server 8.0\\bin\\mysqldump.exe", // MySQL Server Windows
        "C:\\Program Files (x86)\\MySQL\\MySQL Server 8.0\\bin\\mysqldump.exe" // MySQL Server Windows x86
    ];
    
    $command = null;
    foreach ($possible_paths as $path) {
        error_log("Checking mysqldump path: {$path}");
        if (file_exists($path) || $path === 'mysqldump') {
            $command = $path;
            error_log("Found mysqldump at: {$path}");
            break;
        }
    }
    
    if ($command) {
        error_log("Using mysqldump command: {$command}");
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
        
        error_log("Executing mysqldump command: {$full_command}");
        
        // Execute backup command
        $output = [];
        $return_code = 0;
        exec($full_command . ' 2>&1', $output, $return_code);
        
        error_log("mysqldump return code: {$return_code}");
        error_log("mysqldump output: " . implode(' ', $output));
        
        if ($return_code === 0 && file_exists($backup_path) && filesize($backup_path) > 0) {
            $mysqldump_success = true;
            error_log("mysqldump backup successful");
        } else {
            error_log("mysqldump backup failed");
        }
    }
    
    // If mysqldump failed, try PHP-based backup
    if (!$mysqldump_success) {
        error_log("mysqldump not available, trying PHP-based backup");
        
        // Connect to database
        error_log("Connecting to database...");
        $pdo = new PDO("mysql:host={$host};dbname={$database};charset=utf8mb4", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        error_log("Database connection successful");
        
        // Create backup file
        $backup_content = "-- Database Backup Created: " . date('Y-m-d H:i:s') . "\n";
        $backup_content .= "-- Database: {$database}\n";
        $backup_content .= "-- Host: {$host}\n\n";
        
        $backup_content .= "SET FOREIGN_KEY_CHECKS=0;\n";
        $backup_content .= "SET SQL_MODE=\"NO_AUTO_VALUE_ON_ZERO\";\n";
        $backup_content .= "SET AUTOCOMMIT=0;\n";
        $backup_content .= "START TRANSACTION;\n";
        $backup_content .= "SET time_zone = \"+00:00\";\n\n";
        
        // Get all tables
        error_log("Getting list of tables...");
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        error_log("Found " . count($tables) . " tables");
        
        foreach ($tables as $table) {
            error_log("Processing table: {$table}");
            $backup_content .= "-- Table structure for table `{$table}`\n";
            
            // Get table structure
            $stmt = $pdo->query("SHOW CREATE TABLE `{$table}`");
            $create_table = $stmt->fetch(PDO::FETCH_ASSOC);
            $backup_content .= "DROP TABLE IF EXISTS `{$table}`;\n";
            $backup_content .= $create_table['Create Table'] . ";\n\n";
            
            // Get table data
            $stmt = $pdo->query("SELECT * FROM `{$table}`");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($rows)) {
                $backup_content .= "-- Dumping data for table `{$table}`\n";
                
                // Get column names
                $columns = array_keys($rows[0]);
                $column_list = '`' . implode('`, `', $columns) . '`';
                
                foreach ($rows as $row) {
                    $values = [];
                    foreach ($row as $value) {
                        if ($value === null) {
                            $values[] = 'NULL';
                        } else {
                            $values[] = "'" . addslashes($value) . "'";
                        }
                    }
                    $backup_content .= "INSERT INTO `{$table}` ({$column_list}) VALUES (" . implode(', ', $values) . ");\n";
                }
                $backup_content .= "\n";
            }
        }
        
        $backup_content .= "COMMIT;\n";
        $backup_content .= "SET FOREIGN_KEY_CHECKS=1;\n";
        
        // Write backup to file
        error_log("Writing backup to file: {$backup_path}");
        $bytes_written = file_put_contents($backup_path, $backup_content);
        error_log("Bytes written: {$bytes_written}");
        
        if (!file_exists($backup_path) || filesize($backup_path) === 0) {
            error_log("Backup file creation failed or empty");
            throw new Exception('فایلەکەی باک ئەپ دروست نەبوو یان بەتاڵە');
        }
        
        error_log("PHP-based backup completed successfully");
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
        'path' => $backup_path,
        'method' => $mysqldump_success ? 'mysqldump' : 'php'
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
