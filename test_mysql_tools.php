<?php
// Test script to check MySQL tools availability
echo "<h2>MySQL Tools Test</h2>";

// Test different possible paths
$possible_mysqldump_paths = [
    "C:\\xampp\\mysql\\bin\\mysqldump.exe",  // XAMPP Windows
    "mysqldump",                              // System PATH
    "/usr/bin/mysqldump",                     // Linux standard
    "/usr/local/bin/mysqldump",               // Linux alternative
    "/opt/mysql/bin/mysqldump",               // Custom MySQL installation
    "C:\\Program Files\\MySQL\\MySQL Server 8.0\\bin\\mysqldump.exe", // MySQL Server Windows
    "C:\\Program Files (x86)\\MySQL\\MySQL Server 8.0\\bin\\mysqldump.exe" // MySQL Server Windows x86
];

$possible_mysql_paths = [
    "C:\\xampp\\mysql\\bin\\mysql.exe",  // XAMPP Windows
    "mysql",                              // System PATH
    "/usr/bin/mysql",                     // Linux standard
    "/usr/local/bin/mysql",               // Linux alternative
    "/opt/mysql/bin/mysql",               // Custom MySQL installation
    "C:\\Program Files\\MySQL\\MySQL Server 8.0\\bin\\mysql.exe", // MySQL Server Windows
    "C:\\Program Files (x86)\\MySQL\\MySQL Server 8.0\\bin\\mysql.exe" // MySQL Server Windows x86
];

echo "<h3>Testing mysqldump paths:</h3>";
foreach ($possible_mysqldump_paths as $path) {
    if ($path === 'mysqldump') {
        // Test if mysqldump is in PATH
        $output = [];
        $return_code = 0;
        exec("mysqldump --version 2>&1", $output, $return_code);
        if ($return_code === 0) {
            echo "✅ <strong>mysqldump</strong> (in PATH) - " . implode(' ', $output) . "<br>";
        } else {
            echo "❌ <strong>mysqldump</strong> (in PATH) - Not found<br>";
        }
    } else {
        if (file_exists($path)) {
            echo "✅ <strong>{$path}</strong> - Found<br>";
        } else {
            echo "❌ <strong>{$path}</strong> - Not found<br>";
        }
    }
}

echo "<h3>Testing mysql paths:</h3>";
foreach ($possible_mysql_paths as $path) {
    if ($path === 'mysql') {
        // Test if mysql is in PATH
        $output = [];
        $return_code = 0;
        exec("mysql --version 2>&1", $output, $return_code);
        if ($return_code === 0) {
            echo "✅ <strong>mysql</strong> (in PATH) - " . implode(' ', $output) . "<br>";
        } else {
            echo "❌ <strong>mysql</strong> (in PATH) - Not found<br>";
        }
    } else {
        if (file_exists($path)) {
            echo "✅ <strong>{$path}</strong> - Found<br>";
        } else {
            echo "❌ <strong>{$path}</strong> - Not found<br>";
        }
    }
}

echo "<h3>System Information:</h3>";
echo "Operating System: " . PHP_OS . "<br>";
echo "PHP Version: " . PHP_VERSION . "<br>";
echo "Server Software: " . $_SERVER['SERVER_SOFTWARE'] . "<br>";

echo "<h3>Environment Variables:</h3>";
echo "PATH: " . getenv('PATH') . "<br>";

echo "<h3>Testing Database Connection:</h3>";
try {
    require_once 'config/db_conected.php';
    
    $host = env('DB_HOST', 'localhost');
    $username = env('DB_USERNAME', 'dana_user');
    $password = env('DB_PASSWORD', 'Rawezh.Jaza@0894');
    $database = env('DB_DATABASE', 'dana_concrete_db');
    
    $pdo = new PDO("mysql:host={$host};dbname={$database};charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Database connection successful<br>";
    echo "Host: {$host}<br>";
    echo "Database: {$database}<br>";
    echo "Username: {$username}<br>";
    
    // Test a simple query
    $stmt = $pdo->query("SELECT COUNT(*) as table_count FROM information_schema.tables WHERE table_schema = '{$database}'");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Tables in database: " . $result['table_count'] . "<br>";
    
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "<br>";
}

echo "<h3>Recommendations:</h3>";
echo "1. If mysqldump/mysql are not found, you may need to install MySQL client tools<br>";
echo "2. For remote servers, you might need to install mysql-client package<br>";
echo "3. On Ubuntu/Debian: sudo apt-get install mysql-client<br>";
echo "4. On CentOS/RHEL: sudo yum install mysql<br>";
?>
