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
    $materialType = $_GET['material_type'] ?? null;
    
    $where = [];
    $filterParams = [];
    
    if ($from) {
        $where[] = "rms.order_date >= :from_date";
        $filterParams['from_date'] = $from;
    }
    if ($to) {
        $where[] = "rms.order_date <= :to_date";
        $filterParams['to_date'] = $to;
    }
    if ($customerId) {
        $where[] = "rms.customer_id = :customer_id";
        $filterParams['customer_id'] = $customerId;
    }
    if ($materialType) {
        $where[] = "rms.material_type = :material_type";
        $filterParams['material_type'] = $materialType;
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
            1 => 'rms.recipient',
            2 => 'rms.location',
            3 => 'rms.invoice_number',
            4 => 'rms.material_type',
            5 => 'rms.order_date',
            6 => 'rms.quantity',
            7 => 'rms.unit',
            8 => 'rms.price_per_unit',
            9 => 'rms.total_price',
            10 => 'rms.payment_type',
            11 => 'rms.amount_paid_iq',
            12 => 'rms.amount_paid_usd',
            13 => 'rms.remaining_amount',
            14 => 'rms.dolar_rate',
            15 => 'rms.notes'
        ];
        $orderColumn = $columnMap[$orderColumnIndex] ?? 'rms.order_date';
        
        $baseSql = "FROM raw_material_sales rms 
                    LEFT JOIN customers c ON rms.customer_id = c.id";
        
        $whereSql = '';
        $params = $filterParams;
        
        if ($searchValue !== '') {
            $where[] = "(c.name LIKE :search_customer OR rms.recipient LIKE :search_recipient OR rms.location LIKE :search_location OR rms.invoice_number LIKE :search_invoice)";
            $params['search_customer'] = '%' . $searchValue . '%';
            $params['search_recipient'] = '%' . $searchValue . '%';
            $params['search_location'] = '%' . $searchValue . '%';
            $params['search_invoice'] = '%' . $searchValue . '%';
        }
        
        if ($where) {
            $whereSql = ' WHERE ' . implode(' AND ', $where);
        }
        
        $totalRecords = $pdo->query("SELECT COUNT(*) FROM raw_material_sales")->fetchColumn();
        
        $filteredStmt = $pdo->prepare("SELECT COUNT(*) $baseSql $whereSql");
        foreach ($params as $name => $value) {
            $filteredStmt->bindValue(':' . $name, $value);
        }
        $filteredStmt->execute();
        $filteredRecords = $filteredStmt->fetchColumn();
        
        $dataSql = "SELECT rms.*, 
                           c.name AS customer_name
                    $baseSql
                    $whereSql
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
    
    $sql = "SELECT rms.*, c.name AS customer_name 
            FROM raw_material_sales rms 
            LEFT JOIN customers c ON rms.customer_id = c.id";
    if ($where) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }
    $sql .= " ORDER BY rms.order_date DESC";
    
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
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?>
