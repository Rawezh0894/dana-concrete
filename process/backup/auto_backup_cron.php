<?php
// Auto backup cron script for Dana Concrete
require_once '../../config/db_conected.php';

// Get database configuration
$host = env('DB_HOST', 'localhost');
$database = env('DB_NAME', 'dana_concrete_db');

// Use root user for backup operations (most reliable)
$username = 'root';
$password = '';

// Log the configuration being used
error_log("Auto backup using: host=$host, user=$username, database=$database");

// Set backup directory
$backup_dir = '../../backups/';
if (!file_exists($backup_dir)) {
    mkdir($backup_dir, 0755, true);
}

// Load schedule
$schedule_file = $backup_dir . 'auto_backup_schedule.json';
if (!file_exists($schedule_file)) {
    exit("Schedule file not found");
}

$schedule = json_decode(file_get_contents($schedule_file), true);
if (!$schedule || !$schedule['enabled']) {
    exit("Auto backup disabled");
}

// Check if it's time for backup
if (time() < $schedule['next_backup']) {
    exit("Not time for backup yet");
}

try {
    // Create backup filename with timestamp
    $timestamp = date('Y-m-d_H-i-s');
    $backup_filename = "auto_backup_{$database}_{$timestamp}.sql";
    $backup_path = $backup_dir . $backup_filename;
    
    // Build mysqldump command
    $command = "C:\\xampp\\mysql\\bin\\mysqldump.exe";
    
    if (!file_exists($command)) {
        throw new Exception('mysqldump not found');
    }
    
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
    
    // Execute backup command
    $output = [];
    $return_code = 0;
    exec($full_command . ' 2>&1', $output, $return_code);
    
    if ($return_code !== 0) {
        throw new Exception('Backup failed: ' . implode(' ', $output));
    }
    
    if (!file_exists($backup_path) || filesize($backup_path) === 0) {
        throw new Exception('Backup file not created or empty');
    }
    
    // Update schedule
    $schedule['last_backup'] = time();
    $schedule['next_backup'] = time() + ($schedule['interval_hours'] * 3600);
    file_put_contents($schedule_file, json_encode($schedule, JSON_PRETTY_PRINT));
    
    // Clean old backups (keep only last 10)
    cleanOldBackups($backup_dir);
    
    error_log("Auto backup completed: {$backup_filename}");
    
} catch (Exception $e) {
    error_log("Auto backup error: " . $e->getMessage());
}

function cleanOldBackups($backup_dir) {
    $files = glob($backup_dir . 'auto_backup_*.sql');
    if (count($files) > 10) {
        // Sort by modification time
        usort($files, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });
        
        // Remove oldest files
        $files_to_remove = array_slice($files, 10);
        foreach ($files_to_remove as $file) {
            unlink($file);
            error_log("Old auto backup removed: " . basename($file));
        }
    }
}
?>