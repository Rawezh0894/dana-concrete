<?php
session_start();
require_once '../../config/db_conected.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$person_id = isset($_GET['person_id']) ? intval($_GET['person_id']) : 0;

if (!$person_id) {
    echo json_encode(['success' => false, 'error' => 'Person ID is required']);
    exit;
}

try {
    // Get person's opening debt
    $stmt = $pdo->prepare("SELECT opening_debt_usd, opening_debt_iqd FROM other_expense_persons WHERE id = ?");
    $stmt->execute([$person_id]);
    $person = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$person) {
        echo json_encode(['success' => false, 'error' => 'Person not found']);
        exit;
    }
    
    // Get date filters
    $dateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : null;
    $dateTo = isset($_GET['date_to']) ? $_GET['date_to'] : null;
    
    // Build WHERE clause for date filtering
    $dateWhere = "WHERE person_id = ?";
    $params = [$person_id];
    
    if ($dateFrom && $dateTo) {
        $dateWhere .= " AND date BETWEEN ? AND ?";
        $params[] = $dateFrom;
        $params[] = $dateTo;
    } elseif ($dateFrom) {
        $dateWhere .= " AND date >= ?";
        $params[] = $dateFrom;
    } elseif ($dateTo) {
        $dateWhere .= " AND date <= ?";
        $params[] = $dateTo;
    }
    
    // Get total expenses from other_expenses table
    $stmt = $pdo->prepare("
        SELECT 
            SUM(amount_usd) as total_expense_usd,
            SUM(amount_iqd) as total_expense_iqd,
            COUNT(*) as expense_count
        FROM other_expenses 
        $dateWhere
    ");
    $stmt->execute($params);
    $expenses = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get remaining amounts from other_expenses table
    $stmt = $pdo->prepare("
        SELECT 
            SUM(remaining_usd) as total_remaining_usd,
            SUM(remaining_iqd) as total_remaining_iqd
        FROM other_expenses 
        $dateWhere
    ");
    $stmt->execute($params);
    $remaining_expenses = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Build WHERE clause for purchase materials date filtering
    $purchaseDateWhere = "WHERE person_id = ?";
    $purchaseParams = [$person_id];
    
    if ($dateFrom && $dateTo) {
        $purchaseDateWhere .= " AND purchase_date BETWEEN ? AND ?";
        $purchaseParams[] = $dateFrom;
        $purchaseParams[] = $dateTo;
    } elseif ($dateFrom) {
        $purchaseDateWhere .= " AND purchase_date >= ?";
        $purchaseParams[] = $dateFrom;
    } elseif ($dateTo) {
        $purchaseDateWhere .= " AND purchase_date <= ?";
        $purchaseParams[] = $dateTo;
    }
    
    // Get remaining amounts from purchase_materials table - Use remaining_amount field (same as get_debt_totals.php)
    $stmt = $pdo->prepare("
        SELECT 
            SUM(remaining_amount_usd) as total_remaining_usd_purchase,
            SUM(remaining_amount_iqd) as total_remaining_iqd_purchase
        FROM purchase_materials 
        WHERE person_id = ? AND payment_type = 'قەرز'
    ");
    $stmt->execute([$person_id]);
    $remaining_purchase = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get adjustments
    $stmt = $pdo->prepare("
        SELECT 
            SUM(amount_usd) as total_adj_usd,
            SUM(amount_iqd) as total_adj_iqd
        FROM person_other_expenses_adjustments
        WHERE person_id = ?
    ");
    $stmt->execute([$person_id]);
    $adjustments = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Calculate our debt (opening debt + adjustments + remaining amounts)
    $our_debt_usd = ($person['opening_debt_usd'] ?? 0) + 
                   ($adjustments['total_adj_usd'] ?? 0) +
                   ($remaining_expenses['total_remaining_usd'] ?? 0) + 
                   max(0, ($remaining_purchase['total_remaining_usd_purchase'] ?? 0));
    
    $our_debt_iqd = ($person['opening_debt_iqd'] ?? 0) + 
                   ($adjustments['total_adj_iqd'] ?? 0) +
                   ($remaining_expenses['total_remaining_iqd'] ?? 0) + 
                   max(0, ($remaining_purchase['total_remaining_iqd_purchase'] ?? 0));
    
    $responseData = [
        'total_expense_usd' => (float)($expenses['total_expense_usd'] ?? 0),
        'total_expense_iqd' => (float)($expenses['total_expense_iqd'] ?? 0),
        'expense_count' => (int)($expenses['expense_count'] ?? 0),
        'our_debt_usd' => (float)$our_debt_usd,
        'our_debt_iqd' => (float)$our_debt_iqd,
        'opening_debt_usd' => (float)($person['opening_debt_usd'] ?? 0),
        'opening_debt_iqd' => (float)($person['opening_debt_iqd'] ?? 0),
        'adjustment_usd' => (float)($adjustments['total_adj_usd'] ?? 0),
        'adjustment_iqd' => (float)($adjustments['total_adj_iqd'] ?? 0),
        'remaining_expenses_usd' => (float)($remaining_expenses['total_remaining_usd'] ?? 0),
        'remaining_expenses_iqd' => (float)($remaining_expenses['total_remaining_iqd'] ?? 0),
        'remaining_purchase_usd' => max(0, (float)($remaining_purchase['total_remaining_usd_purchase'] ?? 0)),
        'remaining_purchase_iqd' => max(0, (float)($remaining_purchase['total_remaining_iqd_purchase'] ?? 0))
    ];
    
    echo json_encode([
        'success' => true,
        'data' => $responseData
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'General error: ' . $e->getMessage()
    ]);
}
?> 