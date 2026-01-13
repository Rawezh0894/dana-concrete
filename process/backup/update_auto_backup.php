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
    
    // Create/update Windows Task Scheduler entry for automatic backups
    if ($enabled) {
        $taskResult = isWindowsSystem()
            ? createWindowsTask($interval)
            : createLinuxCronTask($interval);

        if (!$taskResult['success']) {
            throw new Exception('نەتوانرا باک ئەپی ئۆتۆماتیکی چالاک بکرێت: ' . $taskResult['message']);
        }
    } else {
        $taskResult = isWindowsSystem()
            ? removeWindowsTask()
            : removeLinuxCronTask();

        if (!$taskResult['success']) {
            error_log("Auto backup task remove warning: " . $taskResult['message']);
        }
    }

    // Save schedule only after task creation/removal handled
    if (!file_put_contents($schedule_file, json_encode($schedule, JSON_PRETTY_PRINT))) {
        throw new Exception('نەتوانرا فایلەکەی ڕێکخستنەکان هەڵبگرێت');
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
    if (!isWindowsSystem()) {
        return [
            'success' => false,
            'message' => 'ئۆتۆماتیکی کردن بە تاسک شیدوڵر تەنها لە ویندۆز کاردەکات. لە لینوکس/مک تکایە cron بەکاربێنە.'
        ];
    }

    $task_name = 'DanaConcreteAutoBackup';
    $php_path = getPhpExecutablePath();
    $script_path = dirname(__DIR__) . '\\backup\\auto_backup_cron.php';

    if (!$php_path) {
        return ['success' => false, 'message' => 'PHP executable نەدۆزرایەوە. تکایە ڕێکخستنی PHP_PATH لە .env بۆ ڕێڕەوی ڕاست بنووسە.'];
    }

    if (!file_exists($script_path)) {
        createAutoBackupCronScript();
    }

    $interval_hours = max(1, intval($interval_hours));
    $interval_minutes = $interval_hours * 60;
    $schedule_switch = '';

    if ($interval_minutes <= 1439) {
        $schedule_switch = "/sc minute /mo {$interval_minutes}";
    } else {
        $days = max(1, ceil($interval_hours / 24));
        $schedule_switch = "/sc daily /mo {$days}";
    }

    $task_run = '"' . $php_path . '" "' . $script_path . '"';
    $command = 'schtasks /create /tn "' . $task_name . '" /tr "' . $task_run . '" ' . $schedule_switch . ' /f';

    $output = [];
    $return_code = 0;
    exec($command . ' 2>&1', $output, $return_code);

    if ($return_code === 0) {
        error_log("Windows Task created successfully: {$task_name}");
        return ['success' => true];
    }

    $message = implode(' ', $output);
    error_log("Failed to create Windows Task: " . $message);
    return ['success' => false, 'message' => $message];
}

function removeWindowsTask() {
    $task_name = 'DanaConcreteAutoBackup';
    $command = "schtasks /delete /tn \"{$task_name}\" /f";

    $output = [];
    $return_code = 0;
    exec($command . ' 2>&1', $output, $return_code);

    if ($return_code === 0) {
        error_log("Windows Task removed successfully: {$task_name}");
        return ['success' => true];
    }

    $message = implode(' ', $output);
    error_log("Failed to remove Windows Task: " . $message);
    return ['success' => false, 'message' => $message];
}

function createLinuxCronTask($interval_hours) {
    if (!function_exists('exec')) {
        return ['success' => false, 'message' => 'فەرمانی exec لەسەر سێرڤەر ناچالاکە (cron ناتوانرێت بەکاربهێنرێت)'];
    }

    $php_path = getPhpExecutablePath();
    if (!$php_path) {
        return ['success' => false, 'message' => 'php نەدۆزرایەوە. تکایە PHP_PATH لە .env دابنێ'];
    }

    $script_path = realpath(dirname(__DIR__) . '/backup/auto_backup_cron.php');
    if (!$script_path) {
        return ['success' => false, 'message' => 'فایل auto_backup_cron.php نەدۆزرایەوە'];
    }

    $identifier = '# DanaConcreteAutoBackup';
    $interval_hours = max(1, intval($interval_hours));
    $cron_expression = "0 */{$interval_hours} * * *";
    $cron_line = "{$cron_expression} " . escapeshellarg($php_path) . ' ' . escapeshellarg($script_path) . " {$identifier}";

    $currentCron = [];
    $returnCode = 0;
    exec('crontab -l 2>/dev/null', $currentCron, $returnCode);
    if ($returnCode !== 0) {
        $currentCron = [];
    }

    $filtered = array_filter($currentCron, function($line) use ($identifier) {
        return strpos($line, $identifier) === false;
    });
    $filtered[] = $cron_line;

    $tempFile = tempnam(sys_get_temp_dir(), 'cron');
    if ($tempFile === false) {
        return ['success' => false, 'message' => 'ناتوانم فایلەکەی کۆتایی بۆ cron دروست بکەم'];
    }

    file_put_contents($tempFile, implode(PHP_EOL, $filtered) . PHP_EOL);
    exec('crontab ' . escapeshellarg($tempFile) . ' 2>&1', $cronOutput, $cronReturn);
    unlink($tempFile);

    if ($cronReturn !== 0) {
        return ['success' => false, 'message' => implode(' ', $cronOutput)];
    }

    return ['success' => true];
}

