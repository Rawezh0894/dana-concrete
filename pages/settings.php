<?php
session_start();
require_once '../config/db_conected.php';
require_once '../config/permissions.php';
if (!isset($_SESSION['user_id'])) {
    redirectToLogin();
    exit;
}
if (!hasPermission('view_settings')) {
    echo '<div style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:100vh;">'
        .'<i class="bi bi-lock-fill" style="font-size:5rem;color:#ccc;"></i>'
        .'<h2 style="color:#888;">توانای دەست گەیشتنت نییە بەم پەیجە</h2>'
        .'</div>';
    exit;
}

// Fetch current settings
$settings = [];
try {
    $stmt = $pdo->query('SELECT name, value FROM settings');
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($results as $row) {
        $settings[$row['name']] = $row['value'];
    }
} catch (Exception $e) {
    error_log('Error fetching settings: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ku">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ڕێکخستنەکان</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="../assets/css/login.css" rel="stylesheet">
    <link href="../assets/css/variables.css" rel="stylesheet">
    <link href="../assets/css/nav.css" rel="stylesheet">
    <link href="../assets/css/comon/table.css" rel="stylesheet">
    <link href="../assets/css/comon/style.css" rel="stylesheet">
    <link href="../assets/css/comon/cards.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js" nonce="<?php echo $csp_nonce; ?>"></script>
    <link href="../assets/css/kurdish-font.css" rel="stylesheet">
</head>
<body dir="rtl">
<?php include '../includes/navbar.php'; ?>
<?php include '../includes/sidebar.php'; ?>
<div class="container-fluid py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0" style="color: var(--seafoam-green); font-weight: bold;">ڕێکخستنەکان</h2>
    </div>
    
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-cog me-2"></i>
                        ڕێکخستنی سیستەم
                    </h5>
                </div>
                <div class="card-body">
                    <form id="settingsForm">
                        <!-- نرخی دۆلار -->
                        <div class="mb-4">
                            <label for="usd_iqd_rate" class="form-label">
                                <i class="fas fa-dollar-sign me-2"></i>
                                نرخی ١٠٠ دۆلار (د.ع)
                            </label>
                            <input type="number" 
                                   class="form-control form-control-lg" 
                                   id="usd_iqd_rate" 
                                   name="usd_iqd_rate" 
                                   min="0" 
                                   step="0.01" 
                                   value="<?= htmlspecialchars($settings['usd_iqd_rate'] ?? '0') ?>"
                                   required>
                            <small class="form-text text-muted">
                                نرخی ١٠٠ دۆلار بە دیناری عێراقی
                            </small>
                        </div>
                        
                        <!-- نرخی کاروانحیسابی -->
                        <div class="mb-4">
                            <label for="overtime_rate" class="form-label">
                                <i class="fas fa-clock me-2"></i>
                                نرخی کاروانحیسابی (د.ع)
                            </label>
                            <input type="number" 
                                   class="form-control form-control-lg" 
                                   id="overtime_rate" 
                                   name="overtime_rate" 
                                   min="0" 
                                   step="0.01" 
                                   value="<?= htmlspecialchars($settings['overtime_rate'] ?? '0') ?>"
                                   required>
                            <small class="form-text text-muted">
                                نرخی کاروانحیسابی بە دیناری عێراقی
                            </small>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-success btn-lg" style="background: var(--seafoam-green); font-weight: bold;">
                                <i class="fas fa-save me-2"></i>
                                پاشەکەوتکردن
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" nonce="<?php echo $csp_nonce; ?>"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11" nonce="<?php echo $csp_nonce; ?>"></script>
<script src="../assets/js/swalAlert.js" nonce="<?php echo $csp_nonce; ?>"></script>
<script nonce="<?php echo $csp_nonce; ?>">
$(function() {
    $('#settingsForm').on('submit', function(e) {
        e.preventDefault();
        
        var formData = $(this).serialize();
        
        $.post('../process/settings/update_settings.php', formData, function(response) {
            if (response.success) {
                swalAlert('سەرکەوتوو', 'ڕێکخستنەکان بە سەرکەوتوویی پاشەکەوت کراون!', 'success');
            } else {
                swalAlert('هەڵە', response.message || 'هەڵەیەک هەیە', 'error');
            }
        }, 'json').fail(function(xhr) {
            console.error('AJAX Error:', xhr.responseText);
            let msg = 'هەڵەیەک هەیە لە پەیوەندیدا.';
            if (xhr.responseText) {
                try {
                    var errorResponse = JSON.parse(xhr.responseText);
                    if (errorResponse.message) {
                        msg = errorResponse.message;
                    }
                } catch (e) {
                    msg += '\n' + xhr.responseText;
                }
            }
            swalAlert('هەڵە', msg, 'error');
        });
    });
});
</script>
</body>
</html>
