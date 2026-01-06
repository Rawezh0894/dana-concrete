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
    
    echo "--- CHECKING TABLES ---\n";

    echo "1. Employees:\n";
    $stmt = $pdo->query("DESCRIBE employees");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo implode(", ", $columns) . "\n\n";

    echo "2. Employee Transactions:\n";
    try {
        $stmt = $pdo->query("DESCRIBE employee_transactions");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo implode(", ", $columns) . "\n\n";
    } catch (Exception $e) {
        echo "Table does not exist.\n\n";
    }

    echo "3. Salary Generations:\n";
    try {
        $stmt = $pdo->query("DESCRIBE salary_generations");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo implode(", ", $columns) . "\n\n";
    } catch (Exception $e) {
        echo "Table does not exist.\n\n";
    }

} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
?>
