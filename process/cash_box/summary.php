<?php
session_start();
require_once '../../config/db_conected.php';
require_once '../../config/permissions.php';
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id']) || !hasPermission('view_cash_box')) {
    echo json_encode(['success' => false, 'error' => 'دەستپێگەیشتن قەدەغەیە']);
    exit;
}

$from   = $_GET['from']   ?? null;
$to     = $_GET['to']     ?? null;
$search = isset($_GET['search']) ? trim((string) $_GET['search']) : '';
$filters_active = ($from || $to || $search !== '');

function fetchDollarRateFromAPI(): ?float {
    $url = 'https://dinarapi.hediworks.site/api/get-price?id=8&api_token=S3gl9SVEkZ1Vvc93cCjsbLLmwDvgzk';
    $ctx = stream_context_create(['http' => ['timeout' => 10, 'user_agent' => 'DanaConcrete/1.0']]);
    $resp = @file_get_contents($url, false, $ctx);
    if ($resp !== false) {
        $data = json_decode($resp, true);
        if ($data && isset($data['value']) && is_numeric($data['value'])) {
            return (float) $data['value'];
        }
    }
    return null;
}

try {
    $usd_iqd_rate = 139250;
    $api_rate = fetchDollarRateFromAPI();
    if ($api_rate !== null) {
        $usd_iqd_rate = $api_rate;
    }

    // Build shared WHERE clause (without currency)
    $base_where  = [];
    $base_params = [];
    if ($from) {
        $base_where[]  = 'date >= ?';
        $base_params[] = $from;
    }
    if ($to) {
        $base_where[]  = 'date <= ?';
        $base_params[] = $to;
    }
    if ($search !== '') {
        $base_where[] = '(note LIKE ? OR CAST(date AS CHAR) LIKE ?)';
        $like = '%' . $search . '%';
        $base_params[] = $like;
        $base_params[] = $like;
    }

    // --- USD-currency transactions ---
    $usd_where  = array_merge($base_where, ["currency='دۆلار'"]);
    $usd_sql    = 'WHERE ' . implode(' AND ', $usd_where);
    $usd_params = $base_params;

    $stmt = $pdo->prepare("
        SELECT
            COALESCE(SUM(CASE WHEN type='deposit'  THEN amount_usd ELSE 0 END), 0) AS inflow_usd,
            COALESCE(SUM(CASE WHEN type='withdraw' THEN amount_usd ELSE 0 END), 0) AS outflow_usd,
            COALESCE(SUM(CASE WHEN type='deposit'  THEN amount_usd ELSE -amount_usd END), 0) AS net_usd
        FROM cash_box $usd_sql
    ");
    $stmt->execute($usd_params);
    $usd = $stmt->fetch(PDO::FETCH_ASSOC);

    // --- IQD-currency transactions ---
    $iqd_where  = array_merge($base_where, ["currency='دینار'"]);
    $iqd_sql    = 'WHERE ' . implode(' AND ', $iqd_where);
    $iqd_params = $base_params;

    $stmt2 = $pdo->prepare("
        SELECT
            COALESCE(SUM(CASE WHEN type='deposit'  THEN amount_iqd ELSE 0 END), 0) AS inflow_iqd,
            COALESCE(SUM(CASE WHEN type='withdraw' THEN amount_iqd ELSE 0 END), 0) AS outflow_iqd,
            COALESCE(SUM(CASE WHEN type='deposit'  THEN amount_iqd ELSE -amount_iqd END), 0) AS net_iqd
        FROM cash_box $iqd_sql
    ");
    $stmt2->execute($iqd_params);
    $iqd = $stmt2->fetch(PDO::FETCH_ASSOC);

    // --- Transaction count ---
    $count_where = $base_where ? ('WHERE ' . implode(' AND ', $base_where)) : '';
    $count_stmt  = $pdo->prepare("SELECT COUNT(*) FROM cash_box $count_where");
    $count_stmt->execute($base_params);
    $tx_count = (int) $count_stmt->fetchColumn();

    $total_usd = (float) $usd['net_usd'];
    $total_iqd = (float) $iqd['net_iqd'];

    $iqd_to_usd = $usd_iqd_rate > 0 ? round($total_iqd / ($usd_iqd_rate / 100), 2) : 0;
    $calculated_total = round($total_usd + $iqd_to_usd, 2);

    // Manual override (no filter active)
    $stmt_manual = $pdo->prepare("SELECT value FROM settings WHERE name = 'cash_box_total_usd_all' LIMIT 1");
    $stmt_manual->execute();
    $manual_total = $stmt_manual->fetchColumn();
    $is_manual      = !$filters_active && ($manual_total !== false && $manual_total !== null && $manual_total !== '');
    $total_usd_all  = $is_manual ? (float) $manual_total : $calculated_total;

    echo json_encode(['success' => true, 'data' => [
        'total_usd_all'    => $total_usd_all,
        'calculated_total' => $calculated_total,
        'is_manual'        => $is_manual,
        // Net balances
        'total_usd'        => $total_usd,
        'total_iqd'        => $total_iqd,
        'iqd_to_usd'       => $iqd_to_usd,
        'usd_iqd_rate'     => $usd_iqd_rate,
        // Inflow (deposits)
        'inflow_usd'       => (float) $usd['inflow_usd'],
        'inflow_iqd'       => (float) $iqd['inflow_iqd'],
        // Outflow (withdrawals)
        'outflow_usd'      => (float) $usd['outflow_usd'],
        'outflow_iqd'      => (float) $iqd['outflow_iqd'],
        // Transaction count
        'transaction_count' => $tx_count,
    ]]);

} catch (\Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
