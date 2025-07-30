<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ماوەی بەسەرچوونی سێشن (بە چرکە) - 24 کاتژمێر = 86400
$session_timeout = 86400;

// هەرکات session هاتەوە، کاتی دوا جووڵەی بەکارهێنەر نوێ بکەوە
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > $session_timeout)) {
    // سێشن بەسەرچووە
    session_unset();
    session_destroy();
    header('Location: ../index.php');
    exit;
}
$_SESSION['LAST_ACTIVITY'] = time();

// Database connection settings

$host = 'localhost';
$db   = 'dana_concrete_db';
$user = 'dana_user'; // گەر ناوی بەکارهێنەر یان وشەی نهێنی جیاوازە، گۆڕی
$pass = 'Rawezh.Jaza@0894';
$charset = 'utf8mb4';


// $host = 'localhost';
// $db   = 'dana_concrete_db';
// $user = 'root'; // گەر ناوی بەکارهێنەر یان وشەی نهێنی جیاوازە، گۆڕی
// $pass = '';
// $charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    // بڵاوکردنەوەی هەڵەی ڕاستی PDO بۆ تاقیکردنەوە
    die("DB ERROR: " . $e->getMessage());
}

/**
 * Helper function to create detailed notifications
 */
function createDetailedNotification($pdo, $user_id, $action, $table_name, $record_id, $description, $old_values = null, $new_values = null, $additional_info = null, $ip_address = null) {
    try {
        $sql = "INSERT INTO notifications (user_id, action, table_name, record_id, description, old_values, new_values, additional_info, ip_address) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $user_id,
            $action,
            $table_name,
            $record_id,
            $description,
            $old_values ? json_encode($old_values, JSON_UNESCAPED_UNICODE) : null,
            $new_values ? json_encode($new_values, JSON_UNESCAPED_UNICODE) : null,
            $additional_info ? json_encode($additional_info, JSON_UNESCAPED_UNICODE) : null,
            $ip_address ?: $_SERVER['REMOTE_ADDR'] ?? null
        ]);
        return $pdo->lastInsertId();
    } catch (Exception $e) {
        error_log("Error creating notification: " . $e->getMessage());
        return false;
    }
}

/**
 * Helper function to get user IP address
 */
function getUserIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
}
?>
