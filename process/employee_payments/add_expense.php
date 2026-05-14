<?php
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';
require_once __DIR__ . '/employee_expense_cash_box_helper.php';
require_once __DIR__ . '/employee_loan_helper.php';
header('Content-Type: application/json');
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'msg' => 'Unauthorized']);
    exit;
}
if (!hasPermission('add_payment')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'msg' => 'ڕێگەپێدراوە بۆ زیادکردنی پارەدان']);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'POST only']);
    exit;
}

$employee_id = intval($_POST['employee_id'] ?? 0);
$expense_date = trim($_POST['expense_date'] ?? '');
$notes = trim($_POST['notes'] ?? '');

$salary = floatval($_POST['salary'] ?? 0);
$bonus = floatval($_POST['bonus'] ?? 0);
$overtime = floatval($_POST['overtime'] ?? 0);
$advance = floatval($_POST['advance'] ?? 0);
$deduction = floatval($_POST['deduction'] ?? 0);
$penalty = floatval($_POST['penalty'] ?? 0);
$overtime_payment = floatval($_POST['overtime_payment'] ?? 0);

$amount_usd = round(floatval($_POST['amount_usd'] ?? 0), 2);
$amount_iqd = round(floatval($_POST['amount_iqd'] ?? 0), 2);
$exchange_rate = floatval($_POST['exchange_rate'] ?? 0);

$loan_deduct_usd = round(floatval($_POST['deduct_loan_usd'] ?? 0), 2);
$loan_deduct_iqd = round(floatval($_POST['deduct_loan_iqd'] ?? 0), 2);

if ($employee_id <= 0 || $expense_date === '') {
    echo json_encode(['success' => false, 'message' => 'کارمەند و بەروار پێویستە']);
    exit;
}

$total_expenses = $salary + $bonus + $overtime + $advance + $deduction + $penalty + $overtime_payment;
if ($total_expenses <= 0) {
    echo json_encode(['success' => false, 'message' => 'لانیکەم یەک جۆری خەرجی پێویستە']);
    exit;
}

$gross_income = $salary + $bonus + $overtime;
$deduction_side_total = $advance + $deduction + $penalty + $overtime_payment;
$loan_deduct_equiv = 0.0;
$loansTableChk = $pdo->query("SHOW TABLES LIKE 'employee_loans'");
$loans_table_ok = $loansTableChk && $loansTableChk->rowCount() > 0;

if ($loan_deduct_usd > 0 || $loan_deduct_iqd > 0) {
    if (!$loans_table_ok) {
        echo json_encode(['success' => false, 'message' => 'خشتەی قەرز دامەزراوە نییە — employee_loans.sql جێبەجێ بکە.']);
        exit;
    }
    if ($loan_deduct_usd > 0 && $exchange_rate <= 0) {
        echo json_encode(['success' => false, 'message' => 'کەمکردنەوەی قەرز بە دۆلار: نرخی گۆڕینەوە پێویستە.']);
        exit;
    }
    $loan_basis = 0.0;
    if ($gross_income > 0 && abs($total_expenses - $gross_income) < 0.01) {
        $loan_basis = $gross_income;
    } elseif ($gross_income <= 0 && $deduction_side_total > 0 && abs($total_expenses - $deduction_side_total) < 0.01) {
        $loan_basis = $deduction_side_total;
    } else {
        echo json_encode(['success' => false, 'message' => 'کەمکردنەوەی قەرز تەنها لەگەڵ تەنها مووچە/بەخشیش/کاروان یان تەنها پێشەکی/کەمکردنەوە/سزا/پێدانی کارواندا دەبێت.']);
        exit;
    }
    $loan_deduct_equiv = employee_expense_cash_iqd_equivalent($loan_deduct_usd, $loan_deduct_iqd, $exchange_rate);
    if ($loan_deduct_equiv > $loan_basis + 1.0) {
        echo json_encode(['success' => false, 'message' => 'کەمکردنەوەی قەرز نابێت زیاتر بێت لە کۆی ئەم خەرجییە.']);
        exit;
    }
    $outstanding = employee_loan_outstanding_totals($pdo, $employee_id);
    if ($loan_deduct_usd > $outstanding['usd'] + 0.01) {
        echo json_encode(['success' => false, 'message' => 'کەمکردنەوەی دۆلار زیاترە لە قەرزی ماوەی دۆلار.']);
        exit;
    }
    if ($loan_deduct_iqd > $outstanding['iqd'] + 0.01) {
        echo json_encode(['success' => false, 'message' => 'کەمکردنەوەی دینار زیاترە لە قەرزی ماوەی دینار.']);
        exit;
    }
}

