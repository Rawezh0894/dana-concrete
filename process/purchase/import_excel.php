<?php
session_start();
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

header('Content-Type: application/json');

// Check permissions
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if (!hasPermission('add_purchase')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'ڕێگەپێدراوە بۆ زیادکردنی کڕین']);
    exit;
}

// Check if data was sent via POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'تەنها POST method قبوڵ دەکرێت']);
    exit;
}

// Get the JSON data from the request body
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || !is_array($data)) {
    echo json_encode(['success' => false, 'error' => 'هیچ داتایەک نەدۆزرایەوە']);
    exit;
}

try {
    $imported = 0;
    $errors = [];
    
    $pdo->beginTransaction();
    
    foreach ($data as $row) {
        try {
            // Validate required fields
            if (empty($row['company_name']) || empty($row['location_name']) || 
                empty($row['driver_name']) || empty($row['invoice_number']) || 
                empty($row['material_name']) || empty($row['date']) || 
                empty($row['payment_type']) || empty($row['type']) || 
                $row['kg'] <= 0 || $row['price'] <= 0) {
                $errors[] = "هەڵە لە ڕیز: پێویستەکان پڕ نەکراونەتەوە";
                continue;
            }
            
            // Get or create company
            $companyId = getOrCreateCompany($row['company_name'], $pdo);
            
            // Get or create location
            $locationId = getOrCreateLocation($row['location_name'], $pdo);
            
            // Get or create driver
            $driverId = getOrCreateDriver($row['driver_name'], $pdo);
            
            // Get or create material
            $materialId = getOrCreateMaterial($row['material_name'], $pdo);
            
            // Get or create bin
            $binId = null;
            if (!empty($row['bin_name'])) {
                $binId = getOrCreateBin($row['bin_name'], $pdo);
            }
            
            // Calculate remaining amounts
            $remainingUsd = $row['price'] - $row['paid_usd'];
            $remainingIqd = $row['amount_iqd'] - $row['paid_iqd'];
            
            // Insert purchase record
            $stmt = $pdo->prepare("
                INSERT INTO purchases (
                    company_id, location, driver, invoice_number, material_id, 
                    date, payment_type, type, kg, price_per_kg_usd, price_per_kg_iqd,
                    price, amount_iqd, exchange_rate, paid_usd, paid_iqd,
                    remaining_usd, remaining_iqd, bin_id, created_at
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW()
                )
            ");
            
            $stmt->execute([
                $companyId, $row['location_name'], $row['driver_name'], $row['invoice_number'],
                $materialId, $row['date'], $row['payment_type'], $row['type'], $row['kg'],
                $row['price_per_kg_usd'], $row['price_per_kg_iqd'], $row['price'],
                $row['amount_iqd'], $row['exchange_rate'], $row['paid_usd'], $row['paid_iqd'],
                $remainingUsd, $remainingIqd, $binId
            ]);
            
            $imported++;
            
        } catch (Exception $e) {
            $errors[] = "هەڵە لە ئیمپۆرتی ڕیز: " . $e->getMessage();
        }
    }
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'ئیمپۆرت بە سەرکەوتوویی ئەنجام درا',
        'data' => [
            'imported_count' => $imported,
            'total_rows' => count($data),
            'errors' => $errors
        ]
    ]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode([
        'success' => false,
        'error' => 'هەڵە لە ئیمپۆرت: ' . $e->getMessage()
    ]);
}

// Helper functions to get or create related records
function getOrCreateCompany($name, $pdo) {
    $stmt = $pdo->prepare("SELECT id FROM company WHERE name = ?");
    $stmt->execute([$name]);
    $result = $stmt->fetch();
    
    if ($result) {
        return $result['id'];
    }
    
    $stmt = $pdo->prepare("INSERT INTO company (name, created_at) VALUES (?, NOW())");
    $stmt->execute([$name]);
    return $pdo->lastInsertId();
}

function getOrCreateLocation($name, $pdo) {
    $stmt = $pdo->prepare("SELECT id FROM locations WHERE name = ?");
    $stmt->execute([$name]);
    $result = $stmt->fetch();
    
    if ($result) {
        return $result['id'];
    }
    
    $stmt = $pdo->prepare("INSERT INTO locations (name, created_at) VALUES (?, NOW())");
    $stmt->execute([$name]);
    return $pdo->lastInsertId();
}

function getOrCreateDriver($name, $pdo) {
    $stmt = $pdo->prepare("SELECT id FROM drivers WHERE name = ?");
    $stmt->execute([$name]);
    $result = $stmt->fetch();
    
    if ($result) {
        return $result['id'];
    }
    
    $stmt = $pdo->prepare("INSERT INTO drivers (name, created_at) VALUES (?, NOW())");
    $stmt->execute([$name]);
    return $pdo->lastInsertId();
}

function getOrCreateMaterial($name, $pdo) {
    $stmt = $pdo->prepare("SELECT id FROM materials WHERE name = ?");
    $stmt->execute([$name]);
    $result = $stmt->fetch();
    
    if ($result) {
        return $result['id'];
    }
    
    $stmt = $pdo->prepare("INSERT INTO materials (name, created_at) VALUES (?, NOW())");
    $stmt->execute([$name]);
    return $pdo->lastInsertId();
}

function getOrCreateBin($name, $pdo) {
    $stmt = $pdo->prepare("SELECT id FROM bins_silos WHERE name = ?");
    $stmt->execute([$name]);
    $result = $stmt->fetch();
    
    if ($result) {
        return $result['id'];
    }
    
    $stmt = $pdo->prepare("INSERT INTO bins_silos (name, created_at) VALUES (?, NOW())");
    $stmt->execute([$name]);
    return $pdo->lastInsertId();
}
?>
