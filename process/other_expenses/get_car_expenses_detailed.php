<?php
require_once '../../config/db_conected.php';
header('Content-Type: application/json');

try {
    // Get filter parameters
    $dateFrom = $_POST['date_from'] ?? '';
    $dateTo = $_POST['date_to'] ?? '';
    $monthFilter = $_POST['month_filter'] ?? '';
    $carFilter = $_POST['car_filter'] ?? '';
    $employeeFilter = $_POST['employee_filter'] ?? '';
    $personFilter = $_POST['person_filter'] ?? '';
    $expenseTypeOther = $_POST['expense_type_other'] ?? '0';
    $expenseTypeMaterial = $_POST['expense_type_material'] ?? '0';
    $expenseTypeGas = $_POST['expense_type_gas'] ?? '0';
    $expenseTypeKhwardnga = $_POST['expense_type_khwardnga'] ?? '0';
    $expenseTypeOffice = $_POST['expense_type_office'] ?? '0';
    $export = $_POST['export'] ?? '';

    // Build WHERE conditions
    $whereConditions = [];
    $params = [];

    if ($dateFrom) {
        $whereConditions[] = "oe.date >= ?";
        $params[] = $dateFrom;
    }
    
    if ($dateTo) {
        $whereConditions[] = "oe.date <= ?";
        $params[] = $dateTo;
    }
    
    if ($monthFilter) {
        $whereConditions[] = "DATE_FORMAT(oe.date, '%Y-%m') = ?";
        $params[] = $monthFilter;
    }
    
    if ($carFilter) {
        $whereConditions[] = "oe.car_id = ?";
        $params[] = $carFilter;
    }
    
    if ($employeeFilter) {
        $whereConditions[] = "oe.employee_id = ?";
        $params[] = $employeeFilter;
    }
    
    if ($personFilter) {
        $whereConditions[] = "oe.person_id = ?";
        $params[] = $personFilter;
    }

    // Build expense type conditions
    $expenseTypeConditions = [];
    if ($expenseTypeOther === '1') $expenseTypeConditions[] = "'خەرجی تر'";
    if ($expenseTypeMaterial === '1') $expenseTypeConditions[] = "'بەکارهێنانی کاڵای کۆگا'";
    if ($expenseTypeGas === '1') $expenseTypeConditions[] = "'بەکارهێنانی گاز'";
    if ($expenseTypeKhwardnga === '1') $expenseTypeConditions[] = "'خواردنگە'";
    if ($expenseTypeOffice === '1') $expenseTypeConditions[] = "'ئۆفیس'";
    
    if (!empty($expenseTypeConditions)) {
        $whereConditions[] = "oe.expense_type IN (" . implode(',', $expenseTypeConditions) . ")";
    }

    $whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

    // Get detailed car expenses
    $detailedQuery = "
        SELECT 
            c.name as car_name,
            oe.expense_type,
            oe.gas_liters,
            oe.gas_total_cost,
            m.name as material_name,
            oe.material_quantity,
            oe.material_total_cost,
            oe.amount_iqd,
            oe.amount_usd,
            oe.date,
            (COALESCE(oe.gas_total_cost, 0) + COALESCE(oe.material_total_cost, 0) + COALESCE(oe.amount_iqd, 0) + COALESCE(oe.amount_usd, 0)) as total_cost
        FROM other_expenses oe
        LEFT JOIN cars c ON oe.car_id = c.id
        LEFT JOIN materials m ON oe.material_id = m.id
        $whereClause
        ORDER BY c.name, oe.date DESC
    ";

    $stmt = $pdo->prepare($detailedQuery);
    $stmt->execute($params);
    $detailedExpenses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get car summary data
    $carSummaryQuery = "
        SELECT 
            c.id as car_id,
            c.name as car_name,
            SUM(CASE WHEN oe.expense_type = 'بەکارهێنانی گاز' THEN COALESCE(oe.gas_total_cost, 0) ELSE 0 END) as gas_cost,
            SUM(CASE WHEN oe.expense_type = 'بەکارهێنانی کاڵای کۆگا' THEN COALESCE(oe.material_total_cost, 0) ELSE 0 END) as material_cost,
            SUM(CASE WHEN oe.expense_type NOT IN ('بەکارهێنانی گاز', 'بەکارهێنانی کاڵای کۆگا') THEN COALESCE(oe.amount_iqd, 0) + COALESCE(oe.amount_usd, 0) ELSE 0 END) as other_expenses
        FROM cars c
        LEFT JOIN other_expenses oe ON c.id = oe.car_id $whereClause
        GROUP BY c.id, c.name
        ORDER BY c.name
    ";

    $stmt = $pdo->prepare($carSummaryQuery);
    $stmt->execute($params);
    $carSummary = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get concrete volume for each car
    $concreteVolumeQuery = "
        SELECT 
            cr.mixer_car_id as car_id,
            SUM(cr.meter_amount) as concrete_volume
        FROM concrete_receipts cr
        WHERE cr.mixer_car_id IS NOT NULL
        GROUP BY cr.mixer_car_id
    ";

    $stmt = $pdo->query($concreteVolumeQuery);
    $concreteVolumes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Create a map of car_id to concrete volume
    $concreteVolumeMap = [];
    foreach ($concreteVolumes as $cv) {
        $concreteVolumeMap[$cv['car_id']] = $cv['concrete_volume'];
    }

    // Process car summary data
    $processedCars = [];
    $totalSummary = [
        'total_gas_cost' => 0,
        'total_material_cost' => 0,
        'total_other_expenses' => 0,
        'total_without_gas' => 0,
        'total_with_gas' => 0,
        'total_concrete_volume' => 0,
        'total_income' => 0
    ];

    foreach ($carSummary as $car) {
        $gasCost = floatval($car['gas_cost'] || 0);
        $materialCost = floatval($car['material_cost'] || 0);
        $otherExpenses = floatval($car['other_expenses'] || 0);
        $totalWithoutGas = $materialCost + $otherExpenses;
        $totalWithGas = $gasCost + $materialCost + $otherExpenses;
        $concreteVolume = floatval($concreteVolumeMap[$car['car_id']] || 0);
        
        // Calculate income: (concrete volume × 5) - total expenses
        $income = ($concreteVolume * 5) - $totalWithGas;

        $processedCars[] = [
            'car_name' => $car['car_name'],
            'gas_cost' => $gasCost,
            'material_cost' => $materialCost,
            'other_expenses' => $otherExpenses,
            'total_without_gas' => $totalWithoutGas,
            'total_with_gas' => $totalWithGas,
            'concrete_volume' => $concreteVolume,
            'income' => $income
        ];

        // Update totals
        $totalSummary['total_gas_cost'] += $gasCost;
        $totalSummary['total_material_cost'] += $materialCost;
        $totalSummary['total_other_expenses'] += $otherExpenses;
        $totalSummary['total_without_gas'] += $totalWithoutGas;
        $totalSummary['total_with_gas'] += $totalWithGas;
        $totalSummary['total_concrete_volume'] += $concreteVolume;
        $totalSummary['total_income'] += $income;
    }

    // Prepare response data
    $responseData = [
        'success' => true,
        'data' => [
            'cars' => $processedCars,
            'summary' => $totalSummary,
            'detailed_expenses' => $detailedExpenses
        ]
    ];

    echo json_encode($responseData);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'هەڵە لە وەرگرتنی زانیاری: ' . $e->getMessage()
    ]);
}
?>
