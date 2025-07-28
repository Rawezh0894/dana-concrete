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
    if (!isset($_POST['receipt_ids']) || !isset($_POST['price_per_meter'])) {
        error_log('Missing required fields for price update');
        echo json_encode(['success' => false, 'error' => 'Missing required fields']);
        exit;
    }
    
    $receipt_ids = $_POST['receipt_ids'];
    $price_per_meter = floatval($_POST['price_per_meter']);
    $notes = $_POST['notes'] ?? '';
    
    // Log parsed variables for debugging
    error_log("Parsed vars: receipt_ids='" . print_r($receipt_ids, true) . "', price_per_meter='$price_per_meter', notes='$notes'");
    
    // Validate receipt_ids
    if (!is_array($receipt_ids) || empty($receipt_ids)) {
        error_log('Invalid receipt_ids: not an array or empty');
        echo json_encode(['success' => false, 'error' => 'Invalid receipt IDs']);
        exit;
    }
    
    // Validate price_per_meter
    if ($price_per_meter <= 0) {
        error_log('Invalid price_per_meter: ' . $price_per_meter);
        echo json_encode(['success' => false, 'error' => 'Price per meter must be greater than 0']);
        exit;
    }
    
    // Prepare the update statement
    $stmt = $pdo->prepare("
        UPDATE concrete_receipts 
        SET price_per_meter = ?, notes = ?, updated_at = NOW() 
        WHERE id = ?
    ");
    
    $success_count = 0;
    $error_count = 0;
    
    // Update each receipt
    foreach ($receipt_ids as $receipt_id) {
        if (is_numeric($receipt_id)) {
            try {
                $result = $stmt->execute([$price_per_meter, $notes, $receipt_id]);
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