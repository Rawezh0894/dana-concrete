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
    echo json_encode(['success' => false, 'msg' => 'ڕێگەپێدراوە بۆ نوێکردنەوەی خەرجی']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'POST only']);
    exit;
}

$id = intval($_POST['id'] ?? 0);
$employee_id = intval($_POST['employee_id'] ?? 0);
$expense_date = trim($_POST['expense_date'] ?? '');
$expense_type = trim($_POST['expense_type'] ?? '');
$amount = floatval($_POST['amount'] ?? 0);
$notes = trim($_POST['notes'] ?? '');

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'هەڵەی ID']);
    exit;
}

if ($employee_id <= 0 || $expense_date === '' || $expense_type === '') {
    echo json_encode(['success' => false, 'message' => 'کارمەند، بەروار و جۆری خەرجی پێویستە']);
    exit;
}

if ($amount <= 0) {
    echo json_encode(['success' => false, 'message' => 'بڕی خەرجی پێویستە']);
    exit;
}

// Validate expense type
$valid_types = ['salary', 'bonus', 'overtime', 'advance', 'deduction', 'penalty'];
if (!in_array($expense_type, $valid_types)) {
    echo json_encode(['success' => false, 'message' => 'جۆری خەرجی نادروستە']);
    exit;
}

try {
    $pdo->beginTransaction();
    
    // Get old record before update
    $stmt = $pdo->prepare('SELECT ee.*, e.name as employee_name FROM employee_expenses ee LEFT JOIN employees e ON ee.employee_id = e.id WHERE ee.id = ?');
    $stmt->execute([$id]);
    $old_record = $stmt->fetch();
    
    if (!$old_record) {
        echo json_encode(['success' => false, 'message' => 'خەرجی نەدۆزرایەوە']);
        exit;
    }
    
    // Get employee information
    $stmt = $pdo->prepare("SELECT name FROM employees WHERE id = ?");
    $stmt->execute([$employee_id]);
    $employee = $stmt->fetch();
    $employee_name = $employee['name'] ?? 'Unknown';
    
    // Update the expense record
    $stmt = $pdo->prepare('UPDATE employee_expenses SET employee_id=?, expense_type=?, amount=?, notes=?, expense_date=?, updated_at=NOW() WHERE id=?');
    $stmt->execute([$employee_id, $expense_type, $amount, $notes, $expense_date, $id]);
    
    // Create notification
    $expense_types_kurdish = [
        'salary' => 'مووچە',
        'bonus' => 'بەخشیش',
        'overtime' => 'کاروانحیسابی',
        'advance' => 'پێشەکی',
        'deduction' => 'کەمکردنەوە',
        'penalty' => 'سزا'
    ];
    
    $old_values = [
        'employee_id' => $old_record['employee_id'],
        'employee_name' => $old_record['employee_name'] ?? 'Unknown',
        'expense_type' => $old_record['expense_type'],
        'expense_type_kurdish' => $expense_types_kurdish[$old_record['expense_type']] ?? $old_record['expense_type'],
        'amount' => $old_record['amount'],
        'expense_date' => $old_record['expense_date'],
        'notes' => $old_record['notes']
    ];
    
    $new_values = [
        'employee_id' => $employee_id,
        'employee_name' => $employee_name,
        'expense_type' => $expense_type,
        'expense_type_kurdish' => $expense_types_kurdish[$expense_type] ?? $expense_type,
        'amount' => $amount,
        'expense_date' => $expense_date,
        'notes' => $notes
    ];
    
    $notification_message = "خەرجی کارمەند نوێکرایەوە (ID: $id)\n";
    $notification_message .= "کۆن: " . $old_values['expense_type_kurdish'] . " - " . $old_values['amount'] . " د.ع\n";
    $notification_message .= "نوێ: " . $new_values['expense_type_kurdish'] . " - " . $new_values['amount'] . " د.ع";
    
    createDetailedNotification(
        $pdo,
        $_SESSION['user_id'],
        'update',
        'employee_expenses',
        $id,
        $notification_message,
        $old_values,
        $new_values,
        ['action_type' => 'employee_expense_update'],
        getUserIP()
    );
    
    // Get updated balances after update
    $stmt = $pdo->prepare("SELECT COALESCE(payable_balance, 0) as payable_balance, COALESCE(receivable_balance, 0) as receivable_balance FROM employees WHERE id = ?");
    $stmt->execute([$employee_id]);
    $updated_balances = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'خەرجی کارمەند بە سەرکەوتوویی نوێکرایەوە',
        'updated_balances' => [
            'payable' => floatval($updated_balances['payable_balance'] ?? 0),
            'receivable' => floatval($updated_balances['receivable_balance'] ?? 0)
        ]
    ]);
    
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('PDOException in employee_payments/update_expense.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'هەڵە لە نوێکردنەوەی خەرجی کارمەند: ' . $e->getMessage()]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Exception in employee_payments/update_expense.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'هەڵە لە نوێکردنەوەی خەرجی کارمەند: ' . $e->getMessage()]);
}
