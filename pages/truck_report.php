<?php
// c:\xampp\htdocs\dana-concrete\pages\truck_report.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../config/db_conected.php';
require_once '../config/permissions.php';

if (!isset($_SESSION['user_id'])) {
    redirectToLogin();
    exit;
}

$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');

// 1. Fetch current exchange rate from settings table (key-value structure)
$rate_stmt = $pdo->prepare("SELECT value FROM settings WHERE name = 'usd_iqd_rate' LIMIT 1");
$rate_stmt->execute();
$settings = $rate_stmt->fetch(PDO::FETCH_ASSOC);
$current_rate = (float)($settings['value'] ?? 150000); // Default to 150,000 if not set

// 2. Main Query for Trucks
$sql = "SELECT 
            ft.id, 
            ft.truck_name,
            ft.plate_number,
            -- Revenue USD and IQD
            (SELECT COALESCE(SUM(p.price), 0) FROM purchases p 
             WHERE p.factory_truck_id = ft.id 
             AND MONTH(p.date) = ? AND YEAR(p.date) = ?) as total_revenue_usd,
            (SELECT COALESCE(SUM(p.amount_iqd), 0) FROM purchases p 
             WHERE p.factory_truck_id = ft.id 
             AND MONTH(p.date) = ? AND YEAR(p.date) = ?) as total_revenue_iqd,
            -- Expenses USD and IQD
            (SELECT COALESCE(SUM(te.amount_usd), 0) FROM truck_expenses te 
             WHERE te.truck_id = ft.id 
             AND MONTH(te.date) = ? AND YEAR(te.date) = ?) as total_expenses_usd,
            (SELECT COALESCE(SUM(te.amount_iqd), 0) FROM truck_expenses te 
             WHERE te.truck_id = ft.id 
             AND MONTH(te.date) = ? AND YEAR(te.date) = ?) as total_expenses_iqd,
            -- Trip Count
            (SELECT COUNT(*) FROM purchases p 
             WHERE p.factory_truck_id = ft.id 
             AND MONTH(p.date) = ? AND YEAR(p.date) = ?) as trip_count
        FROM factory_trucks ft 
        ORDER BY ft.truck_name ASC";

$stmt = $pdo->prepare($sql);
$params = [
    $month, $year, $month, $year, // Revenue
    $month, $year, $month, $year, // Expenses
    $month, $year                 // Trip count
];

try {
    $stmt->execute($params);
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}

// Helper to calculate total equivalent in USD
function calcTotalUsd($usd, $iqd, $rate) {
    // Equation: USD + (IQD / (rate/100))
    // rate is usually like 150000 (for 100$)
    $iqd_in_usd = ($iqd > 0 && $rate > 0) ? ($iqd / ($rate / 100)) : 0;
    return (float)$usd + $iqd_in_usd;
}

