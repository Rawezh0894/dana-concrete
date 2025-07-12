<?php
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';
if (!hasPermission('view_person_other_expenses')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'msg' => 'ڕێگە پێنەدراو']);
    exit;
}
$stmt = $pdo->query("SELECT id, name, expense_usd, expense_iqd, opening_debt_usd, opening_debt_iqd FROM other_expense_persons ORDER BY name ASC");
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
$persons = [];
foreach ($data as $row) {
    $persons[] = [
        'id' => $row['id'],
        'name' => $row['name'],
        'expense_usd' => $row['expense_usd'],
        'expense_iqd' => $row['expense_iqd'],
        'opening_debt_usd' => $row['opening_debt_usd'],
        'opening_debt_iqd' => $row['opening_debt_iqd'],
    ];
}
echo json_encode($persons);
