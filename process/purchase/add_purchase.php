<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';
header('Content-Type: application/json');
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'msg' => 'Unauthorized']);
    exit;
}
if (!hasPermission('add_purchase')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'msg' => 'ڕێگەپێدراوە بۆ زیادکردنی کڕین']);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $company_id = $_POST['company_id'] ?? null;
    $driver_id = $_POST['driver_id'] ?? null;
    $location_id = $_POST['location_id'] ?? null;
    $invoice_number = $_POST['invoice_number'] ?? null;
    $bin_id = $_POST['bin_id'] ?? null;
    $material_id = $_POST['material_id'] ?? null;
    $date = $_POST['date'] ?? null;
    $type = $_POST['type'] ?? null;
    $kg = $_POST['kg'] ?? null;
    $price_per_kg_iqd = $_POST['price_per_kg_iqd'] ?? 0;
    $price_per_kg_usd = $_POST['price_per_kg_usd'] ?? 0;
    $exchange_rate = $_POST['exchange_rate'] ?? null;
    $price = $_POST['price'] ?? null;
    $paid_iqd = $_POST['paid_iqd'] ?? 0;
    $paid_usd = $_POST['paid_usd'] ?? 0;
    $remaining_iqd = $_POST['remaining_iqd'] ?? 0;
    $remaining_usd = $_POST['remaining_usd'] ?? 0;
    $payment_type = $_POST['payment_type'] ?? null;
    $amount_iqd = $_POST['amount_iqd'] ?? 0;
    // Validate required fields (accept 0 as valid)
    $fields = [
        'company_id' => $company_id,
        'driver_id' => $driver_id,
        'location_id' => $location_id,
        'invoice_number' => $invoice_number,
        'material_id' => $material_id,
        'date' => $date,
        'type' => $type,
        'payment_type' => $payment_type,
        'kg' => $kg,
        'price' => $price,
        'exchange_rate' => $exchange_rate
    ];
    foreach ($fields as $key => $value) {
        if ($value === null || $value === '') {
            echo json_encode(['success' => false, 'msg' => "خانەی $key بەتاڵە"]);
            exit;
        }
    }
    // Prevent negative values for all relevant fields
    $number_fields = [
        'kg' => $kg,
        'price_per_kg_iqd' => $price_per_kg_iqd,
        'price_per_kg_usd' => $price_per_kg_usd,
        'price' => $price,
        'amount_iqd' => $amount_iqd,
        'paid_usd' => $paid_usd,
        'paid_iqd' => $paid_iqd,
        'remaining_usd' => $remaining_usd,
        'remaining_iqd' => $remaining_iqd,
    ];
    foreach ($number_fields as $key => $value) {
        if ($value < 0) {
            echo json_encode(['success' => false, 'msg' => "بڕی $key نابێت منفی بێت!"]);
            exit;
        }
    }
    // Prevent negative values for price_per_kg_iqd and price_per_kg_usd only for their type
    if ($type === 'دینار' && $price_per_kg_iqd < 0) {
        echo json_encode(['success' => false, 'msg' => 'بڕی price_per_kg_iqd نابێت منفی بێت!']);
        exit;
    }
    if ($type === 'دۆلار' && $price_per_kg_usd < 0) {
        echo json_encode(['success' => false, 'msg' => 'بڕی price_per_kg_usd نابێت منفی بێت!']);
        exit;
    }
    // Prevent remaining_usd or remaining_iqd if payment_type is 'نەقد'
    if ($payment_type === 'نەقد' && ($remaining_usd !== 0.0 || $remaining_iqd !== 0.0)) {
        echo json_encode(['success' => false, 'msg' => 'بڕی پارەی ماوە نابێت بێت کاتێک جۆری پارەدان نەقدە!']);
        exit;
    }
    // Check for duplicate invoice_number for the same company on the same date
    $dup_stmt = $pdo->prepare("SELECT COUNT(*) FROM purchases WHERE invoice_number = ? AND company_id = ? AND date = ?");
    $dup_stmt->execute([$invoice_number, $company_id, $date]);
    if ($dup_stmt->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'msg' => 'ئەم ژمارە پسووڵەیە بۆ ئەم کۆمپانیایە لەم بەروارە تۆمارکراوە']);
        exit;
    }
    // Get driver and location names
    $driver_stmt = $pdo->prepare("SELECT name FROM drivers WHERE id = ?");
    $driver_stmt->execute([$driver_id]);
    $driver = $driver_stmt->fetchColumn();
    $location_stmt = $pdo->prepare("SELECT name FROM locations WHERE id = ?");
    $location_stmt->execute([$location_id]);
    $location = $location_stmt->fetchColumn();
    try {
        $stmt = $pdo->prepare("INSERT INTO purchases (date, invoice_number, driver, location, material_id, amount_iqd, kg, price, payment_type, exchange_rate, company_id, type, paid_usd, paid_iqd, remaining_usd, remaining_iqd, bin_id, price_per_kg_iqd, price_per_kg_usd) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $ok = $stmt->execute([
            $date, $invoice_number, $driver, $location, $material_id, $amount_iqd, $kg, $price, $payment_type, $exchange_rate, $company_id, $type, $paid_usd, $paid_iqd, $remaining_usd, $remaining_iqd, $bin_id, $price_per_kg_iqd, $price_per_kg_usd
        ]);
        if ($ok) {
            // Update company debt
            if ($payment_type === 'قەرز') {
                if ($type === 'دۆلار') {
                    // No need to update company debt_usd/debt_iqd anymore
                    // The remaining amount is tracked in the purchases table
                } else {
                    // No need to update company debt_usd/debt_iqd anymore
                    // The remaining amount is tracked in the purchases table
                }
            }
            $purchase_id = $pdo->lastInsertId();

            // Get company and material information for notification
            $stmt = $pdo->prepare("SELECT name FROM company WHERE id = ?");
            $stmt->execute([$company_id]);
            $company = $stmt->fetch();
            $company_name = $company['name'] ?? 'Unknown';

            $stmt = $pdo->prepare("SELECT name FROM materials WHERE id = ?");
            $stmt->execute([$material_id]);
            $material = $stmt->fetch();
            $material_name = $material['name'] ?? 'Unknown';

            // Create detailed notification
            $new_values = [
                'company_id' => $company_id,
                'company_name' => $company_name,
                'driver' => $driver,
                'location' => $location,
                'material_id' => $material_id,
                'material_name' => $material_name,
                'amount_iqd' => $amount_iqd,
                'kg' => $kg,
                'price' => $price,
                'payment_type' => $payment_type,
                'exchange_rate' => $exchange_rate,
                'type' => $type,
                'paid_usd' => $paid_usd,
                'paid_iqd' => $paid_iqd,
                'remaining_usd' => $remaining_usd,
                'remaining_iqd' => $remaining_iqd,
                'bin_id' => $bin_id,
                'price_per_kg_iqd' => $price_per_kg_iqd,
                'price_per_kg_usd' => $price_per_kg_usd,
                'invoice_number' => $invoice_number,
                'date' => $date
            ];

            $additional_info = [
                'action_type' => 'purchase_creation',
                'payment_status' => $payment_type === 'نەقد' ? 'paid' : 'credit',
                'currency_used' => $paid_usd > 0 ? 'USD' : ($paid_iqd > 0 ? 'IQD' : 'none'),
                'total_paid' => $paid_usd + $paid_iqd,
                'remaining_debt' => $remaining_usd + $remaining_iqd
            ];

            createDetailedNotification(
                $pdo,
                $_SESSION['user_id'],
                'insert',
                'purchases',
                $purchase_id,
                "کڕینێکی نوێ زیادکرا (invoice: $invoice_number, کۆمپانیا: $company_name, مادە: $material_name, بڕ: $kg کگم)",
                null, // No old values for insert
                $new_values,
                $additional_info,
                getUserIP()
            );

            echo json_encode(['success' => true]);
        } else {
            $errorInfo = $stmt->errorInfo();
            $msg = $errorInfo[2] ?? 'هەڵە لە زیادکردن';
            error_log('DB Insert Error: ' . print_r($errorInfo, true));
            echo json_encode(['success' => false, 'msg' => $msg]);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
        exit;
    }
}
