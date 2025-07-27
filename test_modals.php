<?php
// Test file to check modals
echo "<h2>Modal Test</h2>";

// Check if purchase_materila.php exists
$pageFile = 'pages/purchase_materila.php';
if (file_exists($pageFile)) {
    echo "✅ Page file exists: $pageFile<br>";
    
    // Read the file and check for modals
    $content = file_get_contents($pageFile);
    
    $modals = [
        'viewPurchaseModal' => 'View Purchase Modal',
        'editPurchaseModal' => 'Edit Purchase Modal',
        'addPurchaseModal' => 'Add Purchase Modal'
    ];
    
    echo "<h3>Modal Check:</h3>";
    foreach ($modals as $modalId => $modalName) {
        if (strpos($content, $modalId) !== false) {
            echo "✅ $modalName ($modalId) found<br>";
        } else {
            echo "❌ $modalName ($modalId) NOT found<br>";
        }
    }
    
    // Check for Bootstrap modal structure
    echo "<h3>Bootstrap Modal Structure Check:</h3>";
    if (strpos($content, 'modal fade') !== false) {
        echo "✅ Bootstrap modal structure found<br>";
    } else {
        echo "❌ Bootstrap modal structure NOT found<br>";
    }
    
    if (strpos($content, 'modal-dialog') !== false) {
        echo "✅ Modal dialog found<br>";
    } else {
        echo "❌ Modal dialog NOT found<br>";
    }
    
    if (strpos($content, 'modal-content') !== false) {
        echo "✅ Modal content found<br>";
    } else {
        echo "❌ Modal content NOT found<br>";
    }
    
} else {
    echo "❌ Page file does not exist: $pageFile<br>";
}

// Check if Bootstrap is loaded
echo "<h3>Bootstrap Check:</h3>";
$jsFiles = [
    'assets/js/purchase_materilas/select_purchase.js',
    'assets/js/purchase_materilas/add_purchase.js'
];

foreach ($jsFiles as $jsFile) {
    if (file_exists($jsFile)) {
        echo "✅ JS file exists: $jsFile<br>";
    } else {
        echo "❌ JS file does not exist: $jsFile<br>";
    }
}

echo "<h3>Test Instructions:</h3>";
echo "<p>1. Open browser console (F12)</p>";
echo "<p>2. Click on view button</p>";
echo "<p>3. Check console for logs</p>";
echo "<p>4. Check if modal appears</p>";
?> 