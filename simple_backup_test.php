<?php
// Simple backup test - run this on your server
echo "=== BACKUP DEBUG TEST ===\n";

// Test database connection first
try {
    require_once 'config/db_conected.php';
    
    $host = env('DB_HOST', 'localhost');
    $username = env('DB_USERNAME', 'dana_user');
    $password = env('DB_PASSWORD', 'Rawezh.Jaza@0894');
    $database = env('DB_DATABASE', 'dana_concrete_db');
    
    echo "Database config loaded successfully\n";
    echo "Host: $host\n";
    echo "Username: $username\n";
    echo "Database: $database\n";
    
    // Test connection
    $pdo = new PDO("mysql:host={$host};dbname={$database};charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Database connection successful\n";
    
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
    exit;
}

// Test mysqldump
echo "\n=== Testing mysqldump ===\n";
$mysqldump_path = '/usr/bin/mysqldump';
if (file_exists($mysqldump_path)) {
    echo "✅ mysqldump found at: $mysqldump_path\n";
} else {
    echo "❌ mysqldump not found at: $mysqldump_path\n";
    exit;
}

// Test backup directory
echo "\n=== Testing backup directory ===\n";
$backup_dir = 'backups/';
if (!file_exists($backup_dir)) {
    if (mkdir($backup_dir, 0755, true)) {
        echo "✅ Backup directory created: $backup_dir\n";
    } else {
        echo "❌ Failed to create backup directory: $backup_dir\n";
        exit;
    }
} else {
    echo "✅ Backup directory exists: $backup_dir\n";
}

// Test permissions
echo "Directory permissions: " . substr(sprintf('%o', fileperms($backup_dir)), -4) . "\n";

// Test simple mysqldump command
echo "\n=== Testing mysqldump command ===\n";
$timestamp = date('Y-m-d_H-i-s');
$test_file = $backup_dir . "test_backup_{$timestamp}.sql";

$command = "mysqldump --host=$host --user=$username --password=$password --single-transaction $database > $test_file";

echo "Command: " . str_replace($password, '***', $command) . "\n";

$output = [];
$return_code = 0;
exec($command . ' 2>&1', $output, $return_code);

echo "Return code: $return_code\n";
echo "Output: " . implode(' ', $output) . "\n";

if ($return_code === 0) {
    if (file_exists($test_file) && filesize($test_file) > 0) {
        echo "✅ Test backup created successfully!\n";
        echo "File size: " . filesize($test_file) . " bytes\n";
        
        // Clean up test file
        unlink($test_file);
        echo "Test file cleaned up\n";
    } else {
        echo "❌ Test backup file not created or empty\n";
    }
} else {
    echo "❌ Test backup failed\n";
}

echo "\n=== Test completed ===\n";
?>
