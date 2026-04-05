<?php
require_once 'config/db_conected.php';

try {
    // 1. Create employee_salary_history table
    $sql = "CREATE TABLE IF NOT EXISTS `employee_salary_history` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `employee_id` INT NOT NULL,
        `salary` DECIMAL(15, 2) NOT NULL DEFAULT 0,
        `bonus` DECIMAL(15, 2) NOT NULL DEFAULT 0,
        `effective_date` DATE NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    
    $pdo->exec($sql);
    echo "Table 'employee_salary_history' created or already exists.\n";
    
    // 2. Check if we need to seed it with current salaries
    $count = $pdo->query("SELECT COUNT(*) FROM employee_salary_history")->fetchColumn();
    if ($count == 0) {
        $employees = $pdo->query("SELECT id, salary, COALESCE(bonus, 0) as bonus, COALESCE(join_date, '2020-01-01') as join_date FROM employees")->fetchAll();
        $stmt = $pdo->prepare("INSERT INTO employee_salary_history (employee_id, salary, bonus, effective_date) VALUES (?, ?, ?, ?)");
        foreach ($employees as $emp) {
            $stmt->execute([$emp['id'], $emp['salary'], $emp['bonus'], $emp['join_date']]);
        }
        echo "Seeded " . count($employees) . " records into salary history.\n";
    } else {
        echo "Salary history already has data, skipping seeding.\n";
    }

} catch (Exception $e) {
    die("ERROR: " . $e->getMessage());
}
?>
