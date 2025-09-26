<?php
// Direct backup test - simulates JavaScript behavior
session_start();

echo "<h2>Direct Backup Test</h2>";

if (!isset($_SESSION['user_id'])) {
    echo "❌ Not logged in. Please <a href='login.php'>login</a> first.";
    exit;
}

echo "✅ User logged in: " . $_SESSION['username'] . "<br>";

// Simulate the exact request that JavaScript makes
echo "<h3>Simulating JavaScript Request:</h3>";

// Set up the request data exactly like JavaScript does
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['CONTENT_TYPE'] = 'application/json';

// Simulate the JSON input
$json_input = json_encode(['action' => 'create_backup']);
file_put_contents('php://input', $json_input);

echo "JSON Input: " . $json_input . "<br>";

// Capture output
ob_start();

// Include the backup script directly
include 'process/backup/create_backup.php';

// Get the output
$result = ob_get_clean();

echo "<h3>Result:</h3>";
echo "Length: " . strlen($result) . " characters<br>";
echo "<pre>" . htmlspecialchars($result) . "</pre>";

// Try to parse JSON
$data = json_decode($result, true);
if ($data) {
    echo "<h3>Parsed Response:</h3>";
    echo "<pre>" . print_r($data, true) . "</pre>";
    
    if ($data['success']) {
        echo "<p style='color: green;'>✅ Backup created successfully!</p>";
        echo "Filename: " . $data['filename'] . "<br>";
        echo "Size: " . number_format($data['size']) . " bytes<br>";
    } else {
        echo "<p style='color: red;'>❌ Backup failed: " . $data['message'] . "</p>";
    }
} else {
    echo "<p style='color: red;'>❌ Failed to parse JSON response</p>";
    echo "<p>JSON Error: " . json_last_error_msg() . "</p>";
}

echo "<hr>";
echo "<p><strong>Note:</strong> This test calls the backup script directly, just like JavaScript would.</p>";
?>
