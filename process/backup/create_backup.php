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
$username = env('DB_USER', 'dana_user');
$password = env('DB_PASS', 'Rawezh.Jaza@0894');
$database = env('DB_NAME', 'dana_concrete_db');

// $host = env('DB_HOST', 'localhost');
// $username = env('DB_USER', 'root');
// $password = env('DB_PASS', '');
// $database = env('DB_NAME', 'dana_concrete_db');

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
    if (!file_exists($file_path)) return false;
    
    // Read first few lines of the backup file
    $handle = fopen($file_path, 'r');
    if (!$handle) {
        return false;
    }
    
    $first_line = fgets($handle);
    fclose($handle);
    
    // Check if the first line contains mysqldump warnings or errors
    if ($first_line === false || 
        strpos($first_line, 'mysqldump:') !== false || 
        strpos($first_line, 'Warning:') !== false ||
        strpos($first_line, 'Error:') !== false) {
        return false;
    }
    
    // Check if it starts with proper SQL comment, SET statement, or safety wrapper we added
    $valid_starts = ['--', '/*!', 'SET ', 'DROP ', 'CREATE '];
    $trimmed_line = trim($first_line);
    foreach ($valid_starts as $start) {
        if (strpos($trimmed_line, $start) === 0) {
            return true;
        }
    }
    
    return false;
}

function fixCollationIssues($file_path) {
    if (!file_exists($file_path)) return false;
    
    // For large files, we should process line-by-line to avoid memory issues
    // But for simplicity and current scale, we'll use a slightly safer memory-limited approach
    // or just ensure we don't crash on medium files.
    $size = filesize($file_path);
    if ($size > 50 * 1024 * 1024) { // If > 50MB, use line-by-line
        $temp_path = $file_path . '.tmp';
        $reading = fopen($file_path, 'r');
        $writing = fopen($temp_path, 'w');
        
        $replacements = [
            'utf8mb4_0900_ai_ci' => 'utf8mb4_unicode_ci',
            'utf8mb4_0900_as_cs' => 'utf8mb4_unicode_ci',
            'utf8mb4_0900_as_ci' => 'utf8mb4_unicode_ci',
            'utf8mb4_0900_bin' => 'utf8mb4_bin',
            'utf8mb4_general_ci' => 'utf8mb4_unicode_ci' // Standardization
        ];
        
        if ($reading && $writing) {
            while (($line = fgets($reading)) !== false) {
                foreach ($replacements as $old => $new) {
                    $line = str_replace($old, $new, $line);
                }
                // Also remove problematic SET statements for collation
                $line = preg_replace('/SET NAMES utf8mb4 COLLATE utf8mb4_0900_[^;]+;/', 'SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;', $line);
                fputs($writing, $line);
            }
            fclose($reading);
            fclose($writing);
            unlink($file_path);
            rename($temp_path, $file_path);
            return true;
        }
        return false;
    }

    $content = file_get_contents($file_path);
    if ($content === false) {
        return false;
    }
    
    // Replace problematic collations with compatible ones
    $replacements = [
        'utf8mb4_0900_ai_ci' => 'utf8mb4_unicode_ci',
        'utf8mb4_0900_as_cs' => 'utf8mb4_unicode_ci',
        'utf8mb4_0900_as_ci' => 'utf8mb4_unicode_ci',
        'utf8mb4_0900_bin' => 'utf8mb4_bin',
        'utf8mb4_ja_0900_as_cs' => 'utf8mb4_unicode_ci',
        'utf8mb4_ja_0900_as_cs_ks' => 'utf8mb4_unicode_ci',
        'utf8mb4_general_ci' => 'utf8mb4_unicode_ci'
    ];
    
    $fixed_content = $content;
    foreach ($replacements as $old => $new) {
        $fixed_content = str_replace($old, $new, $fixed_content);
    }
    
    // Also remove problematic SET statements
    $fixed_content = preg_replace('/SET NAMES utf8mb4 COLLATE utf8mb4_0900_[^;]+;/', 'SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;', $fixed_content);
    
    // Add safety wrappers if missing
    $prefix = "SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;\n" .
              "SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\n" .
              "SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;\n\n";
    
    $suffix = "\n\nSET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;\n" .
              "SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;\n" .
              "SET SQL_MODE=@OLD_SQL_MODE;\n";
              
    return file_put_contents($file_path, $prefix . $fixed_content . $suffix);
}

