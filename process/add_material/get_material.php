<?php
session_start();
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !hasPermission('view_materials')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$materialId = (int)($_POST['id'] ?? 0);
$action = $_POST['action'] ?? 'view';

if (!$materialId) {
    echo json_encode(['success' => false, 'message' => 'IDی کاڵا پێویستە']);
    exit;
}

try {
    // Get material details
    $stmt = $pdo->prepare("
        SELECT 
            wm.*,
            ut.name_ku as unit_type_name,
            ut.name as unit_type_english,
            wi.quantity,
            wi.available_quantity,
            wi.average_price_usd,
            wi.average_price_iqd,
            wi.total_value_usd,
            wi.total_value_iqd
        FROM warehouse_materials wm
        LEFT JOIN unit_types ut ON wm.unit_type_id = ut.id
        LEFT JOIN warehouse_inventory wi ON wm.id = wi.material_id
        WHERE wm.id = ? AND wm.is_active = 1
    ");
    $stmt->execute([$materialId]);
    $material = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$material) {
        echo json_encode(['success' => false, 'message' => 'کاڵا نەدۆزرایەوە']);
        exit;
    }

    // Get unit types for dropdown (for edit mode)
    $unitTypes = [];
    if ($action === 'edit') {
        $stmt = $pdo->query("SELECT id, name_ku, description FROM unit_types WHERE is_active = 1");
        $unitTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get recent transactions
    $stmt = $pdo->prepare("
        SELECT 
            wt.*,
            DATE_FORMAT(wt.created_at, '%Y-%m-%d %H:%i') as formatted_date
        FROM warehouse_transactions wt
        WHERE wt.material_id = ?
        ORDER BY wt.created_at DESC
        LIMIT 10
    ");
    $stmt->execute([$materialId]);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($action === 'view') {
        // Generate view HTML
        $html = generateViewHTML($material, $transactions);
    } else {
        // Generate edit HTML
        $html = generateEditHTML($material, $unitTypes);
    }

    echo json_encode([
        'success' => true,
        'html' => $html,
        'material' => $material
    ]);

} catch (Exception $e) {
    error_log("Error getting material: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'هەڵە لە وەرگرتنی زانیاری: ' . $e->getMessage()
    ]);
}

function generateViewHTML($material, $transactions) {
    $materialTypeNames = [
        'black_sand' => 'لمی ڕەش',
        'brown_sand' => 'لمی کەسارە',
        'gravel' => 'چەو',
        'cement' => 'چیمەنتۆ',
        'medicine' => 'دەرمان',
        'gas' => 'گاز',
        'other' => 'تر'
    ];

    $transactionTypeNames = [
        'purchase' => 'کڕین',
        'sale' => 'فرۆشتن',
        'adjustment' => 'ڕێکخستن',
        'transfer' => 'گواستەنەوە'
    ];

    $html = '
    <div class="row">
        <div class="col-12">
            <h6 class="border-bottom pb-2" style="color: var(--seafoam-green);">
                <i class="fas fa-info-circle"></i> زانیاری سەرەکی
            </h6>
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label fw-bold">ناوی کاڵا (کوردی):</label>
            <div class="form-control-plaintext">' . htmlspecialchars($material['name_ku']) . '</div>
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label fw-bold">ناوی کاڵا (ئینگلیزی):</label>
            <div class="form-control-plaintext">' . htmlspecialchars($material['name']) . '</div>
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label fw-bold">جۆری کاڵا:</label>
            <div class="form-control-plaintext">
                <span class="badge bg-' . getMaterialTypeColor($material['type']) . '">
                    ' . ($materialTypeNames[$material['type']] ?? 'تر') . '
                </span>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label fw-bold">جۆری یەکە:</label>
            <div class="form-control-plaintext">
                <i class="fas ' . getUnitTypeIcon($material['unit_type_name']) . '"></i>
                ' . htmlspecialchars($material['unit_type_name']) . '
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label fw-bold">یەکەی بنەڕەت:</label>
            <div class="form-control-plaintext">
                <span class="badge bg-info">' . htmlspecialchars($material['base_unit']) . '</span>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label fw-bold">فاکتەری گۆڕانکاری:</label>
            <div class="form-control-plaintext">' . number_format($material['conversion_factor'], 4) . '</div>
        </div>
        <div class="col-12 mb-3">
            <label class="form-label fw-bold">وەسف:</label>
            <div class="form-control-plaintext">' . htmlspecialchars($material['description'] ?: 'هیچ وەسفێک نییە') . '</div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-12">
            <h6 class="border-bottom pb-2" style="color: var(--seafoam-green);">
                <i class="fas fa-chart-line"></i> ستۆک و نرخ
            </h6>
        </div>
        <div class="col-md-3 mb-3">
            <label class="form-label fw-bold">بڕی بەردەست:</label>
            <div class="form-control-plaintext fw-bold text-primary">
                ' . number_format($material['available_quantity'] ?? 0, 2) . ' ' . htmlspecialchars($material['base_unit']) . '
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <label class="form-label fw-bold">نرخی تێکڕا (دۆلار):</label>
            <div class="form-control-plaintext fw-bold text-success">
                $' . number_format($material['average_price_usd'] ?? 0, 4) . '
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <label class="form-label fw-bold">نرخی تێکڕا (دینار):</label>
            <div class="form-control-plaintext fw-bold text-success">
                ' . number_format($material['average_price_iqd'] ?? 0, 0) . ' د.ع
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <label class="form-label fw-bold">کۆی نرخ (دۆلار):</label>
            <div class="form-control-plaintext fw-bold text-warning">
                $' . number_format($material['total_value_usd'] ?? 0, 2) . '
            </div>
        </div>
    </div>';

    if (!empty($transactions)) {
        $html .= '
        <div class="row mt-4">
            <div class="col-12">
                <h6 class="border-bottom pb-2" style="color: var(--seafoam-green);">
                    <i class="fas fa-history"></i> دواترین کردارەکان
                </h6>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>بەروار</th>
                                <th>جۆری کردار</th>
                                <th>بڕ</th>
                                <th>نرخی یەکە (دۆلار)</th>
                                <th>کۆی نرخ (دۆلار)</th>
                                <th>تێبینی</th>
                            </tr>
                        </thead>
                        <tbody>';
        
        foreach ($transactions as $transaction) {
            $html .= '
                            <tr>
                                <td>' . $transaction['formatted_date'] . '</td>
                                <td>
                                    <span class="badge bg-' . getTransactionTypeColor($transaction['transaction_type']) . '">
                                        ' . ($transactionTypeNames[$transaction['transaction_type']] ?? $transaction['transaction_type']) . '
                                    </span>
                                </td>
                                <td>' . number_format($transaction['quantity'], 2) . '</td>
                                <td>$' . number_format($transaction['unit_price_usd'], 4) . '</td>
                                <td>$' . number_format($transaction['total_value_usd'], 2) . '</td>
                                <td>' . htmlspecialchars($transaction['notes'] ?: '-') . '</td>
                            </tr>';
        }
        
        $html .= '
                        </tbody>
                    </table>
                </div>
            </div>
        </div>';
    }

    return $html;
}

function generateEditHTML($material, $unitTypes) {
    $html = '
    <div class="row mb-3">
        <div class="col-12">
            <h6 class="border-bottom pb-2" style="color: var(--seafoam-green);">
                <i class="fas fa-edit"></i> زانیاری سەرەکی
            </h6>
        </div>
        <div class="col-md-6 mb-3">
            <label for="edit_name_ku" class="form-label">ناوی کاڵا (کوردی) <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="edit_name_ku" name="name_ku" value="' . htmlspecialchars($material['name_ku']) . '" required>
        </div>
        <div class="col-md-6 mb-3">
            <label for="edit_name" class="form-label">ناوی کاڵا (ئینگلیزی)</label>
            <input type="text" class="form-control" id="edit_name" name="name" value="' . htmlspecialchars($material['name']) . '">
        </div>
        <div class="col-md-6 mb-3">
            <label for="edit_type" class="form-label">جۆری کاڵا <span class="text-danger">*</span></label>
            <select class="form-select" id="edit_type" name="type" required>
                <option value="">هەڵبژێرە</option>
                <option value="black_sand"' . ($material['type'] === 'black_sand' ? ' selected' : '') . '>لمی ڕەش</option>
                <option value="brown_sand"' . ($material['type'] === 'brown_sand' ? ' selected' : '') . '>لمی کەسارە</option>
                <option value="gravel"' . ($material['type'] === 'gravel' ? ' selected' : '') . '>چەو</option>
                <option value="cement"' . ($material['type'] === 'cement' ? ' selected' : '') . '>چیمەنتۆ</option>
                <option value="medicine"' . ($material['type'] === 'medicine' ? ' selected' : '') . '>دەرمان</option>
                <option value="gas"' . ($material['type'] === 'gas' ? ' selected' : '') . '>گاز</option>
                <option value="other"' . ($material['type'] === 'other' ? ' selected' : '') . '>تر</option>
            </select>
        </div>
        <div class="col-md-6 mb-3">
            <label for="edit_unit_type_id" class="form-label">جۆری یەکە <span class="text-danger">*</span></label>
            <select class="form-select" id="edit_unit_type_id" name="unit_type_id" required>
                <option value="">هەڵبژێرە</option>';
    
    foreach ($unitTypes as $unitType) {
        $selected = ($unitType['id'] == $material['unit_type_id']) ? ' selected' : '';
        $html .= '<option value="' . $unitType['id'] . '"' . $selected . '>' . htmlspecialchars($unitType['name_ku']) . '</option>';
    }
    
    $html .= '
            </select>
        </div>
        <div class="col-md-6 mb-3">
            <label for="edit_base_unit" class="form-label">یەکەی بنەڕەت <span class="text-danger">*</span></label>
            <select class="form-select" id="edit_base_unit" name="base_unit" required>
                <option value="">هەڵبژێرە</option>
                <option value="kg"' . ($material['base_unit'] === 'kg' ? ' selected' : '') . '>کیلۆگرام (kg)</option>
                <option value="liter"' . ($material['base_unit'] === 'liter' ? ' selected' : '') . '>لیتر (L)</option>
                <option value="piece"' . ($material['base_unit'] === 'piece' ? ' selected' : '') . '>دانە</option>
                <option value="meter"' . ($material['base_unit'] === 'meter' ? ' selected' : '') . '>مەتر (m)</option>
            </select>
        </div>
        <div class="col-md-6 mb-3">
            <label for="edit_conversion_factor" class="form-label">فاکتەری گۆڕانکاری</label>
            <input type="number" class="form-control" id="edit_conversion_factor" name="conversion_factor" min="0.0001" step="0.0001" value="' . $material['conversion_factor'] . '">
            <small class="form-text text-muted">فاکتەری گۆڕانکاری بۆ یەکەی بنەڕەت</small>
        </div>
        <div class="col-12 mb-3">
            <label for="edit_description" class="form-label">وەسف</label>
            <textarea class="form-control" id="edit_description" name="description" rows="3">' . htmlspecialchars($material['description']) . '</textarea>
        </div>
    </div>';

    return $html;
}

function getMaterialTypeColor($type) {
    $colors = [
        'black_sand' => 'dark',
        'brown_sand' => 'warning',
        'gravel' => 'secondary',
        'cement' => 'primary',
        'medicine' => 'danger',
        'gas' => 'info',
        'other' => 'light'
    ];
    return $colors[$type] ?? 'light';
}

function getUnitTypeIcon($unitType) {
    $icons = [
        'کارتۆن' => 'fa-box',
        'دانە' => 'fa-cube',
        'بەرمیل' => 'fa-drum',
        'دەبە' => 'fa-bucket',
        'لیتر' => 'fa-tint'
    ];
    return $icons[$unitType] ?? 'fa-ruler';
}

function getTransactionTypeColor($type) {
    $colors = [
        'purchase' => 'success',
        'sale' => 'primary',
        'adjustment' => 'warning',
        'transfer' => 'info'
    ];
    return $colors[$type] ?? 'secondary';
}
?> 