<?php
session_start();
// Only log errors, don't display them in JSON response
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../php-error.log');

require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

// Log session and POST data for debugging
error_log('SESSION: ' . print_r($_SESSION, true));
error_log('update_prices.php POST: ' . print_r($_POST, true));

header('Content-Type: application/json; charset=utf-8');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    error_log('User not logged in for price update');
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

// Check if user has permission to update prices
if (!hasPermission('edit_concrete_receipts')) {
    error_log('Permission denied for user: ' . $_SESSION['user_id'] . ' to update prices');
    echo json_encode(['success' => false, 'error' => 'Permission denied']);
    exit;
}

try {
    // Validate required fields
    if (!isset($_POST['receipt_ids'])) {
        error_log('Missing receipt_ids for update');
        echo json_encode(['success' => false, 'error' => 'Missing receipt IDs']);
        exit;
    }
    
    $receipt_ids = $_POST['receipt_ids'];
    $price_per_meter_input = $_POST['price_per_meter'] ?? '';
    $notes = $_POST['notes'] ?? '';
    $payment_status = $_POST['payment_status'] ?? 'unpaid';
    
    // Validate payment status
    if (!in_array($payment_status, ['paid', 'unpaid'])) {
        $payment_status = 'unpaid';
    }
    
    // Check if this is a payment status only update
    $is_payment_status_only = empty($price_per_meter_input) && empty($notes);
    
    // Handle price validation
    if (!$is_payment_status_only) {
        if (empty($price_per_meter_input)) {
            error_log('Price per meter is required when not updating payment status only');
            echo json_encode(['success' => false, 'error' => 'Price per meter is required']);
            exit;
        }
        
        $price_per_meter = floatval($price_per_meter_input);
        if ($price_per_meter <= 0) {
            error_log('Invalid price_per_meter: ' . $price_per_meter);
            echo json_encode(['success' => false, 'error' => 'Price per meter must be greater than 0']);
            exit;
        }
    } else {
        $price_per_meter = null; // Will not update price field
    }
    
    // Log parsed variables for debugging
    error_log("Parsed vars: receipt_ids='" . print_r($receipt_ids, true) . "', price_per_meter='$price_per_meter', notes='$notes', payment_status='$payment_status', is_payment_status_only='$is_payment_status_only'");
    
    // Validate receipt_ids
    if (!is_array($receipt_ids) || empty($receipt_ids)) {
        error_log('Invalid receipt_ids: not an array or empty');
        echo json_encode(['success' => false, 'error' => 'Invalid receipt IDs']);
        exit;
    }
    
    // Prepare the update statement based on what's being updated
    if ($is_payment_status_only) {
        // Only update payment status
        $stmt = $pdo->prepare("
            UPDATE concrete_receipts 
            SET payment_status = ?, updated_at = NOW() 
            WHERE id = ?
        ");
    } else {
        // Update price, notes, and payment status
        $stmt = $pdo->prepare("
            UPDATE concrete_receipts 
            SET price_per_meter = ?, notes = ?, payment_status = ?, updated_at = NOW() 
            WHERE id = ?
        ");
    }
    
    $success_count = 0;
    $error_count = 0;
    
    // Update each receipt
    foreach ($receipt_ids as $receipt_id) {
        if (is_numeric($receipt_id)) {
                            try {
            if ($is_payment_status_only) {
                $result = $stmt->execute([$payment_status, $receipt_id]);
            } else {
                $result = $stmt->execute([$price_per_meter, $notes, $payment_status, $receipt_id]);
            }
            
            if ($result) {
                $success_count++;
                error_log('Successfully updated receipt ID: ' . $receipt_id);
            } else {
                $error_count++;
                error_log('Failed to update receipt ID: ' . $receipt_id);
            }
        } catch (Exception $e) {
            $error_count++;
            error_log('Exception updating receipt ID ' . $receipt_id . ': ' . $e->getMessage());
        }
        } else {
            $error_count++;
            error_log('Invalid receipt ID (not numeric): ' . $receipt_id);
        }
    }
    
    if ($success_count > 0) {
        error_log('Price update completed: ' . $success_count . ' successful, ' . $error_count . ' failed');
        echo json_encode([
            'success' => true,
            'message' => "Updated $success_count receipt(s) successfully",
            'updated_count' => $success_count,
            'error_count' => $error_count
        ]);
    } else {
        error_log('No receipts were updated successfully');
        echo json_encode([
            'success' => false,
            'error' => 'No receipts were updated'
        ]);
    }
    
} catch (PDOException $e) {
    error_log("PDOException in update_prices.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Database error occurred'
    ]);
} catch (Exception $e) {
    error_log("Exception in update_prices.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'System error occurred'
    ]);
} 