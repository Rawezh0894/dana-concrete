<?php
require_once '../../config/db_conected.php';

header('Content-Type: text/html; charset=utf-8');

echo "<h2>Testing Other Expenses Data</h2>";

try {
    // Check if there are any expenses with car_id
    $car_expenses_query = "SELECT COUNT(*) as total FROM other_expenses WHERE car_id IS NOT NULL AND car_id != 0";
    $stmt = $pdo->query($car_expenses_query);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>Total expenses with car_id: " . $result['total'] . "</p>";

    // Check expense types
    $expense_types_query = "SELECT DISTINCT expense_type FROM other_expenses WHERE car_id IS NOT NULL AND car_id != 0";
    $stmt = $pdo->query($expense_types_query);
    $expense_types = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "<p>Expense types found: " . implode(', ', $expense_types) . "</p>";

    // Check currency types
    $currency_types_query = "SELECT DISTINCT currency_type FROM other_expenses WHERE car_id IS NOT NULL AND car_id != 0";
    $stmt = $pdo->query($currency_types_query);
    $currency_types = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "<p>Currency types found: " . implode(', ', $currency_types) . "</p>";

    // Show sample data
    $sample_query = "SELECT id, car_id, expense_type, currency_type, amount_usd, amount_iqd, date 
                     FROM other_expenses 
                     WHERE car_id IS NOT NULL AND car_id != 0 
                     LIMIT 10";
    $stmt = $pdo->query($sample_query);
    $sample_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Sample Data:</h3>";
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Car ID</th><th>Expense Type</th><th>Currency</th><th>Amount USD</th><th>Amount IQD</th><th>Date</th></tr>";
    foreach ($sample_data as $row) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['car_id'] . "</td>";
        echo "<td>" . $row['expense_type'] . "</td>";
        echo "<td>" . $row['currency_type'] . "</td>";
        echo "<td>" . $row['amount_usd'] . "</td>";
        echo "<td>" . $row['amount_iqd'] . "</td>";
        echo "<td>" . $row['date'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";

    // Test the summary calculation
    $summary_query = "
        SELECT 
            COUNT(DISTINCT car_id) as total_cars,
            SUM(CASE 
                WHEN expense_type = 'بەکارهێنانی گاز' THEN 
                    CASE 
                        WHEN currency_type = 'دۆلار' THEN COALESCE(amount_usd, 0)
                        WHEN currency_type = 'دینار' THEN COALESCE(amount_iqd, 0) / 139250
                        ELSE 0 
                    END
                ELSE 0 
            END) as total_gas_expenses_usd,
            SUM(CASE 
                WHEN expense_type = 'بەکارهێنانی کاڵای کۆگا' THEN 
                    CASE 
                        WHEN currency_type = 'دۆلار' THEN COALESCE(amount_usd, 0)
                        WHEN currency_type = 'دینار' THEN COALESCE(amount_iqd, 0) / 139250
                        ELSE 0 
                    END
                ELSE 0 
            END) as total_material_expenses_usd,
            SUM(CASE 
                WHEN currency_type = 'دۆلار' THEN COALESCE(amount_usd, 0)
                WHEN currency_type = 'دینار' THEN COALESCE(amount_iqd, 0) / 139250
                ELSE 0 
            END) as total_expenses_usd
        FROM other_expenses 
        WHERE car_id IS NOT NULL AND car_id != 0
    ";
    
    $stmt = $pdo->query($summary_query);
    $summary = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<h3>Summary Calculation Test:</h3>";
    echo "<p>Total cars: " . $summary['total_cars'] . "</p>";
    echo "<p>Total gas expenses USD: " . $summary['total_gas_expenses_usd'] . "</p>";
    echo "<p>Total material expenses USD: " . $summary['total_material_expenses_usd'] . "</p>";
    echo "<p>Total expenses USD: " . $summary['total_expenses_usd'] . "</p>";

} catch (Exception $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>
