<?php
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';
if (!hasPermission('view_person_other_expenses_profile')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'msg' => 'ڕێگە پێنەدراو']);
    exit;
}
$person_id = isset($_GET['person_id']) ? intval($_GET['person_id']) : 0;
$sql = "SELECT * FROM person_other_expenses_debt_payments WHERE person_id = ? ORDER BY id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$person_id]);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($data);
