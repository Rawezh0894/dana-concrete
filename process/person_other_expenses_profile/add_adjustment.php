<?php
session_start();
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'msg' => 'Unauthorized']);
    exit;
}

if (!hasPermission('view_person_other_expenses_profile')) {
    echo json_encode(['success' => false, 'msg' => 'ڕێگە پێنەدراوە!']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $person_id = (int)($_POST['person_id'] ?? 0);
    $amount_usd = (float)($_POST['amount_usd'] ?? 0);
    $amount_iqd = (float)($_POST['amount_iqd'] ?? 0);
    $date = $_POST['date'] ?? date('Y-m-d');
    $note = trim($_POST['note'] ?? '');

    if ($person_id === 0 || empty($note)) {
        echo json_encode(['success' => false, 'msg' => 'زانیارییەکان تەواو نین']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO person_other_expenses_adjustments (person_id, amount_usd, amount_iqd, date, note) VALUES (?, ?, ?, ?, ?)");
        if ($stmt->execute([$person_id, $amount_usd, $amount_iqd, $date, $note])) {
            
            require_once __DIR__ . '/../../includes/notify.php';
            notify(
                'insert',
                'person_other_expenses_adjustments',
                (int)$pdo->lastInsertId(),
                'ڕێکخستنەوەی قەرز بۆ کەسانی تر (کەس: ' . $person_id . ')'
            );

            echo json_encode(['success' => true, 'msg' => 'ڕێکخستنەوە بەسەرکەوتوویی تۆمارکرا']);
        } else {
            echo json_encode(['success' => false, 'msg' => 'هەڵە لە تۆمارکردنی ڕێکخستنەوە']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'msg' => 'Method not allowed']);
}
