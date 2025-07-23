<?php
require_once '../../config/db_conected.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? 0;
    if ($id) {
        $stmt = $pdo->prepare("DELETE FROM list_materials WHERE id=?");
        $stmt->execute([$id]);
        echo 'success';
    } else {
        echo 'error';
    }
}
