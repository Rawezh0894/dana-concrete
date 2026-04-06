<?php
// c:\xampp\htdocs\dana-concrete\pages\truck_report.php
session_start();
require_once '../config/db_conected.php';
require_once '../config/permissions.php';

if (!isset($_SESSION['user_id'])) {
    redirectToLogin();
    exit;
}

// Get month and year as integers to avoid string comparison issues
$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');

// Updated SQL with COALESCE to handle NULL values and more robust date filtering
$sql = "SELECT 
            ft.id, 
            ft.truck_name,
            ft.plate_number,
            -- Revenue from Purchases (USD and IQD)
            (SELECT COALESCE(SUM(p.price), 0) FROM purchases p 
             WHERE p.factory_truck_id = ft.id 
             AND MONTH(p.date) = :month AND YEAR(p.date) = :year) as total_revenue_usd,
             
            (SELECT COALESCE(SUM(p.amount_iqd), 0) FROM purchases p 
             WHERE p.factory_truck_id = ft.id 
             AND MONTH(p.date) = :month AND YEAR(p.date) = :year) as total_revenue_iqd,
            
            -- Expenses (USD and IQD)
            (SELECT COALESCE(SUM(te.amount_usd), 0) FROM truck_expenses te 
             WHERE te.truck_id = ft.id 
             AND MONTH(te.date) = :month AND YEAR(te.date) = :year) as total_expenses_usd,
             
            (SELECT COALESCE(SUM(te.amount_iqd), 0) FROM truck_expenses te 
             WHERE te.truck_id = ft.id 
             AND MONTH(te.date) = :month AND YEAR(te.date) = :year) as total_expenses_iqd,
            
            -- Trip Count
            (SELECT COUNT(*) FROM purchases p 
             WHERE p.factory_truck_id = ft.id 
             AND MONTH(p.date) = :month AND YEAR(p.date) = :year) as trip_count
        FROM factory_trucks ft 
        ORDER BY ft.truck_name ASC";

$stmt = $pdo->prepare($sql);
$stmt->bindParam(':month', $month, PDO::PARAM_INT);
$stmt->bindParam(':year', $year, PDO::PARAM_INT);
$stmt->execute();
$reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fleet Totals
$fleet_revenue_usd = 0;
$fleet_expenses_usd = 0;
foreach($reports as $r) {
    $fleet_revenue_usd += $r['total_revenue_usd'];
    $fleet_expenses_usd += $r['total_expenses_usd'];
}
$fleet_net = $fleet_revenue_usd - $fleet_expenses_usd;
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
            background: linear-gradient(135deg, #0f3443 0%, #34e89e 100%);
            color: white; padding: 4rem 0; border-radius: 0 0 50px 50px; margin-bottom: 2rem;
        }
        .stat-card {
            border: none; border-radius: 20px; padding: 1.5rem; transition: all 0.3s ease;
            box-shadow: 0 10px 20px rgba(0,0,0,0.05); background: white; text-align: center;
        }
        .net-profit-card { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: white; }
        .truck-row {
            background: white; border-radius: 15px; margin-bottom: 1rem; padding: 1.2rem;
            border: 1px solid #eee; transition: all 0.2s ease;
        }
        .filter-bar {
            background: white; padding: 1rem; border-radius: 50px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05); margin-top: -2rem; position: relative; z-index: 10;
        }
    </style>
</head>
<body>

<?php include '../includes/navbar.php'; ?>
<?php include '../includes/sidebar.php'; ?>

<main class="main-content">
    <div class="report-header">
        <div class="container text-center">
            <h1 class="display-5 fw-bold">ڕاپۆرتی تڕێلەکان (مانگی <?= $month ?>)</h1>
            <p class="fs-5 opacity-75">داهات و خەرجی و قازانجی پاکی تڕێلەکانی کارگە</p>
        </div>
    </div>

    <div class="container">
        <div class="row justify-content-center mb-5">
            <div class="col-md-8">
                <form method="GET" class="filter-bar d-flex align-items-center gap-3">
                    <div class="flex-grow-1">
                        <select name="month" class="form-select border-0">
                            <?php for($i=1; $i<=12; $i++): ?>
                                <option value="<?= $i ?>" <?= $month == $i ? 'selected' : '' ?>>مانگی <?= $i ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="flex-grow-1">
                        <select name="year" class="form-select border-0">
                            <?php for($i=date('Y'); $i>=2024; $i--): ?>
                                <option value="<?= $i ?>" <?= $year == $i ? 'selected' : '' ?>><?= $i ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-dark rounded-pill px-4">نمایش</button>
                </form>
            </div>
        </div>

        <div class="row g-4 mb-5">
            <div class="col-md-4">
                <div class="stat-card">
                    <h6 class="text-muted">کۆی گشتی داهات (USD)</h6>
                    <h2 class="fw-bold text-success">$<?= number_format($fleet_revenue_usd, 2) ?></h2>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <h6 class="text-muted">کۆی گشتی خەرجی (USD)</h6>
                    <h2 class="fw-bold text-danger">$<?= number_format($fleet_expenses_usd, 2) ?></h2>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card net-profit-card">
                    <h6 class="text-white opacity-75">قازانجی پاک (USD)</h6>
                    <h2 class="fw-bold text-white">$<?= number_format($fleet_net, 2) ?></h2>
                </div>
            </div>
        </div>

        <h4 class="fw-bold mb-4"><i class="fas fa-truck-moving me-2 text-success"></i> وردەکاری تڕێلەکان</h4>
        
        <?php foreach($reports as $r): 
            $revenue = (float)$r['total_revenue_usd'];
            $expense = (float)$r['total_expenses_usd'];
            $net = $revenue - $expense;
            $trips = (int)$r['trip_count'];
        ?>
        <div class="truck-row shadow-sm">
            <div class="row align-items-center">
                <div class="col-md-3">
                    <span class="fw-bold fs-5 text-dark"><?= htmlspecialchars($r['truck_name']) ?></span>
                    <div class="text-muted small"><?= htmlspecialchars($r['plate_number']) ?></div>
                    <div class="mt-1"><span class="badge bg-secondary">گەشتەکان: <?= $trips ?></span></div>
                </div>
                <div class="col-md-3 text-center border-start">
                    <div class="text-muted small">داهات</div>
                    <div class="fw-bold text-primary">$<?= number_format($revenue, 2) ?></div>
                    <div class="small text-muted"><?= number_format($r['total_revenue_iqd']) ?> دڵ</div>
                </div>
                <div class="col-md-3 text-center border-start">
                    <div class="text-muted small">خەرجی</div>
                    <div class="fw-bold text-danger">$<?= number_format($expense, 2) ?></div>
                    <div class="small text-muted"><?= number_format($r['total_expenses_iqd']) ?> دڵ</div>
                </div>
                <div class="col-md-3 text-end border-start">
                    <div class="text-muted small">قازانجی پاک</div>
                    <div class="fs-4 fw-bold <?= $net >= 0 ? 'text-success' : 'text-danger' ?>">
                        $<?= number_format($net, 2) ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>

        <?php if(empty($reports)): ?>
            <div class="alert alert-info text-center rounded-4 p-5">
                <i class="fas fa-info-circle fs-1 mb-3"></i>
                <h5>هیچ زانیارییەک نییە بۆ ئەم تڕێلانە لەم مانگەدا.</h5>
            </div>
        <?php endif; ?>
    </div>
</main>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
