<?php
session_start();
require_once '../config/db_conected.php';
require_once '../config/permissions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

// Check if user has permission to view notes
if (!hasPermission('view_notes')) {
    echo '<div style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:100vh;">'
        .'<i class="bi bi-lock-fill" style="font-size:5rem;color:#ccc;"></i>'
        .'<h2 style="color:#888;">توانای دەست گەیشتنت نییە بەم پەیجە</h2>'
        .'</div>';
    exit;
}

// Get data for dropdowns
$customers = $pdo->query("SELECT id, name FROM customers ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$formulas = $pdo->query("SELECT id, name FROM concrete_formulas ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$mixer_cars = $pdo->query("SELECT id, name FROM cars WHERE name LIKE 'M%' ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$pump_cars = $pdo->query("SELECT id, name FROM cars WHERE name LIKE 'P%' ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$all_drivers = $pdo->query("SELECT id, name FROM employees WHERE role = 'شۆفێر' ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Filter drivers for pump (only: بازیان, بەرزان, شاڵاو, سەربەست)
$pump_driver_names = ['بازیان', 'بەرزان', 'شاڵاو', 'سەربەست'];
$pump_drivers = array_filter($all_drivers, function($driver) use ($pump_driver_names) {
    return in_array(trim($driver['name']), $pump_driver_names, true);
});

// Filter drivers for mixer (all drivers except: شاڵاو, سەربەست, بەرزان)
$excluded_mixer_driver_names = ['شاڵاو', 'سەربەست', 'بەرزان'];
$mixer_drivers = array_filter($all_drivers, function($driver) use ($excluded_mixer_driver_names) {
    return !in_array(trim($driver['name']), $excluded_mixer_driver_names, true);
});
?>

<!DOCTYPE html>
<html lang="ku" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تێبینیەکان - دانا کۆنکرێت</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="../assets/css/login.css" rel="stylesheet">
    <link href="../assets/css/variables.css" rel="stylesheet">
    <link href="../assets/css/nav.css" rel="stylesheet">
    <link href="../assets/css/comon/table.css" rel="stylesheet">
    <link href="../assets/css/comon/style.css" rel="stylesheet">
    <link href="../assets/css/comon/cards.css" rel="stylesheet" />
    <link href="../assets/css/comon/select2_design.css" rel="stylesheet">
    <link href="../assets/css/notes.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.rtl.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
</head>
<body dir="rtl">
<?php include '../includes/navbar.php'; ?>
<?php include '../includes/sidebar.php'; ?>

<div class="container-fluid py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">

        <div class="d-flex gap-2">
            <?php if (hasPermission('view_concrete_receipts')): ?>
            <a href="concrete_receipts.php" class="btn" style="background: var(--seafoam-green); color:white; font-weight: bold;">
                <i class="fas fa-file-alt me-1"></i>پسووڵەی کۆنکرێت
            </a>
            <?php endif; ?>
        <?php if (hasPermission('add_notes')): ?>
        <button class="btn" data-bs-toggle="modal" data-bs-target="#addNoteModal" style="background: var(--seafoam-green); color:white; font-weight: bold;">
            <i class="fas fa-plus me-2"></i>زیادکردنی تێبینی
        </button>
        <?php endif; ?>
        </div>
    </div>

    <!-- Summary Cards Row -->
    <div class="row mb-3" id="notes-summary">
        <div class="col-md-4 mb-2">
            <div class="card text-center shadow card-gradient-info card-animate-hover">
                <div class="card-body">
                    <i class="fas fa-sticky-note card-icon"></i>
                    <h6 class="card-title">کۆی گشتی تێبینیەکان</h6>
                    <div class="fs-4 fw-bold" id="summary_total_notes">0</div>
                    <small class="text-light">هەموو تێبینیەکان</small>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-2">
            <div class="card text-center shadow  card-gradient-success card-animate-hover">
                <div class="card-body">
                    <i class="fas fa-check-circle card-icon"></i>
                    <h6 class="card-title">تێبینیە خوێندراوەکان</h6>
                    <div class="fs-4 fw-bold" id="summary_read_notes">0</div>
                    <small class="text-light">تێبینیە خوێندراوەکان</small>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-2">
            <div class="card text-center shadow  card-gradient-warning card-animate-hover">
                <div class="card-body">
                    <i class="fas fa-exclamation-circle card-icon"></i>
                    <h6 class="card-title">تێبینیە نەخوێندراوەکان</h6>
                    <div class="fs-4 fw-bold" id="summary_unread_notes">0</div>
                    <small class="text-light">تێبینیە نەخوێندراوەکان</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="row mb-3">
        <div class="col-md-2">
            <label>لە بەروار:</label>
            <input type="date" id="filter_from" class="form-control">
        </div>
        <div class="col-md-2">
            <label>بۆ بەروار:</label>
            <input type="date" id="filter_to" class="form-control">
        </div>
        <div class="col-md-2">
            <label>کڕیار:</label>
            <select id="filter_customer" class="form-select">
                <option value="">هەموو کڕیارەکان</option>
                <?php foreach ($customers as $customer): ?>
                    <option value="<?= $customer['id'] ?>"><?= htmlspecialchars($customer['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label>خوێنراوە:</label>
            <select id="filter_read" class="form-select">
                <option value="">هەموو</option>
                <option value="0">نەخوێندراو</option>
                <option value="1">خوێندرا</option>
            </select>
        </div>
        <div class="col-md-4 d-flex align-items-end gap-2">
            <button class="btn btn-outline-primary" id="filterToday" type="button">ئەمڕۆ</button>
            <button class="btn btn-outline-primary" id="filterTomorrow" type="button">بەیانی</button>
            <button class="btn btn-outline-primary" id="filterYesterday" type="button">دوێنی</button>
            <button class="btn btn-secondary" id="clearFilterBtn" type="button">پاککردنەوە</button>
        </div>
    </div>

    <!-- Notes Cards Grid -->
    <div id="notesGrid" class="notes-grid ">
        <!-- Notes cards will be loaded here by JS -->
    </div>
    
    <!-- Load More Button -->
    <div id="loadMoreBtn" class="load-more-container" style="display: none;">
        <button class="load-more-btn">
            <i class="fas fa-chevron-down"></i>
            زیاتر ببینە
        </button>
    </div>
</div>

<!-- Add Note Modal -->
<div class="modal fade" id="addNoteModal" tabindex="-1" aria-labelledby="addNoteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="addNoteForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="addNoteModalLabel">زیادکردنی تێبینی</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="date" class="form-label">بەروار *</label>
                            <input type="date" class="form-control" id="date" name="date" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="time" class="form-label">سەعات *</label>
                            <input type="time" class="form-control" id="time" name="time" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="customer_id" class="form-label">کڕیار *</label>
                            <select class="form-select" id="customer_id" name="customer_id" required>
                                <option value="">هەڵبژێرە</option>
                                <?php foreach ($customers as $customer): ?>
                                    <option value="<?= $customer['id'] ?>"><?= htmlspecialchars($customer['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="location" class="form-label">شوێن *</label>
                            <input type="text" class="form-control" id="location" name="location" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="recipient" class="form-label">وەرگر</label>
                            <input type="text" class="form-control" id="recipient" name="recipient">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="meter_amount" class="form-label">بڕ (م³) *</label>
                            <input type="number" class="form-control" id="meter_amount" name="meter_amount" min="0" step="0.01" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="formula_id" class="form-label">فۆرمولا *</label>
                            <select class="form-select" id="formula_id" name="formula_id" required>
                                <option value="">هەڵبژێرە</option>
                                <?php foreach ($formulas as $formula): ?>
                                    <option value="<?= $formula['id'] ?>"><?= htmlspecialchars($formula['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-light fw-bold">میکسەر</div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="mixer_car_id" class="form-label">کۆدی میکسەر</label>
                            <select class="form-select" id="mixer_car_id" name="mixer_car_id">
                                <option value="">هەڵبژێرە</option>
                                <?php foreach ($mixer_cars as $car): ?>
                                    <option value="<?= $car['id'] ?>"><?= htmlspecialchars($car['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                                    <div class="mb-3">
                            <label for="mixer_driver_id" class="form-label">شۆفێری میکسەر</label>
                            <select class="form-select" id="mixer_driver_id" name="mixer_driver_id">
                                <option value="">هەڵبژێرە</option>
                                <?php foreach ($mixer_drivers as $driver): ?>
                                    <option value="<?= $driver['id'] ?>"><?= htmlspecialchars($driver['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-light fw-bold">پەمپ</div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="pump_car_id" class="form-label">کۆدی پەمپ</label>
                            <select class="form-select" id="pump_car_id" name="pump_car_id">
                                <option value="">هەڵبژێرە</option>
                                <?php foreach ($pump_cars as $car): ?>
                                    <option value="<?= $car['id'] ?>"><?= htmlspecialchars($car['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                                    <div class="mb-3">
                            <label for="pump_driver_id" class="form-label">شۆفێری پەمپ</label>
                            <select class="form-select" id="pump_driver_id" name="pump_driver_id">
                                <option value="">هەڵبژێرە</option>
                                <?php foreach ($pump_drivers as $driver): ?>
                                    <option value="<?= $driver['id'] ?>"><?= htmlspecialchars($driver['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
                    <button type="submit" class="btn" style="background: var(--seafoam-green); color: white; font-weight: bold;">زیادکردن</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Note Modal -->
<div class="modal fade" id="editNoteModal" tabindex="-1" aria-labelledby="editNoteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="editNoteForm">
                <input type="hidden" id="edit_note_id" name="edit_note_id">
                <div class="modal-header">
                    <h5 class="modal-title" id="editNoteModalLabel">نوێکردنەوەی تێبینی</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_date" class="form-label">بەروار *</label>
                            <input type="date" class="form-control" id="edit_date" name="edit_date" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_time" class="form-label">سەعات *</label>
                            <input type="time" class="form-control" id="edit_time" name="edit_time" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_customer_id" class="form-label">کڕیار *</label>
                            <select class="form-select" id="edit_customer_id" name="edit_customer_id" required>
                                <option value="">هەڵبژێرە</option>
                                <?php foreach ($customers as $customer): ?>
                                    <option value="<?= $customer['id'] ?>"><?= htmlspecialchars($customer['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_location" class="form-label">شوێن *</label>
                            <input type="text" class="form-control" id="edit_location" name="edit_location" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_recipient" class="form-label">وەرگر</label>
                            <input type="text" class="form-control" id="edit_recipient" name="edit_recipient">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_meter_amount" class="form-label">بڕ (م³) *</label>
                            <input type="number" class="form-control" id="edit_meter_amount" name="edit_meter_amount" min="0" step="0.01" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_formula_id" class="form-label">فۆرمولا *</label>
                            <select class="form-select" id="edit_formula_id" name="edit_formula_id" required>
                                <option value="">هەڵبژێرە</option>
                                <?php foreach ($formulas as $formula): ?>
                                    <option value="<?= $formula['id'] ?>"><?= htmlspecialchars($formula['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-light fw-bold">میکسەر</div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="edit_mixer_car_id" class="form-label">کۆدی میکسەر</label>
                            <select class="form-select" id="edit_mixer_car_id" name="edit_mixer_car_id">
                                <option value="">هەڵبژێرە</option>
                                <?php foreach ($mixer_cars as $car): ?>
                                    <option value="<?= $car['id'] ?>"><?= htmlspecialchars($car['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                                    <div class="mb-3">
                            <label for="edit_mixer_driver_id" class="form-label">شۆفێری میکسەر</label>
                            <select class="form-select" id="edit_mixer_driver_id" name="edit_mixer_driver_id">
                                <option value="">هەڵبژێرە</option>
                                <?php foreach ($mixer_drivers as $driver): ?>
                                    <option value="<?= $driver['id'] ?>"><?= htmlspecialchars($driver['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-light fw-bold">پەمپ</div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="edit_pump_car_id" class="form-label">کۆدی پەمپ</label>
                            <select class="form-select" id="edit_pump_car_id" name="edit_pump_car_id">
                                <option value="">هەڵبژێرە</option>
                                <?php foreach ($pump_cars as $car): ?>
                                    <option value="<?= $car['id'] ?>"><?= htmlspecialchars($car['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                                    <div class="mb-3">
                            <label for="edit_pump_driver_id" class="form-label">شۆفێری پەمپ</label>
                            <select class="form-select" id="edit_pump_driver_id" name="edit_pump_driver_id">
                                <option value="">هەڵبژێرە</option>
                                <?php foreach ($pump_drivers as $driver): ?>
                                    <option value="<?= $driver['id'] ?>"><?= htmlspecialchars($driver['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
                    <button type="submit" class="btn" style="background: var(--seafoam-green); color: white; font-weight: bold;">نوێکردنەوە</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../assets/js/swalAlert.js"></script>
<script src="../assets/js/comon/table-controler.js"></script>
<script src="../assets/js/comon/select2_script.js"></script>
<script>
    // Pass permissions to JavaScript
    window.userPermissions = {
        canAdd: <?php echo hasPermission('add_notes') ? 'true' : 'false'; ?>,
        canEdit: <?php echo hasPermission('update_notes') ? 'true' : 'false'; ?>,
        canDelete: <?php echo hasPermission('delete_notes') ? 'true' : 'false'; ?>,
        canMarkRead: <?php echo hasPermission('mark_notes_read') ? 'true' : 'false'; ?>
    };
</script>
<script src="../assets/js/notes/init.js"></script>
<script src="../assets/js/notes/add.js"></script>
<script src="../assets/js/notes/select.js"></script>
<script src="../assets/js/notes/delete.js"></script>
<script src="../assets/js/notes/update.js"></script>

</body>
</html>
