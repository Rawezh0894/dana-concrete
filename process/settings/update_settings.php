<?php
session_start();
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'سێشن نییە! تکایە بچۆ ژوورەوە.']);
    exit;
}

if (!hasPermission('edit_settings')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'ڕێگەت پێنەدراوە!']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'POST only']);
    exit;
}

try {
    $pdo->beginTransaction();
    
    // Update or insert settings
    $settings_to_update = [
        'usd_iqd_rate' => floatval($_POST['usd_iqd_rate'] ?? 0),
        'overtime_rate' => floatval($_POST['overtime_rate'] ?? 0)
    ];
    
    foreach ($settings_to_update as $name => $value) {
        // Check if setting exists
        $stmt = $pdo->prepare('SELECT id FROM settings WHERE name = ?');
        $stmt->execute([$name]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            // Update existing setting
            $stmt = $pdo->prepare('UPDATE settings SET value = ? WHERE name = ?');
            $stmt->execute([$value, $name]);
        } else {
            // Insert new setting
            $stmt = $pdo->prepare('INSERT INTO settings (name, value) VALUES (?, ?)');
            $stmt->execute([$name, $value]);
        }
    }
    
    // Create notification
    $notification_message = "ڕێکخستنەکان نوێکرانەوە";
    createDetailedNotification(
        $pdo,
        $_SESSION['user_id'],
        'update',
        'settings',
        0,
        $notification_message,
        null,
        ['settings' => $settings_to_update],
        ['action_type' => 'settings_update'],
        getUserIP()
    );
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'ڕێکخستنەکان بە سەرکەوتوویی پاشەکەوت کراون'
    ]);
    
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('PDOException in settings/update_settings.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'هەڵە لە پاشەکەوتکردنی ڕێکخستنەکان: ' . $e->getMessage()]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Exception in settings/update_settings.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'هەڵەیەک هەیە: ' . $e->getMessage()]);
}
