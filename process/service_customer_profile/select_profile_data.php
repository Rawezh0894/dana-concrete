<?php
session_start();
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'تکایە بەژمێرەوە!']);
    exit;
}

if (!isset($_GET['customer_id'])) {
    echo json_encode(['success' => false, 'message' => 'customer_id is required']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

try {
    $customerId = (int)$_GET['customer_id'];
    
    // 1. Get Service Receipts
    $sql_receipts = "SELECT 
                        *,
                        (meter_amount * price_per_meter) as total_price,
                        (COALESCE(paid_usd, 0) + (COALESCE(paid_iqd, 0) / NULLIF(exchange_rate, 0))) as total_paid
                    FROM service_receipts 
                    WHERE customer_id = ? 
                    ORDER BY created_at DESC";
    $stmt1 = $pdo->prepare($sql_receipts);
    $stmt1->execute([$customerId]);
    $receipts = $stmt1->fetchAll(PDO::FETCH_ASSOC);

    // 2. Get Service Debt Payments
    $sql_payments = "SELECT * FROM service_debt_payments WHERE customer_id = ? ORDER BY payment_date DESC, id DESC";
    $stmt2 = $pdo->prepare($sql_payments);
    $stmt2->execute([$customerId]);
    $debt_payments = $stmt2->fetchAll(PDO::FETCH_ASSOC);

    // Calculate overall stats
    $totalRevenue = 0;
    $totalPaidDirectly = 0;
    foreach ($receipts as $r) {
        $totalRevenue += (float)$r['total_price'];
        $totalPaidDirectly += (float)$r['total_paid'];
    }

    $totalDebtPayments = 0;
    foreach ($debt_payments as $p) {
        $totalDebtPayments += (float)$p['paid_usd'] + ((float)$p['paid_iqd'] / (float)$p['exchange_rate']);
    }

    $totalPaidTotal = $totalPaidDirectly + $totalDebtPayments;
    $finalBalance = $totalRevenue - $totalPaidTotal;

    echo json_encode([
        'success' => true, 
        'receipts' => $receipts,
        'debt_payments' => $debt_payments,
        'summary' => [
            'total_revenue' => $totalRevenue,
            'total_paid_direct' => $totalPaidDirectly,
            'total_debt_payments' => $totalDebtPayments,
            'total_paid' => $totalPaidTotal,
            'balance' => $finalBalance
        ]
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
