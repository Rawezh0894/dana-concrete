<?php
session_start();
require_once '../../config/db_conected.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$company_id = isset($_GET['company_id']) ? intval($_GET['company_id']) : 0;
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : '';
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : '';

if (!$company_id) {
    echo json_encode([]);
    exit;
}

try {
    $where = "company_id = :company_id AND location IS NOT NULL AND location != ''";
    $params = [':company_id' => $company_id];

    if (!empty($from_date)) {
        $where .= " AND date >= :from_date";
        $params[':from_date'] = $from_date;
    }
    if (!empty($to_date)) {
        $where .= " AND date <= :to_date";
        $params[':to_date'] = $to_date;
    }

    $query = "
        SELECT 
            location,
            SUM(price - total_freight_cost_usd) as total_cost_usd,
            SUM(amount_iqd - total_freight_cost_iqd) as total_cost_iqd,
            SUM(paid_to_location_usd) as total_paid_usd,
            SUM(paid_to_location_iqd) as total_paid_iqd,
            SUM((price - total_freight_cost_usd) - paid_to_location_usd) as remaining_usd,
            SUM((amount_iqd - total_freight_cost_iqd) - paid_to_location_iqd) as remaining_iqd
        FROM purchases
        WHERE $where
        GROUP BY location
        ORDER BY location ASC
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($data);

} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
