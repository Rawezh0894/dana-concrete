<?php
session_start();
require_once '../config/db_conected.php';
require_once '../config/permissions.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ku" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>داشبۆرد</title>
    <link rel="icon" type="image/x-icon" href="../assets/images/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="../assets/css/variables.css" rel="stylesheet">
    <link href="../assets/css/dashboard.css" rel="stylesheet">
    <link href="../assets/css/comon/cards.css" rel="stylesheet">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" nonce="<?php echo $csp_nonce; ?>"></script>

    <link href="../assets/css/kurdish-font.css" rel="stylesheet">
</head>
<body dir="rtl">
<?php include '../includes/navbar.php'; ?>
<?php include '../includes/sidebar.php'; ?>

<?php if (!hasPermission('view_dashboard')): ?>
    <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:100vh;">
        <i class="bi bi-lock-fill" style="font-size:5rem;color:#ccc;"></i>
        <h2 style="color:#888;">توانای دەست گەیشتنت نییە بەم پەیجە</h2>
    </div>
<?php else: ?>
    <div class="container-fluid py-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
            <h2 class="mb-0" style="color: var(--seafoam-green); font-weight: bold;">داشبۆرد</h2>
            <div class="quick-actions d-flex gap-2">
                <button class="btn quick-action" onclick="location.href='../pages/concrete_receipts.php'"><i class="fa fa-plus"></i> پسوڵە</button>
                <button class="btn  quick-action" onclick="location.href='../pages/add_sale.php'"><i class="fa fa-plus"></i> فرۆشتن</button>
                <button class="btn  quick-action" onclick="location.href='../pages/add_purchase.php'"><i class="fa fa-plus"></i> کڕین</button>
            </div>
        </div>
        
        <!-- Summary Cards Row 1 -->
        <div class="row g-3 mb-4" id="dashboard-summary-cards">
            <!-- Cards will be loaded by JS -->
        </div>
        
        <!-- Stock Status Row -->
        <div class="row g-3 mb-4" id="stock-status-cards">
            <!-- Stock status cards will be loaded by JS -->
        </div>
        
        <div class="row g-3">
            <div class="col-lg-6 mb-3">
                <div class="card shadow h-100">
                    <div class="card-body">
                        <h5 class="card-title mb-3" style="color: var(--seafoam-green); font-weight: bold;">
                            <i class="bi bi-clock-history me-2"></i>دواین چالاکییەکان
                        </h5>
                        <ul class="list-group list-group-flush" id="dashboard-recent-activities">
                            <!-- Recent activities will be loaded by JS -->
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 mb-3">
                <div class="card shadow h-100">
                    <div class="card-body">
                        <h5 class="card-title mb-3" style="color: var(--seafoam-green); font-weight: bold;">
                            <i class="bi bi-lightning me-2"></i>کردارە خێراکان
                        </h5>
                        <div class="row g-2" id="dashboard-quick-links">
                            <!-- Quick links will be loaded by JS -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Additional Dashboard Sections -->
        <div class="row g-3">
            <div class="col-lg-8 mb-3">
                <div class="card shadow h-100">
                    <div class="card-body">
                        <h5 class="card-title mb-3" style="color: var(--seafoam-green); font-weight: bold;">
                            <i class="bi bi-graph-up me-2"></i>ئامارە گرنگەکان
                        </h5>
                        <div class="row g-3" id="dashboard-stats">
                            <!-- Statistics will be loaded by JS -->
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 mb-3">
                <div class="card shadow h-100">
                    <div class="card-body">
                        <h5 class="card-title mb-3" style="color: var(--seafoam-green); font-weight: bold;">
                            <i class="bi bi-bell me-2"></i>ئاگادارکردنەوەکان
                        </h5>
                        <div id="dashboard-notifications">
                            <!-- Notifications will be loaded by JS -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<script nonce="<?php echo $csp_nonce; ?>">
    // Pass permissions to JavaScript
    window.userPermissions = {
        canViewDashboardPrices: <?php echo hasPermission('view_dashboard_prices') ? 'true' : 'false'; ?>
    };
</script>
<script src="../assets/js/dashboard/select_information.js" nonce="<?php echo $csp_nonce; ?>"></script>
</body>
</html>
