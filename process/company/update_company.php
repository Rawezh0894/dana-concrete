<?php
session_start();
// Only log errors, don't display them in JSON response
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../php-error.log');

require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

// Log session and POST data for debugging
error_log('SESSION: ' . print_r($_SESSION, true));
error_log('company/update_company.php POST: ' . print_r($_POST, true));

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    error_log('User not logged in for company update');
    http_response_code(401);
    echo json_encode(['error' => 'سێشن نییە! تکایە بچۆ ژوورەوە.']);
    exit;
}

if (!hasPermission('edit_company')) {
    error_log('Permission denied for user: ' . $_SESSION['user_id'] . ' to update company');
    http_response_code(403);
    echo json_encode(['error' => 'ڕێگەت پێنەدراوە!']);
    exit;
}

try {
    $id = intval($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $opening_debt_usd = floatval($_POST['opening_debt_usd'] ?? 0);
    $opening_debt_iqd = floatval($_POST['opening_debt_iqd'] ?? 0);
    $currency_type = $_POST['currency_type'] ?? '';

    // Log parsed variables for debugging
    error_log("Parsed vars: id='$id', name='$name', opening_debt_usd='$opening_debt_usd', opening_debt_iqd='$opening_debt_iqd', currency_type='$currency_type'");

    // Validate required fields
    if ($id <= 0) {
        error_log('Invalid company ID: ' . $id);
        echo json_encode(['success' => false, 'message' => 'ناسنامەی کۆمپانیا پێویستە!']);
        exit;
    }

    if (empty($name)) {
        error_log('Company name is empty');
        echo json_encode(['success' => false, 'message' => 'ناوی کۆمپانیا پێویستە!']);
        exit;
    }

    if (empty($currency_type)) {
        error_log('Currency type is empty');
        echo json_encode(['success' => false, 'message' => 'جۆری دراو پێویستە!']);
        exit;
    }

    if (!in_array($currency_type, ['دینار', 'دۆلار'])) {
        error_log('Invalid currency type: ' . $currency_type);
        echo json_encode(['success' => false, 'message' => 'جۆری دراو نادروستە!']);
        exit;
    }

    // Check if company exists
    $checkStmt = $pdo->prepare('SELECT id, name FROM company WHERE id = ?');
    $checkStmt->execute([$id]);
    $existingCompany = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$existingCompany) {
        error_log('Company not found: ID=' . $id);
        echo json_encode(['success' => false, 'message' => 'کۆمپانیا نەدۆزرایەوە!']);
        exit;
    }

    // Only one of opening_debt_usd or opening_debt_iqd should be nonzero
    if ($opening_debt_usd > 0) $opening_debt_iqd = 0;
    if ($opening_debt_iqd > 0) $opening_debt_usd = 0;

    $stmt = $pdo->prepare('UPDATE company SET name = ?, opening_debt_usd = ?, opening_debt_iqd = ?, currency_type = ? WHERE id = ?');
    $ok = $stmt->execute([$name, $opening_debt_usd, $opening_debt_iqd, $currency_type, $id]);
    
    if ($ok) {
        error_log('Company successfully updated: ID=' . $id . ', Name=' . $name . ', Currency=' . $currency_type);
        echo json_encode(['success' => true, 'message' => 'کۆمپانیا بەسەرکەوتوویی نوێکرایەوە!', 'currency_type' => $currency_type]);
    } else {
        error_log('Failed to update company: ID=' . $id);
        echo json_encode(['success' => false, 'message' => 'هەڵە لە نوێکردنەوە!']);
    }

} catch (PDOException $e) {
    error_log('PDOException in company/update_company.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'هەڵەی داتابەیس: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log('Exception in company/update_company.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'هەڵەی سیستەم: ' . $e->getMessage()]);
}
?>
