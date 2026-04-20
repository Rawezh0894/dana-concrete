<?php
require_once '../../config/db_conected.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name_ku = $_POST['name_ku'] ?? '';
    if (empty($name_ku)) {
        echo json_encode(['success' => false, 'msg' => 'ناوی پۆلێن دیاری بکە']);
        exit;
    }
    try {
        $stmt = $pdo->prepare("INSERT INTO inv_categories (name_ku) VALUES (?)");
        $stmt->execute([$name_ku]);
        echo json_encode(['success' => true, 'msg' => 'پۆلێن بە سەرکەوتوویی زیادکرا']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
    }
}
?>
