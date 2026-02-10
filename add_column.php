<?php
require_once 'config/db_conected.php';
try {
    $pdo->exec("ALTER TABLE employees ADD COLUMN resignation_date DATE DEFAULT NULL");
    echo json_encode(['success' => true, 'msg' => 'Column resignation_date added successfully']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
