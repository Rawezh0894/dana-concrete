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
    if ($action === 'inflow' || $action === 'outflow') {
        $amount_usd = floatval($_POST['amount_usd'] ?? 0);
        $amount_iqd = floatval($_POST['amount_iqd'] ?? 0);
        $category_id = intval($_POST['category_id'] ?? 0);
        $desc = $_POST['description'] ?? '';
        
        if ($amount_usd <= 0 && $amount_iqd <= 0) {
            throw new Exception("بڕی پارە دیاری نەکراوە.");
        }

        $txn_type = ($action === 'inflow') ? 'DEPOSIT' : 'WITHDRAWAL';

        $pdo->beginTransaction();

        // Check balances if outflow
        if ($action === 'outflow') {
            if ($amount_usd > 0) {
                $check = $pdo->prepare("SELECT balance FROM wallets WHERE user_id = ? AND currency_code = 'USD' FOR UPDATE");
                $check->execute([$user_id]);
                if ($check->fetchColumn() < $amount_usd) throw new Exception("باڵانسی دۆلارەکەت بەش ناکات.");
            }
            if ($amount_iqd > 0) {
                $check = $pdo->prepare("SELECT balance FROM wallets WHERE user_id = ? AND currency_code = 'IQD' FOR UPDATE");
                $check->execute([$user_id]);
                if ($check->fetchColumn() < $amount_iqd) throw new Exception("باڵانسی دینارەکەت بەش ناکات.");
            }
        }

        // 1. Transaction
        $ref = uniqid('TXN_');
        $stmt = $pdo->prepare("INSERT INTO transactions (reference_id, type, category_id, created_by) VALUES (?, ?, ?, ?)");
        $stmt->execute([$ref, $txn_type, $category_id ?: null, $user_id]);
        $txn_id = $pdo->lastInsertId();

        // 2. Process USD if exists
        if ($amount_usd > 0) {
            $db_usd = ($action === 'inflow') ? $amount_usd : -$amount_usd;
            $pdo->prepare("UPDATE wallets SET balance = balance + ? WHERE user_id = ? AND currency_code = 'USD'")->execute([$db_usd, $user_id]);
            
            $stmt = $pdo->prepare("INSERT INTO ledger_entries (transaction_id, wallet_id, amount, currency_code, description) VALUES (?, (SELECT id FROM wallets WHERE user_id=? AND currency_code='USD'), ?, 'USD', ?)");
            $stmt->execute([$txn_id, $user_id, $db_usd, $desc]);
        }

        // 3. Process IQD if exists
        if ($amount_iqd > 0) {
            $db_iqd = ($action === 'inflow') ? $amount_iqd : -$amount_iqd;
            $pdo->prepare("UPDATE wallets SET balance = balance + ? WHERE user_id = ? AND currency_code = 'IQD'")->execute([$db_iqd, $user_id]);
            
            $stmt = $pdo->prepare("INSERT INTO ledger_entries (transaction_id, wallet_id, amount, currency_code, description) VALUES (?, (SELECT id FROM wallets WHERE user_id=? AND currency_code='IQD'), ?, 'IQD', ?)");
            $stmt->execute([$txn_id, $user_id, $db_iqd, $desc]);
        }

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'کردارەکە بە سەرکەوتوویی ئەنجامدرا.']);
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
