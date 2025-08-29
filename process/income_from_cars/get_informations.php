<?php
session_start();
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if (!hasPermission('view_income_from_cars')) {
    echo json_encode(['success' => false, 'error' => 'ڕێگەت پێنەدراوە!']);
    exit;
}

try {
    // Get filter parameters
    $fromDate = $_POST['from_date'] ?? '';
    $toDate = $_POST['to_date'] ?? '';
    $mixerCar = $_POST['mixer_car'] ?? '';
    $mixerDriver = $_POST['mixer_driver'] ?? '';
    $pumpCar = $_POST['pump_car'] ?? '';
    $pumpDriver = $_POST['pump_driver'] ?? '';
    $customer = $_POST['customer'] ?? '';
    $export = $_POST['export'] ?? '';

    // Build WHERE conditions
    $whereConditions = [];
    $params = [];

    if ($fromDate) {
        $whereConditions[] = "cr.created_at >= ?";
        $params[] = $fromDate . ' 00:00:00';
    }
    
    if ($toDate) {
        $whereConditions[] = "cr.created_at <= ?";
        $params[] = $toDate . ' 23:59:59';
    }
    
    if ($mixerCar) {
        $whereConditions[] = "cr.mixer_car_id = ?";
        $params[] = $mixerCar;
    }
    
    if ($mixerDriver) {
        $whereConditions[] = "cr.mixer_driver_id = ?";
        $params[] = $mixerDriver;
    }
    
    if ($pumpCar) {
        $whereConditions[] = "cr.pump_car_id = ?";
        $params[] = $pumpCar;
    }
    
    if ($pumpDriver) {
        $whereConditions[] = "cr.pump_driver_id = ?";
        $params[] = $pumpDriver;
    }
    
    if ($customer) {
        $whereConditions[] = "cr.customer_id = ?";
        $params[] = $customer;
    }

    $whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

    if ($export === 'excel') {
        // Get detailed data for Excel export
        $carsQuery = "
            SELECT 
                c.id as car_id,
                c.name as car_name,
                SUM(CASE WHEN oe.expense_type = 'بەکارهێنانی گاز' THEN COALESCE(oe.gas_total_cost, 0) ELSE 0 END) as gas_cost,
                SUM(CASE WHEN oe.expense_type = 'بەکارهێنانی کاڵای کۆگا' THEN COALESCE(oe.material_total_cost, 0) ELSE 0 END) as material_cost,
                SUM(CASE WHEN oe.expense_type NOT IN ('بەکارهێنانی گاز', 'بەکارهێنانی کاڵای کۆگا') THEN COALESCE(oe.amount_iqd, 0) + COALESCE(oe.amount_usd, 0) ELSE 0 END) as other_expenses,
                SUM(cr.meter_amount) as concrete_volume
            FROM cars c
            LEFT JOIN other_expenses oe ON c.id = oe.car_id $whereClause
            LEFT JOIN concrete_receipts cr ON c.id = cr.mixer_car_id $whereClause
            GROUP BY c.id, c.name
            ORDER BY c.name
        ";

        $stmt = $pdo->prepare($carsQuery);
        $stmt->execute($params);
        $cars = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Calculate summary totals
        $summary = [
            'total_gas_cost' => 0,
            'total_material_cost' => 0,
            'total_other_expenses' => 0,
            'total_without_gas' => 0,
            'total_with_gas' => 0,
            'total_concrete_volume' => 0,
            'total_income' => 0
        ];

        foreach ($cars as $car) {
            $gasCost = floatval($car['gas_cost'] || 0);
            $materialCost = floatval($car['material_cost'] || 0);
            $otherExpenses = floatval($car['other_expenses'] || 0);
            $totalWithoutGas = $materialCost + $otherExpenses;
            $totalWithGas = $gasCost + $materialCost + $otherExpenses;
            $concreteVolume = floatval($car['concrete_volume'] || 0);
            
            // Calculate income: (concrete volume × 5) - total expenses
            $income = ($concreteVolume * 5) - $totalWithGas;

            $summary['total_gas_cost'] += $gasCost;
            $summary['total_material_cost'] += $materialCost;
            $summary['total_other_expenses'] += $otherExpenses;
            $summary['total_without_gas'] += $totalWithoutGas;
            $summary['total_with_gas'] += $totalWithGas;
            $summary['total_concrete_volume'] += $concreteVolume;
            $summary['total_income'] += $income;
        }

        $responseData = [
            'success' => true,
            'data' => [
                'cars' => $cars,
                'summary' => $summary
            ]
        ];

        echo json_encode($responseData);
        exit;
    }

    // Get basic data for normal display
    $receiptsQuery = "
        SELECT 
            cr.receipt_number,
            cu.name as customer_name,
            cr.location,
            cr.meter_amount,
            mc.name as mixer_car_name,
            md.name as mixer_driver_name,
            pc.name as pump_car_name,
            pd.name as pump_driver_name,
            cr.created_at,
            cr.receiver_name
        FROM concrete_receipts cr
        LEFT JOIN customers cu ON cr.customer_id = cu.id
        LEFT JOIN cars mc ON cr.mixer_car_id = mc.id
        LEFT JOIN employees md ON cr.mixer_driver_id = md.id
        LEFT JOIN cars pc ON cr.pump_car_id = pc.id
        LEFT JOIN employees pd ON cr.pump_driver_id = pd.id
        $whereClause
        ORDER BY cr.created_at DESC
    ";

    $stmt = $pdo->prepare($receiptsQuery);
    $stmt->execute($params);
    $receipts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get summary statistics
    $totalCars = $pdo->query("SELECT COUNT(*) FROM cars")->fetchColumn();
    $totalDrivers = $pdo->query("SELECT COUNT(*) FROM employees WHERE role = 'شۆفێر'")->fetchColumn();
    $totalMeters = $pdo->query("SELECT SUM(meter_amount) FROM concrete_receipts")->fetchColumn() ?: 0;
    $totalReceipts = $pdo->query("SELECT COUNT(*) FROM concrete_receipts")->fetchColumn();

    $responseData = [
        'success' => true,
        'data' => [
            'receipts' => $receipts,
            'summary' => [
                'total_cars' => $totalCars,
                'total_drivers' => $totalDrivers,
                'total_meters' => $totalMeters,
                'total_receipts' => $totalReceipts
            ]
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
