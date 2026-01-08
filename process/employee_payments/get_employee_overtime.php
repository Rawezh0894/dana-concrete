<?php
session_start();
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'سێشن نییە! تکایە بچۆ ژوورەوە.']);
    exit;
}

if (!hasPermission('view_employee_payment')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'ڕێگەت پێنەدراوە!']);
    exit;
}

$employee_id = isset($_GET['employee_id']) ? intval($_GET['employee_id']) : 0;
$month = isset($_GET['month']) ? trim($_GET['month']) : '';

if ($employee_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'کارمەند پێویستە']);
    exit;
}

try {
    // Get employee name
    $stmt = $pdo->prepare("SELECT name FROM employees WHERE id = ?");
    $stmt->execute([$employee_id]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$employee) {
        echo json_encode(['success' => false, 'message' => 'کارمەند نەدۆزرایەوە']);
        exit;
    }
    
    $employee_name = $employee['name'];
    
    // Build date filter for month
    $dateFilter = '';
    $params = [];
    if ($month && preg_match('/^\d{4}-\d{2}$/', $month)) {
        $dateFilter = "AND DATE_FORMAT(date, '%Y-%m') = ?";
        $params[] = $month;
    }
    
    // Get total meter amount for this employee as mixer driver
    $query = "SELECT COALESCE(SUM(meter_amount), 0) as total_meter 
              FROM concrete_receipts 
              WHERE mixer_driver_id = ? $dateFilter";
    $stmt = $pdo->prepare($query);
    $stmt->execute(array_merge([$employee_id], $params));
    $mixer_result = $stmt->fetch(PDO::FETCH_ASSOC);
    $mixer_total = floatval($mixer_result['total_meter'] ?? 0);
    
    // Get total meter amount for this employee as pump driver
    $query = "SELECT COALESCE(SUM(meter_amount), 0) as total_meter 
              FROM concrete_receipts 
              WHERE pump_driver_id = ? AND pump_driver_id IS NOT NULL $dateFilter";
    $stmt = $pdo->prepare($query);
    $stmt->execute(array_merge([$employee_id], $params));
    $pump_result = $stmt->fetch(PDO::FETCH_ASSOC);
    $pump_total = floatval($pump_result['total_meter'] ?? 0);
    
    // Total meter amount (mixer + pump)
    $total_meter = $mixer_total + $pump_total;
    
    // Get overtime rate from settings
    $stmt = $pdo->query("SELECT value FROM settings WHERE name = 'overtime_rate'");
    $setting = $stmt->fetch(PDO::FETCH_ASSOC);
    $overtime_rate = floatval($setting['value'] ?? 0);
    
    // Calculate overtime amount
    $overtime_amount = $total_meter * $overtime_rate;
    
    echo json_encode([
        'success' => true,
        'data' => [
            'employee_id' => $employee_id,
            'employee_name' => $employee_name,
            'month' => $month,
            'mixer_total_meter' => $mixer_total,
            'pump_total_meter' => $pump_total,
            'total_meter' => $total_meter,
            'overtime_rate' => $overtime_rate,
            'overtime_amount' => $overtime_amount
        ]
    ]);
    
} catch (PDOException $e) {
    error_log('PDOException in get_employee_overtime.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'هەڵە لە وەرگرتنی زانیاری: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log('Exception in get_employee_overtime.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'هەڵەیەک هەیە: ' . $e->getMessage()]);
}
