<?php
session_start();
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../index.php');
    exit;
}

if (!hasPermission('view_person_other_expenses')) {
    echo 'ڕێگەت پێنەدراوە!';
    exit;
}

try {
    // Get only persons who have debt (opening debt OR remaining expenses OR remaining purchases)
    $stmt = $pdo->prepare("
        SELECT DISTINCT 
            p.id, 
            p.name, 
            p.opening_debt_usd, 
            p.opening_debt_iqd
        FROM other_expense_persons p
        WHERE 
            (p.opening_debt_usd > 0 OR p.opening_debt_iqd > 0)
            OR EXISTS (
                SELECT 1 FROM other_expenses oe 
                WHERE oe.person_id = p.id 
                AND oe.payment_type = 'قەرز' 
                AND (oe.remaining_usd > 0 OR oe.remaining_iqd > 0)
            )
            OR EXISTS (
                SELECT 1 FROM purchase_materials pm 
                WHERE pm.person_id = p.id 
                AND pm.payment_type = 'قەرز' 
                AND (pm.remaining_amount_usd > 0 OR pm.remaining_amount_iqd > 0)
            )
        ORDER BY p.name ASC
    ");
    $stmt->execute();
    $persons = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate total debt for each person
    $data = [];
    foreach ($persons as $person) {
        $person_id = $person['id'];
        
        // Get opening debt
        $opening_debt_usd = floatval($person['opening_debt_usd'] ?? 0);
        $opening_debt_iqd = floatval($person['opening_debt_iqd'] ?? 0);
        
        // Get remaining debt from other_expenses
        $expenses_stmt = $pdo->prepare("
            SELECT 
                COALESCE(SUM(remaining_usd), 0) as total_remaining_usd,
                COALESCE(SUM(remaining_iqd), 0) as total_remaining_iqd
            FROM other_expenses 
            WHERE person_id = ? AND payment_type = 'قەرز'
        ");
        $expenses_stmt->execute([$person_id]);
        $expenses_debt = $expenses_stmt->fetch(PDO::FETCH_ASSOC);
        $expenses_debt_usd = floatval($expenses_debt['total_remaining_usd'] ?? 0);
        $expenses_debt_iqd = floatval($expenses_debt['total_remaining_iqd'] ?? 0);
        
        // Get remaining debt from purchase_materials
        $purchases_stmt = $pdo->prepare("
            SELECT 
                COALESCE(SUM(remaining_amount_usd), 0) as total_remaining_usd,
                COALESCE(SUM(remaining_amount_iqd), 0) as total_remaining_iqd
            FROM purchase_materials 
            WHERE person_id = ? AND payment_type = 'قەرز'
        ");
        $purchases_stmt->execute([$person_id]);
        $purchases_debt = $purchases_stmt->fetch(PDO::FETCH_ASSOC);
        $purchases_debt_usd = floatval($purchases_debt['total_remaining_usd'] ?? 0);
        $purchases_debt_iqd = floatval($purchases_debt['total_remaining_iqd'] ?? 0);
        
        // Calculate total debt
        $total_debt_usd = $opening_debt_usd + $expenses_debt_usd + $purchases_debt_usd;
        $total_debt_iqd = $opening_debt_iqd + $expenses_debt_iqd + $purchases_debt_iqd;
        
        // Only include if person has debt
        if ($total_debt_usd > 0 || $total_debt_iqd > 0) {
            // Generate UNIQ CODE (unique identifier)
            $uniq_code = 'DEBT-' . str_pad($person_id, 6, '0', STR_PAD_LEFT) . '-' . date('Ymd') . '-' . strtoupper(substr(md5($person_id . $person['name']), 0, 6));
            
            $data[] = [
                'id' => $person_id,
                'name' => $person['name'],
                'opening_debt_usd' => $opening_debt_usd,
                'opening_debt_iqd' => $opening_debt_iqd,
                'expenses_debt_usd' => $expenses_debt_usd,
                'expenses_debt_iqd' => $expenses_debt_iqd,
                'purchases_debt_usd' => $purchases_debt_usd,
                'purchases_debt_iqd' => $purchases_debt_iqd,
                'total_debt_usd' => $total_debt_usd,
                'total_debt_iqd' => $total_debt_iqd,
                'uniq_code' => $uniq_code
            ];
        }
    }
    
    // Set headers for Excel download with proper UTF-8 encoding
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Transfer-Encoding: binary');
    header('Content-Disposition: attachment; filename*=UTF-8\'\'کڕیارە_قەرزدارەکان_' . date('Y-m-d') . '.xls');
    
    // Start Excel content with UTF-8 BOM
    echo "\xEF\xBB\xBF"; // UTF-8 BOM
    echo '<!DOCTYPE html>';
    echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
    echo '<head>';
    echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
    echo '<meta charset="UTF-8">';
    echo '<style>';
    echo 'table { border-collapse: collapse; width: 100%; font-family: Arial, sans-serif; }';
    echo 'th, td { border: 1px solid #000; padding: 8px; text-align: center; }';
    echo 'th { background-color: #4CAF50; color: white; font-weight: bold; }';
    echo '.number { text-align: right; }';
    echo '.header { background-color: #2196F3; color: white; font-size: 16px; font-weight: bold; }';
    echo '</style>';
    echo '</head>';
    echo '<body>';
    
    echo '<table border="1">';
    
    // Title row
    echo '<tr><th colspan="11" class="header">کڕیارە قەرزدارەکان</th></tr>';
    echo '<tr><th colspan="11" style="background-color: #E0E0E0;">بەروار: ' . date('Y-m-d H:i:s') . '</th></tr>';
    
    // Header row
    echo '<tr>';
    echo '<th>#</th>';
    echo '<th>کۆدی تایبەت (UNIQ CODE)</th>';
    echo '<th>ناوی کڕیار</th>';
    echo '<th>قەرزی سەرەتایی (دۆلار)</th>';
    echo '<th>قەرزی سەرەتایی (دینار)</th>';
    echo '<th>قەرزی خەرجی تر (دۆلار)</th>';
    echo '<th>قەرزی خەرجی تر (دینار)</th>';
    echo '<th>قەرزی کڕین (دۆلار)</th>';
    echo '<th>قەرزی کڕین (دینار)</th>';
    echo '<th>کۆی قەرز (دۆلار)</th>';
    echo '<th>کۆی قەرز (دینار)</th>';
    echo '</tr>';
    
    // Data rows
    foreach ($data as $index => $row) {
        echo '<tr>';
        echo '<td>' . ($index + 1) . '</td>';
        echo '<td style="font-weight: bold; color: #1976D2;">' . htmlspecialchars($row['uniq_code']) . '</td>';
        echo '<td>' . htmlspecialchars($row['name']) . '</td>';
        echo '<td class="number">' . number_format($row['opening_debt_usd'], 2) . '</td>';
        echo '<td class="number">' . number_format($row['opening_debt_iqd'], 0) . '</td>';
        echo '<td class="number">' . number_format($row['expenses_debt_usd'], 2) . '</td>';
        echo '<td class="number">' . number_format($row['expenses_debt_iqd'], 0) . '</td>';
        echo '<td class="number">' . number_format($row['purchases_debt_usd'], 2) . '</td>';
        echo '<td class="number">' . number_format($row['purchases_debt_iqd'], 0) . '</td>';
        echo '<td class="number" style="font-weight: bold; background-color: #FFF9C4;">' . number_format($row['total_debt_usd'], 2) . '</td>';
        echo '<td class="number" style="font-weight: bold; background-color: #FFF9C4;">' . number_format($row['total_debt_iqd'], 0) . '</td>';
        echo '</tr>';
    }
    
    // Summary row
    if (!empty($data)) {
        $total_opening_usd = array_sum(array_column($data, 'opening_debt_usd'));
        $total_opening_iqd = array_sum(array_column($data, 'opening_debt_iqd'));
        $total_expenses_usd = array_sum(array_column($data, 'expenses_debt_usd'));
        $total_expenses_iqd = array_sum(array_column($data, 'expenses_debt_iqd'));
        $total_purchases_usd = array_sum(array_column($data, 'purchases_debt_usd'));
        $total_purchases_iqd = array_sum(array_column($data, 'purchases_debt_iqd'));
        $grand_total_usd = array_sum(array_column($data, 'total_debt_usd'));
        $grand_total_iqd = array_sum(array_column($data, 'total_debt_iqd'));
        
        echo '<tr style="background-color: #E8F5E9; font-weight: bold;">';
        echo '<td colspan="3">کۆی گشتی</td>';
        echo '<td class="number">' . number_format($total_opening_usd, 2) . '</td>';
        echo '<td class="number">' . number_format($total_opening_iqd, 0) . '</td>';
        echo '<td class="number">' . number_format($total_expenses_usd, 2) . '</td>';
        echo '<td class="number">' . number_format($total_expenses_iqd, 0) . '</td>';
        echo '<td class="number">' . number_format($total_purchases_usd, 2) . '</td>';
        echo '<td class="number">' . number_format($total_purchases_iqd, 0) . '</td>';
        echo '<td class="number" style="background-color: #C8E6C9;">' . number_format($grand_total_usd, 2) . '</td>';
        echo '<td class="number" style="background-color: #C8E6C9;">' . number_format($grand_total_iqd, 0) . '</td>';
        echo '</tr>';
    }
    
    echo '</table>';
    echo '</body>';
    echo '</html>';
    
} catch (Exception $e) {
    echo 'هەڵە: ' . htmlspecialchars($e->getMessage());
}

