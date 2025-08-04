<?php
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';
if (!hasPermission('view_person_other_expenses')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'msg' => 'ڕێگە پێنەدراو']);
    exit;
}

// Get persons data
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

// Calculate total debt from other_expenses (remaining amounts)
$other_expenses_debt = $pdo->query("
    SELECT 
        SUM(remaining_usd) as total_usd,
        SUM(remaining_iqd) as total_iqd
    FROM other_expenses 
    WHERE payment_type = 'قەرز'
")->fetch(PDO::FETCH_ASSOC);

// Calculate total debt from purchase_materials (remaining amounts)
$purchase_materials_debt = $pdo->query("
    SELECT 
        SUM(remaining_amount_usd) as total_usd,
        SUM(remaining_amount_iqd) as total_iqd
    FROM purchase_materials 
    WHERE payment_type = 'قەرز'
")->fetch(PDO::FETCH_ASSOC);

// Calculate total opening debt from persons
$persons_opening_debt = $pdo->query("
    SELECT 
        SUM(opening_debt_usd) as total_usd,
        SUM(opening_debt_iqd) as total_iqd
    FROM other_expense_persons
")->fetch(PDO::FETCH_ASSOC);

// Calculate total debt
$total_debt_usd = floatval($other_expenses_debt['total_usd'] ?? 0) + 
                  floatval($purchase_materials_debt['total_usd'] ?? 0) + 
                  floatval($persons_opening_debt['total_usd'] ?? 0);

$total_debt_iqd = floatval($other_expenses_debt['total_iqd'] ?? 0) + 
                  floatval($purchase_materials_debt['total_iqd'] ?? 0) + 
                  floatval($persons_opening_debt['total_iqd'] ?? 0);

$response = [
    'persons' => $persons,
    'summary' => [
        'total_debt_usd' => $total_debt_usd,
        'total_debt_iqd' => $total_debt_iqd,
        'other_expenses_debt' => [
            'usd' => floatval($other_expenses_debt['total_usd'] ?? 0),
            'iqd' => floatval($other_expenses_debt['total_iqd'] ?? 0)
        ],
        'purchase_materials_debt' => [
            'usd' => floatval($purchase_materials_debt['total_usd'] ?? 0),
            'iqd' => floatval($purchase_materials_debt['total_iqd'] ?? 0)
        ],
        'persons_opening_debt' => [
            'usd' => floatval($persons_opening_debt['total_usd'] ?? 0),
            'iqd' => floatval($persons_opening_debt['total_iqd'] ?? 0)
        ]
    ]
];

echo json_encode($response);
