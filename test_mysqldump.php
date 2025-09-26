<?php
// Test script to check mysqldump availability on your server
echo "=== MySQL Dump Test Script ===\n";
echo "Testing mysqldump availability on your server...\n\n";

function findMysqldumpPath() {
    // Common paths for mysqldump on different systems
    $possible_paths = [
        // Linux/Unix paths
        '/usr/bin/mysqldump',
        '/usr/local/bin/mysqldump',
        '/opt/mysql/bin/mysqldump',
        '/usr/local/mysql/bin/mysqldump',
        // Try to find in PATH
        'mysqldump'
    ];
    
    echo "Searching for mysqldump in the following paths:\n";
    
    foreach ($possible_paths as $path) {
        echo "- Checking: $path ... ";
        
        // For Linux/Unix, check if command is executable
        $output = [];
        $return_code = 0;
        exec("which " . escapeshellarg($path) . " 2>/dev/null", $output, $return_code);
        
        if ($return_code === 0 && !empty($output)) {
            $found_path = trim($output[0]);
            echo "FOUND at: $found_path\n";
            
            // Test if it works
            exec($found_path . " --version 2>/dev/null", $version_output, $version_code);
            if ($version_code === 0) {
                echo "  Version: " . implode(' ', $version_output) . "\n";
                return $found_path;
            }
        } else {
            echo "NOT FOUND\n";
        }
    }
    
    return false;
}

$mysqldump_path = findMysqldumpPath();

if ($mysqldump_path) {
    echo "\n✅ SUCCESS: mysqldump found at: $mysqldump_path\n";
    echo "Your backup system should work now!\n";
} else {
    echo "\n❌ ERROR: mysqldump not found!\n";
    echo "\nTo fix this, you need to install MySQL client tools on your server:\n";
    echo "For Ubuntu/Debian: sudo apt-get install mysql-client\n";
    echo "For CentOS/RHEL: sudo yum install mysql\n";
    echo "For other systems, install the MySQL client package.\n";
}

echo "\n=== System Information ===\n";
echo "PHP Version: " . phpversion() . "\n";
echo "Operating System: " . php_uname() . "\n";
echo "Current User: " . get_current_user() . "\n";

// Check if we can execute shell commands
echo "\n=== Shell Command Test ===\n";
$test_output = [];
$test_code = 0;
exec("echo 'Shell commands work'", $test_output, $test_code);
if ($test_code === 0) {
    echo "✅ Shell commands are working: " . implode(' ', $test_output) . "\n";
} else {
    echo "❌ Shell commands are not working properly\n";
}
?>
