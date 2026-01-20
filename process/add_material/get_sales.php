<?php
session_start();
require_once '../../config/db_conected.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['data' => []]);
    exit;
}

try {
    $where = ["1=1"];
    $params = [];

    // Filters
    if (!empty($_GET['from_date'])) {
        $where[] = "ms.date >= ?";
        $params[] = $_GET['from_date'];
    }
    if (!empty($_GET['to_date'])) {
        $where[] = "ms.date <= ?";
        $params[] = $_GET['to_date'];
    }
    if (!empty($_GET['material_id'])) {
        $where[] = "ms.material_id = ?";
        $params[] = $_GET['material_id'];
    }
    if (!empty($_GET['buyer_type'])) {
        $where[] = "ms.buyer_type = ?";
        $params[] = $_GET['buyer_type'];
    }

    $whereClause = implode(" AND ", $where);

    $sql = "SELECT 
                ms.*,
                lm.name as material_name,
                lm.unit_type as base_unit,
                lm.pieces_per_carton,
                lm.buckets_per_barrel,
                lm.liters_per_bucket,
                lm.liters_per_barrel,
                c.name as customer_name,
                comp.name as company_name
            FROM material_sales ms
            JOIN list_materials lm ON ms.material_id = lm.id
            LEFT JOIN customers c ON ms.customer_id = c.id
            LEFT JOIN company comp ON ms.company_id = comp.id
            WHERE $whereClause
            ORDER BY ms.date DESC, ms.id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format data for frontend
    $data = [];
    foreach ($sales as $row) {
        $buyer = "";
        if ($row['buyer_type'] === 'customer') {
            $buyer = $row['customer_name'] ?? 'N/A';
        } elseif ($row['buyer_type'] === 'company') {
            $buyer = $row['company_name'] ?? 'N/A';
        } else {
            $buyer = $row['outsider_name'] ?? 'N/A';
        }

        $row['buyer_name'] = $buyer;
        $data[] = $row;
    }

    echo json_encode(['data' => $data]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
