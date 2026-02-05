<?php
require_once '../../config/db_conected.php';

try {
    $sql = "CREATE TABLE IF NOT EXISTS `monthly_material_cost_overrides` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `year` int(4) NOT NULL,
      `month` int(2) NOT NULL,
      `cost_usd` decimal(15,2) NOT NULL,
      `note` text DEFAULT NULL,
      `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
      `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
      PRIMARY KEY (`id`),
      UNIQUE KEY `unique_year_month` (`year`,`month`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    $pdo->exec($sql);
    echo "Table 'monthly_material_cost_overrides' created successfully (or already exists).";
} catch (PDOException $e) {
    echo "Error creating table: " . $e->getMessage();
}
?>
