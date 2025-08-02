<?php
session_start();
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !hasPermission('edit_material')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    // Validate required fields
    $requiredFields = ['id', 'name_ku', 'type', 'unit_type_id', 'base_unit'];
    foreach ($requiredFields as $field) {
        if (empty($_POST[$field])) {
            echo json_encode(['success' => false, 'message' => "فیلدی $field پێویستە"]);
            exit;
        }
    }

    $materialId = (int)$_POST['id'];
    $nameKu = trim($_POST['name_ku']);
    $name = trim($_POST['name'] ?? '');
    $type = $_POST['type'];
    $unitTypeId = (int)$_POST['unit_type_id'];
    $baseUnit = $_POST['base_unit'];
    $conversionFactor = (float)($_POST['conversion_factor'] ?? 1.0);
    $description = trim($_POST['description'] ?? '');

    // Validate material type
    $validTypes = ['black_sand', 'brown_sand', 'gravel', 'cement', 'medicine', 'gas', 'other'];
    if (!in_array($type, $validTypes)) {
        echo json_encode(['success' => false, 'message' => 'جۆری کاڵا نادروستە']);
        exit;
    }

    // Validate base unit
    $validBaseUnits = ['kg', 'liter', 'piece', 'meter'];
    if (!in_array($baseUnit, $validBaseUnits)) {
        echo json_encode(['success' => false, 'message' => 'یەکەی بنەڕەت نادروستە']);
        exit;
    }

    // Check if material exists
    $stmt = $pdo->prepare("SELECT id, name_ku, name, type, unit_type_id, base_unit, conversion_factor, description FROM warehouse_materials WHERE id = ? AND is_active = 1");
    $stmt->execute([$materialId]);
    $existingMaterial = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$existingMaterial) {
        echo json_encode(['success' => false, 'message' => 'کاڵا نەدۆزرایەوە']);
        exit;
    }

    // Check if new name conflicts with other materials
    $stmt = $pdo->prepare("SELECT id FROM warehouse_materials WHERE name_ku = ? AND id != ? AND is_active = 1");
    $stmt->execute([$nameKu, $materialId]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'ناوی کاڵا هەیە، تکایە ناوێکی تر هەڵبژێرە']);
        exit;
    }

    // Validate unit type exists
    $stmt = $pdo->prepare("SELECT id FROM unit_types WHERE id = ? AND is_active = 1");
    $stmt->execute([$unitTypeId]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'جۆری یەکە نەدۆزرایەوە']);
        exit;
    }

    // Get unit type name for conversion details
    $stmt = $pdo->prepare("SELECT name FROM unit_types WHERE id = ?");
    $stmt->execute([$unitTypeId]);
    $unitType = $stmt->fetch(PDO::FETCH_ASSOC);

    // Prepare conversion details based on unit type
    $conversionDetails = [];
    switch ($unitType['name']) {
        case 'carton':
            $piecesPerCarton = (int)($_POST['pieces_per_carton'] ?? 0);
            if ($piecesPerCarton <= 0) {
                echo json_encode(['success' => false, 'message' => 'ژمارەی دانە لە کارتۆن دەبێت لە سفر زیاتر بێت']);
                exit;
            }
            $conversionDetails = [
                'unit_type' => 'carton',
                'pieces_per_carton' => $piecesPerCarton,
                'conversion_factor' => $piecesPerCarton
            ];
            break;

        case 'piece':
            $conversionDetails = [
                'unit_type' => 'piece',
                'conversion_factor' => 1
            ];
            break;

        case 'barrel':
            $bucketsPerBarrel = (int)($_POST['buckets_per_barrel'] ?? 0);
            $litersPerBucket = (int)($_POST['liters_per_bucket'] ?? 0);
            if ($bucketsPerBarrel <= 0 || $litersPerBucket <= 0) {
                echo json_encode(['success' => false, 'message' => 'ژمارەی دەبە و لیتر دەبێت لە سفر زیاتر بن']);
                exit;
            }
            $conversionDetails = [
                'unit_type' => 'barrel',
                'buckets_per_barrel' => $bucketsPerBarrel,
                'liters_per_bucket' => $litersPerBucket,
                'conversion_factor' => $bucketsPerBarrel * $litersPerBucket
            ];
            break;

        case 'bucket':
            $litersPerBucket = (int)($_POST['liters_per_bucket'] ?? 0);
            if ($litersPerBucket <= 0) {
                echo json_encode(['success' => false, 'message' => 'ژمارەی لیتر لە دەبە دەبێت لە سفر زیاتر بێت']);
                exit;
            }
            $conversionDetails = [
                'unit_type' => 'bucket',
                'liters_per_bucket' => $litersPerBucket,
                'conversion_factor' => $litersPerBucket
            ];
            break;

        case 'liter':
            $conversionDetails = [
                'unit_type' => 'liter',
                'conversion_factor' => 1
            ];
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'جۆری یەکەی نەناسراو']);
            exit;
    }

    // Update conversion factor based on unit type
    $conversionFactor = $conversionDetails['conversion_factor'];

    // Begin transaction
    $pdo->beginTransaction();

    try {
        // Update material
        $stmt = $pdo->prepare("
            UPDATE warehouse_materials 
            SET name = ?, name_ku = ?, type = ?, unit_type_id = ?, base_unit = ?, 
                conversion_factor = ?, description = ?, updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        
        $stmt->execute([
            $name,
            $nameKu,
            $type,
            $unitTypeId,
            $baseUnit,
            $conversionFactor,
            $description,
            $materialId
        ]);

        // Log the activity
        $activityDescription = "کاڵا نوێکرایەوە: $nameKu (جۆری یەکە: {$unitType['name']})";
        
        // Prepare old and new values for logging
        $oldValues = [
            'name_ku' => $existingMaterial['name_ku'],
            'name' => $existingMaterial['name'],
            'type' => $existingMaterial['type'],
            'unit_type_id' => $existingMaterial['unit_type_id'],
            'base_unit' => $existingMaterial['base_unit'],
            'conversion_factor' => $existingMaterial['conversion_factor'],
            'description' => $existingMaterial['description']
        ];

        $newValues = [
            'name_ku' => $nameKu,
            'name' => $name,
            'type' => $type,
            'unit_type_id' => $unitTypeId,
            'base_unit' => $baseUnit,
            'conversion_factor' => $conversionFactor,
            'description' => $description
        ];

        // Use the existing log function if available
        if (function_exists('log_user_activity')) {
            log_user_activity(
                $_SESSION['user_id'],
                $_SESSION['username'] ?? 'Unknown',
                'update',
                'warehouse_materials',
                $activityDescription,
                $materialId,
                'warehouse_materials',
                json_encode($oldValues),
                json_encode($newValues),
                $_SERVER['REMOTE_ADDR'] ?? 'Unknown'
            );
        }

        $pdo->commit();

        echo json_encode([
            'success' => true,
            'message' => "کاڵای '$nameKu' بە سەرکەوتوویی نوێکرایەوە",
            'conversion_details' => $conversionDetails
        ]);

    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    error_log("Error updating warehouse material: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'هەڵە لە نوێکردنەوەی کاڵا: ' . $e->getMessage()
    ]);
}
?>
