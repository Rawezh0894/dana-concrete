<?php
// c:\xampp\htdocs\dana-concrete\pages\truck_report.php
session_start();
require_once '../config/db_conected.php';
require_once '../config/permissions.php';

if (!isset($_SESSION['user_id'])) {
    redirectToLogin();
    exit;
}

$month = isset($_GET['month']) ? $_GET['month'] : date('m');
$year = isset($_GET['year']) ? $_GET['year'] : date('Y');

// Fetch truck statistics
$sql = "SELECT 
            ft.id, 
            ft.truck_name,
            ft.plate_number,
            (SELECT SUM(price) FROM purchases p WHERE p.factory_truck_id = ft.id AND MONTH(p.date) = ? AND YEAR(p.date) = ?) as total_revenue,
            (SELECT SUM(amount_usd) FROM truck_expenses te WHERE te.truck_id = ft.id AND MONTH(te.date) = ? AND YEAR(te.date) = ?) as total_expenses_usd,
            (SELECT SUM(amount_iqd) FROM truck_expenses te WHERE te.truck_id = ft.id AND MONTH(te.date) = ? AND YEAR(te.date) = ?) as total_expenses_iqd,
            (SELECT COUNT(*) FROM purchases p WHERE p.factory_truck_id = ft.id AND MONTH(p.date) = ? AND YEAR(p.date) = ?) as trip_count
        FROM factory_trucks ft 
        WHERE ft.is_active = 1";

