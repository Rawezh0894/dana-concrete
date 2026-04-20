<?php
session_start();
require_once '../config/db_conected.php';
require_once '../config/permissions.php';

if (!isset($_SESSION['user_id'])) {
    redirectToLogin();
    exit;
}

if (!hasPermission('print_service_receipts')) {
    echo '<div style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:100vh;">'
        .'<i class="bi bi-lock-fill" style="font-size:5rem;color:#ccc;"></i>'
        .'<h2 style="color:#888;">توانای دەست گەیشتنت نییە بەم پەیجە</h2>'
        .'</div>';
    exit;
}

$id = $_GET['id'] ?? null;
if (!$id) {
    die("ID is required");
}

try {
    $stmt = $pdo->prepare("SELECT sr.*, 
        c.name as customer_name,
        mc.name as mixer_car_name,
        md.name as mixer_driver_name,
        pc.name as pump_car_name,
        pd.name as pump_driver_name
        FROM service_receipts sr
        LEFT JOIN customers c ON sr.customer_id = c.id
        LEFT JOIN cars mc ON sr.mixer_car_id = mc.id
        LEFT JOIN employees md ON sr.mixer_driver_id = md.id
        LEFT JOIN cars pc ON sr.pump_car_id = pc.id
        LEFT JOIN employees pd ON sr.pump_driver_id = pd.id
        WHERE sr.id = ?");
    $stmt->execute([$id]);
    $receipt = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$receipt) {
        die("Receipt not found");
    }
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ku" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>پسوڵەی خزمەتگوزاری - <?= $receipt['receipt_number'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        @font-face {
            font-family: 'Rabar';
            src: url('../assets/fonts/Rabar_021.ttf') format('truetype');
        }
        :root {
            --primary-dark: #1e293b;
            --accent-blue: #2563eb;
            --border-color: #e2e8f0;
        }
        body {
            background-color: #f8fafc;
            font-family: 'Rabar', sans-serif;
            padding: 20px;
            color: #1e293b;
        }
        .receipt-container {
            background: white;
            max-width: 850px;
            margin: 0 auto;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            position: relative;
            border: 1px solid var(--border-color);
        }
        .receipt-header {
            background: var(--primary-dark);
            color: white;
            padding: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .logo-section img {
            height: 70px;
            filter: brightness(0) invert(1);
        }
        .company-details h1 {
            font-size: 1.8rem;
            font-weight: 800;
            margin: 0;
            letter-spacing: 1px;
        }
        .company-details p {
            margin: 0;
            opacity: 0.8;
            font-size: 0.9rem;
        }
        .receipt-meta {
            padding: 25px 30px;
            background: #f8fafc;
            border-bottom: 1px solid var(--border-color);
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }
        .meta-item label {
            display: block;
            font-size: 0.75rem;
            color: #64748b;
            text-transform: uppercase;
            font-weight: 700;
            margin-bottom: 4px;
        }
        .meta-item span {
            font-weight: 700;
            font-size: 1.1rem;
        }
        .receipt-body {
            padding: 30px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-bottom: 30px;
        }
        .info-section h6 {
            color: var(--accent-blue);
            font-weight: 800;
            border-bottom: 2px solid #dbeafe;
            padding-bottom: 8px;
            margin-bottom: 15px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 0.95rem;
        }
        .info-row strong {
            color: #475569;
            font-weight: 500;
        }
        .table-responsive {
            margin: 30px 0;
        }
        .sap-table {
            width: 100%;
            border-collapse: collapse;
        }
        .sap-table th {
            background: #f1f5f9;
            color: #475569;
            font-weight: 700;
            padding: 12px 15px;
            text-align: center;
            border: 1px solid var(--border-color);
        }
        .sap-table td {
            padding: 12px 15px;
            border: 1px solid var(--border-color);
            text-align: center;
        }
        .totals-section {
            margin-top: 30px;
            display: flex;
            justify-content: flex-end;
        }
        .totals-table {
            width: 300px;
        }
        .totals-table tr td {
            padding: 10px 0;
        }
        .totals-table tr td:first-child {
            color: #64748b;
            font-weight: 600;
        }
        .totals-table tr td:last-child {
            text-align: left;
            font-weight: 800;
            font-size: 1.1rem;
        }
        .grand-total {
            border-top: 2px solid var(--primary-dark);
            margin-top: 5px;
            color: var(--accent-blue);
        }
        .signature-area {
            margin-top: 60px;
            display: flex;
            justify-content: space-between;
            padding: 0 30px 40px;
        }
        .sig-box {
            text-align: center;
            width: 200px;
        }
        .sig-line {
            border-top: 1px solid #94a3b8;
            margin-bottom: 8px;
            margin-top: 50px;
        }
        .sig-box span {
            font-size: 0.85rem;
            color: #64748b;
            font-weight: 600;
        }
        .receipt-footer {
            background: #f1f5f9;
            padding: 15px;
            text-align: center;
            font-size: 0.8rem;
            color: #64748b;
            border-top: 1px solid var(--border-color);
        }
        @media print {
            body {
                background: white;
                padding: 0;
            }
            .receipt-container {
                box-shadow: none;
                border: 1px solid #eee;
                max-width: 100%;
                width: 100%;
            }
            .no-print {
                display: none !important;
            }
            .receipt-header, .sap-table th, .meta-item {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
        }
        .btn-toolbar {
            max-width: 850px;
            margin: 20px auto;
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }
    </style>
</head>
<body>

    <div class="btn-toolbar no-print">
        <button onclick="window.print()" class="btn btn-primary">
            <i class="bi bi-printer me-2"></i> چاپکردن
        </button>
        <button onclick="window.close()" class="btn btn-outline-secondary">
            <i class="bi bi-x-lg me-2"></i> داخستن
        </button>
    </div>

    <div class="receipt-container">
        <div class="receipt-header">
            <div class="company-details">
                <h1>کارگەی کۆنکرێتی دانا</h1>
                <p>بۆ کۆنکرێتی ئامادەکراو</p>
            </div>
            <div class="logo-section">
                <img src="../assets/images/logo.png" alt="Logo">
            </div>
        </div>

        <div class="receipt-meta">
            <div class="meta-item">
                <label>ژمارەی پسوڵە</label>
                <span class="text-primary"># <?= $receipt['receipt_number'] ?></span>
            </div>
            <div class="meta-item">
                <label>بەروار و کات</label>
                <span><?= date('Y-m-d', strtotime($receipt['created_at'])) ?></span>
            </div>
            <div class="meta-item">
                <label>جۆری مامەڵە</label>
                <span><?= $receipt['payment_type'] == 'cash' ? 'نەقد (Cash)' : 'قەرز (Credit)' ?></span>
            </div>
        </div>

        <div class="receipt-body">
            <div class="info-grid">
                <div class="info-section">
                    <h6>زانیارییەکانی کڕیار</h6>
                    <div class="info-row">
                        <strong>کۆمپانیا / کڕیار:</strong>
                        <span><?= $receipt['customer_name'] ?></span>
                    </div>
                    <div class="info-row">
                        <strong>شوێن:</strong>
                        <span><?= $receipt['location'] ?: '-' ?></span>
                    </div>
                    <div class="info-row">
                        <strong>وەرگر:</strong>
                        <span><?= $receipt['receiver_name'] ?: '-' ?></span>
                    </div>
                </div>
                <div class="info-section">
                    <h6>زانیارییەکانی بارکردن</h6>
                    <div class="info-row">
                        <strong>میکسەر:</strong>
                        <span><?= $receipt['mixer_car_name'] ?> - <?= $receipt['mixer_driver_name'] ?></span>
                    </div>
                    <div class="info-row">
                        <strong>پەمپ:</strong>
                        <span><?= $receipt['pump_car_name'] ?> - <?= $receipt['pump_driver_name'] ?></span>
                    </div>
                    <div class="info-row">
                        <strong>بڕی مەتر</strong>
                        <span><?= number_format($receipt['meter_amount'], 2) ?> m³</span>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="sap-table">
                    <thead>
                        <tr>
                            <th>بڕ</th>
                            <th>نرخی یەکە</th>
                            <th>کۆی پارە</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <?php if ($receipt['meter_amount'] <= 1.00): ?>
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary border">بڕی جێگیر (Fixed)</span>
                                <?php else: ?>
                                    <?= number_format($receipt['meter_amount'], 2) ?> m³
                                <?php endif; ?>
                            </td>
                            <td>$ <?= number_format($receipt['price_per_meter'], 2) ?></td>
                            <td class="fw-bold">$ <?= number_format($receipt['meter_amount'] * $receipt['price_per_meter'], 2) ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="totals-section">
                <table class="totals-table">
                    <tr>
                        <td>کۆی گشتی:</td>
                        <td>$ <?= number_format($receipt['meter_amount'] * $receipt['price_per_meter'], 2) ?></td>
                    </tr>
                    <tr>
                        <td>دراو:</td>
                        <td>$ <?= number_format($receipt['paid_usd'] + ($receipt['paid_iqd'] / $receipt['exchange_rate']), 2) ?></td>
                    </tr>
                    <tr class="grand-total">
                        <td>بڕی ماوە:</td>
                        <td>$ <?= number_format(($receipt['meter_amount'] * $receipt['price_per_meter']) - ($receipt['paid_usd'] + ($receipt['paid_iqd'] / $receipt['exchange_rate'])), 2) ?></td>
                    </tr>
                </table>
            </div>

            <?php if ($receipt['notes']): ?>
            <div class="mt-4 p-3 bg-light rounded border">
                <small class="d-block text-muted mb-1 text-uppercase fw-bold">تێبینییەکان:</small>
                <div class="small"><?= nl2br(htmlspecialchars($receipt['notes'])) ?></div>
            </div>
            <?php endif; ?>
        </div>

        <div class="signature-area">
            <div class="sig-box">
                <div class="sig-line"></div>
                <span>واژووی ژمێریار</span>
            </div>
            <div class="sig-box">
                <div class="sig-line"></div>
                <span>واژووی کڕیار</span>
            </div>
            <div class="sig-box">
                <div class="sig-line"></div>
                <span>مۆری کارگە</span>
            </div>
        </div>

        <div class="receipt-footer">
            کارگەی دانا بۆ کۆنکرێتی ئامادەکراو - باشترین کوالێتی و خێراترین خزمەتگوزاری
        </div>
    </div>

    <script>
        window.onload = function() {
            // Auto print if requested
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('auto_print')) {
                window.print();
                window.onafterprint = function() {
                    window.close();
                };
            }
        };
    </script>
</body>
</html>
