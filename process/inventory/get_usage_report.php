<?php
require_once '../../config/db_conected.php';

header('Content-Type: application/json');

try {
    $vehicle_id = $_GET['vehicle_id'] ?? '';
    $category = $_GET['category'] ?? '';
    $from_date = $_GET['from_date'] ?? '';
    $to_date = $_GET['to_date'] ?? '';

    // Current/Default exchange rate for table display only (conversions)
    $current_ex_rate = 148500;

    $params_iss = [];
    $params_oe = [];
    $where_iss = ["1=1"];
    $where_oe  = ["1=1"];

    if (!empty($vehicle_id)) {
        $where_iss[] = "iss.vehicle_id = ?";
        $where_oe[]  = "oe.car_id = ?";
        $params_iss[] = $vehicle_id;
        $params_oe[] = $vehicle_id;
    }

    if (!empty($from_date)) {
        $where_iss[] = "iss.issued_date >= ?";
        $where_oe[]  = "oe.date >= ?";
        $params_iss[] = $from_date;
        $params_oe[] = $from_date;
    }

    if (!empty($to_date)) {
        $where_iss[] = "iss.issued_date <= ?";
        $where_oe[]  = "oe.date <= ?";
        $params_iss[] = $to_date;
        $params_oe[] = $to_date;
    }

    // Query 1: Inventory Issuances
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

    // Query 2: Other Expenses
    $oe_sql = "SELECT oe.*, c.name as vehicle_name
               FROM other_expenses oe
               JOIN cars c ON oe.car_id = c.id
               WHERE " . implode(" AND ", $where_oe);
    
    if (!empty($category)) {
        $oe_sql .= " AND (oe.expense_type = ? OR oe.purpose LIKE ?)";
        $params_oe[] = $category;
        $params_oe[] = "%$category%";
    }

    $stmt_oe = $pdo->prepare($oe_sql);
    $stmt_oe->execute($params_oe);
    $other_expenses = $stmt_oe->fetchAll(PDO::FETCH_ASSOC);

    $combined_data = [];
    $total_pure_usd = 0;
    $total_pure_iqd = 0;

    // Process Issuances
    foreach ($issuances as $row) {
        $line_usd = floatval($row['qty']) * floatval($row['cost_usd_at_time']);
        $line_iqd = $line_usd * ($current_ex_rate / 100);
        
        $combined_data[] = [
            'date' => $row['issued_date'],
            'name' => $row['item_name'],
            'category' => 'بەشی یەدەگ: ' . $row['item_category'],
            'vehicle' => $row['vehicle_name'],
            'qty' => $row['qty'] . ' ' . $row['unit'],
            'cost_usd' => $line_usd,
            'cost_iqd' => $line_iqd,
            'type' => 'کاڵا بەکارهاتن'
        ];
        // Spare parts are always USD
        $total_pure_usd += $line_usd;
    }

    // Process Other Expenses
    foreach ($other_expenses as $row) {
        $row_ex_rate = floatval($row['exchange_rate'] ?: $current_ex_rate);
        $display_usd = 0;
        $display_iqd = 0;

        if (floatval($row['amount_usd']) > 0) {
            $display_usd = floatval($row['amount_usd']);
            $display_iqd = $display_usd * ($row_ex_rate / 100);
            $total_pure_usd += $display_usd;
        } else if (floatval($row['amount_iqd']) > 0) {
            $display_iqd = floatval($row['amount_iqd']);
            $display_usd = $display_iqd / ($row_ex_rate / 100);
            $total_pure_iqd += $display_iqd;
        } else if (floatval($row['gas_total_cost']) > 0) {
            $display_iqd = floatval($row['gas_total_cost']);
            $display_usd = $display_iqd / ($row_ex_rate / 100);
            $total_pure_iqd += $display_iqd;
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
            'cost_usd' => $display_usd,
            'cost_iqd' => $display_iqd,
            'type' => 'خەرجی گشتی'
        ];
    }

    usort($combined_data, function($a, $b) {
        return strcmp($b['date'], $a['date']);
    });

    echo json_encode([
        'success' => true,
        'data' => $combined_data,
        'total_usd' => $total_pure_usd,
        'total_iqd' => $total_pure_iqd
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
}
?>
