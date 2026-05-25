<?php

declare(strict_types=1);

/**
 * Purchase export helpers — columns aligned with AG Grid on add_purchase.php (RTL order).
 */

function purchase_export_num(float $value, int $decimals = 2): string
{
    return number_format($value, $decimals, '.', '');
}

function purchase_export_csv_field(string $value): string
{
    if ($value === '') {
        return '';
    }
    if (preg_match('/[",\r\n]/', $value)) {
        return '"' . str_replace('"', '""', $value) . '"';
    }
    return $value;
}

/** @return list<array{field: string, label: string, format: string}> */
function purchase_get_detailed_export_columns(): array
{
    return [
        ['field' => 'company_name', 'label' => 'کۆمپانیا', 'format' => 'text'],
        ['field' => 'location_name', 'label' => 'شوێن', 'format' => 'text'],
        ['field' => 'factory_truck_name', 'label' => 'تڕێلەی کارگە', 'format' => 'text'],
        ['field' => 'driver_name', 'label' => 'شۆفێر', 'format' => 'text'],
        ['field' => 'invoice_number', 'label' => 'ژمارەی پسوڵە', 'format' => 'text'],
        ['field' => 'material_name', 'label' => 'مەواد', 'format' => 'text'],
        ['field' => 'date', 'label' => 'بەروار', 'format' => 'text'],
        ['field' => 'payment_type', 'label' => 'جۆری پارەدان', 'format' => 'text'],
        ['field' => 'type', 'label' => 'جۆری دراو', 'format' => 'text'],
        ['field' => 'kg', 'label' => 'کیلۆگرام', 'format' => 'num0'],
        ['field' => 'price_per_kg_usd', 'label' => 'نرخی یەک کیلۆ بە دۆلار', 'format' => 'num2'],
        ['field' => 'price_per_kg_iqd', 'label' => 'نرخی یەک کیلۆ بە دینار', 'format' => 'num2'],
        ['field' => 'price', 'label' => 'نرخ (دۆلار)', 'format' => 'num2'],
        ['field' => 'amount_iqd', 'label' => 'بڕی پارە بە دینار', 'format' => 'num0'],
        ['field' => 'exchange_rate', 'label' => 'نرخی 100 دۆلار بە دینار', 'format' => 'num2'],
        ['field' => 'paid_usd', 'label' => 'پارەی دراو بە دۆلار', 'format' => 'num2'],
        ['field' => 'paid_iqd', 'label' => 'پارەی دراو بە دینار', 'format' => 'num0'],
        ['field' => 'remaining_usd', 'label' => 'پارەی ماوە بە دۆلار', 'format' => 'num2'],
        ['field' => 'remaining_iqd', 'label' => 'پارەی ماوە بە دینار', 'format' => 'num0'],
        ['field' => 'bin_name', 'label' => 'چاو/سایلۆ', 'format' => 'text'],
    ];
}

function purchase_export_format_cell(array $row, array $column): string
{
    $field = $column['field'];
    $raw = $row[$field] ?? '';

    switch ($column['format']) {
        case 'num0':
            return purchase_export_num((float) $raw, 0);
        case 'num2':
            return purchase_export_num((float) $raw, 2);
        default:
            return (string) $raw;
    }
}

function purchase_export_send_headers(string $exportFormat, string $filenameBase): void
{
    if ($exportFormat === 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename*=UTF-8\'\'' . $filenameBase . '.csv');
    } else {
        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename*=UTF-8\'\'' . $filenameBase . '.xls');
    }
    header('Content-Transfer-Encoding: binary');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
}

function purchase_export_xls_begin(string $sheetTitle): void
{
    echo "\xEF\xBB\xBF";
    echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
    echo '<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
    echo '<!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet>';
    echo '<x:Name>' . htmlspecialchars($sheetTitle, ENT_QUOTES, 'UTF-8') . '</x:Name>';
    echo '<x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->';
    echo '<style>table{border-collapse:collapse;width:100%;font-family:Arial,sans-serif;}';
    echo 'th,td{border:1px solid #333;padding:6px;}th{background:#4f46e5;color:#fff;font-weight:bold;}';
    echo '.num{mso-number-format:"0.00";text-align:right;} .num0{mso-number-format:"0";text-align:right;}';
    echo 'td.text{mso-number-format:"\\@";}</style></head><body dir="rtl">';
    echo '<table border="1" dir="rtl">';
}

function purchase_export_xls_end(): void
{
    echo '</table></body></html>';
}

/**
 * @param list<array{field: string, label: string, format: string}> $columns
 */
function purchase_export_detailed_csv(array $data, array $columns): void
{
    echo "\xEF\xBB\xBF";
    $header = array_map(static fn ($c) => purchase_export_csv_field($c['label']), $columns);
    echo implode(',', $header) . "\n";

    foreach ($data as $row) {
        $cells = [];
        foreach ($columns as $col) {
            $cells[] = purchase_export_csv_field(purchase_export_format_cell($row, $col));
        }
        echo implode(',', $cells) . "\n";
    }
}

/**
 * @param list<array{field: string, label: string, format: string}> $columns
 */
function purchase_export_detailed_xls(array $data, array $columns): void
{
    purchase_export_xls_begin('کڕینەکان');
    echo '<tr>';
    foreach ($columns as $col) {
        echo '<th>' . htmlspecialchars($col['label'], ENT_QUOTES, 'UTF-8') . '</th>';
    }
    echo '</tr>';

    foreach ($data as $row) {
        echo '<tr>';
        foreach ($columns as $col) {
            $value = purchase_export_format_cell($row, $col);
            $class = str_starts_with($col['format'], 'num') ? ($col['format'] === 'num0' ? 'num0' : 'num') : 'text';
            if ($class === 'text') {
                echo '<td class="text">' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '</td>';
            } else {
                echo '<td class="' . $class . '">' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '</td>';
            }
        }
        echo '</tr>';
    }
    purchase_export_xls_end();
}

function purchase_export_detailed_sql(): string
{
    return "SELECT 
        c.name AS company_name,
        l.name AS location_name,
        COALESCE(ft.truck_name, '') AS factory_truck_name,
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
    LEFT JOIN bins_silos b ON p.bin_id = b.id
    LEFT JOIN factory_trucks ft ON p.factory_truck_id = ft.id";
}
