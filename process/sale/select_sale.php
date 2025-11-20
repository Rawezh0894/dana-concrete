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
    $where = [];
    $filterParams = [];
    
    if ($from) {
        $where[] = "s.order_date >= ?";
        $filterParams[] = $from;
    }
    if ($to) {
        $where[] = "s.order_date <= ?";
        $filterParams[] = $to;
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
            $where[] = "(c.name LIKE ? OR s.recipient LIKE ? OR s.location LIKE ? OR s.invoice_number LIKE ?)";
            $params = array_merge($params, array_fill(0, 4, '%' . $searchValue . '%'));
        }
        
        if ($where) {
            $whereSql = ' WHERE ' . implode(' AND ', $where);
        }
        
        $totalRecords = $pdo->query("SELECT COUNT(*) FROM sales")->fetchColumn();
        
        $filteredStmt = $pdo->prepare("SELECT COUNT(*) $baseSql $whereSql");
        $filteredStmt->execute($params);
        $filteredRecords = $filteredStmt->fetchColumn();
        
        $dataSql = "SELECT s.*, 
                           c.name AS customer_name, 
                           f.name AS formula_name, 
                           r.id AS recipient_id,
                           COUNT(*) OVER (PARTITION BY s.invoice_number) AS duplicate_count
                    $baseSql
                    $whereSql
                    ORDER BY $orderColumn $orderDir";
        
        if ($length > -1) {
            $dataSql .= " LIMIT :start, :length";
        }
        
        $dataStmt = $pdo->prepare($dataSql);
        foreach ($params as $index => $value) {
            $dataStmt->bindValue($index + 1, $value);
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
    
    $sql = "SELECT s.*, c.name AS customer_name, f.name AS formula_name, r.id AS recipient_id 
            FROM sales s 
            LEFT JOIN customers c ON s.customer_id = c.id 
            LEFT JOIN concrete_formulas f ON s.formula_id = f.id
            LEFT JOIN recipients r ON r.name COLLATE utf8mb4_general_ci = s.recipient";
    if ($where) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }
    $sql .= " ORDER BY s.order_date ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($filterParams);
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
