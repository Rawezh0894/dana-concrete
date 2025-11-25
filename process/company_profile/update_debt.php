<?php
session_start();
// Only log errors, don't display them in JSON response
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../php-error.log');

require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

// Log session and POST data for debugging
error_log('SESSION: ' . print_r($_SESSION, true));
error_log('update_debt.php POST: ' . print_r($_POST, true));

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    error_log('User not logged in for company debt update');
    echo json_encode(['success' => false, 'msg' => 'سێشن نییە! تکایە بچۆ ژوورەوە.']);
    exit;
}

if (!hasPermission('update_debt')) {
    error_log('Permission denied for user: ' . $_SESSION['user_id'] . ' to update company debt');
    echo json_encode(['success' => false, 'msg' => 'ڕێگەت پێنەدراوە!']);
    exit;
}

try {
    $id = $_POST['id'] ?? null;
    $company_id = $_POST['company_id'] ?? null;
    $date = $_POST['date'] ?? null;
    $dollar_rate = floatval($_POST['dollar_rate'] ?? 0);
    $amount_usd = floatval($_POST['amount_usd'] ?? 0);
    $amount_iqd = floatval($_POST['amount_iqd'] ?? 0);
    $discount_usd = floatval($_POST['discount_usd'] ?? 0);
    $discount_iqd = floatval($_POST['discount_iqd'] ?? 0);
    $note = $_POST['note'] ?? '';

    // Log parsed variables for debugging
    error_log("Parsed vars: id='$id', company_id='$company_id', date='$date', dollar_rate='$dollar_rate', amount_usd='$amount_usd', amount_iqd='$amount_iqd', note='$note'");

    if (
        !$id || !$company_id || !$date ||
        ($amount_usd <= 0 && $amount_iqd <= 0 && $discount_usd <= 0 && $discount_iqd <= 0)
    ) {
        error_log('Missing required fields for company debt update');
        echo json_encode(['success' => false, 'msg' => 'هەموو خانەکان پڕ بکە!']);
        exit;
    }

    // Check if debt payment exists and get current values
    $checkStmt = $pdo->prepare('SELECT id, amount_usd, amount_iqd, discount_usd, discount_iqd FROM debt_payments WHERE id = ?');
    $checkStmt->execute([$id]);
    $row = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$row) {
        error_log('Company debt payment not found: ID=' . $id);
        echo json_encode(['success' => false, 'msg' => 'قەرز نەدۆزرایەوە!']);
        exit;
    }
    
    error_log('Found company debt payment for update: ' . print_r($row, true));

    // وەرگرتنی بڕەکانی کۆن
    $old_amount_usd = floatval($row['amount_usd'] ?? 0);
    $old_amount_iqd = floatval($row['amount_iqd'] ?? 0);
    $old_discount_usd = floatval($row['discount_usd'] ?? 0);
    $old_discount_iqd = floatval($row['discount_iqd'] ?? 0);

    // هەژمارکردنی جیاوازییەکان
    $diff_usd = $amount_usd - $old_amount_usd;
    $diff_iqd = $amount_iqd - $old_amount_iqd;
    $diff_discount_usd = $discount_usd - $old_discount_usd;
    $diff_discount_iqd = $discount_iqd - $old_discount_iqd;

    error_log("Old amounts - USD: $old_amount_usd, IQD: $old_amount_iqd");
    error_log("New amounts - USD: $amount_usd, IQD: $amount_iqd");
    error_log("Differences - USD: $diff_usd, IQD: $diff_iqd");

    try {
        // Start transaction
        $pdo->beginTransaction();

        // نوێکردنەوەی قەرزەکە
        $upd = $pdo->prepare('UPDATE debt_payments SET date=?, dollar_rate=?, amount_usd=?, amount_iqd=?, discount_usd=?, discount_iqd=?, note=? WHERE id=?');
        $result = $upd->execute([$date, $dollar_rate, $amount_usd, $amount_iqd, $discount_usd, $discount_iqd, $note, $id]);

        if (!$result) {
            throw new Exception('هەڵە لە نوێکردنەوەی قەرزەکە');
        }

        // بەڕێوەبردنی جیاوازییەکان

        // بۆ USD
        if ($diff_usd != 0) {
            if ($diff_usd > 0) {
                // زیادکردنی بڕی نوێ (زیاتر لە کۆن)
                error_log("Adding USD difference: $diff_usd");
                $remaining = $diff_usd;
                
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
            } else {
                // گەڕاندنەوەی بڕی کەم (کەمتر لە کۆن)
                $to_restore = abs($diff_usd);
                error_log("Restoring USD difference: $to_restore");
                
                // Restore to purchases first (LIFO - newest first)
                $purchases = $pdo->prepare('SELECT id, remaining_usd, price FROM purchases WHERE company_id = ? AND payment_type = "قەرز" AND type = "دۆلار" ORDER BY date DESC, id DESC');
                $purchases->execute([$company_id]);
                foreach ($purchases->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    if ($to_restore <= 0) break;
                    $max_restore = $row['price'] - $row['remaining_usd'];
                    if ($max_restore <= 0) continue;
                    $toRestore = min($max_restore, $to_restore);
                    $pdo->prepare('UPDATE purchases SET remaining_usd = remaining_usd + ? WHERE id = ?')->execute([$toRestore, $row['id']]);
                    $to_restore -= $toRestore;
                    error_log("Restored purchase ID {$row['id']} remaining USD by $toRestore");
                }
                
                // Then restore to opening debt
                if ($to_restore > 0) {
                    $pdo->prepare('UPDATE company SET opening_debt_usd = opening_debt_usd + ? WHERE id = ?')->execute([$to_restore, $company_id]);
                    error_log("Restored opening debt USD by $to_restore");
                }
            }
        }
        
        // بۆ IQD
        if ($diff_iqd != 0) {
            if ($diff_iqd > 0) {
                // زیادکردنی بڕی نوێ (زیاتر لە کۆن)
                error_log("Adding IQD difference: $diff_iqd");
                $remaining = $diff_iqd;
                
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
            } else {
                // گەڕاندنەوەی بڕی کەم (کەمتر لە کۆن)
                $to_restore = abs($diff_iqd);
                error_log("Restoring IQD difference: $to_restore");
                
                // Restore to purchases first (LIFO - newest first)
                $purchases = $pdo->prepare('SELECT id, remaining_iqd, amount_iqd FROM purchases WHERE company_id = ? AND payment_type = "قەرز" AND type = "دینار" ORDER BY date DESC, id DESC');
                $purchases->execute([$company_id]);
                foreach ($purchases->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    if ($to_restore <= 0) break;
                    $max_restore = $row['amount_iqd'] - $row['remaining_iqd'];
                    if ($max_restore <= 0) continue;
                    $toRestore = min($max_restore, $to_restore);
                    $pdo->prepare('UPDATE purchases SET remaining_iqd = remaining_iqd + ? WHERE id = ?')->execute([$toRestore, $row['id']]);
                    $to_restore -= $toRestore;
                    error_log("Restored purchase ID {$row['id']} remaining IQD by $toRestore");
                }
                
                // Then restore to opening debt
                if ($to_restore > 0) {
                    $pdo->prepare('UPDATE company SET opening_debt_iqd = opening_debt_iqd + ? WHERE id = ?')->execute([$to_restore, $company_id]);
                    error_log("Restored opening debt IQD by $to_restore");
                }
            }
        }

        // Get company information for notification
        $stmt = $pdo->prepare("SELECT name FROM company WHERE id = ?");
        $stmt->execute([$company_id]);
        $company = $stmt->fetch();
        $company_name = $company['name'] ?? 'Unknown';

        // Get old values for notification
        $stmt = $pdo->prepare("SELECT * FROM debt_payments WHERE id = ?");
        $stmt->execute([$id]);
        $old_record = $stmt->fetch();

        $old_values = [
            'company_id' => $old_record['company_id'],
            'date' => $old_record['date'],
            'dollar_rate' => $old_record['dollar_rate'],
            'amount_usd' => $old_record['amount_usd'],
            'amount_iqd' => $old_record['amount_iqd'],
            'note' => $old_record['note'],
            'discount_usd' => $old_record['discount_usd'],
            'discount_iqd' => $old_record['discount_iqd']
        ];

        $new_values = [
            'company_id' => $company_id,
            'company_name' => $company_name,
            'date' => $date,
            'dollar_rate' => $dollar_rate,
            'amount_usd' => $amount_usd,
            'amount_iqd' => $amount_iqd,
            'discount_usd' => $discount_usd,
            'discount_iqd' => $discount_iqd,
            'note' => $note
        ];

        $additional_info = [
            'action_type' => 'company_debt_payment_update',
            'payment_method' => $amount_usd > 0 ? 'USD' : ($amount_iqd > 0 ? 'IQD' : 'none'),
            'total_amount' => $amount_usd + $amount_iqd,
            'difference_usd' => $diff_usd,
            'difference_iqd' => $diff_iqd,
            'difference_discount_usd' => $diff_discount_usd,
            'difference_discount_iqd' => $diff_discount_iqd
        ];
        // Handle USD discount differences (no cash box):
        if ($diff_discount_usd != 0) {
            if ($diff_discount_usd > 0) {
                // Apply additional discount: reduce opening_debt_usd then purchases.remaining_usd (FIFO)
                $remaining = $diff_discount_usd;
                $stmt = $pdo->prepare('SELECT opening_debt_usd FROM company WHERE id = ?');
                $stmt->execute([$company_id]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                $opening = floatval($row['opening_debt_usd']);
                if ($opening > 0) {
                    $toReduce = min($opening, $remaining);
                    $pdo->prepare('UPDATE company SET opening_debt_usd = opening_debt_usd - ? WHERE id = ?')->execute([$toReduce, $company_id]);
                    $remaining -= $toReduce;
                }
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
            } else {
                // Reduce discount (i.e., restore debt): restore to purchases (LIFO) then opening_debt_usd
                $to_restore = abs($diff_discount_usd);
                $purchases = $pdo->prepare('SELECT id, remaining_usd, price FROM purchases WHERE company_id = ? AND payment_type = "قەرز" AND type = "دۆلار" ORDER BY date DESC, id DESC');
                $purchases->execute([$company_id]);
                foreach ($purchases->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    if ($to_restore <= 0) break;
                    $max_restore = $row['price'] - $row['remaining_usd'];
                    if ($max_restore <= 0) continue;
                    $toRestore = min($max_restore, $to_restore);
                    $pdo->prepare('UPDATE purchases SET remaining_usd = remaining_usd + ? WHERE id = ?')->execute([$toRestore, $row['id']]);
                    $to_restore -= $toRestore;
                }
                if ($to_restore > 0) {
                    $pdo->prepare('UPDATE company SET opening_debt_usd = opening_debt_usd + ? WHERE id = ?')->execute([$to_restore, $company_id]);
                }
            }
        }
        if ($diff_discount_iqd != 0) {
            if ($diff_discount_iqd > 0) {
                $remaining = $diff_discount_iqd;
                $stmt = $pdo->prepare('SELECT opening_debt_iqd FROM company WHERE id = ?');
                $stmt->execute([$company_id]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                $opening = floatval($row['opening_debt_iqd']);
                if ($opening > 0) {
                    $toReduce = min($opening, $remaining);
                    $pdo->prepare('UPDATE company SET opening_debt_iqd = opening_debt_iqd - ? WHERE id = ?')->execute([$toReduce, $company_id]);
                    $remaining -= $toReduce;
                }
                if ($remaining > 0) {
                    $purchases = $pdo->prepare('SELECT id, remaining_iqd FROM purchases WHERE company_id = ? AND type = ? AND payment_type = "قەرز" AND remaining_iqd > 0 ORDER BY date ASC, id ASC');
                    $purchases->execute([$company_id, 'دینار']);
                    foreach ($purchases->fetchAll(PDO::FETCH_ASSOC) as $row) {
                        if ($remaining <= 0) break;
                        $toReduce = min($row['remaining_iqd'], $remaining);
                        $pdo->prepare('UPDATE purchases SET remaining_iqd = remaining_iqd - ? WHERE id = ?')->execute([$toReduce, $row['id']]);
                        $remaining -= $toReduce;
                    }
                }
            } else {
                $to_restore = abs($diff_discount_iqd);
                $purchases = $pdo->prepare('SELECT id, remaining_iqd, amount_iqd FROM purchases WHERE company_id = ? AND payment_type = "قەرز" AND type = "دینار" ORDER BY date DESC, id DESC');
                $purchases->execute([$company_id]);
                foreach ($purchases->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    if ($to_restore <= 0) break;
                    $max_restore = $row['amount_iqd'] - $row['remaining_iqd'];
                    if ($max_restore <= 0) continue;
                    $toRestore = min($max_restore, $to_restore);
                    $pdo->prepare('UPDATE purchases SET remaining_iqd = remaining_iqd + ? WHERE id = ?')->execute([$toRestore, $row['id']]);
                    $to_restore -= $toRestore;
                }
                if ($to_restore > 0) {
                    $pdo->prepare('UPDATE company SET opening_debt_iqd = opening_debt_iqd + ? WHERE id = ?')->execute([$to_restore, $company_id]);
                }
            }
        }

        createDetailedNotification(
            $pdo,
            $_SESSION['user_id'],
            'update',
            'debt_payments',
            $id,
            "پارەدانی قەرزی کۆمپانیا نوێکرایەوە (کۆمپانیا: $company_name)",
            $old_values,
            $new_values,
            $additional_info,
            getUserIP()
        );

        // Commit transaction
        $pdo->commit();
        error_log('Company debt successfully updated: ID=' . $id . ', Company=' . $company_name . ' (ID: ' . $company_id . ')');
        echo json_encode(['success' => true, 'msg' => 'قەرز بەسەرکەوتوویی نوێکرایەوە!']);
        
    } catch (Exception $e) {
        // Rollback transaction
        $pdo->rollBack();
        error_log("Debt update failed: " . $e->getMessage());
        echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
    }
    
} catch (PDOException $e) {
    error_log('PDOException in update_debt.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'msg' => 'هەڵەی داتابەیس: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log('Exception in update_debt.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'msg' => 'هەڵەی سیستەم: ' . $e->getMessage()]);
}
