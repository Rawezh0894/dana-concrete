<?php
session_start();
require_once '../../config/db_conected.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../index.php');
    exit;
}

// Check permission
require_once '../../config/permissions.php';
if (!hasPermission('view_cash_box')) {
    echo 'Access denied';
    exit;
}

$from = $_GET['from'] ?? null;
$to = $_GET['to'] ?? null;

$where = [];
$params = [];
if ($from) {
    $where[] = 'cb.date >= ?';
    $params[] = $from;
}
if ($to) {
    $where[] = 'cb.date <= ?';
    $params[] = $to;
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

try {
    $sql = "SELECT cb.*, u.username as created_by_username
            FROM cash_box cb
            LEFT JOIN users u ON cb.created_by = u.id
            $whereSql
            ORDER BY cb.date DESC, cb.id DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Set headers for Excel download
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="cash_box_export_' . date('Y-m-d') . '.xls"');
    header('Cache-Control: max-age=0');
    
    // Start Excel content
    echo '<table border="1">';
    
    // Header row
    echo '<tr style="background-color: #4CAF50; color: white; font-weight: bold;">';
    echo '<th>#</th>';
    echo '<th>بەروار</th>';
    echo '<th>جۆری مامەڵە</th>';
    echo '<th>هاتوو/ڕۆشتوو</th>';
    echo '<th>بڕی پارە بە دینار</th>';
    echo '<th>بڕی پارە بە دۆلار</th>';
    echo '<th>جۆری دراو</th>';
    echo '<th>تێبینی</th>';
    echo '<th>دروستکراو لەلایەن</th>';
    echo '<th>کات</th>';
    echo '</tr>';
    
    // Data rows
    foreach ($rows as $index => $row) {
        $type_text = $row['type'] === 'deposit' ? 'زیادکردن' : ($row['type'] === 'withdraw' ? 'کەمکردنەوە' : '');
        $in_out = $row['type'] === 'deposit' ? 'هاتوو' : 'ڕۆشتوو';
        $amount_iqd = number_format($row['amount_iqd'], 0) . ' د.ع';
        $amount_usd = '$' . number_format($row['amount_usd'], 2);
        
        // Add color styling for Excel
        $in_out_style = $row['type'] === 'deposit' ? 'background-color: #d4edda; color: #155724;' : 'background-color: #f8d7da; color: #721c24;';
        
        echo '<tr>';
        echo '<td>' . ($index + 1) . '</td>';
        echo '<td>' . $row['date'] . '</td>';
        echo '<td>' . $type_text . '</td>';
        echo '<td style="' . $in_out_style . ' font-weight: bold; text-align: center;">' . $in_out . '</td>';
        echo '<td>' . $amount_iqd . '</td>';
        echo '<td>' . $amount_usd . '</td>';
        echo '<td>' . $row['currency'] . '</td>';
        echo '<td>' . ($row['note'] ?? '') . '</td>';
        echo '<td>' . ($row['created_by_username'] ?? '') . '</td>';
        echo '<td>' . $row['created_at'] . '</td>';
        echo '</tr>';
    }
    
    echo '</table>';
    
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
?>
