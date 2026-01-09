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
    $itemType = $_GET['item_type'] ?? null;
    
    $where = [];
    $filterParams = [];
    
    if ($from) {
        $where[] = "os.order_date >= :from_date";
        $filterParams['from_date'] = $from;
    }
    if ($to) {
        $where[] = "os.order_date <= :to_date";
        $filterParams['to_date'] = $to;
    }
    if ($customerId) {
        $where[] = "os.customer_id = :customer_id";
        $filterParams['customer_id'] = $customerId;
    }
    if ($itemType) {
        $where[] = "os.item_type = :item_type";
        $filterParams['item_type'] = $itemType;
    }
    
    $isDataTable = isset($_GET['draw']);
    
    if ($isDataTable) {
        $draw = intval($_GET['draw'] ?? 0);
        $start = intval($_GET['start'] ?? 0);
        $length = intval($_GET['length'] ?? 10);
        $searchValue = $_GET['search']['value'] ?? '';
        $orderColumnIndex = intval($_GET['order'][0]['column'] ?? 6);
        $orderDir = strtolower($_GET['order'][0]['dir'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';
        
        $columnMap = [
            0 => 'c.name',
            1 => 'os.recipient',
            2 => 'os.location',
            3 => 'os.invoice_number',
            4 => 'os.item_type',
            5 => 'os.item_name',
            6 => 'os.order_date',
            7 => 'os.quantity',
            8 => 'os.unit',
            9 => 'os.price_per_unit',
            10 => 'os.total_price',
            11 => 'os.payment_type',
            12 => 'os.amount_paid_iq',
            13 => 'os.amount_paid_usd',
            14 => 'os.remaining_amount',
            15 => 'os.dolar_rate',
            16 => 'os.notes'
        ];
        $orderColumn = $columnMap[$orderColumnIndex] ?? 'os.order_date';
        
        $baseSql = "FROM other_sales os 
                    LEFT JOIN customers c ON os.customer_id = c.id";
        
        $whereSql = '';
        $params = $filterParams;
        
        if ($searchValue !== '') {
            $where[] = "(c.name LIKE :search_customer OR os.recipient LIKE :search_recipient OR os.location LIKE :search_location OR os.invoice_number LIKE :search_invoice OR os.item_name LIKE :search_item)";
            $params['search_customer'] = '%' . $searchValue . '%';
            $params['search_recipient'] = '%' . $searchValue . '%';
            $params['search_location'] = '%' . $searchValue . '%';
            $params['search_invoice'] = '%' . $searchValue . '%';
            $params['search_item'] = '%' . $searchValue . '%';
        }
        
        if ($where) {
            $whereSql = ' WHERE ' . implode(' AND ', $where);
        }
        
        $totalRecords = $pdo->query("SELECT COUNT(*) FROM other_sales")->fetchColumn();
        
        $filteredStmt = $pdo->prepare("SELECT COUNT(*) $baseSql $whereSql");
        foreach ($params as $name => $value) {
            $filteredStmt->bindValue(':' . $name, $value);
        }
        $filteredStmt->execute();
        $filteredRecords = $filteredStmt->fetchColumn();
        
        $dataSql = "SELECT os.*, 
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
    
    $sql = "SELECT os.*, c.name AS customer_name 
            FROM other_sales os 
            LEFT JOIN customers c ON os.customer_id = c.id";
    if ($where) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }
    $sql .= " ORDER BY os.order_date DESC";
    
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
