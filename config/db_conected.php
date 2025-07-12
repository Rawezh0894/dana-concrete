<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Database connection settings
$host = 'localhost';
$db   = 'dana_concrete';
$user = 'webuser'; // گەر ناوی بەکارهێنەر یان وشەی نهێنی جیاوازە، گۆڕی
$pass = 'Rawezh.Jaza@0894';
$charset = 'utf8mb4';

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
