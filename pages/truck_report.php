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

// Fetch current exchange rate from settings
$rate_stmt = $pdo->prepare("SELECT value FROM settings WHERE name = 'usd_iqd_rate' LIMIT 1");
$rate_stmt->execute();
$current_rate = (float)($rate_stmt->fetchColumn() ?: 150000);

// Use trip_count * commission_per_trip from the factory_trucks table
$sql = "SELECT 
            ft.id, 
            ft.truck_name,
            ft.plate_number,
            ft.commission_per_trip,
            -- Revenue IQD and USD
            (SELECT COALESCE(SUM(p.amount_iqd), 0) FROM purchases p 
             WHERE p.factory_truck_id = ft.id 
             AND MONTH(p.date) = ? AND YEAR(p.date) = ?) as total_revenue_iqd,
            (SELECT COALESCE(SUM(p.price), 0) FROM purchases p 
             WHERE p.factory_truck_id = ft.id 
             AND MONTH(p.date) = ? AND YEAR(p.date) = ?) as total_revenue_usd,
            -- Expenses IQD and USD
            (SELECT COALESCE(SUM(te.amount_iqd), 0) FROM truck_expenses te 
             WHERE te.truck_id = ft.id 
             AND MONTH(te.date) = ? AND YEAR(te.date) = ?) as total_expenses_iqd,
            (SELECT COALESCE(SUM(te.amount_usd), 0) FROM truck_expenses te 
             WHERE te.truck_id = ft.id 
             AND MONTH(te.date) = ? AND YEAR(te.date) = ?) as total_expenses_usd,
            -- Trip Count (Commission is calculated as trip_count * commission_per_trip)
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

function toIQD($usd, $iqd, $rate) {
    return (float)$iqd + ((float)$usd * ($rate / 100));
}

// Fleet Totals
$fleet_rev_iqd = 0;
$fleet_exp_iqd = 0;
$fleet_comm_iqd = 0;
foreach($reports as $r) {
    $fleet_rev_iqd += toIQD($r['total_revenue_usd'], $r['total_revenue_iqd'], $current_rate);
    $fleet_exp_iqd += toIQD($r['total_expenses_usd'], $r['total_expenses_iqd'], $current_rate);
    $fleet_comm_iqd += (int)$r['trip_count'] * (float)$r['commission_per_trip'];
}
$fleet_net_iqd = $fleet_rev_iqd - ($fleet_exp_iqd + $fleet_comm_iqd);
?>
<!DOCTYPE html>
<html lang="ku" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>ڕاپۆرتی تڕێلەکان | دانە کۆنکریت</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
    <link href="../assets/css/variables.css" rel="stylesheet" />
    <link href="../assets/css/nav.css" rel="stylesheet" />
    <link href="../assets/css/comon/style.css" rel="stylesheet" />
    <link href="../assets/css/kurdish-font.css" rel="stylesheet" />
    <style>
        body { background-color: #f8f9fa; font-family: 'Rabar', sans-serif; }
        .report-header { background: #1e3c72; color: white; padding: 2rem 0; text-align: center; border-radius: 0 0 40px 40px; margin-bottom: 2rem; }
        .stat-card { border: none; border-radius: 20px; padding: 1.5rem; background: white; text-align: center; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .truck-row { background: white; border-radius: 15px; margin-bottom: 1rem; padding: 1.25rem; border: 1px solid #eee; }
        .filter-bar { background: white; padding: 1rem 2rem; border-radius: 50px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); margin-top: -2rem; position: relative; z-index: 10; }
    </style>
</head>
<body>
<?php include '../includes/navbar.php'; include '../includes/sidebar.php'; ?>
<main class="main-content">
    <div class="report-header">
        <h1 class="fw-bold">ڕاپۆرتی دارایی تڕێلەکان (IQD)</h1>
        <p class="opacity-75">مانگی <?= $month ?> / <?= $year ?></p>
    </div>
    <div class="container">
        <div class="row justify-content-center mb-5">
            <div class="col-md-5">
                <form method="GET" class="filter-bar d-flex align-items-center gap-3">
                    <select name="month" class="form-select border-0 shadow-none"><?php for($i=1;$i<=12;$i++) echo "<option value='$i' ".($month==$i?'selected':'').">مانگی $i</option>"; ?></select>
                    <select name="year" class="form-select border-0 shadow-none"><?php for($y=date('Y');$y>=2024;$y--) echo "<option value='$y' ".($year==$y?'selected':'').">$y</option>"; ?></select>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">نمایش</button>
                </form>
            </div>
        </div>
        <div class="row g-4 mb-5">
            <div class="col-md-4"><div class="stat-card"><h6>کۆی داهات</h6><h3 class="fw-bold text-success"><?= number_format($fleet_rev_iqd) ?></h3></div></div>
            <div class="col-md-4"><div class="stat-card"><h6>کۆی خەرجی و پاداشت</h6><h3 class="fw-bold text-danger"><?= number_format($fleet_exp_iqd + $fleet_comm_iqd) ?></h3></div></div>
            <div class="col-md-4"><div class="stat-card border-success border-2"><h6>قازانجی پاک</h6><h3 class="fw-bold text-dark"><?= number_format($fleet_net_iqd) ?></h3></div></div>
        </div>
        <?php foreach($reports as $r): 
            $rev = toIQD($r['total_revenue_usd'], $r['total_revenue_iqd'], $current_rate);
            $exp = toIQD($r['total_expenses_usd'], $r['total_expenses_iqd'], $current_rate);
            $comm = (int)$r['trip_count'] * (float)$r['commission_per_trip'];
            $net = $rev - ($exp + $comm);
        ?>
        <div class="truck-row shadow-sm">
            <div class="row align-items-center">
                <div class="col-md-3"><strong><?= htmlspecialchars($r['truck_name']) ?></strong><div class="text-muted small">تەبلێ: <?= htmlspecialchars($r['plate_number']) ?></div><div class="text-primary small mt-1">گەشتەکان: <?= $r['trip_count'] ?></div></div>
                <div class="col-md-3 text-center border-start">داهات<div class="fw-bold text-success"><?= number_format($rev) ?></div></div>
                <div class="col-md-3 text-center border-start">خەرجی + پاداشت<div class="fw-bold text-danger"><?= number_format($exp + $comm) ?></div><small class="text-muted">پاداشت: <?= number_format($comm) ?></small></div>
                <div class="col-md-3 text-end border-start">قازانج (Net)<div class="fs-4 fw-bold"><?= number_format($net) ?></div>
                <a href="truck_monthly_report_print.php?id=<?= $r['id'] ?>&month=<?= $month ?>&year=<?= $year ?>" target="_blank" class="btn btn-outline-dark btn-sm rounded-pill mt-2"><i class="fas fa-print me-1"></i></a></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
