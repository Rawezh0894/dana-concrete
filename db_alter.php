<?php require 'config/db_conected.php'; $stmt = $pdo->query('SHOW COLUMNS FROM purchases'); print_r($stmt->fetchAll(PDO::FETCH_ASSOC)); ?>
