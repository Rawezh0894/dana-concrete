<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/db_conected.php';

function notify($action, $table, $record_id, $description) {
    if (!isset($_SESSION['user_id'])) return;
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, action, table_name, record_id, description) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([
        $_SESSION['user_id'],
        $action,
        $table,
        $record_id,
        $description
    ]);
} 