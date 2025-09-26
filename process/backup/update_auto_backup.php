<?php
session_start();
require_once '../../config/db_conected.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'دەبێت سەرەتا چوونەژوورەوە بکەیت']);
    exit;
}

// Get request data
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

if ($action !== 'update_settings') {
    echo json_encode(['success' => false, 'message' => 'کرداری نەدراوە']);
    exit;
}

try {
    $enabled = $input['enabled'] ?? false;
    $interval = intval($input['interval'] ?? 24);
    
    // Validate interval
    if ($interval < 1 || $interval > 168) { // 1 hour to 1 week
        throw new Exception('کاتی باک ئەپ دەبێت لە نێوان 1 کاتژمێر و 168 کاتژمێر بێت');
    }
    
    // Set backup directory
    $backup_dir = '../../backups/';
    if (!file_exists($backup_dir)) {
        mkdir($backup_dir, 0755, true);
    }
    
    // Create/update schedule file
    $schedule_file = $backup_dir . 'auto_backup_schedule.json';
    
    $schedule = [
        'enabled' => (bool)$enabled,
        'interval_hours' => $interval,
        'last_backup' => time(),
        'next_backup' => time() + ($interval * 3600),
        'updated_at' => time(),
        'updated_by' => $_SESSION['user_id']
    ];
    
    // If schedule file exists, preserve some data
    if (file_exists($schedule_file)) {
        $existing_schedule = json_decode(file_get_contents($schedule_file), true);
        if ($existing_schedule) {
            // Preserve last backup time if it exists
            if (isset($existing_schedule['last_backup'])) {
                $schedule['last_backup'] = $existing_schedule['last_backup'];
            }
            // Preserve next backup time if it exists and is in the future
            if (isset($existing_schedule['next_backup']) && $existing_schedule['next_backup'] > time()) {
                $schedule['next_backup'] = $existing_schedule['next_backup'];
            } else {
                $schedule['next_backup'] = time() + ($interval * 3600);
            }
        }
    }
    
    // Save schedule
    if (!file_put_contents($schedule_file, json_encode($schedule, JSON_PRETTY_PRINT))) {
        throw new Exception('نەتوانرا فایلەکەی ڕێکخستنەکان هەڵبگرێت');
    }
    
    // Create/update Windows Task Scheduler entry for automatic backups
    if ($enabled) {
        createWindowsTask($interval);
    } else {
        removeWindowsTask();
    }
    
    // Log settings update
    error_log("Auto backup settings updated: enabled={$enabled}, interval={$interval} hours");
    
    echo json_encode([
        'success' => true,
        'message' => 'ڕێکخستنەکانی باک ئەپی ئۆتۆماتیکی بە سەرکەوتوویی نوێکرانەوە',
        'settings' => $schedule
    ]);
    
} catch (Exception $e) {
    error_log("Auto backup settings error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

function createWindowsTask($interval_hours) {
    try {
        // Get current script directory
        $script_dir = dirname(__DIR__);
        $php_path = 'C:\\xampp\\php\\php.exe';
        $script_path = $script_dir . '\\backup\\auto_backup_cron.php';
        
        // Create the cron script if it doesn't exist
        createAutoBackupCronScript();
        
        // Task name
        $task_name = 'DanaConcreteAutoBackup';
        
        // Calculate interval in minutes
        $interval_minutes = $interval_hours * 60;
        
        // Create Windows Task Scheduler command
        $command = "schtasks /create /tn \"{$task_name}\" /tr \"{$php_path} {$script_path}\" /sc minute /mo {$interval_minutes} /f";
        
        // Execute command
        $output = [];
        $return_code = 0;
        exec($command . ' 2>&1', $output, $return_code);
        
        if ($return_code === 0) {
            error_log("Windows Task created successfully: {$task_name}");
        } else {
            error_log("Failed to create Windows Task: " . implode(' ', $output));
        }
        
    } catch (Exception $e) {
        error_log("Error creating Windows Task: " . $e->getMessage());
    }
}

function removeWindowsTask() {
    try {
        $task_name = 'DanaConcreteAutoBackup';
        $command = "schtasks /delete /tn \"{$task_name}\" /f";
        
        $output = [];
        $return_code = 0;
        exec($command . ' 2>&1', $output, $return_code);
        
        if ($return_code === 0) {
            error_log("Windows Task removed successfully: {$task_name}");
        } else {
            error_log("Failed to remove Windows Task: " . implode(' ', $output));
        }
        
    } catch (Exception $e) {
        error_log("Error removing Windows Task: " . $e->getMessage());
    }
}

function createAutoBackupCronScript() {
    $script_content = '<?php
// Auto backup cron script for Dana Concrete
require_once \'../../config/db_conected.php\';

// Get database configuration

$host = env(\'DB_HOST\', \'localhost\');
$username = env(\'DB_USERNAME\', \'dana_user\');
$password = env(\'DB_PASSWORD\', \'Rawezh.Jaza@0894\');
$database = env(\'DB_DATABASE\', \'dana_concrete_db\');

// $host = env(\'DB_HOST\', \'localhost\');
// $username = env(\'DB_USERNAME\', \'root\');
// $password = env(\'DB_PASSWORD\', \'\');
// $database = env(\'DB_DATABASE\', \'dana_concrete_db\');

// Set backup directory
$backup_dir = \'../../backups/\';
if (!file_exists($backup_dir)) {
    mkdir($backup_dir, 0755, true);
}

// Load schedule
$schedule_file = $backup_dir . \'auto_backup_schedule.json\';
if (!file_exists($schedule_file)) {
    exit("Schedule file not found");
}

$schedule = json_decode(file_get_contents($schedule_file), true);
if (!$schedule || !$schedule[\'enabled\']) {
    exit("Auto backup disabled");
}

// Check if it\'s time for backup
if (time() < $schedule[\'next_backup\']) {
    exit("Not time for backup yet");
}

try {
    // Create backup filename with timestamp
    $timestamp = date(\'Y-m-d_H-i-s\');
    $backup_filename = "auto_backup_{$database}_{$timestamp}.sql";
    $backup_path = $backup_dir . $backup_filename;
    
    // Build mysqldump command
    $command = "C:\\\\xampp\\\\mysql\\\\bin\\\\mysqldump.exe";
    
    if (!file_exists($command)) {
        throw new Exception(\'mysqldump not found\');
    }
    
    // Build command parameters
    $params = [
        \'--host=\' . escapeshellarg($host),
        \'--user=\' . escapeshellarg($username),
        \'--password=\' . escapeshellarg($password),
        \'--single-transaction\',
        \'--routines\',
        \'--triggers\',
        \'--add-drop-database\',
        \'--add-drop-table\',
        \'--create-options\',
        \'--disable-keys\',
        \'--extended-insert\',
        \'--quick\',
        \'--lock-tables=false\',
        \'--set-charset\',
        \'--default-character-set=utf8mb4\',
        escapeshellarg($database)
    ];
    
    $full_command = $command . \' \' . implode(\' \', $params) . \' > \' . escapeshellarg($backup_path);
    
    // Execute backup command
    $output = [];
    $return_code = 0;
    exec($full_command . \' 2>&1\', $output, $return_code);
    
    if ($return_code !== 0) {
        throw new Exception(\'Backup failed: \' . implode(\' \', $output));
    }
    
    if (!file_exists($backup_path) || filesize($backup_path) === 0) {
        throw new Exception(\'Backup file not created or empty\');
    }
    
    // Update schedule
    $schedule[\'last_backup\'] = time();
    $schedule[\'next_backup\'] = time() + ($schedule[\'interval_hours\'] * 3600);
    file_put_contents($schedule_file, json_encode($schedule, JSON_PRETTY_PRINT));
    
    // Clean old backups (keep only last 10)
    cleanOldBackups($backup_dir);
    
    error_log("Auto backup completed: {$backup_filename}");
    
} catch (Exception $e) {
    error_log("Auto backup error: " . $e->getMessage());
}

function cleanOldBackups($backup_dir) {
    $files = glob($backup_dir . \'auto_backup_*.sql\');
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
?>';
    
    $script_path = dirname(__DIR__) . '\\backup\\auto_backup_cron.php';
    file_put_contents($script_path, $script_content);
}
?>
