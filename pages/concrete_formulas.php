<?php
session_start();
require_once '../config/db_conected.php';
require_once '../config/permissions.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}
if (!hasPermission('view_concrete_formulas')) {
    echo '<div style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:100vh;">'
        .'<i class="bi bi-lock-fill" style="font-size:5rem;color:#ccc;"></i>'
        .'<h2 style="color:#888;">توانای دەست گەیشتنت نییە بەم پەیجە</h2>'
        .'</div>';
    exit;
}
?>
<!DOCTYPE html>
<html lang="ku">
<head>
    <meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>فۆرمولای کۆنکرێت</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="../assets/css/login.css" rel="stylesheet">
    <link href="../assets/css/variables.css" rel="stylesheet">
    <link href="../assets/css/nav.css" rel="stylesheet">
    <link href="../assets/css/comon/table.css" rel="stylesheet">
    <link href="../assets/css/comon/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body dir="rtl">
<?php include '../includes/navbar.php'; ?>
<?php include '../includes/sidebar.php'; ?>
<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0" style="color: var(--seafoam-green); font-weight: bold;">فۆرمولای کۆنکرێت</h2>
        <button class="btn btn-success" id="addFormulaBtn" style="background: var(--seafoam-green); font-weight: bold;">+ زیادکردنی فۆرمولا</button>
    </div>
    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle text-center" id="formulasTable">
            <thead style="background: var(--kelly-green); color: var(--seafoam-green);">
                <tr>
                    <th>#</th>
                    <th>ناو</th>
                    <th>جۆر</th>
                    <th>قووە (KG)</th>
                    <th>قووە (MPA)</th>
                    <th>لمی ڕەش</th>
                    <th>لمی کەسارە</th>
                    <th>چەو 3</th>
                    <th>چەو 4</th>
                    <th>چیمەنتۆ 1</th>
                    <th>چیمەنتۆ 2</th>
                    <th>ئاو</th>
                    <th>زیادکراو</th>
                    <th>کردارەکان</th>
                </tr>
            </thead>
            <tbody>
                <!-- Formulas will be loaded here by JS -->
            </tbody>
        </table>
    </div>
