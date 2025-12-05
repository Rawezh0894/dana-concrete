<?php
session_start();
require_once '../../config/db_conected.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$person_id = isset($_GET['person_id']) ? intval($_GET['person_id']) : 0;
$currency = isset($_GET['currency']) ? $_GET['currency'] : 'usd'; // 'usd' or 'iqd'

if (!$person_id) {
    echo json_encode(['success' => false, 'error' => 'Person ID is required']);
    exit;
}

if (!in_array($currency, ['usd', 'iqd'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid currency']);
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
    
    $opening_debt = $currency === 'usd' ? (float)($person['opening_debt_usd'] ?? 0) : (float)($person['opening_debt_iqd'] ?? 0);
    
    // Get expenses with remaining amounts (only those with remaining > 0)
    $remaining_field = $currency === 'usd' ? 'remaining_usd' : 'remaining_iqd';
    $stmt = $pdo->prepare("
        SELECT
            id,
            purpose,
            date,
            amount_usd,
            amount_iqd,
            remaining_usd,
            remaining_iqd,
            invoice_number,
            expense_type,
            payment_type
        FROM other_expenses
        WHERE person_id = ? AND payment_type = 'قەرز' AND $remaining_field > 0
        ORDER BY date DESC, id DESC
    ");
    $stmt->execute([$person_id]);
    $expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format expenses - json_encode will handle proper encoding for JSON output
    $formatted_expenses = [];
    foreach ($expenses as $expense) {
        $remaining = $currency === 'usd' ? (float)$expense['remaining_usd'] : (float)$expense['remaining_iqd'];
        if ($remaining > 0) {
            $formatted_expenses[] = [
                'id' => (int)$expense['id'],
                'type' => 'expense',
                'description' => $expense['purpose'] ?? '',
                'date' => $expense['date'] ?? '',
                'amount_usd' => (float)$expense['amount_usd'],
                'amount_iqd' => (float)$expense['amount_iqd'],
                'remaining_usd' => (float)$expense['remaining_usd'],
                'remaining_iqd' => (float)$expense['remaining_iqd'],
                'remaining' => $remaining,
                'invoice_number' => $expense['invoice_number'] ?? '',
                'expense_type' => $expense['expense_type'] ?? '',
                'payment_type' => $expense['payment_type'] ?? ''
            ];
        }
    }
    
    // Get purchases with remaining amounts (only those with remaining > 0)
    $remaining_field_purchase = $currency === 'usd' ? 'remaining_amount_usd' : 'remaining_amount_iqd';
    $stmt = $pdo->prepare("
        SELECT
            pm.receipt_number,
            pm.purchase_date,
            pm.currency_type,
            pm.payment_type,
            pm.notes,
            SUM(pm.total_price_usd) as total_price_usd,
            SUM(pm.total_price_iqd) as total_price_iqd,
            SUM(pm.paid_amount_usd) as paid_amount_usd,
            SUM(pm.paid_amount_iqd) as paid_amount_iqd,
            SUM(pm.remaining_amount_usd) as remaining_amount_usd,
            SUM(pm.remaining_amount_iqd) as remaining_amount_iqd,
            COUNT(pm.id) as materials_count
        FROM purchase_materials pm
        WHERE pm.person_id = ? AND pm.payment_type = 'قەرز' AND pm.$remaining_field_purchase > 0
        GROUP BY pm.receipt_number, pm.purchase_date, pm.currency_type, pm.payment_type, pm.notes
        ORDER BY pm.purchase_date DESC
    ");
    $stmt->execute([$person_id]);
    $purchases = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format purchases - json_encode will handle proper encoding for JSON output
    $formatted_purchases = [];
    foreach ($purchases as $purchase) {
        $remaining = $currency === 'usd' ? max(0, (float)$purchase['remaining_amount_usd']) : max(0, (float)$purchase['remaining_amount_iqd']);
        if ($remaining > 0) {
            $formatted_purchases[] = [
                'type' => 'purchase',
                'receipt_number' => $purchase['receipt_number'] ?? '',
                'date' => $purchase['purchase_date'] ?? '',
                'total_price_usd' => (float)$purchase['total_price_usd'],
                'total_price_iqd' => (float)$purchase['total_price_iqd'],
                'paid_amount_usd' => (float)$purchase['paid_amount_usd'],
                'paid_amount_iqd' => (float)$purchase['paid_amount_iqd'],
                'remaining_usd' => max(0, (float)$purchase['remaining_amount_usd']),
                'remaining_iqd' => max(0, (float)$purchase['remaining_amount_iqd']),
                'remaining' => $remaining,
                'notes' => $purchase['notes'] ?? '',
                'materials_count' => (int)$purchase['materials_count'],
                'currency_type' => $purchase['currency_type'] ?? ''
            ];
        }
    }
    
    // Calculate totals
    $total_expenses_remaining = array_sum(array_column($formatted_expenses, 'remaining'));
    $total_purchases_remaining = array_sum(array_column($formatted_purchases, 'remaining'));
    $total_debt = $opening_debt + $total_expenses_remaining + $total_purchases_remaining;
    
    $responseData = [
        'currency' => $currency,
        'opening_debt' => $opening_debt,
        'expenses' => $formatted_expenses,
        'purchases' => $formatted_purchases,
        'totals' => [
            'opening_debt' => $opening_debt,
            'expenses_remaining' => $total_expenses_remaining,
            'purchases_remaining' => $total_purchases_remaining,
            'total_debt' => $total_debt
        ]
    ];
    
    // json_encode properly encodes all data for safe JSON output
    $jsonResponse = json_encode([
        'success' => true,
        'data' => $responseData
    ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    
    echo $jsonResponse;

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'General error: ' . $e->getMessage()
    ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
}

