<?php
// Script to fix collation issues in existing backup files
echo "=== Backup Collation Fixer ===\n";
echo "This script will fix collation compatibility issues in backup files.\n\n";

$backup_dir = './backups/';

if (!is_dir($backup_dir)) {
    echo "❌ Backup directory not found: $backup_dir\n";
    exit(1);
}

$files = glob($backup_dir . '*.sql');
$fixed_count = 0;
$error_count = 0;

echo "Found " . count($files) . " backup files to check.\n\n";

foreach ($files as $file) {
    echo "Checking: " . basename($file) . " ... ";
    
    $content = file_get_contents($file);
    if ($content === false) {
        echo "❌ Cannot read file\n";
        $error_count++;
        continue;
    }
    
    // Check if file contains problematic collations
    $problematic_collations = [
        'utf8mb4_0900_ai_ci',
        'utf8mb4_0900_as_cs',
        'utf8mb4_0900_as_ci',
        'utf8mb4_0900_bin',
        'utf8mb4_ja_0900_as_cs',
        'utf8mb4_ja_0900_as_cs_ks',
        'utf8mb4_0900_ai_ci',
        'utf8mb4_0900_as_cs',
        'utf8mb4_0900_as_ci',
        'utf8mb4_0900_bin'
    ];
    
    $has_issues = false;
    foreach ($problematic_collations as $collation) {
        if (strpos($content, $collation) !== false) {
            $has_issues = true;
            break;
        }
    }
    
    if ($has_issues) {
        echo "⚠️  Contains problematic collations, fixing... ";
        
        // Replace problematic collations with compatible ones
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
        
        // Also remove problematic SET statements
        $fixed_content = preg_replace('/SET NAMES utf8mb4 COLLATE utf8mb4_0900_[^;]+;/', 'SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;', $fixed_content);
        
        // Write fixed content back to file
        if (file_put_contents($file, $fixed_content)) {
            echo "✅ Fixed\n";
            $fixed_count++;
        } else {
            echo "❌ Failed to fix\n";
            $error_count++;
        }
    } else {
        echo "✅ Already compatible\n";
    }
}

echo "\n=== Summary ===\n";
echo "Files fixed: $fixed_count\n";
echo "Errors: $error_count\n";
echo "Total files processed: " . count($files) . "\n";

if ($fixed_count > 0) {
    echo "\n✅ Collation fixes completed! Your backup files should now be compatible with older MySQL/MariaDB versions.\n";
    echo "\nCompatible collations used:\n";
    echo "- utf8mb4_unicode_ci (instead of utf8mb4_0900_ai_ci)\n";
    echo "- utf8mb4_bin (instead of utf8mb4_0900_bin)\n";
} else {
    echo "\n✅ All files were already compatible!\n";
}

echo "\n=== MySQL Version Compatibility ===\n";
echo "These fixes make backups compatible with:\n";
echo "- MySQL 5.7+\n";
echo "- MariaDB 10.2+\n";
echo "- Older MySQL versions\n";
?>
