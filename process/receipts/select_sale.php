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
    http_response_code(400);
    exit(json_encode(
        ['error' => 'Customer ID is required', 'sales_data' => []],
        JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
    ));
}

function sanitize_text($value) {
    return htmlentities((string)($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$type = isset($_GET['type']) ? $_GET['type'] : 'all';
$month = isset($_GET['month']) ? $_GET['month'] : 'all';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$location = isset($_GET['location']) ? $_GET['location'] : 'all';
$recipient = isset($_GET['recipient']) ? $_GET['recipient'] : 'all';
$rezh_name = isset($_GET['rezh_name']) ? trim((string)$_GET['rezh_name']) : '';

try {
    // Get customer information including opening debt, name, and mobile
    $customer_sql = "SELECT opening_debt_usd, name, mobile1 FROM customers WHERE id = :customer_id";
    $customer_stmt = $pdo->prepare($customer_sql);
    $customer_stmt->execute(['customer_id' => $customer_id]);
    $customer_data = $customer_stmt->fetch(PDO::FETCH_ASSOC);
    $opening_debt = is_numeric($customer_data['opening_debt_usd']) ? floatval($customer_data['opening_debt_usd']) : 0;
    $company_name = sanitize_text($customer_data['name'] ?? '');
    $mobile = sanitize_text($customer_data['mobile1'] ?? '');
    
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

    // Do not sanitize option lists here because they are used as filter values
    // Sanitizing would convert characters like '&' to '&amp;', breaking the SQL filter
    $locations = array_map(function($row) {
        return ['location' => $row['location'] ?? ''];
    }, $locations ?: []);
    $recipients = array_map(function($row) {
        return ['recipient' => $row['recipient'] ?? ''];
    }, $recipients ?: []);
} catch (Exception $e) {
    error_log("Database error in select_sale.php: " . $e->getMessage());
    http_response_code(500);
    exit(json_encode(
        ['error' => 'Database error occurred', 'sales_data' => []],
        JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
    ));
}

$sql = "SELECT 
        s.order_date,
        s.location,
        f.strength_mpa, 
        f.strength_kg,
        MAX(f.name) as formula_name,
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
if ($location === 'none') {
    // Explicitly requested no locations => return no rows
    $sql .= " AND 1=0";
}

// Add recipient filter
if ($recipient !== 'all' && $recipient !== 'none') {
    // Handle multiple recipients (comma-separated)
    if (strpos($recipient, ',') !== false) {
        $recipientArray = array_map('trim', explode(',', $recipient));
        $recipientArray = array_filter($recipientArray, fn($v) => $v !== '');

        $includeEmpty = in_array('__EMPTY__', $recipientArray, true);
        $recipientArray = array_values(array_filter($recipientArray, fn($v) => $v !== '__EMPTY__'));

        $recipientConditions = [];

        if (!empty($recipientArray)) {
            $recipientPlaceholders = [];
            foreach ($recipientArray as $index => $recv) {
                $paramName = 'recipient_' . $index;
                $recipientPlaceholders[] = ':' . $paramName;
                $params[$paramName] = $recv;
            }
            $recipientConditions[] = "s.recipient IN (" . implode(',', $recipientPlaceholders) . ")";
        }

        if ($includeEmpty) {
            $recipientConditions[] = "(s.recipient IS NULL OR TRIM(s.recipient) = '')";
        }

        if (!empty($recipientConditions)) {
            $sql .= " AND (" . implode(' OR ', $recipientConditions) . ")";
        } else {
            // No valid recipients after parsing => return no rows
            $sql .= " AND 1=0";
        }
    } else {
        // Single recipient
        if (trim($recipient) === '__EMPTY__') {
            $sql .= " AND (s.recipient IS NULL OR TRIM(s.recipient) = '')";
        } else {
            $sql .= " AND s.recipient = :recipient_filter";
            $params['recipient_filter'] = $recipient;
        }
    }
}
if ($recipient === 'none') {
    // Explicitly requested no recipients => return no rows
    $sql .= " AND 1=0";
}

if ($rezh_name !== '') {
    $sql .= " AND (CONCAT(IFNULL(f.name,''), ' ', IFNULL(f.type,'')) LIKE :rezh_name_search)";
    $params['rezh_name_search'] = '%' . $rezh_name . '%';
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
    $quantity = sanitize_text(number_format($row['total_quantity'], 2) . ' م³');
    $rezhRaw = $row['strength_mpa'] ? $row['strength_mpa'] . ' MPa' : ($row['strength_kg'] ? $row['strength_kg'] . ' Kg' : '');
    $rezh = sanitize_text($rezhRaw);
    $rezhName = sanitize_text($row['formula_name'] ?? '');
    $ppu = sanitize_text(is_numeric($row['price_per_unit']) ? '$' . number_format($row['price_per_unit'], 2, '.', ',') : '');
    $total = sanitize_text(is_numeric($row['total_price_sum']) ? '$' . number_format($row['total_price_sum'], 2, '.', ',') : '');
    $remaining = sanitize_text(is_numeric($row['total_remaining_amount']) ? '$' . number_format($row['total_remaining_amount'], 2, '.', ',') : '$0.00');
    
    // Add to total quantity sum
    $totalQuantitySum += floatval($row['total_quantity']);
    
    // Debug: Log each grouped row
    error_log("Grouped row - Date: " . ($row['order_date'] ?? '') . ", Ratio: " . $rezhRaw . ", Quantity: " . ($row['total_quantity'] ?? '') . ", Invoices: " . ($row['invoice_numbers'] ?? ''));
    
    $data[] = [
        'location' => sanitize_text($row['location'] ?? ''),
        'quantity' => $quantity,
        'rezh' => $rezh,
        'rezh_name' => $rezhName,
        'price_per_unit' => $ppu,
        'total_price' => $total,
        'remaining_amount' => $remaining,
        'invoice_number' => sanitize_text($row['invoice_numbers'] ?? ''),
        'order_date' => sanitize_text($row['order_date'] ?? '')
    ];
    }

    // Debug: Log the final data count
    error_log("Receipt final grouped rows: " . count($data));

    $response = [
        'sales_data' => $data,
        'opening_debt' => sanitize_text('$' . number_format($opening_debt, 2, '.', ',')),
        'total_quantity' => sanitize_text(number_format($totalQuantitySum, 2) . ' م³'),
        'customer_info' => [
            'company_name' => $company_name,
            'mobile' => $mobile
        ],
        'locations' => $locations,
        'recipients' => $recipients
    ];

    exit(json_encode(
        $response,
        JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
    ));
    
} catch (Exception $e) {
    error_log("Database error in select_sale.php main query: " . $e->getMessage());
    http_response_code(500);
    exit(json_encode(
        ['error' => 'Database error occurred', 'sales_data' => []],
        JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
    ));
}