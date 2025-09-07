<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once '../../config/db_conected.php';
header('Content-Type: application/json; charset=utf-8');

$customer_id = isset($_GET['customer_id']) ? intval($_GET['customer_id']) : 0;
if (!$customer_id) { 
    echo json_encode(['locations' => []]); 
    exit; 
}

try {
    // Get unique locations for this customer from sales table
    $sql = "SELECT DISTINCT location FROM sales WHERE customer_id = :customer_id AND location IS NOT NULL AND location != '' ORDER BY location ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['customer_id' => $customer_id]);
    
    $locations = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $locations[] = $row['location'];
    }
    
    echo json_encode(['locations' => $locations], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log("Error getting locations: " . $e->getMessage());
    echo json_encode(['locations' => [], 'error' => 'هەڵە لە بارکردنی شوێنەکان'], JSON_UNESCAPED_UNICODE);
}
?>
