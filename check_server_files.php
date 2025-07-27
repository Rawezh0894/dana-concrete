<?php
echo "=== Server Files Check ===\n";

// Check if add_material directory exists
$dir = 'process/add_material';
if (is_dir($dir)) {
    echo "✅ Directory exists: $dir\n";
    
    // List files in directory
    $files = scandir($dir);
    echo "Files in directory:\n";
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            $filePath = $dir . '/' . $file;
            $size = filesize($filePath);
            $perms = substr(sprintf('%o', fileperms($filePath)), -4);
            echo "  - $file (size: {$size} bytes, perms: $perms)\n";
        }
    }
} else {
    echo "❌ Directory does NOT exist: $dir\n";
    echo "Creating directory...\n";
    
    if (mkdir($dir, 0755, true)) {
        echo "✅ Directory created successfully\n";
    } else {
        echo "❌ Failed to create directory\n";
    }
}

// Check specific files
$requiredFiles = [
    'process/add_material/add.php',
    'process/add_material/select.php',
    'process/add_material/update.php',
    'process/add_material/delete.php'
];

echo "\nChecking required files:\n";
foreach ($requiredFiles as $file) {
    if (file_exists($file)) {
        $size = filesize($file);
        echo "✅ $file (size: {$size} bytes)\n";
    } else {
        echo "❌ $file (MISSING)\n";
    }
}

echo "\nCurrent working directory: " . getcwd() . "\n";
echo "PHP version: " . phpversion() . "\n";
?> 