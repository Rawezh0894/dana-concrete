<?php
session_start();
require_once '../config/db_conected.php';
require_once '../config/permissions.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}
if (!hasPermission('view_users')) {
    echo '<div style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:100vh;">'
        .'<i class="bi bi-lock-fill" style="font-size:5rem;color:#ccc;"></i>'
        .'<h2 style="color:#888;">توانای دەست گەیشتنت نییە بەم پەیجە</h2>'
        .'</div>';
    exit;
}

// Get database configuration
$host = env('DB_HOST', 'localhost');
$username = env('DB_USERNAME', 'root');
$password = env('DB_PASSWORD', '');
$database = env('DB_DATABASE', 'dana_concrete_db');

// Set backup directory
$backup_dir = '../backups/';
if (!file_exists($backup_dir)) {
    mkdir($backup_dir, 0755, true);
}

// Get existing backups
$backups = [];
if (is_dir($backup_dir)) {
    $files = scandir($backup_dir);
    foreach ($files as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) === 'sql') {
            $file_path = $backup_dir . $file;
            $backups[] = [
                'filename' => $file,
                'size' => filesize($file_path),
                'created' => filemtime($file_path),
                'path' => $file_path
            ];
        }
    }
    // Sort by creation time (newest first)
    usort($backups, function($a, $b) {
        return $b['created'] - $a['created'];
    });
}

// Format file size
function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, 2) . ' ' . $units[$pow];
}
?>
<!DOCTYPE html>
<html lang="ku" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>باک ئەپی داتابەیس</title>
    <link rel="icon" type="image/x-icon" href="../assets/images/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="../assets/css/variables.css" rel="stylesheet">
    <link href="../assets/css/nav.css" rel="stylesheet">
    <link href="../assets/css/comon/style.css" rel="stylesheet">
    <link href="../assets/css/comon/cards.css" rel="stylesheet">
    <link href="../assets/css/comon/table.css" rel="stylesheet">
    <link href="../assets/css/database_backup.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link href="../assets/css/kurdish-font.css" rel="stylesheet">
</head>
<body dir="rtl">
<?php include '../includes/navbar.php'; ?>
<?php include '../includes/sidebar.php'; ?>

