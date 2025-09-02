<?php
session_start();
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if (!hasPermission('view_purchase')) {
    echo json_encode(['success' => false, 'message' => 'ڕێگەت پێنەدراوە!']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $month_year = $_POST['month_year'] ?? '';
    $user_id = $_SESSION['user_id'];
    
    // Validate month_year format (YYYY-MM)
    if (!preg_match('/^\d{4}-\d{2}$/', $month_year)) {
        echo json_encode(['success' => false, 'message' => 'فۆرماتی مانگ نادروستە! پێویستە بە شێوەی YYYY-MM بێت']);
        exit;
    }
    
    try {
        // Check if already recorded for this month
        $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM monthly_material_stock WHERE month_year = ?");
        $check_stmt->execute([$month_year]);
        $existing_count = $check_stmt->fetchColumn();
        
        if ($existing_count > 0) {
            echo json_encode(['success' => false, 'message' => 'بڕی مەوادەکان بۆ ئەم مانگە پێشتر تۆمارکراوە!']);
            exit;
        }
        
        // Call the stored procedure to record monthly stock
        $stmt = $pdo->prepare("CALL RecordMonthlyMaterialStock(?, ?)");
        $stmt->execute([$month_year, $user_id]);
        
        // Get the result message
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Create notification
        createDetailedNotification(
            $pdo,
            $user_id,
            'insert',
            'monthly_material_stock',
            0, // No specific record ID for this bulk operation
            "بڕی مەوادەکان بۆ مانگی $month_year تۆمارکرا",
            null,
            ['month_year' => $month_year, 'action' => 'monthly_stock_record'],
            ['action_type' => 'monthly_stock_recording'],
            getUserIP()
        );
        
        echo json_encode([
            'success' => true,
            'message' => 'بڕی مەوادەکان بە سەرکەوتوویی تۆمارکرا بۆ مانگی ' . $month_year
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'هەڵەیەک ڕویدا: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?>
