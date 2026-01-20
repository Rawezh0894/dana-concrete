<?php
session_start();
require_once '../../config/db_conected.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    // Check if table exists, if not create it
    $checkTable = $pdo->query("SHOW TABLES LIKE 'material_sales'");
    if ($checkTable->rowCount() == 0) {
        $sql = "CREATE TABLE material_sales (
            id INT AUTO_INCREMENT PRIMARY KEY,
            material_id INT NOT NULL,
            buyer_type ENUM('customer', 'company', 'outsider') NOT NULL,
            customer_id INT NULL,
            company_id INT NULL,
            outsider_name VARCHAR(255) NULL,
            quantity DECIMAL(10,2) NOT NULL,
            unit VARCHAR(50) NOT NULL,
            price DECIMAL(15,2) NOT NULL,
            total_price DECIMAL(15,2) NOT NULL,
            currency ENUM('USD', 'IQD') NOT NULL,
            date DATE NOT NULL,
            note TEXT,
            created_by INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (material_id) REFERENCES list_materials(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $pdo->exec($sql);
    } else {
        // Table exists, check if created_by column exists (migration)
        $checkCol = $pdo->query("SHOW COLUMNS FROM material_sales LIKE 'created_by'");
        if ($checkCol->rowCount() == 0) {
            $pdo->exec("ALTER TABLE material_sales ADD COLUMN created_by INT NULL AFTER note");
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $material_id = $_POST['material_id'];
        $buyer_type = $_POST['buyer_type'];
        $customer_id = !empty($_POST['customer_id']) ? $_POST['customer_id'] : null;
        $company_id = !empty($_POST['company_id']) ? $_POST['company_id'] : null;
        $outsider_name = !empty($_POST['outsider_name']) ? $_POST['outsider_name'] : null;
        $quantity = floatval($_POST['quantity']);
        $unit = $_POST['unit_type'];
        $price = floatval($_POST['price']);
        $total_price = floatval($_POST['total_price']);
        $currency = $_POST['currency'];
        $date = $_POST['date'];
        $note = $_POST['note'];
        $user_id = $_SESSION['user_id'];

        if ($quantity <= 0) {
            throw new Exception("بڕی فرۆشتن نابێت سفر یان کەمتر بێت");
        }

        // Fetch material for conversion check
        $stmt = $pdo->prepare("SELECT * FROM list_materials WHERE id = ?");
        $stmt->execute([$material_id]);
        $material = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$material) {
            throw new Exception("کاڵا دۆزرایەوە");
        }

        // Calculate deduction quantity based on Base Unit
        $deduct_quantity = $quantity;
        
        if ($unit != $material['unit_type']) {
            // Conversion needed
            if ($material['unit_type'] == 'کارتۆن' && $unit == 'دانە') {
                $deduct_quantity = $quantity / $material['pieces_per_carton'];
            } elseif ($material['unit_type'] == 'بەرمیل') {
                if ($unit == 'دەبە') {
                    $deduct_quantity = $quantity / $material['buckets_per_barrel'];
                } elseif ($unit == 'لیتر') {
                    $deduct_quantity = $quantity / $material['liters_per_barrel'];
                }
            } elseif ($material['unit_type'] == 'دەبە' && $unit == 'لیتر') {
                $deduct_quantity = $quantity / $material['liters_per_bucket'];
            }
        }

        // Begin Transaction
        $pdo->beginTransaction();

        // 1. Deduct Stock
        if ($deduct_quantity > $material['quantity']) {
            throw new Exception("بڕی پێویست لە کۆگا بەردەست نییە. بڕی داواکراو (بە یەکەی سەرەکی): " . round($deduct_quantity, 2));
        }

        $updateStmt = $pdo->prepare("UPDATE list_materials SET quantity = quantity - ? WHERE id = ?");
        $updateStmt->execute([$deduct_quantity, $material_id]);

        // 2. Record Sale
        $insertStmt = $pdo->prepare("INSERT INTO material_sales (
            material_id, buyer_type, customer_id, company_id, outsider_name, 
            quantity, unit, price, total_price, currency, date, note, created_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $insertStmt->execute([
            $material_id, $buyer_type, $customer_id, $company_id, $outsider_name,
            $quantity, $unit, $price, $total_price, $currency, $date, $note, $user_id
        ]);

        $pdo->commit();
        echo json_encode(['success' => true]);

    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid request']);
    }

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
