<?php
/**
 * Script to add Kurdish font CSS to all pages
 * Run this once to update all pages with the Kurdish font
 */

$pages_dir = 'pages/';
$css_link = '    <link href="../assets/css/kurdish-font.css" rel="stylesheet">';

// Get all PHP files in pages directory
$files = glob($pages_dir . '*.php');

foreach ($files as $file) {
    echo "Processing: $file\n";
    
    // Read file content
    $content = file_get_contents($file);
    
    // Check if kurdish-font.css is already included
    if (strpos($content, 'kurdish-font.css') !== false) {
        echo "  - Kurdish font already included, skipping...\n";
        continue;
    }
    
    // Find the position to insert the CSS link (after other CSS links)
    $insert_position = strpos($content, '</head>');
    
    if ($insert_position === false) {
        echo "  - No </head> tag found, skipping...\n";
        continue;
    }
    
    // Insert the CSS link before </head>
    $new_content = substr_replace($content, $css_link . "\n", $insert_position, 0);
    
    // Write back to file
    if (file_put_contents($file, $new_content)) {
        echo "  - Successfully added Kurdish font CSS\n";
    } else {
        echo "  - Failed to write file\n";
    }
}

echo "\nFont update completed!\n";
?>
