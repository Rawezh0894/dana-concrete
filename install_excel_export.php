<?php
/**
 * Excel Export Installation Script
 * Run this script to set up Excel export functionality
 */

echo "<h1>Excel Export Installation Script</h1>";
echo "<p>This script will help you set up Excel export functionality for the Dana Concrete system.</p>";

// Check PHP version
echo "<h2>1. PHP Version Check</h2>";
if (version_compare(PHP_VERSION, '7.4.0', '>=')) {
    echo "<p style='color: green;'>✓ PHP version " . PHP_VERSION . " is compatible</p>";
} else {
    echo "<p style='color: red;'>✗ PHP version " . PHP_VERSION . " is too old. PHP 7.4+ required.</p>";
    exit;
}

// Check if Composer is available
echo "<h2>2. Composer Check</h2>";
if (file_exists('composer.json')) {
    echo "<p style='color: green;'>✓ composer.json found</p>";
} else {
    echo "<p style='color: red;'>✗ composer.json not found</p>";
    echo "<p>Please ensure you're running this script from the project root directory.</p>";
    exit;
}

// Check vendor directory
echo "<h2>3. Dependencies Check</h2>";
if (file_exists('vendor/autoload.php')) {
    echo "<p style='color: green;'>✓ Vendor directory exists</p>";
    
    // Check if PhpSpreadsheet is installed
    if (file_exists('vendor/phpoffice/phpspreadsheet/')) {
        echo "<p style='color: green;'>✓ PhpSpreadsheet library is installed</p>";
        echo "<p>Excel export functionality is ready to use!</p>";
    } else {
        echo "<p style='color: orange;'>⚠ PhpSpreadsheet library not found</p>";
        echo "<p>Installing dependencies...</p>";
        installDependencies();
    }
} else {
    echo "<p style='color: orange;'>⚠ Vendor directory not found</p>";
    echo "<p>Installing dependencies...</p>";
    installDependencies();
}

// Check file permissions
echo "<h2>4. File Permissions Check</h2>";
$directories = ['vendor', 'process/purchase'];
foreach ($directories as $dir) {
    if (is_dir($dir)) {
        if (is_writable($dir)) {
            echo "<p style='color: green;'>✓ Directory '$dir' is writable</p>";
        } else {
            echo "<p style='color: red;'>✗ Directory '$dir' is not writable</p>";
            echo "<p>Please fix permissions: <code>chmod 755 $dir</code></p>";
        }
    }
}

// Check export file
echo "<h2>5. Export File Check</h2>";
if (file_exists('process/purchase/export_excel.php')) {
    echo "<p style='color: green;'>✓ Export script exists</p>";
} else {
    echo "<p style='color: red;'>✗ Export script not found</p>";
}

// Check main page
echo "<h2>6. Main Page Check</h2>";
if (file_exists('pages/add_purchase.php')) {
    echo "<p style='color: green;'>✓ Main purchase page exists</p>";
} else {
    echo "<p style='color: red;'>✗ Main purchase page not found</p>";
}

echo "<h2>7. Installation Summary</h2>";
echo "<p>To complete the installation:</p>";
echo "<ol>";
echo "<li>Run <code>composer install</code> in the project directory</li>";
echo "<li>Ensure web server has write permissions to vendor directory</li>";
echo "<li>Test the export functionality by clicking the export button</li>";
echo "</ol>";

echo "<h2>8. Testing</h2>";
echo "<p>After installation, you can test the export functionality:</p>";
echo "<ol>";
echo "<li>Go to the purchase page</li>";
echo "<li>Apply any filters you want</li>";
echo "<li>Click the 'ئیکسپۆرت بۆ Excel' button</li>";
echo "<li>File should download automatically</li>";
echo "</ol>";

echo "<h2>9. Troubleshooting</h2>";
echo "<p>If you encounter issues:</p>";
echo "<ul>";
echo "<li>Check PHP error logs</li>";
echo "<li>Verify Composer installation</li>";
echo "<li>Check file permissions</li>";
echo "<li>Ensure PHP has required extensions (zip, xml)</li>";
echo "</ul>";

echo "<p><strong>Note:</strong> If PhpSpreadsheet is not available, the system will automatically fall back to CSV export.</p>";

function installDependencies() {
    echo "<p>Running: composer install</p>";
    
    // Check if composer command is available
    $output = [];
    $returnVar = 0;
    
    exec('composer --version 2>&1', $output, $returnVar);
    
    if ($returnVar === 0) {
        echo "<p>Composer is available. Running installation...</p>";
        
        // Run composer install
        exec('composer install 2>&1', $output, $returnVar);
        
        if ($returnVar === 0) {
            echo "<p style='color: green;'>✓ Dependencies installed successfully!</p>";
            echo "<p>Please refresh this page to verify the installation.</p>";
        } else {
            echo "<p style='color: red;'>✗ Failed to install dependencies</p>";
            echo "<p>Error output:</p>";
            echo "<pre>" . implode("\n", $output) . "</pre>";
            echo "<p>Please run <code>composer install</code> manually.</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ Composer command not found</p>";
        echo "<p>Please install Composer first:</p>";
        echo "<p><code>curl -sS https://getcomposer.org/installer | php</code></p>";
        echo "<p><code>sudo mv composer.phar /usr/local/bin/composer</code></p>";
    }
}
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
    line-height: 1.6;
}

h1, h2 {
    color: #333;
    border-bottom: 2px solid #28A745;
    padding-bottom: 10px;
}

code {
    background: #f4f4f4;
    padding: 2px 6px;
    border-radius: 3px;
    font-family: monospace;
}

pre {
    background: #f4f4f4;
    padding: 15px;
    border-radius: 5px;
    overflow-x: auto;
}

ul, ol {
    padding-left: 20px;
}

li {
    margin-bottom: 5px;
}
</style>
