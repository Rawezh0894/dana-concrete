<?php
session_start();
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

if (!isset($_SESSION['user_id'])) { redirectToLogin(); exit; }
if (!hasPermission('view_cash_box')) { echo 'Access denied'; exit; }

$from   = $_GET['from']   ?? null;
$to     = $_GET['to']     ?? null;
$search = isset($_GET['search']) ? trim((string) $_GET['search']) : '';

$where  = [];
$params = [];
if ($from) { $where[] = 'cb.date >= ?'; $params[] = $from; }
if ($to)   { $where[] = 'cb.date <= ?'; $params[] = $to;   }
if ($search !== '') {
    $where[]  = '(cb.note LIKE ? OR CAST(cb.date AS CHAR) LIKE ?)';
    $like     = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

try {
    // Running balance via window functions
    $innerSql = "
        SELECT cb.*, u.username AS created_by_username,
            SUM(CASE WHEN cb.type='deposit'  AND cb.currency='دۆلار' THEN  cb.amount_usd
                     WHEN cb.type='withdraw' AND cb.currency='دۆلار' THEN -(cb.amount_usd)
                     ELSE 0 END) OVER (ORDER BY cb.date ASC, cb.id ASC) AS running_bal_usd,
            SUM(CASE WHEN cb.type='deposit'  AND cb.currency='دینار' THEN  cb.amount_iqd
                     WHEN cb.type='withdraw' AND cb.currency='دینار' THEN -(cb.amount_iqd)
                     ELSE 0 END) OVER (ORDER BY cb.date ASC, cb.id ASC) AS running_bal_iqd
        FROM cash_box cb
        LEFT JOIN users u ON cb.created_by = u.id
        $whereSql
    ";
    $sql  = "SELECT * FROM ($innerSql) t ORDER BY t.date DESC, t.id DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (\Exception $e) {
    // Fallback: no running balance
    $sql  = "SELECT cb.*, u.username AS created_by_username, NULL AS running_bal_usd, NULL AS running_bal_iqd
             FROM cash_box cb LEFT JOIN users u ON cb.created_by = u.id $whereSql ORDER BY cb.date DESC, cb.id DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Summary totals
$sum_where  = [];
$sum_params = [];
if ($from) { $sum_where[] = "date >= ?"; $sum_params[] = $from; }
if ($to)   { $sum_where[] = "date <= ?"; $sum_params[] = $to;   }
$sumWhereSql = $sum_where ? ('WHERE ' . implode(' AND ', $sum_where)) : '';
$s_stmt = $pdo->prepare("
    SELECT
        COALESCE(SUM(CASE WHEN type='deposit'  AND currency='دۆلار' THEN amount_usd ELSE 0 END),0) AS inflow_usd,
        COALESCE(SUM(CASE WHEN type='withdraw' AND currency='دۆلار' THEN amount_usd ELSE 0 END),0) AS outflow_usd,
        COALESCE(SUM(CASE WHEN type='deposit'  AND currency='دینار' THEN amount_iqd ELSE 0 END),0) AS inflow_iqd,
        COALESCE(SUM(CASE WHEN type='withdraw' AND currency='دینار' THEN amount_iqd ELSE 0 END),0) AS outflow_iqd
    FROM cash_box $sumWhereSql
");
$s_stmt->execute($sum_params);
$summary = $s_stmt->fetch(PDO::FETCH_ASSOC);

header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header('Content-Disposition: attachment; filename="cash_box_export_' . date('Y-m-d') . '.xls"');
header('Cache-Control: max-age=0');

echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
echo '<head><meta charset="UTF-8"><style>
  body { font-family: Tahoma, Arial; direction: rtl; }
  th { background-color: #134e4a; color: white; font-weight: bold; padding: 6px; }
  td { padding: 5px 8px; }
  .in  { background-color: #dcfce7; color: #166534; font-weight:bold; }
  .out { background-color: #fee2e2; color: #991b1b; font-weight:bold; }
  .pos { color:#166534; font-weight:bold; }
  .neg { color:#991b1b; font-weight:bold; }
  .summary-head { background-color:#0f766e; color:white; font-weight:bold; }
</style></head><body>';

// -------- Summary Sheet --------
echo '<h3 style="color:#0f766e;">ئێکسپۆرتی قاسەکە — ' . date('Y-m-d') . '</h3>';
echo '<table border="1"><tr class="summary-head">';
echo '<th>کۆی داهات (دۆلار)</th><th>کۆی خەرج (دۆلار)</th><th>باڵانس خالص (دۆلار)</th>';
echo '<th>کۆی داهات (دینار)</th><th>کۆی خەرج (دینار)</th><th>باڵانس خالص (دینار)</th>';
echo '<th>کۆی مامەڵەکان</th>';
echo '</tr><tr>';
$net_usd = (float)$summary['inflow_usd'] - (float)$summary['outflow_usd'];
$net_iqd = (float)$summary['inflow_iqd'] - (float)$summary['outflow_iqd'];
echo '<td class="in">$' . number_format((float)$summary['inflow_usd'], 2) . '</td>';
echo '<td class="out">$' . number_format((float)$summary['outflow_usd'], 2) . '</td>';
echo '<td class="' . ($net_usd >= 0 ? 'pos' : 'neg') . '">$' . number_format($net_usd, 2) . '</td>';
echo '<td class="in">' . number_format((float)$summary['inflow_iqd'], 0) . ' د.ع</td>';
echo '<td class="out">' . number_format((float)$summary['outflow_iqd'], 0) . ' د.ع</td>';
echo '<td class="' . ($net_iqd >= 0 ? 'pos' : 'neg') . '">' . number_format($net_iqd, 0) . ' د.ع</td>';
echo '<td>' . count($rows) . '</td>';
echo '</tr></table><br><br>';

// -------- Transactions Table --------
echo '<table border="1">';
echo '<tr>';
echo '<th>#</th><th>بەروار</th><th>جۆری مامەڵە</th><th>هاتوو/ڕۆشتوو</th>';
echo '<th>بڕ (دینار)</th><th>بڕ (دۆلار)</th><th>دراو</th>';
echo '<th>باڵانس دۆلار</th><th>باڵانس دینار</th>';
echo '<th>تێبینی</th><th>لەلایەن</th><th>کات</th>';
echo '</tr>';

foreach ($rows as $i => $row) {
    $type_text  = $row['type'] === 'deposit' ? 'زیادکردن' : 'کەمکردنەوە';
    $in_out     = $row['type'] === 'deposit' ? 'هاتوو'    : 'ڕۆشتوو';
    $style_cell = $row['type'] === 'deposit' ? 'in'       : 'out';
    $bal_usd    = $row['running_bal_usd'] !== null ? (float) $row['running_bal_usd'] : null;
    $bal_iqd    = $row['running_bal_iqd'] !== null ? (float) $row['running_bal_iqd'] : null;

    $fmt_bal_usd = ($row['currency'] === 'دۆلار' && $bal_usd !== null)
        ? '<span class="' . ($bal_usd >= 0 ? 'pos' : 'neg') . '">$' . number_format($bal_usd, 2) . '</span>'
        : '—';
    $fmt_bal_iqd = ($row['currency'] === 'دینار' && $bal_iqd !== null)
        ? '<span class="' . ($bal_iqd >= 0 ? 'pos' : 'neg') . '">' . number_format($bal_iqd, 0) . ' د.ع</span>'
        : '—';

    echo '<tr>';
    echo '<td>' . ($i + 1) . '</td>';
    echo '<td>' . htmlspecialchars($row['date']) . '</td>';
    echo '<td>' . $type_text . '</td>';
    echo '<td class="' . $style_cell . '">' . $in_out . '</td>';
    echo '<td>' . ($row['amount_iqd'] > 0 ? number_format((float)$row['amount_iqd'], 0) . ' د.ع' : '—') . '</td>';
    echo '<td>' . ($row['amount_usd'] > 0 ? '$' . number_format((float)$row['amount_usd'], 2) : '—') . '</td>';
    echo '<td>' . htmlspecialchars($row['currency']) . '</td>';
    echo '<td>' . $fmt_bal_usd . '</td>';
    echo '<td>' . $fmt_bal_iqd . '</td>';
    echo '<td style="max-width:300px;word-wrap:break-word;">' . nl2br(htmlspecialchars($row['note'] ?? '')) . '</td>';
    echo '<td>' . htmlspecialchars($row['created_by_username'] ?? '') . '</td>';
    echo '<td>' . htmlspecialchars($row['created_at'] ?? '') . '</td>';
    echo '</tr>';
}

echo '</table></body></html>';