$net_ledger_for_cash = round($total_expenses - $loan_deduct_equiv, 2);
if ($net_ledger_for_cash < -0.01) {
    echo json_encode(['success' => false, 'message' => 'کۆی پارەی قاسە نابێت نەرێنی بێت.']);
    exit;
}

$cash_equiv = employee_expense_cash_iqd_equivalent($amount_usd, $amount_iqd, $exchange_rate);
if ($amount_usd > 0 && $exchange_rate <= 0) {
    echo json_encode(['success' => false, 'message' => 'کاتێک بڕی دۆلار هەیە، نرخی گۆڕینەوە پێویستە.']);
    exit;
}

// قاسە: هاوتای دینار = کۆی خەرجی − کەمکردنەوەی قەرز (کاتێک قەرز هەیە)، یان کۆی تەواو
if ($amount_usd > 0 || $amount_iqd > 0) {
    if (abs($cash_equiv - $net_ledger_for_cash) > 1.0) {
        echo json_encode([
            'success' => false,
            'message' => 'کۆی پارەی قاسە بە دینار دەبێت یەکسان بێت بە کۆی خەرجی (' . number_format($total_expenses, 0)
                . ') − قەرز (' . number_format($loan_deduct_equiv, 0) . ') = '
                . number_format($net_ledger_for_cash, 0) . ' د.ع. ئێستا (دۆلار×نرخ)+دینار = '
                . number_format($cash_equiv, 0) . ' د.ع.',
        ]);
        exit;
    }
}

