<?php
require_once 'config/db_conected.php';

try {
    // 1. Modify users table role column
    $pdo->exec("ALTER TABLE `users` MODIFY `role` VARCHAR(50) DEFAULT 'user'");
    echo "Modified users table successfully.\n";

    // 2. Ensure roles table has the basic roles
    $roles = ['admin', 'user', 'accountant', 'manager'];
    $stmt = $pdo->prepare("INSERT IGNORE INTO `roles` (`name`) VALUES (?)");
    foreach ($roles as $role) {
        $stmt->execute([$role]);
    }
    echo "Initial roles inserted successfully.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
unlink(__FILE__); // Self-delete
