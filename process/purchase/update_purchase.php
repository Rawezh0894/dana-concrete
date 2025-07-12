<?php
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);

session_start();
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';
header('Content-Type: application/json');
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'msg' => 'Unauthorized']);
    exit;
}
if (!hasPermission('edit_purchase')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'msg' => 'ڕێگەپێدراوە بۆ نوێکردنەوەی کڕین']);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
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
        'id' => $id,
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
    if ($payment_type === 'نەقد' && ($remaining_usd != 0 || $remaining_iqd != 0)) {
        echo json_encode(['success' => false, 'msg' => 'بڕی پارەی ماوە نابێت بێت کاتێک جۆری پارەدان نەقدە!']);
        exit;
    }
    // Check for duplicate invoice_number for all companies except current record
    $dup_stmt = $pdo->prepare("SELECT COUNT(*) FROM purchases WHERE invoice_number = ? AND id != ?");
    $dup_stmt->execute([$invoice_number, $id]);
    if ($dup_stmt->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'msg' => 'ژمارەی پسوڵە دووبارەیە!']);
        exit;
    }
    // Get driver and location names
    $driver_stmt = $pdo->prepare("SELECT name FROM drivers WHERE id = ?");
    $driver_stmt->execute([$driver_id]);
    $driver = $driver_stmt->fetchColumn();
    $location_stmt = $pdo->prepare("SELECT name FROM locations WHERE id = ?");
    $location_stmt->execute([$location_id]);
    $location = $location_stmt->fetchColumn();
    $stmt = $pdo->prepare("UPDATE purchases SET date=?, invoice_number=?, driver=?, location=?, material_id=?, amount_iqd=?, kg=?, price=?, payment_type=?, exchange_rate=?, company_id=?, type=?, paid_usd=?, paid_iqd=?, remaining_usd=?, remaining_iqd=?, bin_id=?, price_per_kg_iqd=?, price_per_kg_usd=? WHERE id=?");
    $ok = $stmt->execute([
        $date, $invoice_number, $driver, $location, $material_id, $amount_iqd, $kg, $price, $payment_type, $exchange_rate, $company_id, $type, $paid_usd, $paid_iqd, $remaining_usd, $remaining_iqd, $bin_id, $price_per_kg_iqd, $price_per_kg_usd, $id
    ]);
    if ($ok) {
        // Update company debt only if payment_type is 'قەرز'
        if ($payment_type === 'قەرز') {
            if ($type === 'دۆلار') {
                $updateDebt = $pdo->prepare('UPDATE company SET debt_usd = debt_usd + ? WHERE id = ?');
                $updateDebt->execute([$remaining_usd, $company_id]);
            } elseif ($type === 'دینار') {
                $updateDebt = $pdo->prepare('UPDATE company SET debt_iqd = debt_iqd + ? WHERE id = ?');
                $updateDebt->execute([$remaining_iqd, $company_id]);
            }
        }
        echo json_encode(['success' => true]);
    } else {
        error_log('DB Update Error: ' . print_r($stmt->errorInfo(), true));
        echo json_encode(['success' => false, 'msg' => 'هەڵە لە نوێکردنەوە']);
    }
}