// Fleet Totals
$fleet_revenue_total_usd = 0;
$fleet_expenses_total_usd = 0;
?>
<!DOCTYPE html>
<html lang="ku" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ڕاپۆرتی ئەدای تڕێلەکان | دانە کۆنکریت</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
    <link href="../assets/css/variables.css" rel="stylesheet" />
    <link href="../assets/css/nav.css" rel="stylesheet" />
    <link href="../assets/css/comon/style.css" rel="stylesheet" />
    <link href="../assets/css/kurdish-font.css" rel="stylesheet" />

    <style>
        body { background-color: #f4f7f6; font-family: 'Rabar', sans-serif; }
        .report-header {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white; padding: 3rem 0; border-radius: 0 0 40px 40px; margin-bottom: 2rem;
        }
        .stat-card {
            border: none; border-radius: 20px; padding: 1.2rem; transition: all 0.3s ease;
            box-shadow: 0 10px 20px rgba(0,0,0,0.05); background: white; text-align: center;
            height: 100%;
        }
        .stat-card h6 { font-size: 0.9rem; margin-bottom: 10px; }
        .net-profit-card { background: linear-gradient(135deg, #00b09b 0%, #96c93d 100%); color: white; }
        .net-loss-card { background: linear-gradient(135deg, #ff416c 0%, #ff4b2b 100%); color: white; }
        .truck-row {
            background: white; border-radius: 15px; margin-bottom: 1.2rem; padding: 1.2rem;
            border: 1px solid #eee; transition: all 0.2s ease;
        }
        .filter-bar {
            background: white; padding: 0.8rem 1.5rem; border-radius: 50px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05); margin-top: -2rem; position: relative; z-index: 10;
        }
        .badge-trip { background-color: #0f3443; color: #fff; padding: 5px 15px; border-radius: 50px; font-size: 0.8rem; }
        .equivalent-label { font-size: 0.75rem; font-style: italic; color: #666; }
    </style>
</head>
<body>

<?php include '../includes/navbar.php'; ?>
<?php include '../includes/sidebar.php'; ?>

<main class="main-content">
    <div class="report-header text-center">
        <h1 class="fw-bold">ڕاپۆرتی داهات و تێچووی تڕێلەکان</h1>
        <p class="opacity-75">مانگی <?= $month ?> / <?= $year ?> - نرخی سەرف: <?= number_format($current_rate) ?> د.ع</p>
    </div>

    <div class="container">
        <div class="row justify-content-center mb-5">
            <div class="col-md-6 col-lg-5">
                <form method="GET" class="filter-bar d-flex align-items-center gap-3">
                    <select name="month" class="form-select border-0">
                        <?php for($i=1; $i<=12; $i++): ?>
                            <option value="<?= $i ?>" <?= $month == $i ? 'selected' : '' ?>>مانگی <?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                    <select name="year" class="form-select border-0">
                        <?php for($z=date('Y'); $z>=2024; $z--): ?>
                            <option value="<?= $z ?>" <?= $year == $z ? 'selected' : '' ?>><?= $z ?></option>
                        <?php endfor; ?>
                    </select>
                    <button type="submit" class="btn btn-dark rounded-pill px-4">نمایش</button>
                </form>
            </div>
        </div>

        <?php if(!empty($reports)): ?>
            <?php 
            // Calculate Fleet-Wide stats first
            foreach($reports as $row) {
                $fleet_revenue_total_usd += calcTotalUsd($row['total_revenue_usd'], $row['total_revenue_iqd'], $current_rate);
                $fleet_expenses_total_usd += calcTotalUsd($row['total_expenses_usd'], $row['total_expenses_iqd'], $current_rate);
            }
            $fleet_net_total = $fleet_revenue_total_usd - $fleet_expenses_total_usd;
            ?>
            <div class="row g-4 mb-5">
                <div class="col-md-4">
                    <div class="stat-card">
                        <h6 class="text-muted">کۆی گشتی داهات (وەک USD)</h6>
                        <h2 class="fw-bold text-success">$<?= number_format($fleet_revenue_total_usd, 2) ?></h2>
                        <small class="text-muted">داهاتی دۆلار + هاوتای دینار</small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <h6 class="text-muted">کۆی گشتی تێچوو (وەک USD)</h6>
                        <h2 class="fw-bold text-danger">$<?= number_format($fleet_expenses_total_usd, 2) ?></h2>
                        <small class="text-muted">مەسرەفی دۆلار + هاوتای دینار</small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card <?= $fleet_net_total >= 0 ? 'net-profit-card' : 'net-loss-card' ?>">
                        <h6 class="text-white opacity-75">قازانجی پاکی گشتی (فلیت)</h6>
                        <h2 class="fw-bold">$<?= number_format($fleet_net_total, 2) ?></h2>
                        <small class="text-white opacity-75">داهات ناقص مەسرەف</small>
                    </div>
                </div>
            </div>

            <h4 class="fw-bold mb-4"><i class="fas fa-truck-moving me-2 text-primary"></i> وردەکاری بۆ هەر تڕێلەیەک</h4>
            
            <?php foreach($reports as $r): 
                $rev_total = calcTotalUsd($r['total_revenue_usd'], $r['total_revenue_iqd'], $current_rate);
                $exp_total = calcTotalUsd($r['total_expenses_usd'], $r['total_expenses_iqd'], $current_rate);
                $net = $rev_total - $exp_total;
            ?>
            <div class="truck-row shadow-sm">
                <div class="row align-items-center">
                    <div class="col-md-3">
                        <span class="fw-bold fs-5 text-dark"><?= htmlspecialchars($r['truck_name']) ?></span>
                        <div class="text-muted small">تەبلێ: <?= htmlspecialchars($r['plate_number']) ?></div>
                        <div class="mt-2"><span class="badge-trip">گەشتەکان: <?= $r['trip_count'] ?></span></div>
                    </div>
                    <div class="col-md-3 text-center border-start">
                        <div class="text-muted small mb-1">داهات (Revenue)</div>
                        <div class="fw-bold text-primary">$<?= number_format($r['total_revenue_usd'], 2) ?></div>
                        <div class="small text-muted"><?= number_format($r['total_revenue_iqd']) ?> دینار</div>
                        <div class="equivalent-label">کۆی هاوتا: $<?= number_format($rev_total, 2) ?></div>
                    </div>
                    <div class="col-md-3 text-center border-start">
                        <div class="text-muted small mb-1">خەرجی (Expenses)</div>
                        <div class="fw-bold text-danger">$<?= number_format($r['total_expenses_usd'], 2) ?></div>
                        <div class="small text-muted"><?= number_format($r['total_expenses_iqd']) ?> دینار</div>
                        <div class="equivalent-label">کۆی هاوتا: $<?= number_format($exp_total, 2) ?></div>
                    </div>
                    <div class="col-md-3 text-end border-start">
                        <div class="text-muted small mb-1">قازانجی پاک (Net)</div>
                        <div class="fs-4 fw-bold <?= $net >= 0 ? 'text-success' : 'text-danger' ?>">
                            $<?= number_format($net, 2) ?>
                        </div>
                        <div class="small <?= $net >= 0 ? 'text-success' : 'text-danger' ?>">وەک دۆلار</div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-truck-slash fs-1 text-muted opacity-25"></i>
                <h5 class="text-muted mt-3">هیچ بارهەڵگرێک نەدۆزرایەوە!</h5>
            </div>
        <?php endif; ?>
    </div>
</main>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
