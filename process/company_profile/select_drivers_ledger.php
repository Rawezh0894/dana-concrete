<?php
session_start();
require_once '../../config/db_conected.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$company_id = isset($_GET['company_id']) ? intval($_GET['company_id']) : 0;
$driver = isset($_GET['driver']) ? $_GET['driver'] : '';
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : '';
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : '';

if (!$company_id || empty($driver)) {
    echo json_encode([]);
    exit;
}

try {
    $where = "company_id = :company_id AND driver = :driver";
    $params = [':company_id' => $company_id, ':driver' => $driver];

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
            id,
            date,
            invoice_number,
            location,
            kg,
            total_freight_cost_usd,
            total_freight_cost_iqd,
            paid_to_driver_usd,
            paid_to_driver_iqd,
            (total_freight_cost_usd - paid_to_driver_usd) as remaining_usd,
            (total_freight_cost_iqd - paid_to_driver_iqd) as remaining_iqd,
            '' as note
        FROM purchases
        WHERE $where
        ORDER BY date ASC, id ASC
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($data);

} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
