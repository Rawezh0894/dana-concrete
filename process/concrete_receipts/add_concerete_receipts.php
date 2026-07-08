<?php
session_start();
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';
require_once __DIR__ . '/concrete_receipt_helper.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'تکایە بەژمێرەوە!']);
    exit;
}

// Check if user has permission to add concrete receipts
if (!hasPermission('add_concrete_receipts')) {
    echo json_encode(['success' => false, 'message' => 'ڕێگەت پێنەدراوە!']);
    exit;
}

header('Content-Type: application/json');
try {
    // Make sure the DB-level guard exists (idempotent, safe on every call).
    ensureConcreteReceiptUniqueIndex($pdo);

    $receipt_number = trim((string) ($_POST['receipt_number'] ?? ''));
    $customer_id = $_POST['customer_id'] ?? null;
    $location = $_POST['location'] ?? null;
    $meter_amount = $_POST['meter_amount'] ?? null;
    $formulas_id = $_POST['formulas_id'] ?? null;
    $pump_car_id = $_POST['pump_car_id'] ?? null;
    $pump_driver_id = $_POST['pump_driver_id'] ?? null;
    $mixer_car_id = $_POST['mixer_car_id'] ?? null;
    $mixer_driver_id = $_POST['mixer_driver_id'] ?? null;
    $receiver_name = $_POST['receiver_name'] ?? null;

    if (!$location || !$meter_amount || !$formulas_id) {
        echo json_encode(['success' => false, 'message' => 'هەموو خانە پڕ بکە']);
        exit;
    }

    // A number that is empty or follows the auto pattern (<A-Z>-<0000>) may be
    // recomputed by the server on collision. This lets concurrent writers who
    // received the same pre-filled number recover automatically, while a free
    // manually typed number is still honored on the first attempt.
    $canRecompute = concreteReceiptIsAutoNumber($receipt_number);

    $insertSql = "INSERT INTO concrete_receipts
        (receipt_number, customer_id, location, meter_amount, formulas_id, pump_car_id, pump_driver_id, mixer_car_id, mixer_driver_id, receiver_name, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($insertSql);

    $current_timestamp = getCurrentTimestamp();
    $maxAttempts = 30;
    $insertedId = null;
    // First attempt uses the submitted number (or the next number if empty).
    $finalNumber = $receipt_number !== '' ? $receipt_number : concreteReceiptNextNumber($pdo);

    // Friendly pre-check for manually typed numbers (also a safety net if the
    // UNIQUE index is not present yet). Recomputable numbers rely on the retry.
    if (!$canRecompute) {
        $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM concrete_receipts WHERE receipt_number = ?");
        $check_stmt->execute([$finalNumber]);
        if ((int) $check_stmt->fetchColumn() > 0) {
            echo json_encode(['success' => false, 'message' => 'ژمارەی پسوڵە دووبارەیە! تکایە ژمارەیەکی دیکە هەڵبژێرە']);
            exit;
        }
    }

    for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
        if ($attempt > 1) {
            // We only reach here after a duplicate collision on a recomputable
            // number — grab the freshest next number.
            $finalNumber = concreteReceiptNextNumber($pdo);
        }

        try {
            $stmt->execute([
                $finalNumber,
                $customer_id ?: null,
                $location,
                $meter_amount,
                $formulas_id,
                $pump_car_id !== '' ? $pump_car_id : null,
                $pump_driver_id !== '' ? $pump_driver_id : null,
                $mixer_car_id ?: null,
                $mixer_driver_id ?: null,
                $receiver_name,
                $current_timestamp,
            ]);
            $insertedId = $pdo->lastInsertId();
            break; // success
        } catch (PDOException $e) {
            if (concreteReceiptIsDuplicateError($e)) {
                if ($canRecompute && $attempt < $maxAttempts) {
                    // Another user took this number first — jitter briefly and retry.
                    usleep(random_int(1000, 6000));
                    continue;
                }
                echo json_encode(['success' => false, 'message' => 'ژمارەی پسوڵە دووبارەیە! تکایە ژمارەیەکی دیکە هەڵبژێرە']);
                exit;
            }
            throw $e;
        }
    }

    if ($insertedId === null) {
        echo json_encode(['success' => false, 'message' => 'نەتوانرا ژمارەیەکی بەردەست بۆ پسوڵە دابین بکرێت، تکایە دووبارە هەوڵ بدە']);
        exit;
    }

    echo json_encode([
        'success' => true,
        'message' => 'پسوڵە بەسەرکەوتوویی زیادکرا!',
        'id' => $insertedId,
        'receipt_number' => $finalNumber,
    ]);
} catch (PDOException $e) {
    error_log('PDOException in add_concerete_receipts.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
