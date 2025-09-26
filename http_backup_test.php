<?php
// Proper HTTP request test for backup
session_start();

echo "<h2>HTTP Request Test for Backup</h2>";

if (!isset($_SESSION['user_id'])) {
    echo "❌ Not logged in. Please <a href='login.php'>login</a> first.";
    exit;
}

echo "✅ User logged in: " . $_SESSION['username'] . "<br>";

// Test the backup via proper HTTP request
echo "<h3>Testing Backup via HTTP Request:</h3>";

// Create the POST data
$postdata = json_encode(['action' => 'create_backup']);

// Set up the context for HTTP request with session cookie
$session_cookie = 'PHPSESSID=' . session_id();
$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($postdata),
            'Cookie: ' . $session_cookie
        ],
        'content' => $postdata
    ]
]);

// Make the request to the backup script
$url = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/process/backup/create_backup.php';
echo "Request URL: " . $url . "<br>";
echo "POST Data: " . $postdata . "<br>";
echo "Session Cookie: " . $session_cookie . "<br><br>";

$result = file_get_contents($url, false, $context);

echo "<h3>Raw Response (first 500 chars):</h3>";
echo "<pre>" . htmlspecialchars(substr($result, 0, 500)) . "</pre>";

echo "<h3>Response Length:</h3>";
echo strlen($result) . " characters<br>";

echo "<h3>Response Headers:</h3>";
if (isset($http_response_header)) {
    echo "<pre>" . print_r($http_response_header, true) . "</pre>";
}

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
    
    // Show what we actually got
    echo "<h3>What we actually received:</h3>";
    echo "<pre style='background: #f0f0f0; padding: 10px; border: 1px solid #ccc;'>";
    echo htmlspecialchars($result);
    echo "</pre>";
}

echo "<hr>";
echo "<p><strong>Note:</strong> This test simulates the exact same request that the JavaScript makes.</p>";
?>
