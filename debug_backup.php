<?php
// Advanced backup debugging script
echo "=== Advanced Backup Debugging ===\n";
echo "This script will help identify why backup files are empty.\n\n";

// Include the same functions as the backup script
function findMysqldumpPath() {
    $possible_paths = [
        '/usr/bin/mysqldump',
        '/usr/local/bin/mysqldump',
        '/opt/mysql/bin/mysqldump',
        '/usr/local/mysql/bin/mysqldump',
        '/usr/bin/mariadb-dump',
        '/usr/local/bin/mariadb-dump',
        'mysqldump',
        'mariadb-dump'
    ];
    
    foreach ($possible_paths as $path) {
        $output = [];
        $return_code = 0;
        exec("which " . escapeshellarg($path) . " 2>/dev/null", $output, $return_code);
        
        if ($return_code === 0 && !empty($output)) {
            $found_path = trim($output[0]);
            exec($found_path . " --version 2>/dev/null", $version_output, $version_code);
            if ($version_code === 0) {
                return $found_path;
            }
        }
        
        exec($path . " --version 2>/dev/null", $output, $return_code);
        if ($return_code === 0) {
            return $path;
        }
    }
    
    return false;
}

// Test database connection
echo "1. Testing database connection...\n";
require_once 'config/db_conected.php';

$host = env('DB_HOST', 'localhost');
$username = env('DB_USERNAME', 'dana_user');
$password = env('DB_PASSWORD', 'Rawezh.Jaza@0894');
$database = env('DB_DATABASE', 'dana_concrete_db');

echo "   Host: $host\n";
echo "   Username: $username\n";
echo "   Database: $database\n";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    echo "   ✅ Database connection successful\n\n";
} catch (PDOException $e) {
    echo "   ❌ Database connection failed: " . $e->getMessage() . "\n\n";
    exit(1);
}

// Test mysqldump availability
echo "2. Testing mysqldump availability...\n";
$mysqldump_path = findMysqldumpPath();

if ($mysqldump_path) {
    echo "   ✅ Found mysqldump at: $mysqldump_path\n";
    
    // Test mysqldump version
    $output = [];
    $return_code = 0;
    exec($mysqldump_path . " --version 2>&1", $output, $return_code);
    if ($return_code === 0) {
        echo "   ✅ mysqldump version: " . implode(' ', $output) . "\n";
    } else {
        echo "   ❌ mysqldump version test failed\n";
    }
} else {
    echo "   ❌ mysqldump not found!\n";
    echo "   Please install MySQL client: sudo apt-get install mysql-client\n";
    exit(1);
}

// Test mysqldump with actual database
echo "\n3. Testing mysqldump with actual database...\n";
$test_backup_file = './test_backup.sql';
$error_log_file = './test_backup_error.log';

// Build command
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

$full_command = $mysqldump_path . ' ' . implode(' ', $params) . ' > ' . escapeshellarg($test_backup_file) . ' 2> ' . escapeshellarg($error_log_file);

echo "   Command: " . str_replace($password, '***', $full_command) . "\n";

// Execute command
$output = [];
$return_code = 0;
exec($full_command, $output, $return_code);

echo "   Return code: $return_code\n";

if ($return_code !== 0) {
    echo "   ❌ Command failed!\n";
    if (file_exists($error_log_file)) {
        $error_content = file_get_contents($error_log_file);
        echo "   Error output:\n" . $error_content . "\n";
    }
} else {
    echo "   ✅ Command executed successfully\n";
}

// Check backup file
if (file_exists($test_backup_file)) {
    $file_size = filesize($test_backup_file);
    echo "   Backup file size: " . $file_size . " bytes\n";
    
    if ($file_size > 0) {
        echo "   ✅ Backup file created successfully!\n";
        
        // Show first few lines
        $handle = fopen($test_backup_file, 'r');
        if ($handle) {
            echo "   First few lines:\n";
            for ($i = 0; $i < 5 && !feof($handle); $i++) {
                $line = fgets($handle);
                echo "     " . trim($line) . "\n";
            }
            fclose($handle);
        }
    } else {
        echo "   ❌ Backup file is empty!\n";
    }
} else {
    echo "   ❌ Backup file was not created!\n";
}

// Check error log
if (file_exists($error_log_file)) {
    $error_content = file_get_contents($error_log_file);
    if (!empty($error_content)) {
        echo "\n   Error log content:\n" . $error_content . "\n";
    }
}

// Cleanup
if (file_exists($test_backup_file)) {
    unlink($test_backup_file);
}
if (file_exists($error_log_file)) {
    unlink($error_log_file);
}

echo "\n=== Debugging Complete ===\n";
?>
