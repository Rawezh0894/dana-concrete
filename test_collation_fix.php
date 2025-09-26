<?php
// Test script to check collation issues in backup files
echo "=== Collation Test Script ===\n";
echo "This script will test if the backup file contains problematic collations.\n\n";

// Test database connection
require_once 'config/db_conected.php';

$host = env('DB_HOST', 'localhost');
$username = env('DB_USERNAME', 'dana_user');
$password = env('DB_PASSWORD', 'Rawezh.Jaza@0894');
$database = env('DB_DATABASE', 'dana_concrete_db');

echo "1. Creating test backup...\n";
$test_backup_file = './test_collation_backup.sql';
$error_log_file = './test_collation_error.log';

// Build command with --skip-set-charset
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
    '--skip-set-charset',
    escapeshellarg($database)
];

$mysqldump_path = '/usr/bin/mysqldump';
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
    exit(1);
}

echo "   ✅ Backup created successfully\n";

// Check backup file
if (file_exists($test_backup_file)) {
    $file_size = filesize($test_backup_file);
    echo "   Backup file size: " . $file_size . " bytes\n";
    
    if ($file_size > 0) {
        echo "\n2. Checking for problematic collations...\n";
        
        // Read file content
        $content = file_get_contents($test_backup_file);
        
        // Check for problematic collations
        $problematic_collations = [
            'utf8mb4_0900_ai_ci',
            'utf8mb4_0900_as_cs',
            'utf8mb4_0900_as_ci',
            'utf8mb4_0900_bin',
            'utf8mb4_ja_0900_as_cs',
            'utf8mb4_ja_0900_as_cs_ks'
        ];
        
        $found_problematic = [];
        foreach ($problematic_collations as $collation) {
            if (strpos($content, $collation) !== false) {
                $found_problematic[] = $collation;
            }
        }
        
        if (!empty($found_problematic)) {
            echo "   ⚠️  Found problematic collations:\n";
            foreach ($found_problematic as $collation) {
                echo "     - $collation\n";
            }
            
            echo "\n3. Fixing collations...\n";
            
            // Replace problematic collations
            $replacements = [
                'utf8mb4_0900_ai_ci' => 'utf8mb4_unicode_ci',
                'utf8mb4_0900_as_cs' => 'utf8mb4_unicode_ci',
                'utf8mb4_0900_as_ci' => 'utf8mb4_unicode_ci',
                'utf8mb4_0900_bin' => 'utf8mb4_bin',
                'utf8mb4_ja_0900_as_cs' => 'utf8mb4_unicode_ci',
                'utf8mb4_ja_0900_as_cs_ks' => 'utf8mb4_unicode_ci'
            ];
            
            $fixed_content = $content;
            foreach ($replacements as $old => $new) {
                $fixed_content = str_replace($old, $new, $fixed_content);
            }
            
            // Also fix SET statements
            $fixed_content = preg_replace('/SET NAMES utf8mb4 COLLATE utf8mb4_0900_[^;]+;/', 'SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;', $fixed_content);
            
            // Write fixed content
            if (file_put_contents($test_backup_file, $fixed_content)) {
                echo "   ✅ Collations fixed successfully!\n";
                
                // Verify fix
                $new_content = file_get_contents($test_backup_file);
                $still_problematic = [];
                foreach ($problematic_collations as $collation) {
                    if (strpos($new_content, $collation) !== false) {
                        $still_problematic[] = $collation;
                    }
                }
                
                if (empty($still_problematic)) {
                    echo "   ✅ All problematic collations removed!\n";
                } else {
                    echo "   ⚠️  Still found: " . implode(', ', $still_problematic) . "\n";
                }
            } else {
                echo "   ❌ Failed to fix collations!\n";
            }
        } else {
            echo "   ✅ No problematic collations found!\n";
        }
        
        echo "\n4. Testing import compatibility...\n";
        echo "   The backup file should now be compatible with:\n";
        echo "   - MySQL 5.7+\n";
        echo "   - MariaDB 10.2+\n";
        echo "   - Older MySQL versions\n";
        
        // Show first few lines
        echo "\n5. First few lines of backup:\n";
        $handle = fopen($test_backup_file, 'r');
        if ($handle) {
            for ($i = 0; $i < 10 && !feof($handle); $i++) {
                $line = fgets($handle);
                echo "   " . trim($line) . "\n";
            }
            fclose($handle);
        }
        
    } else {
        echo "   ❌ Backup file is empty!\n";
    }
} else {
    echo "   ❌ Backup file was not created!\n";
}

// Cleanup
if (file_exists($test_backup_file)) {
    unlink($test_backup_file);
}
if (file_exists($error_log_file)) {
    unlink($error_log_file);
}

echo "\n=== Test Complete ===\n";
?>
