<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';
header('Content-Type: application/json');

// Add debug logging
error_log('add_debt.php called with POST data: ' . print_r($_POST, true));

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'msg' => 'Unauthorized']);
    exit;
}
if (!hasPermission('add_debt')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'msg' => 'ڕێگەپێدراوە بۆ دانەوەی قەرز']);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $company_id = $_POST['company_id'] ?? null;
    $date = $_POST['date'] ?? null;
    $amount_usd = floatval($_POST['amount_usd'] ?? 0);
    $amount_iqd = floatval($_POST['amount_iqd'] ?? 0);
    $dollar_rate = floatval($_POST['dollar_rate'] ?? 150000);
    $discount_usd = floatval($_POST['discount_usd'] ?? 0);
    $note = $_POST['note'] ?? '';
    $user_id = $_SESSION['user_id'];
    
    // Debug logging
    error_log("Processing debt payment - Company ID: $company_id, Date: $date, USD: $amount_usd, IQD: $amount_iqd, Rate: $dollar_rate");
    
    if (!$company_id || !$date || ($amount_usd <= 0 && $amount_iqd <= 0 && $discount_usd <= 0)) {
        echo json_encode(['success' => false, 'msg' => 'بە لایەنی کەم یەک بڕ پڕبکە (دۆلار یان دینار یان داشکاندن)']);
        exit;
    }
    
    // Check not exceeding current debt (opening + purchases remaining)
    $debt = $pdo->prepare('SELECT opening_debt_usd, opening_debt_iqd FROM company WHERE id = ?');
    $debt->execute([$company_id]);
    $row = $debt->fetch(PDO::FETCH_ASSOC);
    
    // Get remaining amounts from purchases
    $purchases_data = $pdo->prepare("
        SELECT 
            COALESCE(SUM(remaining_usd), 0) as remaining_usd,
            COALESCE(SUM(remaining_iqd), 0) as remaining_iqd
        FROM purchases 
        WHERE company_id = ? AND payment_type = 'قەرز'
    ");
    $purchases_data->execute([$company_id]);
    $purchases_result = $purchases_data->fetch(PDO::FETCH_ASSOC);
    
    $total_usd = floatval($row['opening_debt_usd']) + floatval($purchases_result['remaining_usd']);
    $total_iqd = floatval($row['opening_debt_iqd']) + floatval($purchases_result['remaining_iqd']);
    
    // Debug logging
    error_log("Debt check - Opening USD: {$row['opening_debt_usd']}, Opening IQD: {$row['opening_debt_iqd']}");
    error_log("Debt check - Purchases remaining USD: {$purchases_result['remaining_usd']}, Purchases remaining IQD: {$purchases_result['remaining_iqd']}");
    error_log("Debt check - Total USD: $total_usd, Total IQD: $total_iqd");
    error_log("Debt check - Payment USD: $amount_usd, Payment IQD: $amount_iqd");
    
    if (($amount_usd > 0 && $amount_usd > $total_usd) || ($amount_iqd > 0 && $amount_iqd > $total_iqd) || ($discount_usd > 0 && $discount_usd > $total_usd)) {
        echo json_encode(['success' => false, 'msg' => 'نابێت بڕی پارەی گەرەوا زیاتر بێت لە بڕی قەرز!']);
        exit;
    }
    
    // Get company information for notification
    $stmt = $pdo->prepare("SELECT name FROM company WHERE id = ?");
    $stmt->execute([$company_id]);
    $company = $stmt->fetch();
    $company_name = $company['name'] ?? 'Unknown';

    try {
        // Start transaction
        $pdo->beginTransaction();

        // Insert into debt_payments
        $stmt = $pdo->prepare('INSERT INTO debt_payments (company_id, date, amount_usd, amount_iqd, discount_usd, dollar_rate, note, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        $ok = $stmt->execute([$company_id, $date, $amount_usd, $amount_iqd, $discount_usd, $dollar_rate, $note, $user_id]);
        if (!$ok) {
            error_log('Failed to insert debt payment: ' . print_r($stmt->errorInfo(), true));
            throw new Exception('هەڵە لە تۆمارکردن');
        }

        $debt_payment_id = $pdo->lastInsertId();
        error_log("Debt payment inserted with ID: $debt_payment_id");

        // Create detailed notification with company information
        $new_values = [
            'company_id' => $company_id,
            'company_name' => $company_name,
            'date' => $date,
            'amount_usd' => $amount_usd,
            'amount_iqd' => $amount_iqd,
            'discount_usd' => $discount_usd,
            'dollar_rate' => $dollar_rate,
            'note' => $note,
            'created_by' => $user_id
        ];

        $additional_info = [
            'action_type' => 'company_debt_payment',
            'payment_method' => $amount_usd > 0 ? 'USD' : ($amount_iqd > 0 ? 'IQD' : 'none'),
            'total_amount' => $amount_usd + $amount_iqd
        ];

        createDetailedNotification(
            $pdo,
            $_SESSION['user_id'],
            'insert',
            'debt_payments',
            $debt_payment_id,
            "پارەدان بۆ قەرزی کۆمپانیا زیادکرا (کۆمپانیا: $company_name)",
            null, // No old values for insert
            $new_values,
            $additional_info,
            getUserIP()
        );
        
        // FIFO: Reduce opening_debt_usd first, then remaining_usd in purchases
        if ($amount_usd > 0) {
            $remaining = $amount_usd;
            // Reduce opening_debt_usd first
            $stmt = $pdo->prepare('SELECT opening_debt_usd FROM company WHERE id = ?');
            $stmt->execute([$company_id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $opening = floatval($row['opening_debt_usd']);
            if ($opening > 0) {
                $toPay = min($opening, $remaining);
                $pdo->prepare('UPDATE company SET opening_debt_usd = opening_debt_usd - ? WHERE id = ?')->execute([$toPay, $company_id]);
                $remaining -= $toPay;
                error_log("Reduced opening debt USD by $toPay, remaining: $remaining");
            }
            // Then FIFO from purchases
            if ($remaining > 0) {
                $purchases = $pdo->prepare('SELECT id, remaining_usd FROM purchases WHERE company_id = ? AND type = ? AND payment_type = "قەرز" AND remaining_usd > 0 ORDER BY date ASC, id ASC');
                $purchases->execute([$company_id, 'دۆلار']);
                foreach ($purchases->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    if ($remaining <= 0) break;
                    $toPay = min($row['remaining_usd'], $remaining);
                    $pdo->prepare('UPDATE purchases SET remaining_usd = remaining_usd - ? WHERE id = ?')->execute([$toPay, $row['id']]);
                    $remaining -= $toPay;
                    error_log("Reduced purchase ID {$row['id']} remaining USD by $toPay, remaining: $remaining");
                }
            }
        }

        // Apply USD discount (no cash box, FIFO against opening and purchases)
        if ($discount_usd > 0) {
            $remaining = $discount_usd;
            // Reduce opening_debt_usd first
            $stmt = $pdo->prepare('SELECT opening_debt_usd FROM company WHERE id = ?');
            $stmt->execute([$company_id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $opening = floatval($row['opening_debt_usd']);
            if ($opening > 0) {
                $toReduce = min($opening, $remaining);
                $pdo->prepare('UPDATE company SET opening_debt_usd = opening_debt_usd - ? WHERE id = ?')->execute([$toReduce, $company_id]);
                $remaining -= $toReduce;
            }
            // Then FIFO from purchases USD
            if ($remaining > 0) {
                $purchases = $pdo->prepare('SELECT id, remaining_usd FROM purchases WHERE company_id = ? AND type = ? AND payment_type = "قەرز" AND remaining_usd > 0 ORDER BY date ASC, id ASC');
                $purchases->execute([$company_id, 'دۆلار']);
                foreach ($purchases->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    if ($remaining <= 0) break;
                    $toReduce = min($row['remaining_usd'], $remaining);
                    $pdo->prepare('UPDATE purchases SET remaining_usd = remaining_usd - ? WHERE id = ?')->execute([$toReduce, $row['id']]);
                    $remaining -= $toReduce;
                }
            }
        }
        
        // FIFO: Reduce opening_debt_iqd first, then remaining_iqd in purchases
        if ($amount_iqd > 0) {
            $remaining = $amount_iqd;
            // Reduce opening_debt_iqd first
            $stmt = $pdo->prepare('SELECT opening_debt_iqd FROM company WHERE id = ?');
            $stmt->execute([$company_id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $opening = floatval($row['opening_debt_iqd']);
            if ($opening > 0) {
                $toPay = min($opening, $remaining);
                $pdo->prepare('UPDATE company SET opening_debt_iqd = opening_debt_iqd - ? WHERE id = ?')->execute([$toPay, $company_id]);
                $remaining -= $toPay;
                error_log("Reduced opening debt IQD by $toPay, remaining: $remaining");
            }
            // Then FIFO from purchases
            if ($remaining > 0) {
                $purchases = $pdo->prepare('SELECT id, remaining_iqd FROM purchases WHERE company_id = ? AND type = ? AND payment_type = "قەرز" AND remaining_iqd > 0 ORDER BY date ASC, id ASC');
                $purchases->execute([$company_id, 'دینار']);
                foreach ($purchases->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    if ($remaining <= 0) break;
                    $toPay = min($row['remaining_iqd'], $remaining);
                    $pdo->prepare('UPDATE purchases SET remaining_iqd = remaining_iqd - ? WHERE id = ?')->execute([$toPay, $row['id']]);
                    $remaining -= $toPay;
                    error_log("Reduced purchase ID {$row['id']} remaining IQD by $toPay, remaining: $remaining");
                }
            }
        }
        
        // Commit transaction
        $pdo->commit();
        error_log("Debt payment completed successfully");
        echo json_encode(['success' => true]);
        
    } catch (Exception $e) {
        // Rollback transaction
        $pdo->rollBack();
        error_log("Debt payment failed: " . $e->getMessage());
        echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
    }
    
    exit;
}
echo json_encode(['success' => false, 'msg' => 'داواکاری نادروست']);
