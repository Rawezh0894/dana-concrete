<?php
session_start();
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';
header('Content-Type: application/json');
if (isset($_SESSION['user_id']) && !hasPermission('view_debt')) {
    http_response_code(403);
    echo json_encode([]);
    exit;
}
$company_id = $_GET['company_id'] ?? null;
if (!$company_id) {
    echo json_encode([]);
    exit;
}
if (isset($_GET['stats'])) {
    // Get opening debt
    $row = $pdo->prepare('SELECT opening_debt_usd, opening_debt_iqd FROM company WHERE id = ?');
    $row->execute([$company_id]);
    $debt = $row->fetch(PDO::FETCH_ASSOC);
    // Sum of remaining amounts from purchases
    $purchases_usd = $pdo->prepare("SELECT COALESCE(SUM(remaining_usd), 0) FROM purchases WHERE company_id = ? AND payment_type = 'قەرز'");
    $purchases_usd->execute([$company_id]);
    $total_remaining_usd = $purchases_usd->fetchColumn();
    $purchases_iqd = $pdo->prepare("SELECT COALESCE(SUM(remaining_iqd), 0) FROM purchases WHERE company_id = ? AND payment_type = 'قەرز'");
    $purchases_iqd->execute([$company_id]);
    $total_remaining_iqd = $purchases_iqd->fetchColumn();
    // Add opening debt
    $total_debt_usd = floatval($total_remaining_usd) + floatval($debt['opening_debt_usd'] ?? 0);
    $total_debt_iqd = floatval($total_remaining_iqd) + floatval($debt['opening_debt_iqd'] ?? 0);
    $count = $pdo->prepare("SELECT COUNT(*) FROM purchases WHERE company_id = ? AND payment_type = 'قەرز'");
    $count->execute([$company_id]);
    $credit_count = $count->fetchColumn();
    echo json_encode(['stats' => [
        'total_debt_usd' => $total_debt_usd,
        'total_debt_iqd' => $total_debt_iqd,
        'opening_debt_usd' => $debt['opening_debt_usd'] ?? 0,
        'opening_debt_iqd' => $debt['opening_debt_iqd'] ?? 0,
        'credit_count' => $credit_count
    ]]);
    exit;
}
if (isset($_GET['total_remaining'])) {
    // Get total remaining amount: purchases remaining + opening debt
    $company = $pdo->prepare('SELECT opening_debt_usd, opening_debt_iqd FROM company WHERE id = ?');
    $company->execute([$company_id]);
    $company_data = $company->fetch(PDO::FETCH_ASSOC);
    
    // Sum of remaining amounts from purchases
    $purchases_usd = $pdo->prepare("SELECT COALESCE(SUM(remaining_usd), 0) FROM purchases WHERE company_id = ? AND payment_type = 'قەرز'");
    $purchases_usd->execute([$company_id]);
    $total_remaining_usd = $purchases_usd->fetchColumn();
    
    $purchases_iqd = $pdo->prepare("SELECT COALESCE(SUM(remaining_iqd), 0) FROM purchases WHERE company_id = ? AND payment_type = 'قەرز'");
    $purchases_iqd->execute([$company_id]);
    $total_remaining_iqd = $purchases_iqd->fetchColumn();
    
    // Add opening debt
    $total_remaining_usd += floatval($company_data['opening_debt_usd'] ?? 0);
    $total_remaining_iqd += floatval($company_data['opening_debt_iqd'] ?? 0);
    
    echo json_encode([
        'total_remaining_usd' => $total_remaining_usd,
        'total_remaining_iqd' => $total_remaining_iqd
    ]);
    exit;
}
if (isset($_GET['company_info'])) {
    $stmt = $pdo->prepare('SELECT currency_type FROM company WHERE id = ?');
    $stmt->execute([$company_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo json_encode($row);
    exit;
}
$stmt = $pdo->prepare('SELECT id, date, amount_usd, amount_iqd, dollar_rate, note FROM debt_payments WHERE company_id = ? ORDER BY date DESC, id DESC');
$stmt->execute([$company_id]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($rows);
