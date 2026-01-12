<?php
session_start();
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../php-error.log');

require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'سێشن نییە! تکایە بچۆ ژوورەوە.']);
    exit;
}

if (!hasPermission('calculate_depreciation')) {
    echo json_encode(['success' => false, 'message' => 'ڕێگەت پێنەدراوە!']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'تەنها POST ڕێگەپێدراوە']);
    exit;
}

try {
    $asset_id = intval($_POST['asset_id'] ?? 0);
    $period_start = $_POST['period_start'] ?? '';
    $period_end = $_POST['period_end'] ?? '';
    
    if ($asset_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ناسنامەی ئامێر پێویستە!']);
        exit;
    }
    
    if (empty($period_start) || empty($period_end)) {
        echo json_encode(['success' => false, 'message' => 'بەرواری دەستپێکردن و کۆتایی پێویستە!']);
        exit;
    }
    
    // Get asset information
    $assetStmt = $pdo->prepare("SELECT * FROM assets WHERE id = ?");
    $assetStmt->execute([$asset_id]);
    $asset = $assetStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$asset) {
        echo json_encode(['success' => false, 'message' => 'ئامێر نەدۆزرایەوە!']);
        exit;
    }
    
    if ($asset['status'] !== 'active') {
        echo json_encode(['success' => false, 'message' => 'تەنها ئامێرە چالاکەکان دەتوانن داخوران ببن!']);
        exit;
    }
    
    // Check if period already has depreciation
    $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM depreciation_schedules WHERE asset_id = ? AND period_start = ? AND period_end = ?");
    $checkStmt->execute([$asset_id, $period_start, $period_end]);
    if ($checkStmt->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'message' => 'بۆ ئەم ماوەیە داخوران پێشتر ژمێریاری کراوە!']);
        exit;
    }
    
    // Get last depreciation to get accumulated value
    $lastDepStmt = $pdo->prepare("SELECT accumulated_depreciation, book_value FROM depreciation_schedules WHERE asset_id = ? AND is_posted = 1 ORDER BY period_end DESC LIMIT 1");
    $lastDepStmt->execute([$asset_id]);
    $lastDep = $lastDepStmt->fetch(PDO::FETCH_ASSOC);
    
    $accumulated_depreciation = $lastDep ? floatval($lastDep['accumulated_depreciation']) : 0.00;
    $current_book_value = $lastDep ? floatval($lastDep['book_value']) : floatval($asset['purchase_cost']);
    
    // Calculate depreciation based on method
    $depreciation_amount = 0.00;
    $purchase_cost = floatval($asset['purchase_cost']);
    $salvage_value = floatval($asset['salvage_value']);
    $useful_life_years = intval($asset['useful_life_years']);
    $depreciation_method = $asset['depreciation_method'];
    
    // Calculate days in period
    $startDate = new DateTime($period_start);
    $endDate = new DateTime($period_end);
    $daysInPeriod = $startDate->diff($endDate)->days + 1;
    $daysInYear = 365;
    
    if ($depreciation_method === 'straight_line') {
        // Straight-line: (Cost - Salvage) / Useful Life * (Days in Period / Days in Year)
        $annual_depreciation = ($purchase_cost - $salvage_value) / $useful_life_years;
        $depreciation_amount = $annual_depreciation * ($daysInPeriod / $daysInYear);
        
    } else if ($depreciation_method === 'declining_balance') {
        // Declining balance: Book Value * Rate * (Days in Period / Days in Year)
        $rate = floatval($asset['depreciation_rate']) / 100;
        if ($rate <= 0) {
            // Default to double declining balance if rate not set
            $rate = 2 / $useful_life_years;
        }
        $depreciation_amount = $current_book_value * $rate * ($daysInPeriod / $daysInYear);
        
        // Don't depreciate below salvage value
        if (($current_book_value - $depreciation_amount) < $salvage_value) {
            $depreciation_amount = max(0, $current_book_value - $salvage_value);
        }
        
    } else if ($depreciation_method === 'units_of_production') {
        // Units of production: Need usage data for the period
        $unitsUsed = floatval($_POST['units_used'] ?? 0);
        
        if ($unitsUsed <= 0) {
            echo json_encode(['success' => false, 'message' => 'بۆ شێوازی بەپێی بەرهەم، بڕی بەکارهاتوو پێویستە!']);
            exit;
        }
        
        $total_units = floatval($asset['useful_life_units']);
        if ($total_units <= 0) {
            echo json_encode(['success' => false, 'message' => 'کۆی یەکەکانی بەکارهێنان پێویستە!']);
            exit;
        }
        
        $depreciation_per_unit = ($purchase_cost - $salvage_value) / $total_units;
        $depreciation_amount = $depreciation_per_unit * $unitsUsed;
        
        // Log usage
        $usageStmt = $pdo->prepare("INSERT INTO asset_usage_log (asset_id, usage_date, units_used, description) VALUES (?, ?, ?, ?)");
        $usageStmt->execute([
            $asset_id,
            $period_end,
            $unitsUsed,
            "بەکارهێنان بۆ ماوەی $period_start بۆ $period_end"
        ]);
    }
    
    // Ensure depreciation doesn't exceed remaining value
    $max_depreciation = max(0, $current_book_value - $salvage_value);
    $depreciation_amount = min($depreciation_amount, $max_depreciation);
    
    // Calculate new accumulated and book value
    $new_accumulated = $accumulated_depreciation + $depreciation_amount;
    $new_book_value = $current_book_value - $depreciation_amount;
    
    // Ensure book value doesn't go below salvage value
    if ($new_book_value < $salvage_value) {
        $depreciation_amount = $current_book_value - $salvage_value;
        $new_accumulated = $purchase_cost - $salvage_value;
        $new_book_value = $salvage_value;
    }
    
    // Insert depreciation schedule
    $insertStmt = $pdo->prepare("INSERT INTO depreciation_schedules (
        asset_id, period_start, period_end, depreciation_amount,
        accumulated_depreciation, book_value, is_posted, notes
    ) VALUES (?, ?, ?, ?, ?, ?, 0, ?)");
    
    $notes = $_POST['notes'] ?? '';
    $insertStmt->execute([
        $asset_id,
        $period_start,
        $period_end,
        $depreciation_amount,
        $new_accumulated,
        $new_book_value,
        $notes ?: null
    ]);
    
    // Update asset current value and accumulated depreciation (temporary until posted)
    $updateStmt = $pdo->prepare("UPDATE assets SET 
        current_value = ?,
        accumulated_depreciation = ?
    WHERE id = ?");
    $updateStmt->execute([$new_book_value, $new_accumulated, $asset_id]);
    
    echo json_encode([
        'success' => true,
        'message' => 'داخوران بەسەرکەوتوویی ژمێریاری کرا!',
        'data' => [
            'depreciation_amount' => $depreciation_amount,
            'accumulated_depreciation' => $new_accumulated,
            'book_value' => $new_book_value
        ]
    ]);
    
} catch (PDOException $e) {
    error_log('PDOException in depreciation/calculate_depreciation.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'هەڵە لە ژمێریاری داخوران!']);
} catch (Exception $e) {
    error_log('Exception in depreciation/calculate_depreciation.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'هەڵە لە ژمێریاری داخوران!']);
}
