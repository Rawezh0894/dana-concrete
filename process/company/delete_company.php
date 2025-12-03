<?php
session_start();
// Only log errors, don't display them in JSON response
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../php-error.log');

require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

// Log session and POST data for debugging
error_log('SESSION: ' . print_r($_SESSION, true));
error_log('company/delete_company.php POST: ' . print_r($_POST, true));

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    error_log('User not logged in for company deletion');
    echo json_encode(['success' => false, 'message' => 'سێشن نییە! تکایە بچۆ ژوورەوە.']);
    exit;
}

if (!hasPermission('delete_company')) {
    error_log('Permission denied for user: ' . $_SESSION['user_id'] . ' to delete company');
    echo json_encode(['success' => false, 'message' => 'ڕێگەت پێنەدراوە!']);
    exit;
}

try {
    $id = intval($_POST['id'] ?? 0);
    
    // Log parsed variables for debugging
    error_log("Parsed vars: id='$id'");

    if ($id <= 0) {
        error_log('Invalid company ID: ' . $id);
        echo json_encode(['success' => false, 'message' => 'ناسنامەی کۆمپانیا پێویستە!']);
        exit;
    }

    // Check if company exists
    $checkStmt = $pdo->prepare('SELECT id, name FROM company WHERE id = ?');
    $checkStmt->execute([$id]);
    $existingCompany = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$existingCompany) {
        error_log('Company not found for deletion: ID=' . $id);
        echo json_encode(['success' => false, 'message' => 'کۆمپانیا نەدۆزرایەوە!']);
        exit;
    }

    // Check for related purchases
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM purchases WHERE company_id = ?');
    $stmt->execute([$id]);
    if ($stmt->fetchColumn() > 0) {
        error_log('Cannot delete company: Has related purchases. Company ID=' . $id);
        echo json_encode(['success' => false, 'message' => 'ناتوانرێت کۆمپانیا بسڕدرێت: مامەڵەی کڕین یان قەرز پێوە تۆمارکراوە!']);
        exit;
    }

    // Check for related debts
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM debt_payments WHERE company_id = ?');
    $stmt->execute([$id]);
    if ($stmt->fetchColumn() > 0) {
        error_log('Cannot delete company: Has related debt payments. Company ID=' . $id);
        echo json_encode(['success' => false, 'message' => 'ناتوانرێت کۆمپانیا بسڕدرێت: مامەڵەی کڕین یان قەرز پێوە تۆمارکراوە!']);
        exit;
    }

    $stmt = $pdo->prepare('DELETE FROM company WHERE id = ?');
    $ok = $stmt->execute([$id]);
    
    if ($ok) {
        error_log('Company successfully deleted: ID=' . $id . ', Name=' . $existingCompany['name']);
        echo json_encode(['success' => true, 'message' => 'کۆمپانیا بەسەرکەوتوویی سڕایەوە!']);
    } else {
        error_log('Failed to delete company: ID=' . $id);
        echo json_encode(['success' => false, 'message' => 'هەڵە لە سڕینەوە!']);
    }

} catch (PDOException $e) {
    error_log('PDOException in company/delete_company.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'هەڵە لە سڕینەوەی کۆمپانیا!']);
} catch (Exception $e) {
    error_log('Exception in company/delete_company.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'هەڵە لە سڕینەوەی کۆمپانیا!']);
}
