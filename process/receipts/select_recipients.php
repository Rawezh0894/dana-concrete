<?php
session_start();

header('Content-Type: application/json; charset=utf-8');

require_once '../../config/db_conected.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$customerId = isset($_GET['customer_id']) ? intval($_GET['customer_id']) : 0;

if ($customerId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Customer ID is required']);
    exit;
}

try {
    $sql = "
        SELECT 
            COALESCE(r.id, c.id, 0) AS id,
            dr.recipient_name AS name,
            COALESCE(r.phone1, c.mobile1, '') AS phone1,
            COALESCE(r.phone2, c.mobile2, '') AS phone2,
            COALESCE(r.opening_meter_total, 0) AS opening_meter_total,
            CASE
                WHEN r.id IS NOT NULL THEN 'recipient_only'
                WHEN c.id IS NOT NULL THEN 'customer_and_recipient'
                ELSE 'sales_only'
            END AS recipient_type
        FROM (
            SELECT DISTINCT TRIM(recipient) AS recipient_name
            FROM sales
            WHERE customer_id = :customer_id
              AND recipient IS NOT NULL
              AND recipient != ''
        ) AS dr
        LEFT JOIN recipients r ON LOWER(TRIM(r.name)) = LOWER(dr.recipient_name)
        LEFT JOIN customers c ON c.is_recipient = 1 AND LOWER(TRIM(c.name)) = LOWER(dr.recipient_name)
        ORDER BY dr.recipient_name ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['customer_id' => $customerId]);

    $recipients = [];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if (!isset($row['name']) || trim($row['name']) === '') {
            continue;
        }

        $name = trim($row['name']);
        $id = $row['id'] ?? 0;

        if (!$id) {
            $id = 'sale_' . substr(md5(strtolower($name)), 0, 8);
        }

        $recipients[] = [
            'id' => $id,
            'name' => $name,
            'phone1' => $row['phone1'] ?? '',
            'phone2' => $row['phone2'] ?? '',
            'opening_meter_total' => $row['opening_meter_total'] ?? 0,
            'recipient_type' => $row['recipient_type'] ?? 'sales_only'
        ];
    }

    echo json_encode(['success' => true, 'data' => $recipients], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}

