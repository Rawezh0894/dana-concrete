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

$employee_id = isset($_GET['employee_id']) ? intval($_GET['employee_id']) : 0;

if ($employee_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'کارمەند پێویستە']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT role FROM employees WHERE id = ?");
    $stmt->execute([$employee_id]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$employee) {
        echo json_encode(['success' => false, 'message' => 'کارمەند نەدۆزرایەوە']);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'role' => $employee['role'] ?? ''
    ]);
    
} catch (PDOException $e) {
    error_log('PDOException in get_employee_role.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'هەڵە لە وەرگرتنی زانیاری: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log('Exception in get_employee_role.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'هەڵەیەک هەیە: ' . $e->getMessage()]);
}
