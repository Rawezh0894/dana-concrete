<?php
// Disable error display to prevent HTML output in JSON response
ini_set('display_errors', 0);
error_reporting(E_ALL);
// Log errors instead of displaying them
ini_set('log_errors', 1);

require_once '../../config/db_conected.php';
header('Content-Type: application/json; charset=utf-8');
$customer_id = isset($_GET['customer_id']) ? intval($_GET['customer_id']) : 0;
if (!$customer_id) { 
    echo json_encode(['error' => 'Customer ID is required', 'sales_data' => []]); 
    exit; 
}

$type = isset($_GET['type']) ? $_GET['type'] : 'all';
$month = isset($_GET['month']) ? $_GET['month'] : 'all';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$location = isset($_GET['location']) ? $_GET['location'] : 'all';
$recipient = isset($_GET['recipient']) ? $_GET['recipient'] : 'all';

try {
    // Get customer information including opening debt, name, and mobile
    $customer_sql = "SELECT opening_debt_usd, name, mobile1 FROM customers WHERE id = :customer_id";
    $customer_stmt = $pdo->prepare($customer_sql);
    $customer_stmt->execute(['customer_id' => $customer_id]);
    $customer_data = $customer_stmt->fetch(PDO::FETCH_ASSOC);
    $opening_debt = is_numeric($customer_data['opening_debt_usd']) ? floatval($customer_data['opening_debt_usd']) : 0;
    $company_name = $customer_data['name'] ?? '';
    $mobile = $customer_data['mobile1'] ?? '';
    
    // Get unique locations from sales table for this customer
    $locations_sql = "SELECT DISTINCT location FROM sales WHERE customer_id = :customer_id AND location IS NOT NULL AND location != '' ORDER BY location ASC";
    $locations_stmt = $pdo->prepare($locations_sql);
    $locations_stmt->execute(['customer_id' => $customer_id]);
    $locations = $locations_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get unique recipients from sales table for this customer
    $recipients_sql = "SELECT DISTINCT recipient FROM sales WHERE customer_id = :customer_id AND recipient IS NOT NULL AND recipient != '' ORDER BY recipient ASC";
    $recipients_stmt = $pdo->prepare($recipients_sql);
    $recipients_stmt->execute(['customer_id' => $customer_id]);
    $recipients = $recipients_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Database error in select_sale.php: " . $e->getMessage());
    echo json_encode(['error' => 'Database error occurred', 'sales_data' => []]);
    exit;
}

$sql = "SELECT 
        s.order_date,
        s.location,
        f.strength_mpa, 
        f.strength_kg,
        SUM(s.quantity) as total_quantity,
        s.price_per_unit,
        SUM(s.total_price) as total_price_sum,
        GROUP_CONCAT(s.invoice_number ORDER BY s.invoice_number SEPARATOR ',') as invoice_numbers,
        SUM(s.remaining_amount) as total_remaining_amount
        FROM sales s 
        LEFT JOIN concrete_formulas f ON s.formula_id = f.id 
        WHERE s.customer_id = :customer_id";

if ($type === 'cash') {
    $sql .= " AND (s.remaining_amount = 0 OR s.remaining_amount IS NULL)";
} elseif ($type === 'debt') {
    $sql .= " AND s.remaining_amount > 0";
} elseif ($type === 'has_remaining') {
    $sql .= " AND s.remaining_amount > 0";
}

$params = ['customer_id' => $customer_id];
if ($month !== 'all') {
    $sql .= " AND MONTH(s.order_date) = :month";
    $params['month'] = $month;
}
if ($date_from) {
    $sql .= " AND s.order_date >= :date_from";
    $params['date_from'] = $date_from;
}
if ($date_to) {
    $sql .= " AND s.order_date <= :date_to";
    $params['date_to'] = $date_to;
}
if ($location !== 'all' && $location !== 'none') {
    // Handle multiple locations (comma-separated)
    if (strpos($location, ',') !== false) {
        $locations = explode(',', $location);
        $locationPlaceholders = [];
        foreach ($locations as $index => $loc) {
            $paramName = 'location_' . $index;
            $locationPlaceholders[] = ':' . $paramName;
            $params[$paramName] = trim($loc);
        }
        $sql .= " AND s.location IN (" . implode(',', $locationPlaceholders) . ")";
    } else {
        // Single location
        $sql .= " AND s.location = :location";
        $params['location'] = $location;
    }
}

// Add recipient filter
if ($recipient !== 'all' && $recipient !== 'none') {
    // Handle multiple recipients (comma-separated)
    if (strpos($recipient, ',') !== false) {
        $recipientArray = explode(',', $recipient);
        $recipientPlaceholders = [];
        foreach ($recipientArray as $index => $recv) {
            $paramName = 'recipient_' . $index;
            $recipientPlaceholders[] = ':' . $paramName;
            $params[$paramName] = trim($recv);
        }
        $sql .= " AND s.recipient IN (" . implode(',', $recipientPlaceholders) . ")";
    } else {
        // Single recipient
        $sql .= " AND s.recipient = :recipient_filter";
        $params['recipient_filter'] = $recipient;
    }
}

$sql .= " GROUP BY s.order_date, s.location, f.strength_mpa, f.strength_kg, s.price_per_unit";
$sql .= " ORDER BY s.order_date ASC";

// Debug: Log the SQL query
error_log("Receipt SQL Query: " . $sql);

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $data = [];

    // Debug: Log the number of rows before grouping
    $rowCount = $stmt->rowCount();
    error_log("Receipt rows before grouping: " . $rowCount);

    $totalQuantitySum = 0; // Track total quantity for summary
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $quantity = number_format($row['total_quantity'], 2) . ' م³';
    $rezh = $row['strength_mpa'] ? $row['strength_mpa'] . ' MPa' : ($row['strength_kg'] ? $row['strength_kg'] . ' Kg' : '');
    $ppu = is_numeric($row['price_per_unit']) ? '$' . number_format($row['price_per_unit'], 2, '.', ',') : '';
    $total = is_numeric($row['total_price_sum']) ? '$' . number_format($row['total_price_sum'], 2, '.', ',') : '';
    $remaining = is_numeric($row['total_remaining_amount']) ? '$' . number_format($row['total_remaining_amount'], 2, '.', ',') : '$0.00';
    
    // Add to total quantity sum
    $totalQuantitySum += floatval($row['total_quantity']);
    
    // Debug: Log each grouped row
    error_log("Grouped row - Date: " . $row['order_date'] . ", Ratio: " . $rezh . ", Quantity: " . $row['total_quantity'] . ", Invoices: " . $row['invoice_numbers']);
    
    $data[] = [
        'location' => $row['location'] ?? '',
        'quantity' => $quantity,
        'rezh' => $rezh,
        'price_per_unit' => $ppu,
        'total_price' => $total,
        'remaining_amount' => $remaining,
        'invoice_number' => $row['invoice_numbers'],
        'order_date' => $row['order_date']
    ];
    }

    // Debug: Log the final data count
    error_log("Receipt final grouped rows: " . count($data));

    $response = [
        'sales_data' => $data,
        'opening_debt' => '$' . number_format($opening_debt, 2, '.', ','),
        'total_quantity' => number_format($totalQuantitySum, 2) . ' م³',
        'customer_info' => [
            'company_name' => $company_name,
            'mobile' => $mobile
        ],
        'locations' => $locations,
        'recipients' => $recipients
    ];

    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log("Database error in select_sale.php main query: " . $e->getMessage());
    echo json_encode(['error' => 'Database error occurred', 'sales_data' => []]);
}