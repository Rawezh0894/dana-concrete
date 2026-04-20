<?php
session_start();
require_once '../../config/db_conected.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["data" => [], "recordsTotal" => 0, "recordsFiltered" => 0]);
    exit;
}

$user_id = $_SESSION['user_id'];

$draw = isset($_POST['draw']) ? intval($_POST['draw']) : 1;
$start = isset($_POST['start']) ? intval($_POST['start']) : 0;
$length = isset($_POST['length']) ? intval($_POST['length']) : 10;

// Filters
$from_date = $_POST['from_date'] ?? '';
$to_date = $_POST['to_date'] ?? '';
$type = $_POST['type'] ?? '';
$category = $_POST['category'] ?? '';
$amount = $_POST['amount'] ?? '';
$notes = $_POST['notes'] ?? '';

$searchQuery = " ";
$params = [$user_id];

if ($from_date != '') {
    $searchQuery .= " AND DATE(t.created_at) >= ? ";
    $params[] = $from_date;
}
if ($to_date != '') {
    $searchQuery .= " AND DATE(t.created_at) <= ? ";
    $params[] = $to_date;
}
if ($type != '') {
    if ($type === 'EXCHANGE') {
        $searchQuery .= " AND t.type = 'EXCHANGE' ";
    } else {
        $searchQuery .= " AND t.type != 'EXCHANGE' ";
        if ($type === 'INFLOW') {
            $searchQuery .= " AND EXISTS (SELECT 1 FROM ledger_entries le WHERE le.transaction_id = t.id AND le.amount > 0) ";
        } elseif ($type === 'OUTFLOW') {
            $searchQuery .= " AND EXISTS (SELECT 1 FROM ledger_entries le WHERE le.transaction_id = t.id AND le.amount < 0) ";
        }
    }
}
if ($category != '') {
    $searchQuery .= " AND t.category_id = ? ";
    $params[] = $category;
}
if ($amount != '') {
    // Search exact amount matches via ABS since outflow might be negative inside ledger
    $searchQuery .= " AND EXISTS (SELECT 1 FROM ledger_entries le WHERE le.transaction_id = t.id AND ABS(le.amount) = ?) ";
    $params[] = $amount;
}
if ($notes != '') {
    $searchQuery .= " AND EXISTS (SELECT 1 FROM ledger_entries le WHERE le.transaction_id = t.id AND le.description LIKE ?) ";
    $params[] = "%$notes%";
}

// Total records without filter
$stmt = $pdo->prepare("SELECT count(*) as allcount FROM transactions WHERE created_by = ?");
$stmt->execute([$user_id]);
$records = $stmt->fetch();
$totalRecords = $records['allcount'];

// Total records with filter
$stmt = $pdo->prepare("SELECT count(*) as allcount FROM transactions t WHERE t.created_by = ? " . $searchQuery);
$stmt->execute($params);
$records = $stmt->fetch();
$totalRecordwithFilter = $records['allcount'];

// Sort configurations
$columnIndex_arr = $_POST['order'] ?? null;
$columnName_arr = $_POST['columns'] ?? null;
$order_sql = "ORDER BY t.created_at DESC";

if ($columnIndex_arr && count($columnIndex_arr) > 0) {
    $columnIndex = $columnIndex_arr[0]['column']; 
    $columnName = $columnName_arr[$columnIndex]['data']; 
    $columnSortOrder = $columnIndex_arr[0]['dir'];
    
    // Map data fields to database columns
    $sortColMap = [
        'created_at' => 't.created_at',
        'type' => 't.type',
        'category' => 't.category_id',
        'usd' => 'usd_amount',
        'iqd' => 'iqd_amount'
    ];
    
    if (array_key_exists($columnName, $sortColMap)) {
        $mapped = $sortColMap[$columnName];
        $dir = strtoupper($columnSortOrder) == 'ASC' ? 'ASC' : 'DESC';
        $order_sql = "ORDER BY $mapped $dir";
    }
}


// Fetch records
$query = "
    SELECT t.id, t.created_at, t.type as trans_type, t.category_id, tc.name as category_name,
    (SELECT amount FROM ledger_entries WHERE transaction_id = t.id AND currency_code = 'USD' LIMIT 1) as usd_amount,
    (SELECT amount FROM ledger_entries WHERE transaction_id = t.id AND currency_code = 'IQD' LIMIT 1) as iqd_amount,
    (SELECT description FROM ledger_entries WHERE transaction_id = t.id LIMIT 1) as description
    FROM transactions t
    LEFT JOIN transaction_categories tc ON t.category_id = tc.id
    WHERE t.created_by = ? " . $searchQuery . "
    $order_sql 
    LIMIT $start, $length
";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$transactions = $stmt->fetchAll();

