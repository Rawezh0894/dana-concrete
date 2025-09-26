<?php
// Simple backup test script
require_once 'config/db_conected.php';

echo "<h2>Backup Test Script</h2>";

// Get database configuration
$host = env('DB_HOST', 'localhost');
$username = env('DB_USERNAME', 'dana_user');
$password = env('DB_PASSWORD', 'Rawezh.Jaza@0894');
$database = env('DB_DATABASE', 'dana_concrete_db');

echo "<h3>Database Configuration:</h3>";
echo "Host: $host<br>";
echo "Username: $username<br>";
echo "Database: $database<br>";

// Set backup directory
$backup_dir = 'backups/';
if (!file_exists($backup_dir)) {
    mkdir($backup_dir, 0755, true);
}

// Create test backup filename
$timestamp = date('Y-m-d_H-i-s');
$backup_filename = "test_backup_{$database}_{$timestamp}.sql";
$backup_path = $backup_dir . $backup_filename;

echo "<h3>Backup Details:</h3>";
echo "Backup filename: $backup_filename<br>";
echo "Backup path: $backup_path<br>";

// Find mysqldump command
$possible_paths = [
    '/usr/bin/mysqldump',
    '/usr/local/bin/mysqldump',
    '/opt/mysql/bin/mysqldump',
    '/usr/local/mysql/bin/mysqldump',
    'mysqldump'
];

$command = null;
foreach ($possible_paths as $path) {
    if ($path === 'mysqldump') {
        $which_result = shell_exec('which mysqldump 2>/dev/null');
        if ($which_result) {
            $command = trim($which_result);
            break;
        }
    } else {
        if (file_exists($path)) {
            $command = $path;
            break;
        }
    }
}

if (!$command) {
    echo "<p style='color: red;'>❌ mysqldump not found!</p>";
    exit;
}

echo "<p style='color: green;'>✅ mysqldump found at: $command</p>";

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

echo "<h3>Command:</h3>";
echo "<code>" . htmlspecialchars($full_command) . "</code><br><br>";

// Execute backup command
echo "<h3>Executing backup...</h3>";
$output = [];
$return_code = 0;
exec($full_command . ' 2>&1', $output, $return_code);

echo "<h3>Results:</h3>";
echo "Return code: $return_code<br>";
echo "Output: " . implode(' ', $output) . "<br>";

if ($return_code === 0) {
    if (file_exists($backup_path) && filesize($backup_path) > 0) {
        $file_size = filesize($backup_path);
        echo "<p style='color: green;'>✅ Backup created successfully!</p>";
        echo "File size: " . number_format($file_size) . " bytes<br>";
        echo "File path: $backup_path<br>";
        
        // Show first few lines of backup
        echo "<h3>Backup Preview (first 10 lines):</h3>";
        $lines = file($backup_path);
        $preview_lines = array_slice($lines, 0, 10);
        echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ddd;'>";
        foreach ($preview_lines as $line) {
            echo htmlspecialchars($line);
        }
        echo "</pre>";
        
    } else {
        echo "<p style='color: red;'>❌ Backup file not created or empty</p>";
    }
} else {
    echo "<p style='color: red;'>❌ Backup failed with return code: $return_code</p>";
    echo "Error output: " . implode(' ', $output) . "<br>";
}

echo "<hr>";
echo "<p><strong>Note:</strong> You can delete this test file after debugging.</p>";
?>
