<?php
session_start();
// Only log errors, don't display them in JSON response
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../php-error.log');

require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

// Log session and POST data for debugging
error_log('SESSION: ' . print_r($_SESSION, true));
error_log('company/add_company.php POST: ' . print_r($_POST, true));

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    error_log('User not logged in for company addition');
    http_response_code(401);
    echo json_encode(['error' => 'سێشن نییە! تکایە بچۆ ژوورەوە.']);
    exit;
}

if (!hasPermission('view_accounts')) {
    error_log('Permission denied for user: ' . $_SESSION['user_id'] . ' to add company');
    http_response_code(403);
    echo json_encode(['error' => 'ڕێگەت پێنەدراوە!']);
    exit;
}

try {
    $name = trim($_POST['name'] ?? '');
    $opening_debt_usd = floatval($_POST['opening_debt_usd'] ?? 0);
    $opening_debt_iqd = floatval($_POST['opening_debt_iqd'] ?? 0);
    $currency_type = $_POST['currency_type'] ?? '';

    // Log parsed variables for debugging
    error_log("Parsed vars: name='$name', opening_debt_usd='$opening_debt_usd', opening_debt_iqd='$opening_debt_iqd', currency_type='$currency_type'");

    // Validate required fields
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

    // Check for duplicate company name
    $stmt = $pdo->prepare('SELECT id FROM company WHERE name = ?');
    $stmt->execute([$name]);
    if ($stmt->fetch()) {
        error_log('Duplicate company name found: ' . $name);
        echo json_encode(['success' => false, 'message' => 'ئەم ناوی کۆمپانیا پێشتر تۆمارکراوە!']);
        exit;
    }

    // Only one of opening_debt_usd or opening_debt_iqd should be nonzero
    if ($opening_debt_usd > 0) $opening_debt_iqd = 0;
    if ($opening_debt_iqd > 0) $opening_debt_usd = 0;

    $stmt = $pdo->prepare('INSERT INTO company (name, opening_debt_usd, opening_debt_iqd, currency_type) VALUES (?, ?, ?, ?)');
    $ok = $stmt->execute([$name, $opening_debt_usd, $opening_debt_iqd, $currency_type]);
    
    if ($ok) {
        error_log('Company successfully added: Name=' . $name . ', Currency=' . $currency_type);
        echo json_encode(['success' => true, 'message' => 'کۆمپانیا بەسەرکەوتوویی زیادکرا!', 'currency_type' => $currency_type]);
    } else {
        error_log('Failed to add company: Name=' . $name);
        echo json_encode(['success' => false, 'message' => 'هەڵە لە زیادکردن!']);
    }

} catch (PDOException $e) {
    error_log('PDOException in company/add_company.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'هەڵەی داتابەیس: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log('Exception in company/add_company.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'هەڵەی سیستەم: ' . $e->getMessage()]);
}
?>
