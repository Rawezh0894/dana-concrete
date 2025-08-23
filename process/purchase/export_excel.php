<?php
session_start();
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// Check if user has permission to view purchases
if (!hasPermission('view_purchase')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'ڕێگەپێدراوە بۆ بینینی کڕینەکان']);
    exit;
}

// Get filter parameters
$company_id = $_GET['company_id'] ?? null;
$location_id = $_GET['location_id'] ?? null;
$driver_id = $_GET['driver_id'] ?? null;
$material_id = $_GET['material_id'] ?? null;
$from_date = $_GET['from'] ?? null;
$to_date = $_GET['to'] ?? null;

// Build WHERE clause
$where_conditions = [];
$params = [];

if ($company_id) {
    $where_conditions[] = "p.company_id = ?";
    $params[] = $company_id;
}

if ($location_id) {
    $where_conditions[] = "l.id = ?";
    $params[] = $location_id;
}

if ($driver_id) {
    $where_conditions[] = "d.id = ?";
    $params[] = $driver_id;
}

if ($material_id) {
    $where_conditions[] = "p.material_id = ?";
    $params[] = $material_id;
}

if ($from_date) {
    $where_conditions[] = "p.date >= ?";
    $params[] = $from_date;
}

if ($to_date) {
    $where_conditions[] = "p.date <= ?";
    $params[] = $to_date;
}

// Build SQL query
$sql = "SELECT 
    p.id,
    c.name AS company_name,
    l.name AS location_name,
    d.name AS driver_name,
    p.invoice_number,
    m.name AS material_name,
    p.date,
    p.payment_type,
    p.type,
    p.kg,
    p.price_per_kg_usd,
    p.price_per_kg_iqd,
    p.price,
    p.amount_iqd,
    p.exchange_rate,
    p.paid_usd,
    p.paid_iqd,
    p.remaining_usd,
    p.remaining_iqd,
    b.name AS bin_name
FROM purchases p
LEFT JOIN company c ON p.company_id = c.id
LEFT JOIN locations l ON p.location = l.name
LEFT JOIN drivers d ON p.driver = d.name
LEFT JOIN materials m ON p.material_id = m.id
LEFT JOIN bins_silos b ON p.bin_id = b.id";

if (!empty($where_conditions)) {
    $sql .= " WHERE " . implode(" AND ", $where_conditions);
}