function findMysqldumpPath() {
    // Common paths for mysqldump on different systems
    $possible_paths = [
        // Linux/Unix paths
        '/usr/bin/mysqldump',
        '/usr/local/bin/mysqldump',
        '/opt/mysql/bin/mysqldump',
        '/usr/local/mysql/bin/mysqldump',
        '/usr/bin/mariadb-dump',  // MariaDB alternative
        '/usr/local/bin/mariadb-dump',
        // Windows XAMPP path (for local development)
        'C:\\xampp\\mysql\\bin\\mysqldump.exe',
        // Try to find in PATH
        'mysqldump',
        'mariadb-dump'
    ];
    
    foreach ($possible_paths as $path) {
        // For Windows XAMPP, check if file exists
        if (strpos($path, 'C:\\') === 0) {
            if (file_exists($path)) {
                error_log("Found mysqldump at Windows path: " . $path);
                return $path;
            }
        } else {
            // For Linux/Unix, check if command is executable
            $output = [];
            $return_code = 0;
            exec("which " . escapeshellarg($path) . " 2>/dev/null", $output, $return_code);
            
            if ($return_code === 0 && !empty($output)) {
                $found_path = trim($output[0]);
                error_log("Found mysqldump via which: " . $found_path);
                
                // Test if it actually works
                exec($found_path . " --version 2>/dev/null", $version_output, $version_code);
                if ($version_code === 0) {
                    error_log("mysqldump version test successful: " . implode(' ', $version_output));
                    return $found_path;
                }
            }
            
            // Also try direct execution test
            exec($path . " --version 2>/dev/null", $output, $return_code);
            if ($return_code === 0) {
                error_log("Found mysqldump via direct test: " . $path);
                return $path;
            }
        }
    }
    
    error_log("mysqldump not found in any of the expected paths");
    return false;
}

try {
    $error_details = null;
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
        '--add-drop-table',
        '--create-options',
        '--extended-insert',
        '--quick',
        '--set-charset',
        '--default-character-set=utf8mb4',
        '--no-tablespaces',
        '--hex-blob',
        '--complete-insert',
        '--quote-names',
        '--max-allowed-packet=512M',
        escapeshellarg($database)
    ];
    
    // Build the full command with error logging
    $error_log_file = $backup_dir . 'backup_error_' . time() . '.log';
    $full_command = $command . ' ' . implode(' ', $params) . ' > ' . escapeshellarg($backup_path) . ' 2> ' . escapeshellarg($error_log_file);
    
    // Log the command being executed
    error_log("Executing backup command: " . $full_command);
    
    // Execute backup command
    $output = [];
    $return_code = 0;
    exec($full_command, $output, $return_code);
    
    // Check for errors
    if ($return_code !== 0) {
        $error_content = file_exists($error_log_file) ? file_get_contents($error_log_file) : 'No error log found';
        $command_output = implode(' ', $output);
        $error_details = trim($error_content ?: $command_output);
        
        error_log("Backup command failed with return code: $return_code");
        error_log("Error output: " . $error_content);
        error_log("Command output: " . $command_output);
        
        if (file_exists($error_log_file)) {
            unlink($error_log_file);
        }
        
        echo json_encode([
            'success' => false,
            'message' => 'هەڵە لە دروستکردنی باک ئەپ. کۆد: ' . $return_code . '.',
            'error_code' => $return_code,
            'error_details' => $error_details
        ]);
        exit;
    }
    
    // Clean up error log file if backup was successful
    if (file_exists($error_log_file)) {
        unlink($error_log_file);
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
    
    // Fix any collation compatibility issues
    if (!fixCollationIssues($backup_path)) {
        error_log("Warning: Could not fix collation issues in backup file: {$backup_filename}");
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
        'message' => $e->getMessage(),
        'error_details' => isset($error_details) ? $error_details : null
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
