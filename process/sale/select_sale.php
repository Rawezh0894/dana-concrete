<?php
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';
if (!hasPermission('view_sale')) {
    echo json_encode(['success' => false, 'message' => 'ڕێگەت پێنەدراوە!']);
    exit;
}
header('Content-Type: application/json; charset=utf-8');

try {
$from = $_GET['from'] ?? null;
$to = $_GET['to'] ?? null;
$customerId = $_GET['customer_id'] ?? null;
$minQuantity = $_GET['min_quantity'] ?? null;
$maxQuantity = $_GET['max_quantity'] ?? null;
$amountMin = $_GET['amount_min'] ?? null;
$amountMax = $_GET['amount_max'] ?? null;
    $where = [];
    $filterParams = [];
    
    if ($from) {
        $where[] = "s.order_date >= :from_date";
        $filterParams['from_date'] = $from;
    }
    if ($to) {
        $where[] = "s.order_date <= :to_date";
        $filterParams['to_date'] = $to;
    }
if ($customerId) {
    $where[] = "s.customer_id = :customer_id";
    $filterParams['customer_id'] = $customerId;
}
if ($minQuantity !== null && $minQuantity !== '') {
    $where[] = "s.quantity >= :min_quantity";
    $filterParams['min_quantity'] = (float)$minQuantity;
}
if ($maxQuantity !== null && $maxQuantity !== '') {
    $where[] = "s.quantity <= :max_quantity";
    $filterParams['max_quantity'] = (float)$maxQuantity;
}
if ($amountMin !== null && $amountMin !== '') {
    $where[] = "s.total_price >= :amount_min";
    $filterParams['amount_min'] = (float)$amountMin;
}
if ($amountMax !== null && $amountMax !== '') {
    $where[] = "s.total_price <= :amount_max";
    $filterParams['amount_max'] = (float)$amountMax;
}
    
    $isDataTable = isset($_GET['draw']);
    
    if ($isDataTable) {
        $draw = intval($_GET['draw'] ?? 0);
        $start = intval($_GET['start'] ?? 0);
        $length = intval($_GET['length'] ?? 10);
        $searchValue = $_GET['search']['value'] ?? '';
        $orderColumnIndex = intval($_GET['order'][0]['column'] ?? 5);
        $orderDir = strtolower($_GET['order'][0]['dir'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';
        
        $columnMap = [
            0 => 'c.name',
            1 => 's.recipient',
            2 => 's.location',
            3 => 's.invoice_number',
            4 => 'f.name',
            5 => 's.order_date',
            6 => 's.payment_type',
            7 => 's.quantity',
            8 => 's.price_per_unit',
            9 => 's.total_price',
            10 => 's.amount_paid_iq',
            11 => 's.amount_paid_usd',
            12 => 's.remaining_amount',
            13 => 's.dolar_rate',
            14 => 's.notes',
            15 => 's.discount'
        ];
        $orderColumn = $columnMap[$orderColumnIndex] ?? 's.order_date';
        
        $baseSql = "FROM sales s 
                    LEFT JOIN customers c ON s.customer_id = c.id 
                    LEFT JOIN concrete_formulas f ON s.formula_id = f.id
                    LEFT JOIN recipients r ON r.name COLLATE utf8mb4_general_ci = s.recipient";
        
        $whereSql = '';
        $params = $filterParams;
        
        if ($searchValue !== '') {
            $where[] = "(c.name LIKE :search_customer OR s.recipient LIKE :search_recipient OR s.location LIKE :search_location OR s.invoice_number LIKE :search_invoice)";
            $params['search_customer'] = '%' . $searchValue . '%';
            $params['search_recipient'] = '%' . $searchValue . '%';
            $params['search_location'] = '%' . $searchValue . '%';
            $params['search_invoice'] = '%' . $searchValue . '%';
        }
        
        if ($where) {
            $whereSql = ' WHERE ' . implode(' AND ', $where);
        }
        
        $totalRecords = $pdo->query("SELECT COUNT(*) FROM sales")->fetchColumn();
        
        $filteredStmt = $pdo->prepare("SELECT COUNT(*) $baseSql $whereSql");
        foreach ($params as $name => $value) {
            $filteredStmt->bindValue(':' . $name, $value);
        }
        $filteredStmt->execute();
        $filteredRecords = $filteredStmt->fetchColumn();
        
        $dataSql = "SELECT s.*, 
                           c.name AS customer_name, 
                           f.name AS formula_name, 
                           r.id AS recipient_id,
                            COUNT(*) OVER (PARTITION BY s.invoice_number) AS duplicate_count
                     $baseSql
                     $whereSql
                     GROUP BY s.id
                     ORDER BY $orderColumn $orderDir";
        
        if ($length > -1) {
            $dataSql .= " LIMIT :start, :length";
        }
        
        $dataStmt = $pdo->prepare($dataSql);
        foreach ($params as $name => $value) {
            $dataStmt->bindValue(':' . $name, $value);
        }
        if ($length > -1) {
            $dataStmt->bindValue(':start', $start, PDO::PARAM_INT);
            $dataStmt->bindValue(':length', $length, PDO::PARAM_INT);
        }
        $dataStmt->execute();
        $rows = $dataStmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'draw' => $draw,
            'recordsTotal' => intval($totalRecords),
            'recordsFiltered' => intval($filteredRecords),
            'data' => $rows,
            'success' => true
        ]);
        exit;
    }
    
    // Check if AG Grid request
    $isAgGrid = isset($_GET['ag_grid']) && $_GET['ag_grid'] == '1';
    
    if ($isAgGrid) {
        // AG Grid format - return all data with filters
        $baseSql = "FROM sales s 
                    LEFT JOIN customers c ON s.customer_id = c.id 
                    LEFT JOIN concrete_formulas f ON s.formula_id = f.id
                    LEFT JOIN recipients r ON r.name COLLATE utf8mb4_general_ci = s.recipient";
        
        $whereSql = "WHERE 1=1";
        if ($where) {
            $whereSql .= " AND " . implode(' AND ', $where);
        }
        
        $params = $filterParams;
        
        $dataSql = "SELECT s.*, 
                           c.name AS customer_name, 
                           f.name AS formula_name, 
                           r.id AS recipient_id,
                           COUNT(*) OVER (PARTITION BY s.invoice_number) AS duplicate_count
                    $baseSql
                    $whereSql
                    GROUP BY s.id
                    ORDER BY s.order_date DESC";
        
        $dataStmt = $pdo->prepare($dataSql);
        foreach ($params as $name => $value) {
            $dataStmt->bindValue(':' . $name, $value);
        }
        $dataStmt->execute();
        $rows = $dataStmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => $rows
        ]);
        exit;
    }
    
    $sql = "SELECT s.*, c.name AS customer_name, f.name AS formula_name, r.id AS recipient_id 
            FROM sales s 
            LEFT JOIN customers c ON s.customer_id = c.id 
            LEFT JOIN concrete_formulas f ON s.formula_id = f.id
            LEFT JOIN recipients r ON r.name COLLATE utf8mb4_general_ci = s.recipient
            WHERE 1=1";
    if ($where) {
        $sql .= " AND " . implode(" AND ", $where);
    }
    $sql .= " GROUP BY s.id ORDER BY s.order_date ASC";
    
        $stmt = $pdo->prepare($sql);
        foreach ($filterParams as $name => $value) {
            $stmt->bindValue(':' . $name, $value);
        }
        $stmt->execute();
    $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'data' => $sales]);
} catch (Exception $e) {
    if (isset($isDataTable) && $isDataTable) {
        echo json_encode([
            'draw' => intval($_GET['draw'] ?? 0),
            'recordsTotal' => 0,
            'recordsFiltered' => 0,
            'data' => [],
            'error' => $e->getMessage()
        ]);
    } else {
        error_log("Select Sale Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