$data = array();
foreach ($transactions as $tx) {
    $is_exchange = $tx['trans_type'] === 'EXCHANGE';
    
    // Type Badge
    $typeHtml = "";
    if($is_exchange) $typeHtml = '<span class="badge bg-warning text-dark">ئاڵوگۆڕ 💱</span>';
    elseif(($tx['usd_amount'] ?? 0) > 0 || ($tx['iqd_amount'] ?? 0) > 0) $typeHtml = '<span class="badge bg-success">هاتن 📥</span>';
    else $typeHtml = '<span class="badge bg-danger">دەرچوون 📤</span>';

    // Categories - Using a variety of subtle badge styles based on category ID
    $category_colors = ['bg-primary-subtle text-primary border-primary-subtle', 'bg-info-subtle text-info border-info-subtle', 'bg-warning-subtle text-warning border-warning-subtle', 'bg-secondary-subtle text-secondary border-secondary-subtle', 'bg-dark-subtle text-dark border-dark-subtle'];
    $color_class = $category_colors[($tx['category_id'] ?? 0) % count($category_colors)];
    $categoryHtml = '<span class="badge '.$color_class.' border fw-bold" style="font-size: 0.9rem; padding: 6px 12px; border-radius: 8px;">' . htmlspecialchars($tx['category_name'] ?? ($is_exchange ? 'ئاڵوگۆڕ' : 'بی جۆر')) . '</span>';
    
    // Amounts
    $usd_class = ($tx['usd_amount'] ?? 0) > 0 ? 'text-success' : (($tx['usd_amount'] ?? 0) < 0 ? 'text-danger' : 'text-muted');
    $usdHtml = '<div class="text-center w-100"><span dir="ltr" class="fw-bold '.$usd_class.'">' . ($tx['usd_amount'] ? number_format(abs($tx['usd_amount']), 2) . ' $' : '-') . '</span></div>';
    
    $iqd_class = ($tx['iqd_amount'] ?? 0) > 0 ? 'text-success' : (($tx['iqd_amount'] ?? 0) < 0 ? 'text-danger' : 'text-muted');
    $iqdHtml = '<div class="text-center w-100"><span dir="ltr" class="fw-bold '.$iqd_class.'">' . ($tx['iqd_amount'] ? number_format(abs($tx['iqd_amount']), 0) . ' IQD' : '-') . '</span></div>';

    // Action buttons
    $encodedTx = htmlspecialchars(json_encode($tx), ENT_QUOTES, 'UTF-8');
    $actionHtml = '<div class="d-flex justify-content-center">';
    if(!$is_exchange) {
        $actionHtml .= '<button class="btn btn-sm btn-outline-info border-0 me-1" onclick="prepareEdit('.$encodedTx.')"><i class="fa fa-edit"></i></button>';
    }
    $actionHtml .= '<button class="btn btn-sm btn-outline-danger border-0" onclick="deleteTransaction('.$tx['id'].')"><i class="fa fa-trash"></i></button>';
    $actionHtml .= '</div>';

    $data[] = array(
        "created_at" => '<div class="text-center w-100"><small class="text-muted" style="direction: ltr; display: inline-block;">' . $tx['created_at'] . '</small></div>',
        "type" => '<div class="text-center w-100">' . $typeHtml . '</div>',
        "category" => '<div class="text-center w-100">' . $categoryHtml . '</div>',
        "usd" => $usdHtml,
        "iqd" => $iqdHtml,
        "notes" => '<div class="text-start px-2"><span class="text-muted small">' . htmlspecialchars($tx['description'] ?? '') . '</span></div>',
        "action" => $actionHtml
    );
}

// Calculate Filtered Totals
$totalsQuery = "
    SELECT 
        le.currency_code,
        SUM(CASE WHEN le.amount > 0 THEN le.amount ELSE 0 END) as total_inflow,
        SUM(CASE WHEN le.amount < 0 THEN ABS(le.amount) ELSE 0 END) as total_outflow
    FROM ledger_entries le
    JOIN transactions t ON le.transaction_id = t.id
    WHERE t.created_by = ? " . $searchQuery . "
    GROUP BY le.currency_code
";
$stmtTotals = $pdo->prepare($totalsQuery);
$stmtTotals->execute($params);
$totalsRaw = $stmtTotals->fetchAll(PDO::FETCH_ASSOC);

$filteredTotals = [
    'usd_in' => 0, 'usd_out' => 0,
    'iqd_in' => 0, 'iqd_out' => 0
];

foreach ($totalsRaw as $row) {
    if ($row['currency_code'] === 'USD') {
        $filteredTotals['usd_in'] = floatval($row['total_inflow']);
        $filteredTotals['usd_out'] = floatval($row['total_outflow']);
    } elseif ($row['currency_code'] === 'IQD') {
        $filteredTotals['iqd_in'] = floatval($row['total_inflow']);
        $filteredTotals['iqd_out'] = floatval($row['total_outflow']);
    }
}

$response = array(
    "draw" => $draw,
    "recordsTotal" => $totalRecords,
    "recordsFiltered" => $totalRecordwithFilter,
    "data" => $data,
    "totals" => $filteredTotals
);

echo json_encode($response);
?>
