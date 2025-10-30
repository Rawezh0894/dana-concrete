<?php
require_once '../../config/db_conected.php';
header('Content-Type: application/json');

$employee_id = $_GET['employee_id'] ?? null;
$month = $_GET['month'] ?? null;

if (!$employee_id) {
    echo json_encode(['error' => 'employee_id required']);
    exit;
}

// Get employee basic data
$stmt = $pdo->prepare("SELECT salary, name FROM employees WHERE id = ?");
$stmt->execute([$employee_id]);
$employee = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$employee) {
    echo json_encode(['error' => 'Employee not found']);
    exit;
}

// Get SUM of payments for employee (salary + karwanhisabi + bonus for each payment)
$query = "SELECT SUM(COALESCE(salary,0) + COALESCE(CAST(REPLACE(karwanhisabi, ',', '') AS DECIMAL(15,2)),0) + COALESCE(bonus,0)) as total_paid FROM employee_payments WHERE employee_id = ?";
$params = [$employee_id];
if (!empty($month)) {
    $query .= " AND DATE_FORMAT(pay_month, '%Y-%m') = ?";
    $params[] = $month;
}
$stmt2 = $pdo->prepare($query);
$stmt2->execute($params);
$total_paid = $stmt2->fetchColumn();
if ($total_paid === false) $total_paid = 0;
// Final balance
$balance = $employee['salary'] - $total_paid;

echo json_encode([
    'name' => $employee['name'],
    'salary' => (float)$employee['salary'],
    'total_paid' => (float)$total_paid,
    'balance' => (float)$balance
]);
