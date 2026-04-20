<?php
require_once '../../config/db_conected.php';

header('Content-Type: application/json');

try {
    $vehicle_id = intval($_GET['vehicle_id'] ?? 0);
    
    $stmt = $pdo->prepare("SELECT SUM(qty * cost_usd_at_time) as total_cost 
                           FROM inv_issuance 
                           WHERE vehicle_id = ?");
    $stmt->execute([$vehicle_id]);
    $result = $stmt->fetch();
    
    echo json_encode(['success' => true, 'total_cost' => $result['total_cost'] ?? 0]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
}
?>
