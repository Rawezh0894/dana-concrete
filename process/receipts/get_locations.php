<?php
require_once '../../config/db_conected.php';
header('Content-Type: application/json; charset=utf-8');

$customer_id = isset($_GET['customer_id']) ? intval($_GET['customer_id']) : 0;
if (!$customer_id) { 
    echo json_encode(['locations' => []]); 
    exit; 
}

try {
    // Get unique locations for this customer
    $sql = "SELECT DISTINCT s.location 
            FROM sales s 
            WHERE s.customer_id = :customer_id 
            AND s.location IS NOT NULL 
            AND s.location != '' 
            ORDER BY s.location ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['customer_id' => $customer_id]);
    
    $locations = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $locations[] = $row['location'];
    }
    
    echo json_encode(['locations' => $locations], JSON_UNESCAPED_UNICODE);
    
} catch (PDOException $e) {
    error_log('PDOException in get_locations.php: ' . $e->getMessage());
    echo json_encode(['locations' => []]);
} catch (Exception $e) {
    error_log('Exception in get_locations.php: ' . $e->getMessage());
    echo json_encode(['locations' => []]);
}
?>