$sql .= " ORDER BY p.date ASC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Check if PhpSpreadsheet is available
    if (file_exists('../../vendor/autoload.php')) {
        try {
            // Create Excel file using PhpSpreadsheet
            require_once '../../vendor/autoload.php';
            
            use PhpOffice\PhpSpreadsheet\Spreadsheet;
            use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
            use PhpOffice\PhpSpreadsheet\Style\Alignment;
            use PhpOffice\PhpSpreadsheet\Style\Border;
            use PhpOffice\PhpSpreadsheet\Style\Fill;
            
            // Create new Spreadsheet object
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            
            // Set document properties
            $spreadsheet->getProperties()
                ->setCreator('Dana Concrete System')
                ->setLastModifiedBy('Dana Concrete System')
                ->setTitle('Purchase Report')
                ->setSubject('Purchase Data Export')
                ->setDescription('Purchase data exported from Dana Concrete System');
            
            // Define headers
            $headers = [
                'A1' => '#',
                'B1' => 'کۆمپانیا',
                'C1' => 'شوێن',
                'D1' => 'شۆفێر',
                'E1' => 'ژمارەی پسوڵە',
                'F1' => 'مەواد',
                'G1' => 'بەروار',
                'H1' => 'جۆری پارەدان',
                'I1' => 'جۆری دراو',
                'J1' => 'کیلۆگرام',
                'K1' => 'نرخی یەک کیلۆ بە دۆلار',
                'L1' => 'نرخی یەک کیلۆ بە دینار',
                'M1' => 'نرخ',
                'N1' => 'بڕی پارە بە دینار',
                'O1' => 'نرخی 100 دۆلار بە دینار',
                'P1' => 'پارەی دراو بە دۆلار',
                'Q1' => 'پارەی دراو بە دینار',
                'R1' => 'پارەی ماوە بە دۆلار',
                'S1' => 'پارەی ماوە بە دینار',
                'T1' => 'چاو/سایلۆ'
            ];
            
            // Set headers
            foreach ($headers as $cell => $value) {
                $sheet->setCellValue($cell, $value);
            }
            
            // Style headers
            $headerStyle = [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '28A745'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '000000'],
                    ],
                ],
            ];
            
            $sheet->getStyle('A1:T1')->applyFromArray($headerStyle);
            
            // Set column widths
            $columnWidths = [
                'A' => 8,   // #
                'B' => 20,  // کۆمپانیا
                'C' => 15,  // شوێن
                'D' => 15,  // شۆفێر
                'E' => 18,  // ژمارەی پسوڵە
                'F' => 15,  // مەواد
                'G' => 12,  // بەروار
                'H' => 15,  // جۆری پارەدان
                'I' => 12,  // جۆری دراو
                'J' => 12,  // کیلۆگرام
                'K' => 20,  // نرخی یەک کیلۆ بە دۆلار
                'L' => 20,  // نرخی یەک کیلۆ بە دینار
                'M' => 15,  // نرخ
                'N' => 20,  // بڕی پارە بە دینار
                'O' => 20,  // نرخی 100 دۆلار بە دینار
                'P' => 20,  // پارەی دراو بە دۆلار
                'Q' => 20,  // پارەی دراو بە دینار
                'R' => 20,  // پارەی ماوە بە دۆلار
                'S' => 20,  // پارەی ماوە بە دینار
                'T' => 15   // چاو/سایلۆ
            ];
            
            foreach ($columnWidths as $column => $width) {
                $sheet->getColumnDimension($column)->setWidth($width);
            }
            
            // Add data rows
            $row = 2;
            foreach ($data as $index => $item) {
                $sheet->setCellValue('A' . $row, $index + 1);
                $sheet->setCellValue('B' . $row, $item['company_name'] ?? '');
                $sheet->setCellValue('C' . $row, $item['location_name'] ?? '');
                $sheet->setCellValue('D' . $row, $item['driver_name'] ?? '');
                $sheet->setCellValue('E' . $row, $item['invoice_number'] ?? '');
                $sheet->setCellValue('F' . $row, $item['material_name'] ?? '');
                $sheet->setCellValue('G' . $row, $item['date'] ?? '');
                $sheet->setCellValue('H' . $row, $item['payment_type'] ?? '');
                $sheet->setCellValue('I' . $row, $item['type'] ?? '');
                $sheet->setCellValue('J' . $row, $item['kg'] ?? 0);
                $sheet->setCellValue('K' . $row, $item['price_per_kg_usd'] ?? 0);
                $sheet->setCellValue('L' . $row, $item['price_per_kg_iqd'] ?? 0);
                $sheet->setCellValue('M' . $row, $item['price'] ?? 0);
                $sheet->setCellValue('N' . $row, $item['amount_iqd'] ?? 0);
                $sheet->setCellValue('O' . $row, $item['exchange_rate'] ?? 0);
                $sheet->setCellValue('P' . $row, $item['paid_usd'] ?? 0);
                $sheet->setCellValue('Q' . $row, $item['paid_iqd'] ?? 0);
                $sheet->setCellValue('R' . $row, $item['remaining_usd'] ?? 0);
                $sheet->setCellValue('S' . $row, $item['remaining_iqd'] ?? 0);
                $sheet->setCellValue('T' . $row, $item['bin_name'] ?? '');
                
                $row++;
            }
            
            // Style data rows
            if (!empty($data)) {
                $dataStyle = [
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'CCCCCC'],
                        ],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ];
                
                $sheet->getStyle('A2:T' . ($row - 1))->applyFromArray($dataStyle);
                
                // Alternate row colors
                for ($i = 2; $i < $row; $i++) {
                    if ($i % 2 == 0) {
                        $sheet->getStyle('A' . $i . ':T' . $i)->getFill()
                            ->setFillType(Fill::FILL_SOLID)
                            ->setStartColor(['rgb' => 'F8F9FA']);
                    }
                }
            }
            
            // Add summary information
            $summaryRow = $row + 2;
            $sheet->setCellValue('A' . $summaryRow, 'کۆی زانیاری:');
            $sheet->setCellValue('B' . $summaryRow, count($data));
            
            // Style summary row
            $summaryStyle = [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => '28A745'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E8F5E8'],
                ],
            ];
            
            $sheet->getStyle('A' . $summaryRow . ':B' . $summaryRow)->applyFromArray($summaryStyle);
            
            // Add filter information
            $filterRow = $summaryRow + 2;
            $sheet->setCellValue('A' . $filterRow, 'فلتەرەکان:');
            
            $filterInfo = [];
            if ($company_id) $filterInfo[] = 'کۆمپانیا: ' . $company_id;
            if ($location_id) $filterInfo[] = 'شوێن: ' . $location_id;
            if ($driver_id) $filterInfo[] = 'شۆفێر: ' . $driver_id;
            if ($material_id) $filterInfo[] = 'مەواد: ' . $material_id;
            if ($from_date) $filterInfo[] = 'لە: ' . $from_date;
            if ($to_date) $filterInfo[] = 'بۆ: ' . $to_date;
            
            if (!empty($filterInfo)) {
                $sheet->setCellValue('B' . $filterRow, implode(', ', $filterInfo));
            } else {
                $sheet->setCellValue('B' . $filterRow, 'هیچ فلتەرێک نەکراوە');
            }
            
            // Style filter row
            $filterStyle = [
                'font' => [
                    'italic' => true,
                    'color' => ['rgb' => '6C757D'],
                ],
            ];
            
            $sheet->getStyle('A' . $filterRow . ':B' . $filterRow)->applyFromArray($filterStyle);
            
            // Set response headers for Excel download
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="purchases_export.xlsx"');
            header('Cache-Control: max-age=0');
            
            // Create Excel writer and output
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            
        } catch (Exception $excelError) {
            // Log Excel-specific error
            error_log('Excel generation error: ' . $excelError->getMessage());
            error_log('Excel error trace: ' . $excelError->getTraceAsString());
            
            // Fallback to CSV export
            exportAsCSV($data);
        }
        
    } else {
        // PhpSpreadsheet not available, use CSV fallback
        error_log('PhpSpreadsheet library not found, using CSV fallback');
        exportAsCSV($data);
    }
    
} catch (Exception $e) {
    // Log error
    error_log('Export error: ' . $e->getMessage());
    error_log('Export error trace: ' . $e->getTraceAsString());
    
    // Return error response
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'هەڵەیەک لە کاتی ئیکسپۆرت کردندا هەیە: ' . $e->getMessage()
    ]);
}

