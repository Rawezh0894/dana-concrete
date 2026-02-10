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
    // Get employee name, role, and resignation_date
    $stmt = $pdo->prepare("SELECT name, role, resignation_date FROM employees WHERE id = ?");
    $stmt->execute([$employee_id]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$employee) {
        echo json_encode(['success' => false, 'message' => 'کارمەند نەدۆزرایەوە']);
        exit;
    }
    
    $employee_name = $employee['name'];
    $employee_role = $employee['role'] ?? '';
    
    // Check if employee has role "شۆفێری میکسەر" (supports multiple roles)
    $hasMixerRole = (strpos($employee_role, 'شۆفێری میکسەر') !== false);
    
    // If employee doesn't have mixer role, return 0 overtime
    if (!$hasMixerRole) {
        echo json_encode([
            'success' => true,
            'data' => [
                'employee_id' => $employee_id,
                'employee_name' => $employee_name,
                'month' => $month,
                'mixer_receipt_count' => 0,
                'pump_receipt_count' => 0,
                'total_receipts' => 0,
                'total_meter' => 0,
                'overtime_rate' => 0,
                'overtime_amount' => 0
            ]
        ]);
        exit;
    }
    
    // Build date filter for month (using created_at field)
    $dateFilter = '';
    $params = [];
    if ($month && preg_match('/^\d{4}-\d{2}$/', $month)) {
        $dateFilter = "AND DATE_FORMAT(created_at, '%Y-%m') = ?";
        $params[] = $month;
    }
    
    // Get count of receipts for this employee as mixer driver (respect resignation_date)
    $query = "SELECT COUNT(*) as receipt_count 
              FROM concrete_receipts cr
              WHERE cr.mixer_driver_id = ? $dateFilter
              AND (? IS NULL OR COALESCE(cr.`date`, DATE(cr.created_at)) <= ?)";
    $stmt = $pdo->prepare($query);
    $stmt->execute(array_merge([$employee_id], $params, [$employee['resignation_date'], $employee['resignation_date']]));
    $mixer_result = $stmt->fetch(PDO::FETCH_ASSOC);
    $mixer_count = intval($mixer_result['receipt_count'] ?? 0);
    
    // Get count of receipts for this employee as pump driver (respect resignation_date)
    $query = "SELECT COUNT(*) as receipt_count 
              FROM concrete_receipts cr
              WHERE cr.pump_driver_id = ? AND cr.pump_driver_id IS NOT NULL $dateFilter
              AND (? IS NULL OR COALESCE(cr.`date`, DATE(cr.created_at)) <= ?)";
    $stmt = $pdo->prepare($query);
    $stmt->execute(array_merge([$employee_id], $params, [$employee['resignation_date'], $employee['resignation_date']]));
    $pump_result = $stmt->fetch(PDO::FETCH_ASSOC);
    $pump_count = intval($pump_result['receipt_count'] ?? 0);
    
    // Total receipt count (mixer + pump) - respect resignation_date
    $query = "SELECT COUNT(DISTINCT cr.id) as total_receipts 
              FROM concrete_receipts cr
              WHERE (cr.mixer_driver_id = ? OR cr.pump_driver_id = ?) $dateFilter
              AND (? IS NULL OR COALESCE(cr.`date`, DATE(cr.created_at)) <= ?)";
    $stmt = $pdo->prepare($query);
    $stmt->execute(array_merge([$employee_id, $employee_id], $params, [$employee['resignation_date'], $employee['resignation_date']]));
    $total_result = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_receipts = intval($total_result['total_receipts'] ?? 0);
    
    // Also get total meter for display
    $query = "SELECT COALESCE(SUM(meter_amount), 0) as total_meter 
              FROM concrete_receipts 
              WHERE (mixer_driver_id = ? OR pump_driver_id = ?) $dateFilter";
    $stmt = $pdo->prepare($query);
    $stmt->execute(array_merge([$employee_id, $employee_id], $params));
    $meter_result = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_meter = floatval($meter_result['total_meter'] ?? 0);
    
    // Get overtime rate from settings
    $stmt = $pdo->query("SELECT value FROM settings WHERE name = 'overtime_rate'");
    $setting = $stmt->fetch(PDO::FETCH_ASSOC);
    $overtime_rate = floatval($setting['value'] ?? 0);
    
    // Calculate overtime amount: receipt count × overtime rate
    $overtime_amount = $total_receipts * $overtime_rate;
    
    echo json_encode([
        'success' => true,
        'data' => [
            'employee_id' => $employee_id,
            'employee_name' => $employee_name,
            'month' => $month,
            'mixer_receipt_count' => $mixer_count,
            'pump_receipt_count' => $pump_count,
            'total_receipts' => $total_receipts,
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
