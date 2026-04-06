<?php
// c:\xampp\htdocs\dana-concrete\process\company_profile\select_debt.php
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
    $from_date = $_GET['from_date'] ?? '';
    $to_date = $_GET['to_date'] ?? '';
    $params = [$company_id];
    $date_condition = '';
    
    if ($from_date && $to_date) {
        $date_condition = ' AND p.date >= ? AND p.date <= ?';
        $params[] = $from_date;
        $params[] = $to_date;
    }

    // Get basic debt info
    $row = $pdo->prepare('SELECT opening_debt_usd, opening_debt_iqd FROM company WHERE id = ?');
    $row->execute([$company_id]);
    $debt_base = $row->fetch(PDO::FETCH_ASSOC);

    // Sum of remaining amounts from purchases
    $purch_stmt = $pdo->prepare("
        SELECT 
            COALESCE(SUM(remaining_usd), 0) as remaining_usd,
            COALESCE(SUM(remaining_iqd), 0) as remaining_iqd,
            COALESCE(SUM(remaining_iqd / NULLIF(exchange_rate / 100, 0)), 0) as remaining_iqd_converted
        FROM purchases p
        WHERE p.company_id = ? AND p.payment_type = 'قەرز' $date_condition
    ");
    $purch_stmt->execute($params);
    $purch_res = $purch_stmt->fetch(PDO::FETCH_ASSOC);

    // Sum of Adjustments
    $adj_stmt = $pdo->prepare("
        SELECT 
            COALESCE(SUM(amount_usd), 0) as total_adj_usd,
            COALESCE(SUM(amount_iqd), 0) as total_adj_iqd
        FROM company_adjustments 
        WHERE company_id = ?
    ");
    $adj_stmt->execute([$company_id]);
    $adj_res = $adj_stmt->fetch(PDO::FETCH_ASSOC);

    // Totals logic
    $total_debt_usd = floatval($purch_res['remaining_usd']) + floatval($purch_res['remaining_iqd_converted']);
    $total_debt_iqd = floatval($purch_res['remaining_iqd']);

    // Always include opening debt and adjustments in the "Total Debt" card
    $total_debt_usd += floatval($debt_base['opening_debt_usd']) + floatval($adj_res['total_adj_usd']);
    $total_debt_iqd += floatval($debt_base['opening_debt_iqd']) + floatval($adj_res['total_adj_iqd']);

    // Total purchase stats for filters
    $totals_stmt = $pdo->prepare("
        SELECT 
            COALESCE(SUM(CASE WHEN p.type = 'دۆلار' THEN p.price ELSE 0 END), 0) AS total_price_usd,
            COALESCE(SUM(CASE WHEN p.type = 'دینار' THEN p.price ELSE 0 END), 0) AS total_price_iqd,
            COALESCE(SUM(p.kg), 0) AS total_kg
        FROM purchases p
        WHERE p.company_id = ? $date_condition
    ");
    $totals_stmt->execute($params);
    $totals = $totals_stmt->fetch(PDO::FETCH_ASSOC);

    $credit_count_stmt = $pdo->prepare("SELECT COUNT(*) FROM purchases p WHERE p.company_id = ? AND p.payment_type = 'قەرز' $date_condition");
    $credit_count_stmt->execute($params);
    
    echo json_encode(['stats' => [
        'total_debt_usd' => $total_debt_usd,
        'total_debt_iqd' => $total_debt_iqd,
        'opening_debt_usd' => $debt_base['opening_debt_usd'] ?? 0,
        'opening_debt_iqd' => $debt_base['opening_debt_iqd'] ?? 0,
        'credit_count' => $credit_count_stmt->fetchColumn(),
        'total_price_usd' => floatval($totals['total_price_usd'] ?? 0),
        'total_price_iqd' => floatval($totals['total_price_iqd'] ?? 0),
        'total_kg' => floatval($totals['total_kg'] ?? 0)
    ]]);
    exit;
}

// Payment history list
$from_date = $_GET['from_date'] ?? '';
$to_date = $_GET['to_date'] ?? '';
$params = [$company_id];
$date_condition = '';
if ($from_date && $to_date) {
    $date_condition = ' AND date >= ? AND date <= ?';
    $params[] = $from_date;
    $params[] = $to_date;
}

$stmt = $pdo->prepare('SELECT id, date, amount_usd, amount_iqd, discount_usd, discount_iqd, dollar_rate, note FROM debt_payments WHERE company_id = ?' . $date_condition . ' ORDER BY date DESC, id DESC');
$stmt->execute($params);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
