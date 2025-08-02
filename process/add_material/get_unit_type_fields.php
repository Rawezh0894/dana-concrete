<?php
session_start();
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

if (!isset($_SESSION['user_id']) || !hasPermission('add_material')) {
    http_response_code(403);
    exit('Access denied');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed');
}

$unitTypeId = $_POST['unit_type_id'] ?? null;

if (!$unitTypeId) {
    echo '<div class="alert alert-warning">هەڵە لە وەرگرتنی جۆری یەکە</div>';
    exit;
}

try {
    // Get unit type information
    $stmt = $pdo->prepare("SELECT name, name_ku, description FROM unit_types WHERE id = ?");
    $stmt->execute([$unitTypeId]);
    $unitType = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$unitType) {
        echo '<div class="alert alert-warning">جۆری یەکە نەدۆزرایەوە</div>';
        exit;
    }

    // Generate fields based on unit type
    $html = '<div class="unit-info">';
    $html .= '<h6><i class="fas fa-info-circle"></i> ڕێکخستنی یەکە: ' . htmlspecialchars($unitType['name_ku']) . '</h6>';
    $html .= '<p class="text-muted">' . htmlspecialchars($unitType['description']) . '</p>';

    switch ($unitType['name']) {
        case 'carton':
            $html .= '
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="pieces_per_carton" class="form-label">ژمارەی دانە لە کارتۆن <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" id="pieces_per_carton" name="pieces_per_carton" min="1" required>
                    <small class="form-text text-muted">چەند دانەیەک لە هەر کارتۆنێکدا هەیە</small>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="carton_price_usd" class="form-label">نرخی کارتۆن بە دۆلار</label>
                    <input type="number" class="form-control" id="carton_price_usd" name="carton_price_usd" min="0" step="0.01">
                </div>
            </div>
            <div class="price-calculation">
                <h6><i class="fas fa-calculator"></i> ژماردنی نرخ</h6>
                <p>نرخی دانە = نرخی کارتۆن ÷ ژمارەی دانە لە کارتۆن</p>
                <div id="carton-calculation-result"></div>
            </div>';
            break;

        case 'piece':
            $html .= '
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="piece_price_usd" class="form-label">نرخی دانە بە دۆلار</label>
                    <input type="number" class="form-control" id="piece_price_usd" name="piece_price_usd" min="0" step="0.01">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="piece_price_iqd" class="form-label">نرخی دانە بە دینار</label>
                    <input type="number" class="form-control" id="piece_price_iqd" name="piece_price_iqd" min="0" step="0.01">
                </div>
            </div>';
            break;

        case 'barrel':
            $html .= '
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="buckets_per_barrel" class="form-label">ژمارەی دەبە لە بەرمیل <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" id="buckets_per_barrel" name="buckets_per_barrel" min="1" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="liters_per_bucket" class="form-label">ژمارەی لیتر لە دەبە <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" id="liters_per_bucket" name="liters_per_bucket" min="1" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="barrel_price_usd" class="form-label">نرخی بەرمیل بە دۆلار</label>
                    <input type="number" class="form-control" id="barrel_price_usd" name="barrel_price_usd" min="0" step="0.01">
                </div>
            </div>
            <div class="price-calculation">
                <h6><i class="fas fa-calculator"></i> ژماردنی نرخ</h6>
                <p>نرخی دەبە = نرخی بەرمیل ÷ ژمارەی دەبە لە بەرمیل</p>
                <p>نرخی لیتر = نرخی دەبە ÷ ژمارەی لیتر لە دەبە</p>
                <div id="barrel-calculation-result"></div>
            </div>';
            break;

        case 'bucket':
            $html .= '
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="liters_per_bucket" class="form-label">ژمارەی لیتر لە دەبە <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" id="liters_per_bucket" name="liters_per_bucket" min="1" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="bucket_price_usd" class="form-label">نرخی دەبە بە دۆلار</label>
                    <input type="number" class="form-control" id="bucket_price_usd" name="bucket_price_usd" min="0" step="0.01">
                </div>
            </div>
            <div class="price-calculation">
                <h6><i class="fas fa-calculator"></i> ژماردنی نرخ</h6>
                <p>نرخی لیتر = نرخی دەبە ÷ ژمارەی لیتر لە دەبە</p>
                <div id="bucket-calculation-result"></div>
            </div>';
            break;

        case 'liter':
            $html .= '
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="liter_price_usd" class="form-label">نرخی لیتر بە دۆلار</label>
                    <input type="number" class="form-control" id="liter_price_usd" name="liter_price_usd" min="0" step="0.01">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="liter_price_iqd" class="form-label">نرخی لیتر بە دینار</label>
                    <input type="number" class="form-control" id="liter_price_iqd" name="liter_price_iqd" min="0" step="0.01">
                </div>
            </div>';
            break;

        default:
            $html .= '<div class="alert alert-info">هیچ ڕێکخستنی تایبەت نییە بۆ ئەم جۆری یەکەیە</div>';
    }

    $html .= '</div>';

    // Add JavaScript for calculations
    $html .= '<script>
        function updateCalculations() {
            const unitType = "' . $unitType['name'] . '";
            
            switch(unitType) {
                case "carton":
                    updateCartonCalculation();
                    break;
                case "barrel":
                    updateBarrelCalculation();
                    break;
                case "bucket":
                    updateBucketCalculation();
                    break;
            }
        }
        
        function updateCartonCalculation() {
            const piecesPerCarton = parseFloat($("#pieces_per_carton").val()) || 0;
            const cartonPrice = parseFloat($("#carton_price_usd").val()) || 0;
            
            if (piecesPerCarton > 0 && cartonPrice > 0) {
                const piecePrice = cartonPrice / piecesPerCarton;
                $("#carton-calculation-result").html(`
                    <div class="alert alert-info">
                        <strong>نرخی دانە:</strong> $${piecePrice.toFixed(4)}<br>
                        <strong>کۆی لیتر لە کارتۆن:</strong> ${piecesPerCarton}
                    </div>
                `);
            }
        }
        
        function updateBarrelCalculation() {
            const bucketsPerBarrel = parseFloat($("#buckets_per_barrel").val()) || 0;
            const litersPerBucket = parseFloat($("#liters_per_bucket").val()) || 0;
            const barrelPrice = parseFloat($("#barrel_price_usd").val()) || 0;
            
            if (bucketsPerBarrel > 0 && litersPerBucket > 0 && barrelPrice > 0) {
                const bucketPrice = barrelPrice / bucketsPerBarrel;
                const literPrice = bucketPrice / litersPerBucket;
                const totalLiters = bucketsPerBarrel * litersPerBucket;
                
                $("#barrel-calculation-result").html(`
                    <div class="alert alert-info">
                        <strong>نرخی دەبە:</strong> $${bucketPrice.toFixed(4)}<br>
                        <strong>نرخی لیتر:</strong> $${literPrice.toFixed(4)}<br>
                        <strong>کۆی لیتر لە بەرمیل:</strong> ${totalLiters}
                    </div>
                `);
            }
        }
        
        function updateBucketCalculation() {
            const litersPerBucket = parseFloat($("#liters_per_bucket").val()) || 0;
            const bucketPrice = parseFloat($("#bucket_price_usd").val()) || 0;
            
            if (litersPerBucket > 0 && bucketPrice > 0) {
                const literPrice = bucketPrice / litersPerBucket;
                
                $("#bucket-calculation-result").html(`
                    <div class="alert alert-info">
                        <strong>نرخی لیتر:</strong> $${literPrice.toFixed(4)}
                    </div>
                `);
            }
        }
        
        // Add event listeners
        $(document).ready(function() {
            $("#pieces_per_carton, #carton_price_usd").on("input", updateCartonCalculation);
            $("#buckets_per_barrel, #liters_per_bucket, #barrel_price_usd").on("input", updateBarrelCalculation);
            $("#liters_per_bucket, #bucket_price_usd").on("input", updateBucketCalculation);
        });
    </script>';

    echo $html;

} catch (Exception $e) {
    echo '<div class="alert alert-danger">هەڵە لە وەرگرتنی زانیاری: ' . htmlspecialchars($e->getMessage()) . '</div>';
}
?> 