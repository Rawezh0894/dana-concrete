<?php
session_start();
require_once '../../config/db_conected.php'; 

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'ڕۆچوون (Login) پێویستە.']);
    exit;
}
$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

try {
    if ($action === 'inflow' || $action === 'outflow' || $action === 'edit_transaction') {
        $txn_id = intval($_POST['transaction_id'] ?? 0);
        $amount_usd = floatval($_POST['amount_usd'] ?? 0);
        $amount_iqd = floatval($_POST['amount_iqd'] ?? 0);
        $category_id = intval($_POST['category_id'] ?? 0);
        $desc = $_POST['description'] ?? '';
        
        if ($amount_usd <= 0 && $amount_iqd <= 0 && $action !== 'delete_transaction') {
            throw new Exception("بڕی پارە دیاری نەکراوە.");
        }

        $pdo->beginTransaction();

        $is_editing = false;

        // If Editing, FIRST reverse the OLD transaction effects
        if ($action === 'edit_transaction' && $txn_id > 0) {
            $is_editing = true;
            
            // Get original transaction type to correctly process new amounts
            $stmtType = $pdo->prepare("SELECT type FROM transactions WHERE id = ?");
            $stmtType->execute([$txn_id]);
            $originalType = $stmtType->fetchColumn();

            // Reverse old wallet balances
            $old_entries = $pdo->prepare("SELECT wallet_id, amount FROM ledger_entries WHERE transaction_id = ?");
            $old_entries->execute([$txn_id]);
            while ($row = $old_entries->fetch()) {
                $pdo->prepare("UPDATE wallets SET balance = balance - ? WHERE id = ?")
                    ->execute([$row['amount'], $row['wallet_id']]);
            }
            // Clear old entries
            $pdo->prepare("DELETE FROM ledger_entries WHERE transaction_id = ?")->execute([$txn_id]);
            
            // Re-assign action so the rest of the flow knows if it should be positive or negative
            $action = ($originalType === 'WITHDRAWAL') ? 'outflow' : 'inflow';
        }

        $txn_type = ($action === 'outflow') ? 'WITHDRAWAL' : 'DEPOSIT';

        // 1. Create or Update Transaction Record
        if ($is_editing && $txn_id > 0) {
            $stmt = $pdo->prepare("UPDATE transactions SET category_id = ? WHERE id = ?");
            $stmt->execute([$category_id ?: null, $txn_id]);
        } else {
            $ref = uniqid('TXN_');
            $stmt = $pdo->prepare("INSERT INTO transactions (reference_id, type, category_id, created_by) VALUES (?, ?, ?, ?)");
            $stmt->execute([$ref, $txn_type, $category_id ?: null, $user_id]);
            $txn_id = $pdo->lastInsertId();
        }

        // 2. Process USD
        if ($amount_usd > 0) {
            $real_amount = ($action === 'outflow') ? -$amount_usd : $amount_usd;
            // Update Wallet
            $pdo->prepare("UPDATE wallets SET balance = balance + ? WHERE user_id = ? AND currency_code = 'USD'")->execute([$real_amount, $user_id]);
            // Create Ledger Entry
            $stmt = $pdo->prepare("INSERT INTO ledger_entries (transaction_id, wallet_id, amount, currency_code, description) VALUES (?, (SELECT id FROM wallets WHERE user_id=? AND currency_code='USD'), ?, 'USD', ?)");
            $stmt->execute([$txn_id, $user_id, $real_amount, $desc]);
        }

        // 3. Process IQD
        if ($amount_iqd > 0) {
            $real_amount = ($action === 'outflow') ? -$amount_iqd : $amount_iqd;
            // Update Wallet
            $pdo->prepare("UPDATE wallets SET balance = balance + ? WHERE user_id = ? AND currency_code = 'IQD'")->execute([$real_amount, $user_id]);
            // Create Ledger Entry
            $stmt = $pdo->prepare("INSERT INTO ledger_entries (transaction_id, wallet_id, amount, currency_code, description) VALUES (?, (SELECT id FROM wallets WHERE user_id=? AND currency_code='IQD'), ?, 'IQD', ?)");
            $stmt->execute([$txn_id, $user_id, $real_amount, $desc]);
        }

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'کردارەکە بە سەرکەوتوویی ئەنجامدرا.']);
        exit;
    }

    if ($action === 'delete_transaction') {
        $txn_id = intval($_POST['transaction_id'] ?? 0);
        if ($txn_id <= 0) throw new Exception("Transaction ID missing");

        $pdo->beginTransaction();

        // 1. Reverse balance changes
        $entries = $pdo->prepare("SELECT wallet_id, amount FROM ledger_entries WHERE transaction_id = ?");
        $entries->execute([$txn_id]);
        while ($row = $entries->fetch()) {
            $pdo->prepare("UPDATE wallets SET balance = balance - ? WHERE id = ?")
                ->execute([$row['amount'], $row['wallet_id']]);
        }

        // 2. Delete entries and transaction (Cascade should handle entries, but manual is safer depends on schema)
        $pdo->prepare("DELETE FROM transactions WHERE id = ?")->execute([$txn_id]);

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'بە سەرکەوتوویی سڕایەوە']);
        exit;
    }

    if ($action === 'exchange') {
        $from_curr = $_POST['from_currency'] ?? '';
        $to_curr   = $_POST['to_currency'] ?? '';
        $amount    = floatval($_POST['exchange_amount'] ?? 0);
        $rate      = floatval($_POST['exchange_rate'] ?? 0);
        
        if ($amount <= 0 || $rate <= 0 || $from_curr === $to_curr) throw new Exception("زانیاری هەڵە.");

        $receive_amount = ($from_curr === 'USD') ? ($amount * $rate) : ($amount / $rate);

        $pdo->beginTransaction();
        $stmt = $pdo->prepare("SELECT balance FROM wallets WHERE user_id = ? AND currency_code = ? FOR UPDATE");
        $stmt->execute([$user_id, $from_curr]);
        if ($stmt->fetchColumn() < $amount) throw new Exception("باڵانس بەش ناکات.");

        $ref = uniqid('EXC_');
        $stmt = $pdo->prepare("INSERT INTO transactions (reference_id, type, created_by) VALUES (?, 'EXCHANGE', ?)");
        $stmt->execute([$ref, $user_id]);
        $txn_id = $pdo->lastInsertId();

        $pdo->prepare("UPDATE wallets SET balance = balance - ? WHERE user_id = ? AND currency_code = ?")->execute([$amount, $user_id, $from_curr]);
        $pdo->prepare("UPDATE wallets SET balance = balance + ? WHERE user_id = ? AND currency_code = ?")->execute([$receive_amount, $user_id, $to_curr]);

        $pdo->prepare("INSERT INTO ledger_entries (transaction_id, wallet_id, amount, currency_code, exchange_rate_applied, description) VALUES (?, (SELECT id FROM wallets WHERE user_id=? AND currency_code=?), ?, ?, ?, 'ئاڵوگۆڕ - هاتن/چوون')")
            ->execute([$txn_id, $user_id, $from_curr, -$amount, $from_curr, $rate]);
        $pdo->prepare("INSERT INTO ledger_entries (transaction_id, wallet_id, amount, currency_code, exchange_rate_applied, description) VALUES (?, (SELECT id FROM wallets WHERE user_id=? AND currency_code=?), ?, ?, ?, 'ئاڵوگۆڕ - هاتن/چوون')")
            ->execute([$txn_id, $user_id, $to_curr, $receive_amount, $to_curr, $rate]);

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'ئاڵوگۆڕ ئەنجامدرا.']);
        exit;
    }
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
