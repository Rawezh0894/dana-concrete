<?php
// Test script to find MySQL paths on your server
echo "<h2>MySQL Path Detection Test</h2>";

// Test mysqldump paths
echo "<h3>Testing mysqldump paths:</h3>";
$mysqldump_paths = [
    '/usr/bin/mysqldump',
    '/usr/local/bin/mysqldump',
    '/opt/mysql/bin/mysqldump',
    '/usr/local/mysql/bin/mysqldump',
    '/usr/bin/mariadb-dump',
    '/usr/local/bin/mariadb-dump',
    'mysqldump'
];

foreach ($mysqldump_paths as $path) {
    if ($path === 'mysqldump') {
        $which_result = shell_exec('which mysqldump 2>/dev/null');
        if ($which_result) {
            echo "✅ <strong>$path</strong> - Found at: " . trim($which_result) . "<br>";
        } else {
            echo "❌ <strong>$path</strong> - Not found in PATH<br>";
        }
    } else {
        if (file_exists($path)) {
            echo "✅ <strong>$path</strong> - File exists<br>";
        } else {
            echo "❌ <strong>$path</strong> - File not found<br>";
        }
    }
}

// Test mysql paths
echo "<h3>Testing mysql paths:</h3>";
$mysql_paths = [
    '/usr/bin/mysql',
    '/usr/local/bin/mysql',
    '/opt/mysql/bin/mysql',
    '/usr/local/mysql/bin/mysql',
    '/usr/bin/mariadb',
    '/usr/local/bin/mariadb',
    'mysql'
];

foreach ($mysql_paths as $path) {
    if ($path === 'mysql') {
        $which_result = shell_exec('which mysql 2>/dev/null');
        if ($which_result) {
            echo "✅ <strong>$path</strong> - Found at: " . trim($which_result) . "<br>";
        } else {
            echo "❌ <strong>$path</strong> - Not found in PATH<br>";
        }
    } else {
        if (file_exists($path)) {
            echo "✅ <strong>$path</strong> - File exists<br>";
        } else {
            echo "❌ <strong>$path</strong> - File not found<br>";
        }
    }
}

// Test database connection
echo "<h3>Database Connection Test:</h3>";
try {
    require_once 'config/db_conected.php';
    
    $host = env('DB_HOST', 'localhost');
    $username = env('DB_USERNAME', 'dana_user');
    $password = env('DB_PASSWORD', 'Rawezh.Jaza@0894');
    $database = env('DB_DATABASE', 'dana_concrete_db');
    
    echo "Host: $host<br>";
    echo "Username: $username<br>";
    echo "Database: $database<br>";
    
    $pdo = new PDO("mysql:host={$host};dbname={$database};charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Database connection successful<br>";
    
    // Test a simple query
    $stmt = $pdo->query("SELECT VERSION() as version");
    $version = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "MySQL Version: " . $version['version'] . "<br>";
    
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "<br>";
}

// Test backup directory
echo "<h3>Backup Directory Test:</h3>";
$backup_dir = 'backups/';
if (!file_exists($backup_dir)) {
    if (mkdir($backup_dir, 0755, true)) {
        echo "✅ Backup directory created: $backup_dir<br>";
    } else {
        echo "❌ Failed to create backup directory: $backup_dir<br>";
    }
} else {
    echo "✅ Backup directory exists: $backup_dir<br>";
    echo "Directory permissions: " . substr(sprintf('%o', fileperms($backup_dir)), -4) . "<br>";
}

// Test permissions
echo "<h3>File Permissions Test:</h3>";
echo "Current user: " . get_current_user() . "<br>";
echo "Current working directory: " . getcwd() . "<br>";
echo "PHP version: " . PHP_VERSION . "<br>";

// Test shell_exec
echo "<h3>Shell Command Test:</h3>";
$test_command = 'echo "Shell test successful"';
$result = shell_exec($test_command);
if ($result) {
    echo "✅ Shell commands work: " . trim($result) . "<br>";
} else {
    echo "❌ Shell commands may be disabled<br>";
}

echo "<hr>";
echo "<p><strong>Note:</strong> After running this test, you can delete this file for security.</p>";
?>
