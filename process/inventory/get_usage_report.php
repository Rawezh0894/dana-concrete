<?php
require_once '../../config/db_conected.php';

header('Content-Type: application/json');

try {
    $vehicle_id = $_GET['vehicle_id'] ?? '';
    $category = $_GET['category'] ?? '';
    $from_date = $_GET['from_date'] ?? '';
    $to_date = $_GET['to_date'] ?? '';

    $params = [];
    $where_iss = ["1=1"];
    $where_oe  = ["1=1"];

    if (!empty($vehicle_id)) {
        $where_iss[] = "iss.vehicle_id = ?";
        $where_oe[]  = "oe.car_id = ?";
        $params[] = $vehicle_id;
    }

    if (!empty($from_date)) {
        $where_iss[] = "iss.issued_date >= ?";
        $where_oe[]  = "oe.date >= ?";
        $params[] = $from_date;
    }

    if (!empty($to_date)) {
        $where_iss[] = "iss.issued_date <= ?";
        $where_oe[]  = "oe.date <= ?";
        $params[] = $to_date;
    }

    // Since we use the same params for both but they might have different count of same-named variables
    // It's safer to have separate param arrays for each query
    $params_iss = [];
    $params_oe = [];
    if(!empty($vehicle_id)) { $params_iss[] = $vehicle_id; $params_oe[] = $vehicle_id; }
    if(!empty($from_date)) { $params_iss[] = $from_date; $params_oe[] = $from_date; }
    if(!empty($to_date)) { $params_iss[] = $to_date; $params_oe[] = $to_date; }

    // Query 1: Inventory Issuances (Spare Parts)
    // We only filter by category here because other_expenses category is different
    $iss_sql = "SELECT iss.*, i.name as item_name, i.category as item_category, i.unit, c.name as vehicle_name
                FROM inv_issuance iss
                JOIN inv_items i ON iss.item_id = i.id
                JOIN cars c ON iss.vehicle_id = c.id
                WHERE " . implode(" AND ", $where_iss);
    
    if (!empty($category)) {
        $iss_sql .= " AND i.category = ?";
        $params_iss[] = $category;
    }
    
    $stmt_iss = $pdo->prepare($iss_sql);
    $stmt_iss->execute($params_iss);
    $issuances = $stmt_iss->fetchAll(PDO::FETCH_ASSOC);

    // Query 2: Other Expenses (Fuel, Maintenance, etc.)
    $oe_sql = "SELECT oe.*, c.name as vehicle_name
               FROM other_expenses oe
               JOIN cars c ON oe.car_id = c.id
               WHERE " . implode(" AND ", $where_oe);
    
    // If category filter is applied, we check it against expense_type
    if (!empty($category)) {
        $oe_sql .= " AND (oe.expense_type = ? OR oe.purpose LIKE ?)";
        $params_oe[] = $category;
        $params_oe[] = "%$category%";
    }

    $stmt_oe = $pdo->prepare($oe_sql);
    $stmt_oe->execute($params_oe);
    $other_expenses = $stmt_oe->fetchAll(PDO::FETCH_ASSOC);

    $combined_data = [];
    $total_cost = 0;

    // Process Issuances
    foreach ($issuances as $row) {
        $line_cost = floatval($row['qty']) * floatval($row['cost_usd_at_time']);
        $combined_data[] = [
            'date' => $row['issued_date'],
            'name' => $row['item_name'],
            'category' => 'بەشی یەدەگ: ' . $row['item_category'],
            'vehicle' => $row['vehicle_name'],
            'qty' => $row['qty'] . ' ' . $row['unit'],
            'unit_price' => $row['cost_usd_at_time'],
            'total_cost' => $line_cost,
            'type' => 'گۆڕینی پارچە'
        ];
        $total_cost += $line_cost;
    }

    // Process Other Expenses
    foreach ($other_expenses as $row) {
        // Convert to USD if it's IQD
        $cost_usd = 0;
        if (floatval($row['amount_usd']) > 0) {
            $cost_usd = floatval($row['amount_usd']);
        } else if (floatval($row['amount_iqd']) > 0) {
            $ex_rate = floatval($row['exchange_rate'] ?: 150000);
            $cost_usd = floatval($row['amount_iqd']) / ($ex_rate / 100);
        } else if (floatval($row['gas_total_cost']) > 0) {
            // For Gas, it might be in IQD in gas_total_cost column usually
            // However, looking at summary cards in other_expenses.php, gas is often IQD.
            // Let's check how they handle it.
            $ex_rate = floatval($row['exchange_rate'] ?: 150000);
            $cost_usd = floatval($row['gas_total_cost']) / ($ex_rate / 100);
        }

        $qty_str = '-';
        if ($row['expense_type'] == 'بەکارهێنانی گاز') {
            $qty_str = $row['gas_liters'] . ' لیتر';
        }

        $combined_data[] = [
            'date' => $row['date'],
            'name' => $row['purpose'] ?: $row['expense_type'],
            'category' => $row['expense_type'],
            'vehicle' => $row['vehicle_name'],
            'qty' => $qty_str,
            'unit_price' => $qty_str != '-' ? ($cost_usd / (floatval($row['gas_liters']) ?: 1)) : $cost_usd,
            'total_cost' => $cost_usd,
            'type' => 'خەرجی گشتی'
        ];
        $total_cost += $cost_usd;
    }

    // Sort combined data by date DESC
    usort($combined_data, function($a, $b) {
        return strcmp($b['date'], $a['date']);
    });

    echo json_encode([
        'success' => true,
        'data' => $combined_data,
        'total_cost' => $total_cost
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
}
?>
