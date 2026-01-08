<?php
require_once '../../config/db_conected.php';

echo "Debug Overtime Calculation\n";
echo "--------------------------\n";

// 1. Check Overtime Rate
$stmt = $pdo->query("SELECT value FROM settings WHERE name = 'overtime_rate'");
$setting = $stmt->fetch(PDO::FETCH_ASSOC);
echo "Overtime Rate Setting: " . ($setting ? $setting['value'] : 'Not Found') . "\n";

// 2. Check Date Column existence and content
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total, COUNT(date) as has_date, COUNT(created_at) as has_created_at FROM concrete_receipts");
    $counts = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Total Receipts: " . $counts['total'] . "\n";
    echo "Receipts with Date: " . $counts['has_date'] . "\n";
    echo "Receipts with Created_at: " . $counts['has_created_at'] . "\n";
} catch (Exception $e) {
    echo "Error checking table: " . $e->getMessage() . "\n";
}

// 3. Check Mixer Drivers
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM concrete_receipts WHERE mixer_driver_id IS NOT NULL AND mixer_driver_id > 0");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Receipts with Mixer Driver: " . $row['count'] . "\n";
} catch (Exception $e) {
    echo "Error checking drivers: " . $e->getMessage() . "\n";
}

// 4. Test Calculation Query (Current Month)
$start_date = date('Y-m-01');
$end_date = date('Y-m-t');
echo "Testing Period: $start_date to $end_date\n";

// Try Date column
try {
    $sql = "SELECT COUNT(*) as count FROM concrete_receipts WHERE date BETWEEN ? AND ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$start_date, $end_date]);
    $res = $stmt->fetch();
    echo "Count using 'date' column: " . $res['count'] . "\n";
} catch (Exception $e) {
    echo "Query using 'date' failed: " . $e->getMessage() . "\n";
}

// Try Created_at column
try {
    $sql = "SELECT COUNT(*) as count FROM concrete_receipts WHERE created_at BETWEEN ? AND ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
    $res = $stmt->fetch();
    echo "Count using 'created_at' column: " . $res['count'] . "\n";
} catch (Exception $e) {
    echo "Query using 'created_at' failed: " . $e->getMessage() . "\n";
}

?>
