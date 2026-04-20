<?php
require_once '../../config/db_conected.php';

header('Content-Type: application/json');

try {
    $vehicle_id = $_GET['vehicle_id'] ?? '';
    $category = $_GET['category'] ?? '';
    $from_date = $_GET['from_date'] ?? '';
    $to_date = $_GET['to_date'] ?? '';

    // Fetch a default/current exchange rate for spare parts conversion
    $ex_stmt = $pdo->query("SELECT rate FROM currency_rates ORDER BY id DESC LIMIT 1");
    $current_ex_rate = $ex_stmt->fetchColumn() ?: 150000;

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

    // Query 1: Inventory Issuances (Spare Parts)
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
    $total_usd = 0;
    $total_iqd = 0;

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
            'type' => 'گۆڕینی پارچە'
        ];
        $total_usd += $line_usd;
        $total_iqd += $line_iqd;
    }

    // Process Other Expenses
    foreach ($other_expenses as $row) {
        $line_usd = 0;
        $line_iqd = 0;
        $row_ex_rate = floatval($row['exchange_rate'] ?: $current_ex_rate);

        if (floatval($row['amount_usd']) > 0) {
            $line_usd = floatval($row['amount_usd']);
            $line_iqd = $line_usd * ($row_ex_rate / 100);
        } else if (floatval($row['amount_iqd']) > 0) {
            $line_iqd = floatval($row['amount_iqd']);
            $line_usd = $line_iqd / ($row_ex_rate / 100);
        } else if (floatval($row['gas_total_cost']) > 0) {
            $line_iqd = floatval($row['gas_total_cost']);
            $line_usd = $line_iqd / ($row_ex_rate / 100);
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
            'cost_usd' => $line_usd,
            'cost_iqd' => $line_iqd,
            'type' => 'خەرجی گشتی'
        ];
        $total_usd += $line_usd;
        $total_iqd += $line_iqd;
    }

    usort($combined_data, function($a, $b) {
        return strcmp($b['date'], $a['date']);
    });

    echo json_encode([
        'success' => true,
        'data' => $combined_data,
        'total_usd' => $total_usd,
        'total_iqd' => $total_iqd
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
}
?>