/**
 * Fallback CSV export function
 */
function exportAsCSV($data) {
    try {
        // Set headers for CSV download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="purchases_export.csv"');
        header('Cache-Control: max-age=0');
        
        // Create output stream
        $output = fopen('php://output', 'w');
        
        // Add BOM for proper UTF-8 encoding in Excel
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // CSV headers
        $headers = [
            '#',
            'کۆمپانیا',
            'شوێن',
            'شۆفێر',
            'ژمارەی پسوڵە',
            'مەواد',
            'بەروار',
            'جۆری پارەدان',
            'جۆری دراو',
            'کیلۆگرام',
            'نرخی یەک کیلۆ بە دۆلار',
            'نرخی یەک کیلۆ بە دینار',
            'نرخ',
            'بڕی پارە بە دینار',
            'نرخی 100 دۆلار بە دینار',
            'پارەی دراو بە دۆلار',
            'پارەی دراو بە دینار',
            'پارەی ماوە بە دۆلار',
            'پارەی ماوە بە دینار',
            'چاو/سایلۆ'
        ];
        
        // Write headers
        fputcsv($output, $headers);
        
        // Write data rows
        foreach ($data as $index => $item) {
            $row = [
                $index + 1,
                $item['company_name'] ?? '',
                $item['location_name'] ?? '',
                $item['driver_name'] ?? '',
                $item['invoice_number'] ?? '',
                $item['material_name'] ?? '',
                $item['date'] ?? '',
                $item['payment_type'] ?? '',
                $item['type'] ?? '',
                $item['kg'] ?? 0,
                $item['price_per_kg_usd'] ?? 0,
                $item['price_per_kg_iqd'] ?? 0,
                $item['price'] ?? 0,
                $item['amount_iqd'] ?? 0,
                $item['exchange_rate'] ?? 0,
                $item['paid_usd'] ?? 0,
                $item['paid_iqd'] ?? 0,
                $item['remaining_usd'] ?? 0,
                $item['remaining_iqd'] ?? 0,
                $item['bin_name'] ?? ''
            ];
            
            fputcsv($output, $row);
        }
        
        // Add summary information
        fputcsv($output, ['']); // Empty row
        fputcsv($output, ['کۆی زانیاری:', count($data)]);
        
        // Close output stream
        fclose($output);
        
    } catch (Exception $csvError) {
        error_log('CSV export error: ' . $csvError->getMessage());
        
        // Final fallback - return JSON error
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'هەڵەیەک لە کاتی CSV ئیکسپۆرت کردندا هەیە: ' . $csvError->getMessage()
        ]);
    }
}
?>
