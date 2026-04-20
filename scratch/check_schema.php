<?php
require_once dirname(__DIR__) . '/config/db_conected.php';

try {
    $stmt = $pdo->query("DESCRIBE purchase_materials");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($columns, JSON_PRETTY_PRINT);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
