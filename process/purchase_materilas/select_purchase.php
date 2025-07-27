<?php
session_start();
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

// Check permission
if (!hasPermission('view_materials')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Permission denied']);
    exit;
}

// Check if it's a GET request
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    // Build query with filters
    $where_conditions = [];
    $params = [];
    
    // Date filter
    if (!empty($_GET['filter_from'])) {
        $where_conditions[] = "pm.purchase_date >= ?";
        $params[] = $_GET['filter_from'];
    }
    
    if (!empty($_GET['filter_to'])) {
        $where_conditions[] = "pm.purchase_date <= ?";
        $params[] = $_GET['filter_to'];
    }
    
    $where_clause = '';
    if (!empty($where_conditions)) {
        $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
    }
    
    // Main query to get purchase summaries
    $query = "
        SELECT 
            ANY_VALUE(pm.id) as id,
            pm.receipt_number,
            ANY_VALUE(pm.purchase_date) as purchase_date,
            ANY_VALUE(pm.currency_type) as currency_type,
            ANY_VALUE(pm.notes) as notes,
            ANY_VALUE(pm.transfer_loss) as transfer_loss,
            ANY_VALUE(pm.other_loss) as other_loss,
            ANY_VALUE(pm.usd_to_iqd_rate) as usd_to_iqd_rate,
            ANY_VALUE(oep.name) as person_name,
            COUNT(pm.material_id) as materials_count,
            SUM(pm.total_price_usd) as total_usd,
            SUM(pm.total_price_iqd) as total_iqd
        FROM purchase_materials pm
        LEFT JOIN other_expense_persons oep ON pm.person_id = oep.id
        $where_clause
        GROUP BY pm.receipt_number
        ORDER BY purchase_date DESC, id DESC
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $purchases = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $purchases
    ]);
    
} catch (Exception $e) {
    error_log("Error in select_purchase.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
