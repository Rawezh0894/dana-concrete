<?php
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';
require_once __DIR__ . '/concrete_formulas_schema_helper.php';
header('Content-Type: application/json; charset=utf-8');

if (!hasPermission('add_concrete_formulas')) {
    echo json_encode(['success' => false, 'message' => 'ڕێگە پێنەدراوە بۆ زیادکردن.']);
    exit;
}

// Validate required fields
$required = ['name', 'type'];
foreach ($required as $field) {
    if (empty($_POST[$field])) {
        echo json_encode(['success' => false, 'message' => 'تکایە هەموو خانەکان پڕبکەوە']);
        exit;
    }
}

$name = $_POST['name'];
$type = $_POST['type'];
$strength_type = isset($_POST['strength_type']) ? $_POST['strength_type'] : 'kg';

if ($strength_type === 'kg') {
    $strength_kg = isset($_POST['strength_kg']) && $_POST['strength_kg'] !== '' ? $_POST['strength_kg'] : null;
    $strength_mpa = null;
} else if ($strength_type === 'mpa') {
    $strength_kg = null;
    $strength_mpa = isset($_POST['strength_mpa']) && $_POST['strength_mpa'] !== '' ? $_POST['strength_mpa'] : null;
} else {
    $strength_kg = null;
    $strength_mpa = null;
}
$black_sand_kg = isset($_POST['black_sand_kg']) ? $_POST['black_sand_kg'] : 0;
$brown_sand_kg = isset($_POST['brown_sand_kg']) ? $_POST['brown_sand_kg'] : 0;
$gravel_bin3_kg = isset($_POST['gravel_bin3_kg']) ? $_POST['gravel_bin3_kg'] : 0;
$gravel_bin4_kg = isset($_POST['gravel_bin4_kg']) ? $_POST['gravel_bin4_kg'] : 0;
$cement_cem1_kg = isset($_POST['cement_cem1_kg']) ? $_POST['cement_cem1_kg'] : 0;
$cement_cem2_kg = isset($_POST['cement_cem2_kg']) ? $_POST['cement_cem2_kg'] : 0;
$water_kg = isset($_POST['water_kg']) ? $_POST['water_kg'] : 0;
$additive_kg = isset($_POST['additive_kg']) ? $_POST['additive_kg'] : 0;

try {
    ensure_concrete_formula_type_enum($pdo);
    $stmt = $pdo->prepare("INSERT INTO concrete_formulas (name, type, strength_kg, strength_mpa, black_sand_kg, brown_sand_kg, gravel_bin3_kg, gravel_bin4_kg, cement_cem1_kg, cement_cem2_kg, water_kg, additive_kg) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $result = $stmt->execute([
        $name,
        $type,
        $strength_kg,
        $strength_mpa,
        $black_sand_kg,
        $brown_sand_kg,
        $gravel_bin3_kg,
        $gravel_bin4_kg,
        $cement_cem1_kg,
        $cement_cem2_kg,
        $water_kg,
        $additive_kg
    ]);
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'فۆرمولا بە سەرکەوتوویی زیادکرا']);
    } else {
        echo json_encode(['success' => false, 'message' => 'هەڵە لە زیادکردنەوەی فۆرمولا']);
    }
} catch (Exception $e) {
    error_log('Exception in concrete_fomulas/add_formulas.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'هەڵە لە زیادکردنی فۆرمولا!']);
}
