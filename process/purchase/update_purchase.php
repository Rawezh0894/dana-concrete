<?php
session_start();
// Only log errors, don't display them in JSON response
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../php-error.log');

require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

// Log session and POST data for debugging
error_log('SESSION: ' . print_r($_SESSION, true));
error_log('update_purchase.php POST: ' . print_r($_POST, true));

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    error_log('User not logged in for purchase update');
    http_response_code(403);
    echo json_encode(['success' => false, 'msg' => 'Unauthorized']);
    exit;
}

if (!hasPermission('edit_purchase')) {
    error_log('Permission denied for user: ' . $_SESSION['user_id'] . ' to edit purchase');
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

    // Log parsed variables for debugging
    error_log("Parsed vars: id='$id', company_id='$company_id', driver_id='$driver_id', location_id='$location_id', invoice_number='$invoice_number', bin_id='$bin_id', material_id='$material_id', date='$date', type='$type', kg='$kg', price_per_kg_iqd='$price_per_kg_iqd', price_per_kg_usd='$price_per_kg_usd', exchange_rate='$exchange_rate', price='$price', paid_iqd='$paid_iqd', paid_usd='$paid_usd', remaining_iqd='$remaining_iqd', remaining_usd='$remaining_usd', payment_type='$payment_type', amount_iqd='$amount_iqd'");

    // Validate required fields (accept 0 as valid)
    if (!$id || !$company_id || !$driver_id || !$location_id || !$invoice_number || !$bin_id || !$material_id || !$date || !$type || !$kg || !$exchange_rate || !$price || !$payment_type) {
        error_log('Missing required fields for purchase update');
        echo json_encode(['success' => false, 'msg' => 'هەموو خانە پڕ بکە']);
        exit;
    }

    // Validate numeric fields
    if (!is_numeric($kg) || $kg <= 0) {
        error_log('Invalid kg value: ' . $kg);
        echo json_encode(['success' => false, 'msg' => 'بڕی کیلۆگرام نابێت منفی بێت']);
        exit;
    }

    if (!is_numeric($price) || $price <= 0) {
        error_log('Invalid price value: ' . $price);
        echo json_encode(['success' => false, 'msg' => 'نرخ نابێت منفی بێت']);
        exit;
    }

    if (!is_numeric($exchange_rate) || $exchange_rate <= 0) {
        error_log('Invalid exchange rate: ' . $exchange_rate);
        echo json_encode(['success' => false, 'msg' => 'نرخی گۆڕانکاری نابێت منفی بێت']);
        exit;
    }

    // Validate payment type logic
    if ($payment_type === 'نەقد' && ($remaining_usd != 0 || $remaining_iqd != 0)) {
        error_log('Invalid payment type: cash payment with remaining amount');
        echo json_encode(['success' => false, 'msg' => 'بڕی پارەی ماوە نابێت بێت کاتێک جۆری پارەدان نەقدە!']);
        exit;
    }

    // Check for duplicate invoice_number for all companies except current record
    $dup_stmt = $pdo->prepare("SELECT COUNT(*) FROM purchases WHERE invoice_number = ? AND id != ?");
    $dup_stmt->execute([$invoice_number, $id]);
    if ($dup_stmt->fetchColumn() > 0) {
        error_log('Duplicate invoice number: ' . $invoice_number);
        echo json_encode(['success' => false, 'msg' => 'ژمارەی پسوڵە دووبارەیە!']);
        exit;
    }

    try {
        // Check if purchase exists
        $checkStmt = $pdo->prepare('SELECT id, invoice_number FROM purchases WHERE id = ?');
        $checkStmt->execute([$id]);
        $purchase = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$purchase) {
            error_log('Purchase not found for update: ID=' . $id);
            echo json_encode(['success' => false, 'msg' => 'کڕین نەدۆزرایەوە']);
            exit;
        }
        
        error_log('Found purchase for update: ' . print_r($purchase, true));

        // Get driver and location names
        $driver_stmt = $pdo->prepare("SELECT name FROM drivers WHERE id = ?");
        $driver_stmt->execute([$driver_id]);
        $driver = $driver_stmt->fetchColumn();
        
        if (!$driver) {
            error_log('Driver not found: ID=' . $driver_id);
            echo json_encode(['success' => false, 'msg' => 'شۆفێر نەدۆزرایەوە']);
            exit;
        }
        
        $location_stmt = $pdo->prepare("SELECT name FROM locations WHERE id = ?");
        $location_stmt->execute([$location_id]);
        $location = $location_stmt->fetchColumn();
        
        if (!$location) {
            error_log('Location not found: ID=' . $location_id);
            echo json_encode(['success' => false, 'msg' => 'شوێن نەدۆزرایەوە']);
            exit;
        }

        $stmt = $pdo->prepare("UPDATE purchases SET date=?, invoice_number=?, driver=?, location=?, material_id=?, amount_iqd=?, kg=?, price=?, payment_type=?, exchange_rate=?, company_id=?, type=?, paid_usd=?, paid_iqd=?, remaining_usd=?, remaining_iqd=?, bin_id=?, price_per_kg_iqd=?, price_per_kg_usd=? WHERE id=?");
        $result = $stmt->execute([
            $date, $invoice_number, $driver, $location, $material_id, $amount_iqd, $kg, $price, $payment_type, $exchange_rate, $company_id, $type, $paid_usd, $paid_iqd, $remaining_usd, $remaining_iqd, $bin_id, $price_per_kg_iqd, $price_per_kg_usd, $id
        ]);

        if ($result && $stmt->rowCount() > 0) {
            // Update company debt logic (simplified)
            if ($purchase['payment_type'] === 'قەرز') {
                // No need to update company debt_usd/debt_iqd anymore
                // The remaining amount is tracked in the purchases table itself
                // This is handled by the remaining_amount field in the purchases table
            }
            
            require_once __DIR__ . '/../../includes/notify.php';
            notify('update', 'purchases', $id, 'کڕینەکە نوێکرایەوە (invoice: ' . $invoice_number . ')');
            error_log('Purchase successfully updated: ID=' . $id . ', Invoice=' . $invoice_number);
            echo json_encode(['success' => true, 'msg' => 'کڕین نوێکرایەوە']);
        } else {
            error_log('No rows affected when updating purchase: ID=' . $id);
            echo json_encode(['success' => false, 'msg' => 'هیچ گۆڕانکارییەک نەکرا']);
        }
    } catch (PDOException $e) {
        error_log('PDOException in update_purchase.php: ' . $e->getMessage());
        echo json_encode(['success' => false, 'msg' => 'هەڵەی داتابەیس: ' . $e->getMessage()]);
    } catch (Exception $e) {
        error_log('Exception in update_purchase.php: ' . $e->getMessage());
        echo json_encode(['success' => false, 'msg' => 'هەڵەی سیستەم: ' . $e->getMessage()]);
    }
} else {
    error_log('Invalid request method: ' . $_SERVER['REQUEST_METHOD']);
    echo json_encode(['success' => false, 'msg' => 'داواکاری نادروست']);
}
