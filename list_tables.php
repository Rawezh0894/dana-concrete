<?php
$host = 'localhost';
$db   = 'dana_concrete_db';
$user = 'dana_user';
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
    
    echo "--- TABLES in $db ---\n";
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo implode("\n", $tables);
    echo "\n----------------------\n";

} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
?>
