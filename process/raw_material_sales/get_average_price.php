<?php
/**
 * Get Average Purchase Price for Raw Materials
 * Calculates average price per KG from purchases table
 * Following the same logic as reports page
 */
session_start();
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'سێشن نییە!']);
    exit;
}

/**
 * Get average price per KG for a specific material type from purchases
 * 
 * @param PDO $pdo Database connection
 * @param string $material_type Material type name (e.g., 'چەو', 'لمی کەسارە', etc.)
 * @return array ['price_per_kg' => float, 'currency' => string]
 */
function getAveragePriceFromPurchases($pdo, $material_type) {
    // Determine if this material uses USD or IQD
    $usd_materials = ['چیمەنتۆ', 'دەرمان'];
    $currency = in_array($material_type, $usd_materials) ? 'دۆلار' : 'دینار';
    
    try {
        // Get average price from purchases table
        // Only consider purchases from year 2026 onwards
        // Similar logic to reports page but returns price per KG
        $query = "
            SELECT 
                SUM(CASE 
                    WHEN p.type = 'دۆلار' THEN p.price 
                    ELSE p.amount_iqd / NULLIF(p.exchange_rate / 100, 0) 
                END) as total_usd,
                SUM(p.kg) as total_kg
            FROM purchases p
            JOIN materials m ON p.material_id = m.id
            WHERE m.name = ? 
            AND p.kg > 0
            AND YEAR(p.date) >= 2026
        ";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([$material_type]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result && $result['total_kg'] > 0 && $result['total_usd'] > 0) {
            // Calculate price per KG in USD
            $price_per_kg_usd = $result['total_usd'] / $result['total_kg'];
            
            // If material uses IQD, convert USD to IQD
            if ($currency === 'دینار') {
                // Get current exchange rate
                $rateStmt = $pdo->query("SELECT value FROM settings WHERE name = 'exchange_rate' LIMIT 1");
                $exchangeRate = $rateStmt->fetchColumn() ?: 150000;
                
                // Convert: price_per_kg_usd * (exchangeRate / 100) = price in IQD
                $price_per_kg = $price_per_kg_usd * ($exchangeRate / 100);
            } else {
                $price_per_kg = $price_per_kg_usd;
            }
            
            return [
                'price_per_kg' => round($price_per_kg, 6),
                'currency' => $currency
            ];
        }
        
        // Fallback: If no purchases in 2026, get all purchases (without date filter)
        $fallback_query = "
            SELECT 
                SUM(CASE 
                    WHEN p.type = 'دۆلار' THEN p.price 
                    ELSE p.amount_iqd / NULLIF(p.exchange_rate / 100, 0) 
                END) as total_usd,
                SUM(p.kg) as total_kg
            FROM purchases p
            JOIN materials m ON p.material_id = m.id
            WHERE m.name = ? AND p.kg > 0
        ";
        
        $fallback_stmt = $pdo->prepare($fallback_query);
        $fallback_stmt->execute([$material_type]);
        $fallback_result = $fallback_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($fallback_result && $fallback_result['total_kg'] > 0 && $fallback_result['total_usd'] > 0) {
            $price_per_kg_usd = $fallback_result['total_usd'] / $fallback_result['total_kg'];
            
            if ($currency === 'دینار') {
                $rateStmt = $pdo->query("SELECT value FROM settings WHERE name = 'exchange_rate' LIMIT 1");
                $exchangeRate = $rateStmt->fetchColumn() ?: 150000;
                $price_per_kg = $price_per_kg_usd * ($exchangeRate / 100);
            } else {
                $price_per_kg = $price_per_kg_usd;
            }
            
            return [
                'price_per_kg' => round($price_per_kg, 6),
                'currency' => $currency
            ];
        }
        
        // Final fallback: no purchases found at all, return 0
        return [
            'price_per_kg' => 0,
            'currency' => $currency
        ];
        
    } catch (Exception $e) {
        error_log("Error getting average price for $material_type: " . $e->getMessage());
        return [
            'price_per_kg' => 0,
            'currency' => $currency
        ];
    }
}

/**
 * Get average prices for all material types
 */
function getAllAveragePrices($pdo) {
    $materials = ['لمی کەسارە', 'لمی ڕەش', 'چەو', 'چیمەنتۆ', 'دەرمان', 'گاز'];
    $prices = [];
    
    foreach ($materials as $material) {
        $prices[$material] = getAveragePriceFromPurchases($pdo, $material);
    }
    
    return $prices;
}

// If called directly, return all prices
if (basename($_SERVER['SCRIPT_FILENAME']) === basename(__FILE__)) {
    try {
        $material_type = $_GET['material_type'] ?? null;
        
        if ($material_type) {
            $result = getAveragePriceFromPurchases($pdo, $material_type);
            echo json_encode([
                'success' => true,
                'data' => $result
            ]);
        } else {
            $prices = getAllAveragePrices($pdo);
            echo json_encode([
                'success' => true,
                'data' => $prices
            ]);
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'هەڵە: ' . $e->getMessage()
        ]);
    }
}
