<?php
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';
if (!hasPermission('view_sale')) {
    echo json_encode(['success' => false, 'message' => 'ڕێگەت پێنەدراوە!']);
    exit;
}
header('Content-Type: application/json; charset=utf-8');
$customer_id = isset($_GET['customer_id']) ? intval($_GET['customer_id']) : 0;
if (!$customer_id) {
    echo json_encode(['success' => false, 'message' => 'کڕیار دیاری نەکراوە']);
    exit;
}
if (isset($_GET['stats'])) {
    // Get opening debt
    $row = $pdo->prepare('SELECT opening_debt_usd, opening_debt_iqd FROM customers WHERE id = ?');
    $row->execute([$customer_id]);
    $debt = $row->fetch(PDO::FETCH_ASSOC);
    // Sum of remaining amounts from sales (USD)
    $sales_usd = $pdo->prepare("SELECT COALESCE(SUM(remaining_amount), 0) FROM sales WHERE customer_id = ? AND payment_type = 'قەرز' AND (dolar_rate IS NOT NULL AND dolar_rate > 0)");
    $sales_usd->execute([$customer_id]);
    $total_remaining_usd = $sales_usd->fetchColumn();
    // Sum of remaining amounts from sales (IQD)
    $sales_iqd = $pdo->prepare("SELECT COALESCE(SUM(remaining_amount), 0) FROM sales WHERE customer_id = ? AND payment_type = 'قەرز' AND (dolar_rate IS NULL OR dolar_rate = 0)");
    $sales_iqd->execute([$customer_id]);
    $total_remaining_iqd = $sales_iqd->fetchColumn();
    // Add opening debt
    $total_debt_usd = floatval($total_remaining_usd) + floatval($debt['opening_debt_usd'] ?? 0);
    $total_debt_iqd = floatval($total_remaining_iqd) + floatval($debt['opening_debt_iqd'] ?? 0);
    $count = $pdo->prepare("SELECT COUNT(*) FROM sales WHERE customer_id = ?");
    $count->execute([$customer_id]);
    $sales_count = $count->fetchColumn();
    echo json_encode(['stats' => [
        'total_debt_usd' => $total_debt_usd,
        'total_debt_iqd' => $total_debt_iqd,
        'opening_debt_usd' => $debt['opening_debt_usd'] ?? 0,
        'opening_debt_iqd' => $debt['opening_debt_iqd'] ?? 0,
        'sales_count' => $sales_count
    ]]);
    exit;
}
try {
    $stmt = $pdo->prepare('
        SELECT s.*, c.name AS customer_name, f.name AS formula_name
        FROM sales s
        LEFT JOIN customers c ON s.customer_id = c.id
        LEFT JOIN concrete_formulas f ON s.formula_id = f.id
        WHERE s.customer_id = ?
        ORDER BY s.id DESC
    ');
    $stmt->execute([$customer_id]);
    $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'data' => $sales]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