$stmt = $pdo->prepare($sql);
$stmt->execute([$month, $year, $month, $year, $month, $year, $month, $year]);
$reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate overall fleet totals
$fleet_revenue = 0;
$fleet_expenses_usd = 0;
foreach($reports as $r) {
    $fleet_revenue += $r['total_revenue'];
    $fleet_expenses_usd += $r['total_expenses_usd'];
}
$fleet_net = $fleet_revenue - $fleet_expenses_usd;
?>
<!DOCTYPE html>
<html lang="ku" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ڕاپۆرتی تڕێلەکان | دانە کۆنکریت</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
    <link href="../assets/css/variables.css" rel="stylesheet" />
    <link href="../assets/css/nav.css" rel="stylesheet" />
    <link href="../assets/css/comon/style.css" rel="stylesheet" />
    <link href="../assets/css/comon/cards.css" rel="stylesheet" />
    <link href="../assets/css/kurdish-font.css" rel="stylesheet" />

    <style>
        body { background-color: #f8f9fa; font-family: 'Rabar', sans-serif; }
        .report-header {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 4rem 0;
            border-radius: 0 0 50px 50px;
            margin-bottom: 2rem;
        }
        .stat-card {
            border: none;
            border-radius: 20px;
            padding: 1.5rem;
            transition: all 0.3s ease;
            box-shadow: 0 10px 20px rgba(0,0,0,0.05);
            background: white;
            text-align: center;
        }
        .stat-card:hover { transform: translateY(-5px); }
        .net-profit-card {
            background: linear-gradient(135deg, #00b09b 0%, #96c93d 100%);
            color: white;
        }
        .net-loss-card {
            background: linear-gradient(135deg, #ff416c 0%, #ff4b2b 100%);
            color: white;
        }
        .truck-row {
            background: white;
            border-radius: 15px;
            margin-bottom: 1rem;
            padding: 1rem;
            border: 1px solid #eee;
            transition: all 0.2s ease;
        }
        .truck-row:hover { border-color: var(--seafoam-green); box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        .progress { height: 8px; border-radius: 10px; }
        .currency-label { font-size: 0.8rem; opacity: 0.8; }
        .filter-bar {
            background: white;
            padding: 1rem;
            border-radius: 50px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            margin-top: -2rem;
            position: relative;
            z-index: 10;
        }
    </style>
</head>
<body>

<?php include '../includes/navbar.php'; ?>
<?php include '../includes/sidebar.php'; ?>

<main class="main-content">
    <div class="report-header">
        <div class="container text-center">
            <h1 class="display-5 fw-bold">ڕاپۆرتی ئەدای تڕێلەکان</h1>
            <p class="fs-5 opacity-75">داهات و تێچووی تڕێلەکانی ناوخۆ بە شێوەی وردەکاری</p>
        </div>
    </div>

    <div class="container">
        <!-- Filter Bar -->
        <div class="row justify-content-center mb-5">
            <div class="col-md-8">
                <form method="GET" class="filter-bar d-flex align-items-center gap-3">
                    <div class="flex-grow-1">
                        <select name="month" class="form-select border-0 shadow-none">
                            <?php for($i=1; $i<=12; $i++): ?>
                                <option value="<?= str_pad($i, 2, '0', STR_PAD_LEFT) ?>" <?= $month == $i ? 'selected' : '' ?>>مانگی <?= $i ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="flex-grow-1">
                        <select name="year" class="form-select border-0 shadow-none">
                            <?php for($i=date('Y'); $i>=2024; $i--): ?>
                                <option value="<?= $i ?>" <?= $year == $i ? 'selected' : '' ?>><?= $i ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">نمایش بکە</button>
                </form>
            </div>
        </div>

        <!-- Fleet Overview -->
        <div class="row g-4 mb-5">
            <div class="col-md-4">
                <div class="stat-card">
                    <h6 class="text-muted">کۆی گشتی داهات</h6>
                    <h2 class="fw-bold text-primary">$<?= number_format($fleet_revenue, 2) ?></h2>
                    <p class="small text-muted mb-0">کۆی بەهای هەموو ئەو مەوادانەی هێنراوە</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <h6 class="text-muted">کۆی گشتی خەرجی</h6>
                    <h2 class="fw-bold text-danger">$<?= number_format($fleet_expenses_usd, 2) ?></h2>
                    <p class="small text-muted mb-0">سووتەمەنی و چاککردنەوە</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card <?= $fleet_net >= 0 ? 'net-profit-card' : 'net-loss-card' ?>">
                    <h6 class="text-white opacity-75">قازانجی پاکی فلیت</h6>
                    <h2 class="fw-bold text-white">$<?= number_format($fleet_net, 2) ?></h2>
                    <p class="small text-white opacity-75 mb-0">داهات ناقص خەرجی</p>
                </div>
            </div>
        </div>

        <!-- Truck Breakdown -->
        <h4 class="fw-bold mb-4 mt-5"><i class="fas fa-list me-2"></i>وردەکاری بۆ هەر تڕێلەیەک</h4>
        
        <?php foreach($reports as $r): 
            $revenue = $r['total_revenue'] ?? 0;
            $expense = $r['total_expenses_usd'] ?? 0;
            $net = $revenue - $expense;
            $profit_percent = ($revenue > 0) ? min(100, round(($net / $revenue) * 100)) : 0;
        ?>
        <div class="truck-row">
            <div class="row align-items-center">
                <div class="col-md-3">
                    <div class="d-flex align-items-center">
                        <div class="bg-light p-3 rounded-circle me-3">
                            <i class="fas fa-truck text-primary fs-4"></i>
                        </div>
                        <div>
                            <h5 class="mb-0 fw-bold"><?= htmlspecialchars($r['truck_name']) ?></h5>
                            <span class="text-muted small"><?= htmlspecialchars($r['plate_number']) ?></span>
                        </div>
                    </div>
                </div>
                <div class="col-md-2 text-center border-start border-end">
                    <div class="text-muted small">گەشتەکان (Trip)</div>
                    <div class="fs-4 fw-bold text-dark"><?= $r['trip_count'] ?></div>
                </div>
                <div class="col-md-4">
                    <div class="px-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="small text-muted">ئاستی قازانج (Margin)</span>
                            <span class="small fw-bold <?= $net >= 0 ? 'text-success' : 'text-danger' ?>"><?= $profit_percent ?>%</span>
                        </div>
                        <div class="progress">
                            <div class="progress-bar <?= $net >= 0 ? 'bg-success' : 'bg-danger' ?>" style="width: <?= $profit_percent ?>%"></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 text-end">
                    <div class="row">
                        <div class="col-6 text-start">
                            <div class="text-muted small">داهات: <span class="text-primary fw-bold">$<?= number_format($revenue) ?></span></div>
                            <div class="text-muted small">خەرجی: <span class="text-danger fw-bold">$<?= number_format($expense) ?></span></div>
                        </div>
                        <div class="col-6">
                            <div class="text-muted small">قازانجی پاک</div>
                            <div class="fs-5 fw-bold <?= $net >= 0 ? 'text-success' : 'text-danger' ?>">$<?= number_format($net) ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>

        <?php if(empty($reports)): ?>
            <div class="text-center py-5">
                <i class="fas fa-folder-open text-muted fs-1 mb-3"></i>
                <h5 class="text-muted">هیچ داتایەک نەدۆزرایەوە بۆ ئەم مانگە</h5>
            </div>
        <?php endif; ?>
    </div>
</main>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
