<?php
/**
 * Auto Record Monthly Material Stock
 * This script should be run via cron job at the end of each month
 * Example cron job: 0 23 28-31 * * /usr/bin/php /path/to/auto_record_monthly_stock.php
 */

require_once '../config/db_conected.php';

// Check if it's the last day of the month
function isLastDayOfMonth() {
    $today = new DateTime();
    $tomorrow = clone $today;
    $tomorrow->add(new DateInterval('P1D'));
    
    return $today->format('m') !== $tomorrow->format('m');
}

// Get the last day of current month
function getLastDayOfMonth() {
    $today = new DateTime();
    return $today->format('Y-m-t'); // t = number of days in month
}

// Main execution
try {
    // Check if it's the last day of the month
    if (!isLastDayOfMonth()) {
        echo "Not the last day of the month. Exiting.\n";
        exit(0);
    }
    
    $currentMonth = date('Y-m');
    $lastDay = getLastDayOfMonth();
    
    // Check if already recorded for this month
    $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM monthly_material_stock WHERE month_year = ?");
    $check_stmt->execute([$currentMonth]);
    $existing_count = $check_stmt->fetchColumn();
    
    if ($existing_count > 0) {
        echo "Monthly stock already recorded for $currentMonth. Exiting.\n";
        exit(0);
    }
    
    // Record monthly stock using stored procedure
    $stmt = $pdo->prepare("CALL RecordMonthlyMaterialStock(?, ?)");
    $stmt->execute([$currentMonth, 1]); // Use system user ID 1
    
    echo "Monthly stock recorded successfully for $currentMonth\n";
    
    // Log the action
    error_log("Auto monthly stock recording completed for $currentMonth");
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    error_log("Auto monthly stock recording failed: " . $e->getMessage());
    exit(1);
}
?>
