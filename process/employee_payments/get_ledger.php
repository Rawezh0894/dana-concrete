<?php
require_once '../../config/db_conected.php';

header('Content-Type: application/json');

try {
    $stmt = $pdo->prepare("
        SELECT 
            t.*, 
            e.name as employee_name 
        FROM employee_transactions t
        LEFT JOIN employees e ON t.employee_id = e.id
        ORDER BY t.transaction_date DESC, t.id DESC
        LIMIT 200
    ");
    $stmt->execute();
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($transactions);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
