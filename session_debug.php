<?php
// Session test for backup debugging
session_start();

echo "<h2>Session Debug Test</h2>";

echo "<h3>Session Status:</h3>";
echo "Session ID: " . session_id() . "<br>";
echo "Session Status: " . session_status() . "<br>";

echo "<h3>Session Variables:</h3>";
if (isset($_SESSION['user_id'])) {
    echo "✅ User ID: " . $_SESSION['user_id'] . "<br>";
} else {
    echo "❌ User ID not set<br>";
}

if (isset($_SESSION['username'])) {
    echo "✅ Username: " . $_SESSION['username'] . "<br>";
} else {
    echo "❌ Username not set<br>";
}

if (isset($_SESSION['role'])) {
    echo "✅ Role: " . $_SESSION['role'] . "<br>";
} else {
    echo "❌ Role not set<br>";
}

echo "<h3>All Session Data:</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h3>Test Backup Script Access:</h3>";
if (isset($_SESSION['user_id'])) {
    echo "✅ User is logged in - backup should work<br>";
    
    // Test the actual backup script
    echo "<h4>Testing backup script directly:</h4>";
    
    $postdata = json_encode(['action' => 'create_backup']);
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/json',
            'content' => $postdata
        ]
    ]);
    
    $result = file_get_contents('process/backup/create_backup.php', false, $context);
    echo "Raw response: " . htmlspecialchars($result) . "<br>";
    
    $data = json_decode($result, true);
    if ($data) {
        echo "Parsed response: <pre>" . print_r($data, true) . "</pre>";
    } else {
        echo "❌ Failed to parse JSON response<br>";
    }
    
} else {
    echo "❌ User not logged in - backup will fail<br>";
    echo "<p>Please <a href='login.php'>login</a> first</p>";
}
?>
