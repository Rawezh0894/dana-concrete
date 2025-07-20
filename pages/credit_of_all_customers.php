<?php
session_start();
require_once '../config/db_conected.php';
require_once '../config/permissions.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}
if (!hasPermission('view_customer')) {
    echo '<div style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:100vh;">'
        .'<i class="bi bi-lock-fill" style="font-size:5rem;color:#ccc;"></i>'
        .'<h2 style="color:#888;">توانای دەست گەیشتنت نییە بەم پەیجە</h2>'
        .'</div>';
    exit;
}
// Query all customers with debt (USD or IQD > 0)
$sql = "SELECT c.id, c.name, c.mobile1, c.mobile2, c.debt_usd, c.debt_iqd FROM customers c WHERE c.debt_usd > 0 OR c.debt_iqd > 0 ORDER BY c.name ASC";
$stmt = $pdo->query($sql);
$customers = $stmt ? $stmt->fetchAll() : [];
// For each customer, get total concrete meter (from sales table, quantity sum) and all transactions with remaining debt
function getTotalConcreteMeter($pdo, $customer_id) {
    $sql = "SELECT SUM(quantity) as total_meter FROM sales WHERE customer_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$customer_id]);
    $row = $stmt->fetch();
    return $row && $row['total_meter'] ? $row['total_meter'] : 0;
}
function getDebtTransactions($pdo, $customer_id) {
    $sql = "SELECT invoice_number, order_date, total_price, payment_type, amount_paid_usd, amount_paid_iq, remaining_amount FROM sales WHERE customer_id = ? AND remaining_amount > 0 ORDER BY order_date ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$customer_id]);
    return $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="ku">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="../assets/css/credit_of_all_customers.css" rel="stylesheet">
    <title>پرێنتی قەرزەکانی کڕیاران</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">

</head>
<body dir="rtl">
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4 no-print">
        <h2 class="mb-0" style="color: #00796b; font-weight: bold;">پرێنتی قەرزەکانی کڕیاران</h2>
        <button onclick="window.print()" class="btn btn-primary">پرێنت</button>
    </div>
    <?php if (empty($customers)): ?>
        <div class="alert alert-info">هیچ کڕیارێک قەرزی نییە.</div>
    <?php else: ?>
        <?php foreach ($customers as $customer): ?>
            <div class="customer-section">
                <div class="customer-header">
                    <strong>ناو:</strong> <?= htmlspecialchars($customer['name']) ?> |
                    <strong>ژمارە مۆبایل:</strong> <?= htmlspecialchars($customer['mobile1']) ?>
                    <?php if (!empty($customer['mobile2'])): ?> / <?= htmlspecialchars($customer['mobile2']) ?><?php endif; ?>
                    |
                    <strong>کۆی قەرز:</strong>
                    <?= ($customer['debt_usd'] > 0 ? number_format($customer['debt_usd'],2).' USD' : '') ?>
                    <?= ($customer['debt_iqd'] > 0 ? number_format($customer['debt_iqd'],0).' IQD' : '') ?>
                    |
                    <strong>کۆی مەتری سێجا:</strong> <?= number_format(getTotalConcreteMeter($pdo, $customer['id']),2) ?>
                </div>
                <?php $transactions = getDebtTransactions($pdo, $customer['id']); ?>
                <?php if (!empty($transactions)): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm align-middle text-center mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>ژمارە پسوڵە</th>
                                    <th>بەروار</th>
                                    <th>کۆی گشتی</th>
                                    <th>جۆری پارەدان</th>
                                    <th>پارەی داوە (USD)</th>
                                    <th>پارەی داوە (IQD)</th>
                                    <th>قەرزی ماوە</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($transactions as $tr): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($tr['invoice_number']) ?></td>
                                        <td><?= htmlspecialchars($tr['order_date']) ?></td>
                                        <td><?= number_format($tr['total_price'],2) ?></td>
                                        <td><?= htmlspecialchars($tr['payment_type']) ?></td>
                                        <td><?= number_format($tr['amount_paid_usd'],2) ?></td>
                                        <td><?= number_format($tr['amount_paid_iq'],0) ?></td>
                                        <td><?= number_format($tr['remaining_amount'],2) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning mb-0">هیچ مامەڵەی قەرزی ماوە نییە.</div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
</body>
</html>
