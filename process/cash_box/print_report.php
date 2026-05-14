<?php
session_start();
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';

if (!isset($_SESSION['user_id']) || !hasPermission('view_cash_box')) {
    echo '<p style="color:red;text-align:center;">دەستپێگەیشتن قەدەغەیە</p>';
    exit;
}

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
$whereSql    = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
$whereNoAlias = str_replace('cb.', '', $whereSql);

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

// Summary
$sum_params = array_filter([$from, $to], fn($v) => $v !== null);
$sum_where_parts = [];
$sum_params_arr  = [];
if ($from) { $sum_where_parts[] = "date >= ?"; $sum_params_arr[] = $from; }
if ($to)   { $sum_where_parts[] = "date <= ?"; $sum_params_arr[] = $to;   }
$sumWhere = $sum_where_parts ? ('WHERE ' . implode(' AND ', $sum_where_parts)) : '';
$s = $pdo->prepare("
    SELECT
        COALESCE(SUM(CASE WHEN type='deposit'  AND currency='دۆلار' THEN amount_usd ELSE 0 END),0) AS inflow_usd,
        COALESCE(SUM(CASE WHEN type='withdraw' AND currency='دۆلار' THEN amount_usd ELSE 0 END),0) AS outflow_usd,
        COALESCE(SUM(CASE WHEN type='deposit'  AND currency='دینار' THEN amount_iqd ELSE 0 END),0) AS inflow_iqd,
        COALESCE(SUM(CASE WHEN type='withdraw' AND currency='دینار' THEN amount_iqd ELSE 0 END),0) AS outflow_iqd,
        COUNT(*) AS tx_count
    FROM cash_box $sumWhere
");
$s->execute($sum_params_arr);
$summary = $s->fetch(PDO::FETCH_ASSOC);
$net_usd = (float)$summary['inflow_usd'] - (float)$summary['outflow_usd'];
$net_iqd = (float)$summary['inflow_iqd'] - (float)$summary['outflow_iqd'];

$period_label = ($from && $to) ? "$from — $to" : ($from ? "لە $from" : ($to ? "بۆ $to" : 'هەموو ماوەکان'));
$generated_at = date('Y-m-d H:i:s');

function fmt_usd($v): string { return '$' . number_format((float)$v, 2); }
function fmt_iqd($v): string { return number_format((float)$v, 0) . ' د.ع'; }
?>
<!DOCTYPE html>
<html lang="ku" dir="rtl">
<head>
<meta charset="UTF-8">
<title>ڕاپۆرتی قاسەکە — <?= htmlspecialchars($period_label) ?></title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: 'Segoe UI', Tahoma, Arial, sans-serif; font-size: 11pt; color: #1a1a1a; direction: rtl; }
  .page-header { background: linear-gradient(135deg,#0f766e,#134e4a); color:#fff; padding:20px 30px; }
  .page-header h1 { font-size:18pt; margin-bottom:4px; }
  .page-header p  { font-size:10pt; opacity:.8; }
  .meta-row { display:flex; gap:30px; padding:12px 30px; background:#f0fdf4; border-bottom:1px solid #d1fae5; font-size:10pt; }
  .summary-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:12px; padding:16px 30px; }
  .summary-card { border-radius:8px; padding:12px 16px; }
  .inflow  { background:#dcfce7; border:1px solid #86efac; }
  .outflow { background:#fee2e2; border:1px solid #fca5a5; }
  .net     { background:#dbeafe; border:1px solid #93c5fd; }
  .summary-card .label { font-size:9pt; color:#555; margin-bottom:4px; }
  .summary-card .val   { font-size:13pt; font-weight:700; }
  .val.green { color:#166534; }
  .val.red   { color:#991b1b; }
  .val.blue  { color:#1e40af; }
  section { padding:16px 30px; }
  section h2 { font-size:12pt; font-weight:700; margin-bottom:10px; border-bottom:2px solid #0f766e; padding-bottom:4px; color:#0f766e; }
  table  { width:100%; border-collapse:collapse; font-size:9.5pt; }
  thead  { background:#134e4a; color:#fff; }
  th, td { padding:6px 8px; border:1px solid #d1d5db; text-align:right; }
  tr:nth-child(even) { background:#f9fafb; }
  .badge-in  { background:#dcfce7; color:#166534; border-radius:4px; padding:2px 6px; font-size:8.5pt; font-weight:600; }
  .badge-out { background:#fee2e2; color:#991b1b; border-radius:4px; padding:2px 6px; font-size:8.5pt; font-weight:600; }
  .bal-pos { color:#166534; font-weight:600; }
  .bal-neg { color:#991b1b; font-weight:600; }
  .print-footer { text-align:center; padding:12px; font-size:9pt; color:#888; border-top:1px solid #e5e7eb; margin-top:20px; }
  @media print {
    body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .no-print { display:none !important; }
  }
</style>
</head>
<body>

<div class="page-header">
  <h1>ڕاپۆرتی قاسەکە</h1>
  <p><?= htmlspecialchars($period_label) ?> &nbsp;|&nbsp; دروستکراو: <?= $generated_at ?></p>
</div>

<div class="meta-row">
  <span><strong>کۆی مامەڵەکان:</strong> <?= number_format($summary['tx_count']) ?></span>
  <span><strong>ماوەی ڕاپۆرت:</strong> <?= htmlspecialchars($period_label) ?></span>
  <?php if ($search): ?>
  <span><strong>گەڕان:</strong> <?= htmlspecialchars($search) ?></span>
  <?php endif; ?>
</div>

<div class="summary-grid">
  <div class="summary-card inflow">
    <div class="label">کۆی داهات (زیادکردن)</div>
    <div class="val green"><?= fmt_usd($summary['inflow_usd']) ?></div>
    <div class="val green" style="font-size:11pt;margin-top:2px;"><?= fmt_iqd($summary['inflow_iqd']) ?></div>
  </div>
  <div class="summary-card outflow">
    <div class="label">کۆی خەرج (کەمکردنەوە)</div>
    <div class="val red"><?= fmt_usd($summary['outflow_usd']) ?></div>
    <div class="val red" style="font-size:11pt;margin-top:2px;"><?= fmt_iqd($summary['outflow_iqd']) ?></div>
  </div>
  <div class="summary-card net">
    <div class="label">باڵانسی خالص</div>
    <div class="val <?= $net_usd >= 0 ? 'blue' : 'red' ?>"><?= fmt_usd($net_usd) ?></div>
    <div class="val <?= $net_iqd >= 0 ? 'blue' : 'red' ?>" style="font-size:11pt;margin-top:2px;"><?= fmt_iqd($net_iqd) ?></div>
  </div>
</div>

<section>
  <h2>مێژووی مامەڵەکان</h2>
  <table>
    <thead>
      <tr>
        <th>#</th>
        <th>بەروار</th>
        <th>جۆر</th>
        <th>هاتوو/ڕۆشتوو</th>
        <th>بڕ (دینار)</th>
        <th>بڕ (دۆلار)</th>
        <th>دراو</th>
        <th>باڵانس دۆلار</th>
        <th>باڵانس دینار</th>
        <th>تێبینی</th>
        <th>لەلایەن</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($rows as $i => $r): ?>
      <?php
        $bal_usd = (float)$r['running_bal_usd'];
        $bal_iqd = (float)$r['running_bal_iqd'];
      ?>
      <tr>
        <td><?= count($rows) - $i ?></td>
        <td><?= htmlspecialchars($r['date']) ?></td>
        <td><?= $r['type'] === 'deposit' ? 'زیادکردن' : 'کەمکردنەوە' ?></td>
        <td>
          <span class="<?= $r['type'] === 'deposit' ? 'badge-in' : 'badge-out' ?>">
            <?= $r['type'] === 'deposit' ? 'هاتوو' : 'ڕۆشتوو' ?>
          </span>
        </td>
        <td><?= $r['amount_iqd'] > 0 ? fmt_iqd($r['amount_iqd']) : '—' ?></td>
        <td><?= $r['amount_usd'] > 0 ? fmt_usd($r['amount_usd']) : '—' ?></td>
        <td><?= htmlspecialchars($r['currency']) ?></td>
        <td class="<?= $bal_usd >= 0 ? 'bal-pos' : 'bal-neg' ?>"><?= $r['currency'] === 'دۆلار' ? fmt_usd($bal_usd) : '—' ?></td>
        <td class="<?= $bal_iqd >= 0 ? 'bal-pos' : 'bal-neg' ?>"><?= $r['currency'] === 'دینار' ? fmt_iqd($bal_iqd) : '—' ?></td>
        <td style="max-width:200px;word-wrap:break-word;"><?= nl2br(htmlspecialchars($r['note'] ?? '')) ?></td>
        <td><?= htmlspecialchars($r['created_by_username'] ?? '') ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</section>

<div class="print-footer">
  Dana Concrete &nbsp;|&nbsp; ئەم ڕاپۆرتە لە <?= $generated_at ?> دروستکرا
</div>

<script>window.onload = function() { window.print(); };</script>
</body>
</html>
