<?php
session_start();
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Check permission (using a general report permission or add a new one if needed)
if (!hasPermission('view_reports')) { // assuming view_reports users can edit this, or maybe add 'manage_reports' later
    echo json_encode(['success' => false, 'message' => 'Permission denied']);
    exit;
}

$year = isset($_POST['year']) ? intval($_POST['year']) : 0;
$month = isset($_POST['month']) ? intval($_POST['month']) : 0;
// cost can be null/empty to remove the override
$cost = isset($_POST['cost']) && $_POST['cost'] !== '' ? floatval($_POST['cost']) : null;

if ($year < 2000 || $year > 3000 || $month < 1 || $month > 12) {
    echo json_encode(['success' => false, 'message' => 'Invalid date']);
    exit;
}

try {
    if ($cost === null) {
        // Delete override
        $stmt = $pdo->prepare("DELETE FROM monthly_material_cost_overrides WHERE year = ? AND month = ?");
        $stmt->execute([$year, $month]);
    } else {
        // Insert or Update
        $stmt = $pdo->prepare("
            INSERT INTO monthly_material_cost_overrides (year, month, cost_usd) 
            VALUES (?, ?, ?) 
            ON DUPLICATE KEY UPDATE cost_usd = VALUES(cost_usd)
        ");
        $stmt->execute([$year, $month, $cost]);
    }

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    // Check if table exists error
    if ($e->getCode() == '42S02') { // Table not found
         echo json_encode(['success' => false, 'message' => 'Table monthly_material_cost_overrides does not exist. Please run the SQL migration.']);
    } else {
        error_log("Error setting monthly cost: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
}