function removeLinuxCronTask() {
    if (!function_exists('exec')) {
        return ['success' => false, 'message' => 'exec ناچالاکە'];
    }

    $identifier = '# DanaConcreteAutoBackup';
    $currentCron = [];
    $returnCode = 0;
    exec('crontab -l 2>/dev/null', $currentCron, $returnCode);

    if ($returnCode !== 0 || empty($currentCron)) {
        return ['success' => true];
    }

    $filtered = array_filter($currentCron, function($line) use ($identifier) {
        return strpos($line, $identifier) === false;
    });

    $tempFile = tempnam(sys_get_temp_dir(), 'cron');
    if ($tempFile === false) {
        return ['success' => false, 'message' => 'ناتوانم فایلە مۆقەت بۆ cron دروست بکەم'];
    }

    file_put_contents($tempFile, implode(PHP_EOL, $filtered) . PHP_EOL);
    exec('crontab ' . escapeshellarg($tempFile) . ' 2>&1', $cronOutput, $cronReturn);
    unlink($tempFile);

    if ($cronReturn !== 0) {
        return ['success' => false, 'message' => implode(' ', $cronOutput)];
    }

    return ['success' => true];
}

function getPhpExecutablePath() {
    $candidates = [];

    // Highest priority: .env override
    $envPath = env('PHP_PATH', null);
    if (!empty($envPath)) {
        $candidates[] = $envPath;
    }

    // PHP binary used to run current script
    if (defined('PHP_BINARY')) {
        $candidates[] = PHP_BINARY;
    }

    // Common Windows/Linux paths
    $candidates = array_merge($candidates, [
        'C:\\xampp\\php\\php.exe',
        'C:\\Program Files\\php\\php.exe',
        'C:\\Program Files (x86)\\php\\php.exe',
        '/usr/bin/php',
        '/usr/local/bin/php',
    ]);

    foreach ($candidates as $path) {
        if ($path && file_exists($path)) {
            return $path;
        }
    }

    return null;
}

function isWindowsSystem() {
    $family = PHP_OS_FAMILY ?? php_uname('s');
    return stripos($family, 'Windows') !== false;
}

function createAutoBackupCronScript() {
$script_content = '<?php
// Auto backup cron script for Dana Concrete
require_once \'../../config/db_conected.php\';

// Get database configuration

$host = env(\'DB_HOST\', \'localhost\');
$username = env(\'DB_USER\', \'dana_user\');
$password = env(\'DB_PASS\', \'Rawezh.Jaza@0894\');
$database = env(\'DB_NAME\', \'dana_concrete_db\');

// $host = env(\'DB_HOST\', \'localhost\');
// $username = env(\'DB_USER\', \'root\');
// $password = env(\'DB_PASS\', \'\');
// $database = env(\'DB_NAME\', \'dana_concrete_db\');

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
        \'--add-drop-table\',
        \'--create-options\',
        \'--extended-insert\',
        \'--quick\',
        \'--set-charset\',
        \'--default-character-set=utf8mb4\',
        \'--no-tablespaces\',
        \'--hex-blob\',
        \'--complete-insert\',
        \'--quote-names\',
        \'--max-allowed-packet=512M\',
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

    // Fix collation compatibility issues
    fixCollationIssues($backup_path);
    
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

function fixCollationIssues($file_path) {
    if (!file_exists($file_path)) return false;
    
    $content = file_get_contents($file_path);
    if ($content === false) {
        throw new Exception(\'Unable to read backup file for collation fixes\');
    }

    $replacements = [
        \'utf8mb4_0900_ai_ci\' => \'utf8mb4_unicode_ci\',
        \'utf8mb4_0900_as_cs\' => \'utf8mb4_unicode_ci\',
        \'utf8mb4_0900_as_ci\' => \'utf8mb4_unicode_ci\',
        \'utf8mb4_0900_bin\' => \'utf8mb4_bin\',
        \'utf8mb4_ja_0900_as_cs\' => \'utf8mb4_unicode_ci\',
        \'utf8mb4_ja_0900_as_cs_ks\' => \'utf8mb4_unicode_ci\',
        \'utf8mb4_general_ci\' => \'utf8mb4_unicode_ci\'
    ];

    $fixed_content = $content;
    foreach ($replacements as $old => $new) {
        $fixed_content = str_replace($old, $new, $fixed_content);
    }

    $fixed_content = preg_replace(\'/SET NAMES utf8mb4 COLLATE utf8mb4_0900_[^;]+;/\', \'SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;\', $fixed_content);

    // Add safety wrappers if missing
    $prefix = "SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;\\n" .
              "SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE=\'NO_AUTO_VALUE_ON_ZERO\';\\n" .
              "SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;\\n\\n";
    
    $suffix = "\\n\\nSET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;\\n" .
              "SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;\\n" .
              "SET SQL_MODE=@OLD_SQL_MODE;\\n";

    if (file_put_contents($file_path, $prefix . $fixed_content . $suffix) === false) {
        throw new Exception(\'Unable to write fixed backup file\');
    }
}
?>';
    
    $script_path = dirname(__DIR__) . '\\backup\\auto_backup_cron.php';
    file_put_contents($script_path, $script_content);
}
?>
