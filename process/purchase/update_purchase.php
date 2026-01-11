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

    // Validate required fields
    $missing_fields = [];
    
    if (!$id) $missing_fields[] = 'id';
    if (!$company_id) $missing_fields[] = 'company_id';
    if (!$driver_id) $missing_fields[] = 'driver_id';
    if (!$location_id) $missing_fields[] = 'location_id';
    if (!$invoice_number) $missing_fields[] = 'invoice_number';
    if (!$material_id) $missing_fields[] = 'material_id';
    if (!$date) $missing_fields[] = 'date';
    if (!$type) $missing_fields[] = 'type';
    if (!$kg) $missing_fields[] = 'kg';
    if (!$exchange_rate) $missing_fields[] = 'exchange_rate';
    if ($price === null || $price === '') $missing_fields[] = 'price';
    if (!$payment_type) $missing_fields[] = 'payment_type';
    
    // Validate price_per_kg based on type
    if ($type === 'دینار' && (!$price_per_kg_iqd || $price_per_kg_iqd <= 0)) {
        $missing_fields[] = 'price_per_kg_iqd';
    }
    if ($type === 'دۆلار' && (!$price_per_kg_usd || $price_per_kg_usd <= 0)) {
        $missing_fields[] = 'price_per_kg_usd';
    }
    
    if (!empty($missing_fields)) {
        error_log('Missing required fields for purchase update: ' . implode(', ', $missing_fields));
        echo json_encode(['success' => false, 'msg' => 'هەموو خانەکان پڕ بکە: ' . implode(', ', $missing_fields)]);
        exit;
    }

    // Validate numeric fields
    if (!is_numeric($kg) || $kg <= 0) {
        error_log('Invalid kg value: ' . $kg);
        echo json_encode(['success' => false, 'msg' => 'بڕی کیلۆگرام نابێت منفی بێت']);
        exit;
    }

    if (!is_numeric($price) || $price < 0) {
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
    if ($payment_type === 'نەقد' && ($remaining_usd !== 0.0 || $remaining_iqd !== 0.0)) {
        error_log('Invalid payment type: cash payment with remaining amount');
        echo json_encode(['success' => false, 'msg' => 'بڕی پارەی ماوە نابێت بێت کاتێک جۆری پارەدان نەقدە!']);
        exit;
    }

    // Check for duplicate invoice_number for the same company except current record
    $dup_stmt = $pdo->prepare("SELECT COUNT(*) FROM purchases WHERE invoice_number = ? AND company_id = ? AND id != ?");
    $dup_stmt->execute([$invoice_number, $company_id, $id]);
    if ($dup_stmt->fetchColumn() > 0) {
        error_log('Duplicate invoice number for company: ' . $invoice_number . ', company_id: ' . $company_id);
        echo json_encode(['success' => false, 'msg' => 'ژمارەی پسوڵە بۆ ئەم کۆمپانیا دووبارەیە!']);
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

        // Get old values BEFORE updating
        $stmt = $pdo->prepare("SELECT * FROM purchases WHERE id = ?");
        $stmt->execute([$id]);
        $old_record = $stmt->fetch();

        // Get old company and material information
        $old_company_name = 'Unknown';
        $old_material_name = 'Unknown';

        if ($old_record['company_id']) {
            $stmt = $pdo->prepare("SELECT name FROM company WHERE id = ?");
            $stmt->execute([$old_record['company_id']]);
            $old_company = $stmt->fetch();
            $old_company_name = $old_company['name'] ?? 'Unknown';
        }

        if ($old_record['material_id']) {
            $stmt = $pdo->prepare("SELECT name FROM materials WHERE id = ?");
            $stmt->execute([$old_record['material_id']]);
            $old_material = $stmt->fetch();
            $old_material_name = $old_material['name'] ?? 'Unknown';
        }

        $old_values = [
            'company_id' => $old_record['company_id'],
            'company_name' => $old_company_name,
            'driver' => $old_record['driver'],
            'location' => $old_record['location'],
            'material_id' => $old_record['material_id'],
            'material_name' => $old_material_name,
            'amount_iqd' => $old_record['amount_iqd'],
            'kg' => $old_record['kg'],
            'price' => $old_record['price'],
            'payment_type' => $old_record['payment_type'],
            'exchange_rate' => $old_record['exchange_rate'],
            'type' => $old_record['type'],
            'paid_usd' => $old_record['paid_usd'],
            'paid_iqd' => $old_record['paid_iqd'],
            'remaining_usd' => $old_record['remaining_usd'],
            'remaining_iqd' => $old_record['remaining_iqd'],
            'bin_id' => $old_record['bin_id'],
            'price_per_kg_iqd' => $old_record['price_per_kg_iqd'],
            'price_per_kg_usd' => $old_record['price_per_kg_usd'],
            'invoice_number' => $old_record['invoice_number'],
            'date' => $old_record['date']
        ];

        // Now perform the update
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
            
            // Get company and material information for notification
            $stmt = $pdo->prepare("SELECT name FROM company WHERE id = ?");
            $stmt->execute([$company_id]);
            $company = $stmt->fetch();
            $company_name = $company['name'] ?? 'Unknown';

            $stmt = $pdo->prepare("SELECT name FROM materials WHERE id = ?");
            $stmt->execute([$material_id]);
            $material = $stmt->fetch();
            $material_name = $material['name'] ?? 'Unknown';

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
                'action_type' => 'purchase_update',
                'payment_status' => $payment_type === 'نەقد' ? 'paid' : 'credit',
                'currency_used' => $paid_usd > 0 ? 'USD' : ($paid_iqd > 0 ? 'IQD' : 'none'),
                'total_paid' => $paid_usd + $paid_iqd,
                'remaining_debt' => $remaining_usd + $remaining_iqd
            ];

            createDetailedNotification(
                $pdo,
                $_SESSION['user_id'],
                'update',
                'purchases',
                $id,
                "کڕینەکە نوێکرایەوە (invoice: $invoice_number, کۆمپانیا: $company_name, مادە: $material_name)",
                $old_values,
                $new_values,
                $additional_info,
                getUserIP()
            );

            error_log('Purchase successfully updated: ID=' . $id . ', Invoice=' . $invoice_number . ', Company=' . $company_name);
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