$lines = [];
if ($salary > 0) {
    $lines[] = ['type' => 'salary', 'amount' => $salary];
}
if ($bonus > 0) {
    $lines[] = ['type' => 'bonus', 'amount' => $bonus];
}
if ($overtime > 0) {
    $lines[] = ['type' => 'overtime', 'amount' => $overtime];
}
if ($advance > 0) {
    $lines[] = ['type' => 'advance', 'amount' => $advance];
}
if ($deduction > 0) {
    $lines[] = ['type' => 'deduction', 'amount' => $deduction];
}
if ($penalty > 0) {
    $lines[] = ['type' => 'penalty', 'amount' => $penalty];
}
if ($overtime_payment > 0) {
    $lines[] = ['type' => 'overtime_payment', 'amount' => $overtime_payment];
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare('SELECT name FROM employees WHERE id = ?');
    $stmt->execute([$employee_id]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);
    $employee_name = $employee['name'] ?? 'Unknown';

    $expense_ids = [];
    $first_expense_id = null;

    $stmtInsert = $pdo->prepare(
        'INSERT INTO employee_expenses (employee_id, expense_type, amount, amount_usd, amount_iqd, exchange_rate, notes, created_by, expense_date)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );

    foreach ($lines as $idx => $ln) {
        $is_first = ($idx === 0);
        $row_usd = $is_first ? $amount_usd : 0.0;
        $row_iqd = $is_first ? $amount_iqd : 0.0;
        $row_rate = $is_first ? $exchange_rate : 0.0;

        $stmtInsert->execute([
            $employee_id,
            $ln['type'],
            $ln['amount'],
            $row_usd,
            $row_iqd,
            $row_rate,
            $notes,
            $_SESSION['user_id'],
            $expense_date,
        ]);
        $newId = (int) $pdo->lastInsertId();
        if ($is_first) {
            $first_expense_id = $newId;
        }
        $expense_ids[] = ['type' => $ln['type'], 'id' => $newId, 'amount' => $ln['amount']];
    }

    if ($first_expense_id === null) {
        throw new RuntimeException('هەڵە لە دروستکردنی تۆمار');
    }

    if ($loans_table_ok && ($loan_deduct_usd > 0 || $loan_deduct_iqd > 0)) {
        employee_loan_apply_repayment($pdo, $employee_id, $loan_deduct_usd, $loan_deduct_iqd, $first_expense_id);
    }

    $first_type = $lines[0]['type'] ?? 'batch';
    $fallback_iqd = 0.0;
    if ($amount_usd <= 0 && $amount_iqd <= 0) {
        $fallback_iqd = max(0.0, $net_ledger_for_cash);
    }
    employee_expense_replace_cash_withdrawals(
        $pdo,
        $first_expense_id,
        $employee_name,
        $first_type,
        $expense_date,
        $amount_usd,
        $amount_iqd,
        $exchange_rate,
        $notes,
        (int) $_SESSION['user_id'],
        $fallback_iqd
    );

    $expense_types_kurdish = [
        'salary' => 'مووچە',
        'bonus' => 'بەخشیش',
        'overtime' => 'کاروانحیسابی',
        'advance' => 'پێشەکی',
        'deduction' => 'کەمکردنەوە',
        'penalty' => 'سزا',
        'overtime_payment' => 'پێدانی کاروانحیسابی',
    ];

    $expense_details = [];
    foreach ($expense_ids as $exp) {
        $detail = $expense_types_kurdish[$exp['type']] . ': ' . $exp['amount'] . ' د.ع';
        if ($exp['type'] === 'advance') {
            $detail .= ' (لە مووچە دەکەم)';
        }
        $expense_details[] = $detail;
    }

    $notification_message = "خەرجی کارمەند زیادکرا (کارمەند: $employee_name, مانگ: $expense_date)\n" . implode(', ', $expense_details);

    createDetailedNotification(
        $pdo,
        $_SESSION['user_id'],
        'insert',
        'employee_expenses',
        $expense_ids[0]['id'] ?? 0,
        $notification_message,
        null,
        [
            'employee_id' => $employee_id,
            'employee_name' => $employee_name,
            'expense_date' => $expense_date,
            'expenses' => $expense_ids,
        ],
        ['action_type' => 'employee_expense_creation'],
        getUserIP()
    );

    $stmt = $pdo->prepare('SELECT COALESCE(payable_balance, 0) as payable_balance, COALESCE(receivable_balance, 0) as receivable_balance FROM employees WHERE id = ?');
    $stmt->execute([$employee_id]);
    $updated_balances = $stmt->fetch(PDO::FETCH_ASSOC);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'خەرجی کارمەند بە سەرکەوتوویی زیادکرا',
        'expense_ids' => $expense_ids,
        'updated_balances' => [
            'payable' => floatval($updated_balances['payable_balance'] ?? 0),
            'receivable' => floatval($updated_balances['receivable_balance'] ?? 0),
        ],
    ]);
} catch (RuntimeException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('PDOException in employee_payments/add_expense.php: ' . $e->getMessage());
    $msg = $e->getMessage();
    if (strpos($msg, 'Unknown column') !== false && strpos($msg, 'amount_usd') !== false) {
        $msg .= ' — تکایە database/migrations/employee_expenses_multicurrency_cashbox.sql جێبەجێ بکە.';
    }
    echo json_encode(['success' => false, 'message' => 'هەڵە لە زیادکردنی خەرجی کارمەند: ' . $msg]);
}
