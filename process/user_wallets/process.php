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
        $currency = $_POST['currency'] ?? '';
        $amount = floatval($_POST['amount'] ?? 0);
        $category_id = intval($_POST['category_id'] ?? 0);
        $desc = $_POST['description'] ?? '';
        
        if ($amount <= 0 || !in_array($currency, ['USD', 'IQD'])) {
            throw new Exception("بڕی پارە یان جۆری دراو هەڵەیە.");
        }

        $db_amount = ($action === 'inflow') ? $amount : -$amount;
        $txn_type = ($action === 'inflow') ? 'DEPOSIT' : 'WITHDRAWAL';

        // دەستپێکردنی مامەڵەی داتابەیس (ACID Transaction)
        $pdo->beginTransaction();

        // گەر پارە ڕادەکێشێت پێویستە باڵانس چک بکەین وە لۆکی درێژخایەنی Rowـەکە بکەین (FOR UPDATE)
        if ($action === 'outflow') {
            $checkStmt = $pdo->prepare("SELECT balance FROM wallets WHERE user_id = ? AND currency_code = ? FOR UPDATE");
            $checkStmt->execute([$user_id, $currency]);
            $current_balance = $checkStmt->fetchColumn();
            if ($current_balance < $amount) {
                throw new Exception("باڵانسەکەت بەش ناکات.");
            }
        }

        // 1. Transaction
        $ref = uniqid('TXN_');
        $stmt = $pdo->prepare("INSERT INTO transactions (reference_id, type, category_id, created_by) VALUES (?, ?, ?, ?)");
        $stmt->execute([$ref, $txn_type, $category_id ?: null, $user_id]);
        $txn_id = $pdo->lastInsertId();

        // 2. Wallets Table
        $stmt = $pdo->prepare("UPDATE wallets SET balance = balance + ? WHERE user_id = ? AND currency_code = ?");
        $stmt->execute([$db_amount, $user_id, $currency]);

        // 3. Ledger Entry Table
        $stmt = $pdo->prepare("
            INSERT INTO ledger_entries (transaction_id, wallet_id, amount, currency_code, description) 
            VALUES (?, (SELECT id FROM wallets WHERE user_id=? AND currency_code=?), ?, ?, ?)
        ");
        $stmt->execute([$txn_id, $user_id, $currency, $db_amount, $currency, $desc]);

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'مامەڵەکە بە سەرکەوتوویی جێبەجێکرا.']);
        exit;
    }

    if ($action === 'exchange') {
        $from_curr = $_POST['from_currency'] ?? '';
        $to_curr   = $_POST['to_currency'] ?? '';
        $amount    = floatval($_POST['exchange_amount'] ?? 0);
        $rate      = floatval($_POST['exchange_rate'] ?? 0);
        
        if ($amount <= 0 || $rate <= 0 || $from_curr === $to_curr) {
            throw new Exception("زانیارییەکانی گۆڕینەوە دروست نین.");
        }

        // حیسابکردنی بڕی وەرگیراو بە پێی سێرعی گۆڕینەوەکە
        $receive_amount = ($from_curr === 'USD' && $to_curr === 'IQD') ? ($amount * $rate) : ($amount / $rate);

        $pdo->beginTransaction();

        $checkStmt = $pdo->prepare("SELECT balance FROM wallets WHERE user_id = ? AND currency_code = ? FOR UPDATE");
        $checkStmt->execute([$user_id, $from_curr]);
        $current_balance = $checkStmt->fetchColumn();
        
        if ($current_balance < $amount) {
            throw new Exception("بڕی خەرجکراو لە باڵانسەکەی ئێستات زیاترە.");
        }

        // لۆککردن لەسەر قاسەکەی تریشی
        $pdo->prepare("SELECT id FROM wallets WHERE user_id = ? AND currency_code = ? FOR UPDATE")->execute([$user_id, $to_curr]);

        // Transaction
        $ref = uniqid('EXC_');
        $stmt = $pdo->prepare("INSERT INTO transactions (reference_id, type, created_by) VALUES (?, 'EXCHANGE', ?)");
        $stmt->execute([$ref, $user_id]);
        $txn_id = $pdo->lastInsertId();

        // Wallets - بڕین 
        $stmt = $pdo->prepare("UPDATE wallets SET balance = balance - ? WHERE user_id = ? AND currency_code = ?");
        $stmt->execute([$amount, $user_id, $from_curr]);
        // Wallets - زیادکردن
        $stmt = $pdo->prepare("UPDATE wallets SET balance = balance + ? WHERE user_id = ? AND currency_code = ?");
        $stmt->execute([$receive_amount, $user_id, $to_curr]);

        // Ledger - دەرچوون (Outflow)
        $stmt = $pdo->prepare("INSERT INTO ledger_entries (transaction_id, wallet_id, amount, currency_code, exchange_rate_applied, description) VALUES (?, (SELECT id FROM wallets WHERE user_id=? AND currency_code=?), ?, ?, ?, 'ئاڵوگۆڕی دراو - خەرجکراو')");
        $stmt->execute([$txn_id, $user_id, $from_curr, -$amount, $from_curr, $rate]);
        
        // Ledger - هاتن (Inflow)
        $stmt = $pdo->prepare("INSERT INTO ledger_entries (transaction_id, wallet_id, amount, currency_code, exchange_rate_applied, description) VALUES (?, (SELECT id FROM wallets WHERE user_id=? AND currency_code=?), ?, ?, ?, 'ئاڵوگۆڕی دراو - بەدەستهاتوو')");
        $stmt->execute([$txn_id, $user_id, $to_curr, $receive_amount, $to_curr, $rate]);

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'ئاڵوگۆڕ بە سەرکەوتوویی ئەنجامدرا.']);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'جۆری مامەڵەکە نادیارە.']);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
