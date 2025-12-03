<?php
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';
header('Content-Type: application/json; charset=utf-8');

if (!hasPermission('edit_concrete_formulas')) {
    echo json_encode(['success' => false, 'message' => 'ڕێگە پێنەدراوە بۆ نوێکردنەوە.']);
    exit;
}

if (empty($_POST['id']) || !is_numeric($_POST['id'])) {
    echo json_encode(['success' => false, 'message' => 'ناسنامەی هەڵە']);
    exit;
}
$id = (int)$_POST['id'];
$name = $_POST['name'] ?? '';
$type = $_POST['type'] ?? '';
$strength_type = $_POST['strength_type'] ?? 'kg';

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
$black_sand_kg = $_POST['black_sand_kg'] ?? 0;
$brown_sand_kg = $_POST['brown_sand_kg'] ?? 0;
$gravel_bin3_kg = $_POST['gravel_bin3_kg'] ?? 0;
$gravel_bin4_kg = $_POST['gravel_bin4_kg'] ?? 0;
$cement_cem1_kg = $_POST['cement_cem1_kg'] ?? 0;
$cement_cem2_kg = $_POST['cement_cem2_kg'] ?? 0;
$water_kg = $_POST['water_kg'] ?? 0;
$additive_kg = $_POST['additive_kg'] ?? 0;

try {
    $stmt = $pdo->prepare("UPDATE concrete_formulas SET name=?, type=?, strength_kg=?, strength_mpa=?, black_sand_kg=?, brown_sand_kg=?, gravel_bin3_kg=?, gravel_bin4_kg=?, cement_cem1_kg=?, cement_cem2_kg=?, water_kg=?, additive_kg=? WHERE id=?");
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
        $additive_kg,
        $id
    ]);
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'فۆرمولا نوێکرایەوە']);
    } else {
        echo json_encode(['success' => false, 'message' => 'هەڵە لە نوێکردنەوەی فۆرمولا']);
    }
} catch (Exception $e) {
    error_log('Exception in concrete_fomulas/update_formulas.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'هەڵە لە نوێکردنەوەی فۆرمولا!']);
}