<div class="container-fluid py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <h2 class="mb-0" style="color: var(--seafoam-green); font-weight: bold;">
            <i class="fas fa-database"></i>
            باک ئەپی داتابەیس
        </h2>
        <div class="quick-actions d-flex gap-2">
            <button class="btn btn-backup" onclick="createBackup()">
                <i class="fas fa-save"></i> دروستکردنی باک ئەپ
            </button>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="stats-card">
                <div class="stats-number"><?php echo count($backups); ?></div>
                <div class="stats-label">باک ئەپەکان</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stats-card">
                <div class="stats-number"><?php echo $database; ?></div>
                <div class="stats-label">ناوی داتابەیس</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stats-card">
                <div class="stats-number">
                    <span class="status-indicator status-active"></span>
                    چالاک
                </div>
                <div class="stats-label">دۆخی سیستەم</div>
            </div>
        </div>
    </div>

    <!-- Manual Backup -->
    <div class="backup-card">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-save"></i>
                باک ئەپی دەستی
            </h5>
        </div>
        <div class="card-body">
            <p class="mb-3">باک ئەپی دەستی داتابەیس بکە بە شێوەیەکی خێرا و سەلامەت</p>
            <button class="btn btn-backup" onclick="createBackup()">
                <i class="fas fa-download"></i>
                دروستکردنی باک ئەپ
            </button>
        </div>
    </div>

    <!-- Automatic Backup Settings -->
    <div class="backup-card">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-clock"></i>
                باک ئەپی ئۆتۆماتیکی
            </h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">دۆخی باک ئەپی ئۆتۆماتیکی:</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="autoBackupEnabled" checked>
                            <label class="form-check-label" for="autoBackupEnabled">
                                چالاککردنی باک ئەپی ئۆتۆماتیکی
                            </label>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">کاتەکانی باک ئەپ:</label>
                        <select class="form-select" id="backupInterval">
                            <option value="24">هەموو 24 کاتژمێرێک</option>
                            <option value="12">هەموو 12 کاتژمێرێک</option>
                            <option value="6">هەموو 6 کاتژمێرێک</option>
                            <option value="1">هەموو کاتژمێرێک</option>
                        </select>
                    </div>
                </div>
            </div>
            <button class="btn btn-backup" onclick="updateAutoBackupSettings()">
                <i class="fas fa-cog"></i>
                نوێکردنەوەی ڕێکخستنەکان
            </button>
        </div>
    </div>

    <!-- Excel Export Section -->
    <div class="backup-card">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-file-excel"></i>
                Export بۆ Excel
            </h5>
        </div>
        <div class="card-body">
            <!-- Table Exports -->
            <div class="export-group mb-4">
                <h6 class="mb-3">
                    <i class="fas fa-table"></i>
                    Export خشتەکان
                </h6>
                <div class="d-flex flex-wrap gap-2">
                    <button class="btn btn-success" data-export-type="table" data-table-name="customers">
                        <i class="fas fa-file-excel"></i> Export کڕیاران
                    </button>
                    <button class="btn btn-success" data-export-type="table" data-table-name="sales">
                        <i class="fas fa-file-excel"></i> Export فرۆشتن
                    </button>
                    <button class="btn btn-success" data-export-type="table" data-table-name="materials">
                        <i class="fas fa-file-excel"></i> Export ماددەکان
                    </button>
                    <button class="btn btn-success" data-export-type="table" data-table-name="purchases">
                        <i class="fas fa-file-excel"></i> Export کڕین
                    </button>
                    <button class="btn btn-primary" data-export-type="all_tables">
                        <i class="fas fa-file-excel"></i> Export هەموو خشتەکان
                    </button>
                </div>
            </div>
            
            <!-- Report Exports -->
            <div class="export-group">
                <h6 class="mb-3">
                    <i class="fas fa-chart-bar"></i>
                    Export ڕاپۆرتەکان
                </h6>
                <div class="d-flex flex-wrap gap-2">
                    <button class="btn btn-info" data-export-type="sales_report">
                        <i class="fas fa-chart-line"></i> ڕاپۆرتی فرۆشتن
                    </button>
                    <button class="btn btn-info" data-export-type="customers_report">
                        <i class="fas fa-users"></i> ڕاپۆرتی کڕیاران
                    </button>
                    <button class="btn btn-info" data-export-type="materials_report">
                        <i class="fas fa-boxes"></i> ڕاپۆرتی ماددەکان
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Progress Bar -->
    <div class="progress-container" id="progressContainer">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <span>باک ئەپ دەکرێت...</span>
            <span id="progressText">0%</span>
        </div>
        <div class="progress" style="height: 20px;">
            <div class="progress-bar" id="progressBar" role="progressbar" style="width: 0%"></div>
        </div>
    </div>

    <!-- Backup List -->
    <div class="backup-card">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-list"></i>
                لیستی باک ئەپەکان
            </h5>
        </div>
        <div class="card-body">
            <!-- Backup List Controls -->
            <div class="row mb-3">
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" class="form-control" id="backupSearch" placeholder="گەڕان بە ناوی فایل...">
                    </div>
                </div>
                <div class="col-md-3">
                    <select class="form-select" id="backupSort">
                        <option value="newest">نوێترین یەکەم</option>
                        <option value="oldest">کۆنترین یەکەم</option>
                        <option value="name_asc">ناو (A-Z)</option>
                        <option value="name_desc">ناو (Z-A)</option>
                        <option value="size_large">قەبارە (گەورە)</option>
                        <option value="size_small">قەبارە (بچووک)</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select" id="backupFilter">
                        <option value="all">هەموو باک ئەپەکان</option>
                        <option value="manual">باک ئەپەکانی دەستی</option>
                        <option value="auto">باک ئەپەکانی ئۆتۆماتیکی</option>
                        <option value="today">ئەمڕۆ</option>
                        <option value="week">ئەم حەفتەیە</option>
                        <option value="month">ئەم مانگە</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <div class="d-flex gap-2">
                        <button class="btn btn-outline-secondary" onclick="refreshBackupList()" title="نوێکردنەوە">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                        <button class="btn btn-outline-secondary" onclick="toggleViewMode()" title="گۆڕینی دیمەن">
                            <i class="fas fa-th" id="viewModeIcon"></i>
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Backup List Info -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <span class="text-muted">نیشاندانی </span>
                    <span id="showingCount"><?php echo count($backups); ?></span>
                    <span class="text-muted"> لە </span>
                    <span id="totalCount"><?php echo count($backups); ?></span>
                    <span class="text-muted"> باک ئەپ</span>
                </div>
                <div>
                    <button class="btn btn-sm btn-outline-danger" onclick="deleteSelectedBackups()" id="deleteSelectedBtn" style="display: none;">
                        <i class="fas fa-trash"></i> سڕینەوەی هەڵبژاردووەکان
                    </button>
                </div>
            </div>
            
            <!-- Backup List Container -->
            <div class="backup-list" id="backupList">
                <?php if (empty($backups)): ?>
                    <div class="empty-state">
                        <i class="fas fa-database"></i>
                        <h4>هیچ باک ئەپێک نەدۆزرایەوە</h4>
                        <p>یەکەم باک ئەپەکەت دروست بکە بۆ دەستپێکردن</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($backups as $backup): ?>
                        <div class="backup-item" data-filename="<?php echo htmlspecialchars($backup['filename']); ?>" 
                             data-created="<?php echo $backup['created']; ?>" 
                             data-size="<?php echo $backup['size']; ?>"
                             data-type="<?php echo strpos($backup['filename'], 'auto_backup_') === 0 ? 'auto' : 'manual'; ?>">
                            <div class="backup-info">
                                <div class="backup-details">
                                    <div class="form-check d-inline-block me-3">
                                        <input class="form-check-input backup-checkbox" type="checkbox" value="<?php echo htmlspecialchars($backup['filename']); ?>">
                                    </div>
                                    <div class="d-inline-block">
                                        <h6 class="mb-1">
                                            <i class="fas fa-file-archive"></i>
                                            <?php echo htmlspecialchars($backup['filename']); ?>
                                            <?php if (strpos($backup['filename'], 'auto_backup_') === 0): ?>
                                                <span class="badge bg-info ms-2">ئۆتۆماتیکی</span>
                                            <?php else: ?>
                                                <span class="badge bg-primary ms-2">دەستی</span>
                                            <?php endif; ?>
                                        </h6>
                                        <small class="text-muted">
                                            <i class="fas fa-calendar"></i>
                                            <?php echo date('Y-m-d H:i:s', $backup['created']); ?>
                                            <span class="mx-2">|</span>
                                            <i class="fas fa-weight"></i>
                                            <?php echo formatFileSize($backup['size']); ?>
                                        </small>
                                    </div>
                                </div>
                                <div class="backup-actions">
                                    <button class="btn btn-download" onclick="downloadBackup('<?php echo $backup['filename']; ?>')">
                                        <i class="fas fa-download"></i>
                                        داونلۆد
                                    </button>
                                    <button class="btn btn-restore" onclick="restoreBackup('<?php echo $backup['filename']; ?>')">
                                        <i class="fas fa-undo"></i>
                                        گەڕاندنەوە
                                    </button>
                                    <button class="btn btn-delete" onclick="deleteBackup('<?php echo $backup['filename']; ?>')">
                                        <i class="fas fa-trash"></i>
                                        سڕینەوە
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- Pagination -->
            <div class="d-flex justify-content-center mt-3" id="paginationContainer" style="display: none;">
                <nav aria-label="باک ئەپەکان">
                    <ul class="pagination" id="backupPagination">
                        <!-- Pagination will be generated by JavaScript -->
                    </ul>
                </nav>
            </div>
        </div>
    </div>
</div>

<!-- Alert Container -->
<div id="alertContainer" style="position: fixed; top: 20px; right: 20px; z-index: 9999;"></div>

<script src="../assets/js/database_backup.js"></script>
</body>
</html>
