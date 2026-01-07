<?php
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';
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
$expense_date = trim($_POST['expense_date'] ?? ''); // Format: YYYY-MM
$notes = trim($_POST['notes'] ?? '');

// Get expense amounts
$salary = floatval($_POST['salary'] ?? 0);
$bonus = floatval($_POST['bonus'] ?? 0);
$overtime = floatval($_POST['overtime'] ?? 0);
$advance = floatval($_POST['advance'] ?? 0);
$deduction = floatval($_POST['deduction'] ?? 0);
$penalty = floatval($_POST['penalty'] ?? 0);

if ($employee_id <= 0 || $expense_date === '') {
    echo json_encode(['success' => false, 'message' => 'کارمەند و بەروار پێویستە']);
    exit;
}

// Check if at least one expense type has a value
$total_expenses = $salary + $bonus + $overtime + $advance + $deduction + $penalty;
if ($total_expenses <= 0) {
    echo json_encode(['success' => false, 'message' => 'لانیکەم یەک جۆری خەرجی پێویستە']);
    exit;
}

try {
    $pdo->beginTransaction();
    
    // Get employee information
    $stmt = $pdo->prepare("SELECT name FROM employees WHERE id = ?");
    $stmt->execute([$employee_id]);
    $employee = $stmt->fetch();
    $employee_name = $employee['name'] ?? 'Unknown';
    
    $expense_ids = [];
    
    // Insert salary
    if ($salary > 0) {
        $stmt = $pdo->prepare('INSERT INTO employee_expenses (employee_id, expense_type, amount, notes, created_by, expense_date) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$employee_id, 'salary', $salary, $notes, $_SESSION['user_id'], $expense_date]);
        $expense_ids[] = ['type' => 'salary', 'id' => $pdo->lastInsertId(), 'amount' => $salary];
    }
    
    // Insert bonus
    if ($bonus > 0) {
        $stmt = $pdo->prepare('INSERT INTO employee_expenses (employee_id, expense_type, amount, notes, created_by, expense_date) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$employee_id, 'bonus', $bonus, $notes, $_SESSION['user_id'], $expense_date]);
        $expense_ids[] = ['type' => 'bonus', 'id' => $pdo->lastInsertId(), 'amount' => $bonus];
    }
    
    // Insert overtime
    if ($overtime > 0) {
        $stmt = $pdo->prepare('INSERT INTO employee_expenses (employee_id, expense_type, amount, notes, created_by, expense_date) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$employee_id, 'overtime', $overtime, $notes, $_SESSION['user_id'], $expense_date]);
        $expense_ids[] = ['type' => 'overtime', 'id' => $pdo->lastInsertId(), 'amount' => $overtime];
    }
    
    // Insert advance
    if ($advance > 0) {
        $stmt = $pdo->prepare('INSERT INTO employee_expenses (employee_id, expense_type, amount, notes, created_by, expense_date) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$employee_id, 'advance', $advance, $notes, $_SESSION['user_id'], $expense_date]);
        $expense_ids[] = ['type' => 'advance', 'id' => $pdo->lastInsertId(), 'amount' => $advance];
    }
    
    // Insert deduction
    if ($deduction > 0) {
        $stmt = $pdo->prepare('INSERT INTO employee_expenses (employee_id, expense_type, amount, notes, created_by, expense_date) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$employee_id, 'deduction', $deduction, $notes, $_SESSION['user_id'], $expense_date]);
        $expense_ids[] = ['type' => 'deduction', 'id' => $pdo->lastInsertId(), 'amount' => $deduction];
    }
    
    // Insert penalty
    if ($penalty > 0) {
        $stmt = $pdo->prepare('INSERT INTO employee_expenses (employee_id, expense_type, amount, notes, created_by, expense_date) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$employee_id, 'penalty', $penalty, $notes, $_SESSION['user_id'], $expense_date]);
        $expense_ids[] = ['type' => 'penalty', 'id' => $pdo->lastInsertId(), 'amount' => $penalty];
    }
    
    // Create notification
    $expense_types_kurdish = [
        'salary' => 'مووچە',
        'bonus' => 'بەخشیش',
        'overtime' => 'کاروانحیسابی',
        'advance' => 'پێشەکی',
        'deduction' => 'کەمکردنەوە',
        'penalty' => 'سزا'
    ];
    
    $expense_details = [];
    foreach ($expense_ids as $exp) {
        $detail = $expense_types_kurdish[$exp['type']] . ': ' . $exp['amount'] . ' د.ع';
        // Add note for advance
        if ($exp['type'] == 'advance') {
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
            'expenses' => $expense_ids
        ],
        ['action_type' => 'employee_expense_creation'],
        getUserIP()
    );
    
    // Get updated balances after insert
    $stmt = $pdo->prepare("SELECT COALESCE(payable_balance, 0) as payable_balance, COALESCE(receivable_balance, 0) as receivable_balance FROM employees WHERE id = ?");
    $stmt->execute([$employee_id]);
    $updated_balances = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => 'خەرجی کارمەند بە سەرکەوتوویی زیادکرا',
        'expense_ids' => $expense_ids,
        'updated_balances' => [
            'payable' => floatval($updated_balances['payable_balance'] ?? 0),
            'receivable' => floatval($updated_balances['receivable_balance'] ?? 0)
        ]
    ]);
    
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('PDOException in employee_payments/add_expense.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'هەڵە لە زیادکردنی خەرجی کارمەند: ' . $e->getMessage()]);
}