</div>
<!-- Add Formula Modal -->
<div class="modal fade" id="addFormulaModal" tabindex="-1" aria-labelledby="addFormulaModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form id="addFormulaForm" >
        <div class="modal-header">
          <h5 class="modal-title" id="addFormulaModalLabel">زیادکردنی فۆرمولای کۆنکرێت</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label for="formula_name" class="form-label">ناو</label>
              <input type="text" class="form-control" id="formula_name" name="name" required>
            </div>
            <div class="col-md-6">
              <label for="formula_type" class="form-label">جۆر</label>
              <select class="form-control" id="formula_type" name="type" required>
                <option value="">-- هەلبژێرە --</option>
                <option value="عەرزی تێکەڵ">عەرزی تێکەڵ</option>
                <option value="عەرزی سادە">عەرزی سادە</option>
                <option value="سەقف">سەقف</option>
                <option value="پایە">پایە</option>
              </select>
            </div>
            <div class="col-md-6">
              <label for="strength_type" class="form-label">جۆری بەهیزی</label>
              <select class="form-control" id="strength_type" name="strength_type" required>
                <option value="">-- هەلبژێرە --</option>
                <option value="mpa">بەهیزی (MPa)</option>
                <option value="kg">بەهیزی (Kg)</option>
              </select>
            </div>
            <div class="col-md-6">
              <label for="strength_kg" class="form-label">قووە (KG)</label>
              <select class="form-control" id="strength_kg" name="strength_kg">
                <option value="">-- هەلبژێرە --</option>
                <option value="150">150</option>
                <option value="200">200</option>
                <option value="250">250</option>
                <option value="300">300</option>
                <option value="350">350</option>
                <option value="400">400</option>
                <option value="450">450</option>
              </select>
            </div>
            <div class="col-md-6">
              <label for="strength_mpa" class="form-label">قووە (MPA)</label>
              <select class="form-control" id="strength_mpa" name="strength_mpa">
                <option value="">-- هەلبژێرە --</option>
                <option value="15">15</option>
                <option value="18">18</option>
                <option value="21">21</option>
                <option value="25">25</option>
                <option value="30">30</option>
                <option value="35">35</option>
                <option value="40">40</option>
                <option value="45">45</option>
                <option value="50">50</option>
                <option value="55">55</option>
              </select>
            </div>
            <div class="col-md-6">
              <label for="black_sand_kg" class="form-label">لمی ڕەش (kg)</label>
              <input type="number" step="0.01" class="form-control" id="black_sand_kg" name="black_sand_kg" value="0">
            </div>
            <div class="col-md-6">
              <label for="brown_sand_kg" class="form-label">لمی کەسارە (kg)</label>
              <input type="number" step="0.01" class="form-control" id="brown_sand_kg" name="brown_sand_kg" value="0">
            </div>
            <div class="col-md-6">
              <label for="gravel_bin3_kg" class="form-label">چەو 3 (kg)</label>
              <input type="number" step="0.01" class="form-control" id="gravel_bin3_kg" name="gravel_bin3_kg" value="0">
            </div>
            <div class="col-md-6">
              <label for="gravel_bin4_kg" class="form-label">چەو 4 (kg)</label>
              <input type="number" step="0.01" class="form-control" id="gravel_bin4_kg" name="gravel_bin4_kg" value="0">
            </div>
            <div class="col-md-6">
              <label for="cement_cem1_kg" class="form-label">چیمەنتۆ 1 (kg)</label>
              <input type="number" step="0.01" class="form-control" id="cement_cem1_kg" name="cement_cem1_kg" value="0">
            </div>
            <div class="col-md-6">
              <label for="cement_cem2_kg" class="form-label">چیمەنتۆ 2 (kg)</label>
              <input type="number" step="0.01" class="form-control" id="cement_cem2_kg" name="cement_cem2_kg" value="0">
            </div>
            <div class="col-md-6">
              <label for="water_kg" class="form-label">ئاو (kg)</label>
              <input type="number" step="0.01" class="form-control" id="water_kg" name="water_kg" value="0">
            </div>
            <div class="col-md-6">
              <label for="additive_kg" class="form-label">زیادکراو (kg)</label>
              <input type="number" step="0.01" class="form-control" id="additive_kg" name="additive_kg" value="0">
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
          <button type="submit" class="btn btn-success" style="background: var(--seafoam-green); font-weight: bold;">زیادکردن</button>
        </div>
      </form>
    </div>
  </div>
