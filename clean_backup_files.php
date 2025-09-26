<?php
// Script to clean existing backup files that contain mysqldump warnings
echo "=== Backup File Cleaner ===\n";
echo "This script will clean backup files that contain mysqldump warnings.\n\n";

$backup_dir = './backups/';

if (!is_dir($backup_dir)) {
    echo "❌ Backup directory not found: $backup_dir\n";
    exit(1);
}

$files = glob($backup_dir . '*.sql');
$cleaned_count = 0;
$error_count = 0;

echo "Found " . count($files) . " backup files to check.\n\n";

foreach ($files as $file) {
    echo "Checking: " . basename($file) . " ... ";
    
    $handle = fopen($file, 'r');
    if (!$handle) {
        echo "❌ Cannot open file\n";
        $error_count++;
        continue;
    }
    
    $first_line = fgets($handle);
    fclose($handle);
    
    // Check if file contains warnings
    if (strpos($first_line, 'mysqldump:') !== false || 
        strpos($first_line, 'Warning:') !== false) {
        
        echo "⚠️  Contains warnings, cleaning... ";
        
        // Read all lines and filter out warning lines
        $lines = file($file, FILE_IGNORE_NEW_LINES);
        $clean_lines = [];
        
        foreach ($lines as $line) {
            // Skip lines that contain mysqldump warnings
            if (strpos($line, 'mysqldump:') === false && 
                strpos($line, 'Warning:') === false &&
                strpos($line, 'Using a password') === false) {
                $clean_lines[] = $line;
            }
        }
        
        // Write cleaned content back to file
        if (file_put_contents($file, implode("\n", $clean_lines))) {
            echo "✅ Cleaned\n";
            $cleaned_count++;
        } else {
            echo "❌ Failed to clean\n";
            $error_count++;
        }
    } else {
        echo "✅ Already clean\n";
    }
}

echo "\n=== Summary ===\n";
echo "Files cleaned: $cleaned_count\n";
echo "Errors: $error_count\n";
echo "Total files processed: " . count($files) . "\n";

if ($cleaned_count > 0) {
    echo "\n✅ Cleanup completed! Your backup files should now import properly.\n";
} else {
    echo "\n✅ All files were already clean!\n";
}
?>
