<?php
session_start();
require_once '../../config/db_conected.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Get Filters
$from_date = $_POST['from'] ?? null;
$to_date = $_POST['to'] ?? null;
$type = $_POST['type'] ?? null;
$category_id = $_POST['category_id'] ?? null;
$min_amount = $_POST['min_amount'] ?? null;
$max_amount = $_POST['max_amount'] ?? null;
$search = $_POST['search'] ?? null;

$query = "
    SELECT t.id, t.created_at, t.type as trans_type, t.category_id, tc.name as category_name,
    (SELECT amount FROM ledger_entries WHERE transaction_id = t.id AND currency_code = 'USD' LIMIT 1) as usd_amount,
    (SELECT amount FROM ledger_entries WHERE transaction_id = t.id AND currency_code = 'IQD' LIMIT 1) as iqd_amount,
    (SELECT description FROM ledger_entries WHERE transaction_id = t.id LIMIT 1) as description
    FROM transactions t
    LEFT JOIN transaction_categories tc ON t.category_id = tc.id
    WHERE t.created_by = :user_id
";

$params = [':user_id' => $user_id];

if ($from_date) {
    $query .= " AND DATE(t.created_at) >= :from_date";
    $params[':from_date'] = $from_date;
}
if ($to_date) {
    $query .= " AND DATE(t.created_at) <= :to_date";
    $params[':to_date'] = $to_date;
}
if ($type && $type !== 'ALL') {
    if ($type === 'EXCHANGE') {
        $query .= " AND t.type = 'EXCHANGE'";
    } elseif ($type === 'INFLOW') {
        $query .= " AND t.type != 'EXCHANGE' AND EXISTS (SELECT 1 FROM ledger_entries WHERE transaction_id = t.id AND amount > 0)";
    } elseif ($type === 'OUTFLOW') {
        $query .= " AND t.type != 'EXCHANGE' AND EXISTS (SELECT 1 FROM ledger_entries WHERE transaction_id = t.id AND amount < 0)";
    }
}
if ($category_id) {
    $query .= " AND t.category_id = :category_id";
    $params[':category_id'] = $category_id;
}
if ($min_amount !== null && $min_amount !== '') {
    $query .= " AND EXISTS (SELECT 1 FROM ledger_entries WHERE transaction_id = t.id AND ABS(amount) >= :min_amount)";
    $params[':min_amount'] = $min_amount;
}
if ($max_amount !== null && $max_amount !== '') {
    $query .= " AND EXISTS (SELECT 1 FROM ledger_entries WHERE transaction_id = t.id AND ABS(amount) <= :max_amount)";
    $params[':max_amount'] = $max_amount;
}
if ($search) {
    $query .= " AND EXISTS (SELECT 1 FROM ledger_entries WHERE transaction_id = t.id AND description LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

$query .= " ORDER BY t.created_at DESC";

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'data' => $data]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