</div>
<!-- Update Formula Modal -->
<div class="modal fade" id="updateFormulaModal" tabindex="-1" aria-labelledby="updateFormulaModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form id="updateFormulaForm">
        <div class="modal-header">
          <h5 class="modal-title" id="updateFormulaModalLabel">نوێکردنەوەی فۆرمولای کۆنکرێت</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label for="update_formula_name" class="form-label">ناو</label>
              <input type="text" class="form-control" id="update_formula_name" name="name" required>
            </div>
            <div class="col-md-6">
              <label for="update_formula_type" class="form-label">جۆر</label>
              <select class="form-control" id="update_formula_type" name="type" required>
                <option value="">-- هەلبژێرە --</option>
                <option value="عەرزی تێکەڵ">عەرزی تێکەڵ</option>
                <option value="عەرزی سادە">عەرزی سادە</option>
                <option value="سەقف">سەقف</option>
                <option value="پایە">پایە</option>
              </select>
            </div>
            <div class="col-md-6">
              <label for="update_strength_type" class="form-label">جۆری بەهیزی</label>
              <select class="form-control" id="update_strength_type" name="strength_type" required>
                <option value="">-- هەلبژێرە --</option>
                <option value="mpa">بەهیزی (MPa)</option>
                <option value="kg">بەهیزی (Kg)</option>
              </select>
            </div>
            <div class="col-md-6">
              <label for="update_strength_kg" class="form-label">قووە (KG)</label>
              <select class="form-control" id="update_strength_kg" name="strength_kg">
                <option value="">-- هەلبژێرە --</option>
                <option value="150">150</option>
                <option value="200">200</option>
                <option value="250">250</option>
                <option value="300">300</option>
                <option value="350">350</option>
                <option value="400">400</option>
                <option value="450">450</option>
              </select>
            </div>
            <div class="col-md-6">
              <label for="update_strength_mpa" class="form-label">قووە (MPA)</label>
              <select class="form-control" id="update_strength_mpa" name="strength_mpa">
                <option value="">-- هەلبژێرە --</option>
                <option value="15">15</option>
                <option value="18">18</option>
                <option value="21">21</option>
                <option value="25">25</option>
                <option value="30">30</option>
                <option value="35">35</option>
                <option value="40">40</option>
                <option value="45">45</option>
                <option value="50">50</option>
                <option value="55">55</option>
              </select>
            </div>
            <div class="col-md-6">
              <label for="update_black_sand_kg" class="form-label">لمی ڕەش (kg)</label>
              <input type="number" step="0.01" class="form-control" id="update_black_sand_kg" name="black_sand_kg" value="0">
            </div>
            <div class="col-md-6">
              <label for="update_brown_sand_kg" class="form-label">لمی کەسارە (kg)</label>
              <input type="number" step="0.01" class="form-control" id="update_brown_sand_kg" name="brown_sand_kg" value="0">
            </div>
            <div class="col-md-6">
              <label for="update_gravel_bin3_kg" class="form-label">چەو 3 (kg)</label>
              <input type="number" step="0.01" class="form-control" id="update_gravel_bin3_kg" name="gravel_bin3_kg" value="0">
            </div>
            <div class="col-md-6">
              <label for="update_gravel_bin4_kg" class="form-label">چەو 4 (kg)</label>
              <input type="number" step="0.01" class="form-control" id="update_gravel_bin4_kg" name="gravel_bin4_kg" value="0">
            </div>
            <div class="col-md-6">
              <label for="update_cement_cem1_kg" class="form-label">چیمەنتۆ 1 (kg)</label>
              <input type="number" step="0.01" class="form-control" id="update_cement_cem1_kg" name="cement_cem1_kg" value="0">
            </div>
            <div class="col-md-6">
              <label for="update_cement_cem2_kg" class="form-label">چیمەنتۆ 2 (kg)</label>
              <input type="number" step="0.01" class="form-control" id="update_cement_cem2_kg" name="cement_cem2_kg" value="0">
            </div>
            <div class="col-md-6">
              <label for="update_water_kg" class="form-label">ئاو (kg)</label>
              <input type="number" step="0.01" class="form-control" id="update_water_kg" name="water_kg" value="0">
            </div>
            <div class="col-md-6">
              <label for="update_additive_kg" class="form-label">زیادکراو (kg)</label>
              <input type="number" step="0.01" class="form-control" id="update_additive_kg" name="additive_kg" value="0">
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">داخستن</button>
          <button type="submit" class="btn btn-primary">نوێکردنەوە</button>
        </div>
      </form>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../assets/js/swalAlert.js"></script>
<script src="../assets/js/concrete_fomulas/select_formulas.js"></script>
<script src="../assets/js/comon/table-controler.js"></script>
<script src="../assets/js/concrete_fomulas/concrete_formulas.js"></script>
<script src="../assets/js/concrete_fomulas/add_formulas.js"></script>
<script src="../assets/js/concrete_fomulas/delete_formulas.js"></script>
<script src="../assets/js/concrete_fomulas/update_formulas.js"></script>

<script>
document.getElementById('addFormulaBtn').onclick = function() {
    var modal = new bootstrap.Modal(document.getElementById('addFormulaModal'));
    modal.show();
};
</script>
</body>
</html>
