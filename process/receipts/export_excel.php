<?php
session_start();
require_once '../../config/db_conected.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized'], JSON_UNESCAPED_UNICODE);
    exit;
}

$customer_id = isset($_REQUEST['customer_id']) ? intval($_REQUEST['customer_id']) : 0;
if ($customer_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Customer ID is required'], JSON_UNESCAPED_UNICODE);
    exit;
}

$type = $_REQUEST['type'] ?? 'all';
$month = $_REQUEST['month'] ?? 'all';
$date_from = $_REQUEST['date_from'] ?? '';
$date_to = $_REQUEST['date_to'] ?? '';
$location = $_REQUEST['location'] ?? 'all';
$recipient = $_REQUEST['recipient'] ?? 'all';
$paid_date_from = $_REQUEST['paid_date_from'] ?? '';
$paid_date_to = $_REQUEST['paid_date_to'] ?? '';
$invoice_number = trim($_REQUEST['invoice_number'] ?? '');
$invoice_filter_mode = ($_REQUEST['invoice_filter_mode'] ?? 'include') === 'exclude' ? 'exclude' : 'include';

function format_strength(?string $mpa, ?string $kg): string
{
    $mpa = trim((string)$mpa);
    $kg = trim((string)$kg);
    if ($mpa !== '') {
        return $mpa . ' MPa';
    }
    if ($kg !== '') {
        return $kg . ' Kg';
    }
    return '';
}

try {
    $customerStmt = $pdo->prepare('SELECT name, mobile1, IFNULL(opening_debt_usd, 0) AS opening_debt_usd FROM customers WHERE id = ?');
    $customerStmt->execute([$customer_id]);
    $customer = $customerStmt->fetch(PDO::FETCH_ASSOC);
    if (!$customer) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Customer not found'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $salesSql = "SELECT
            s.order_date,
            s.location,
            f.strength_mpa,
            f.strength_kg,
            f.type AS formula_type,
            SUM(s.quantity) AS total_quantity,
            s.price_per_unit,
            SUM(s.total_price) AS total_price_sum,
            GROUP_CONCAT(s.invoice_number ORDER BY s.invoice_number SEPARATOR ', ') AS invoice_numbers,
            SUM(s.remaining_amount) AS total_remaining_amount
        FROM sales s
        LEFT JOIN concrete_formulas f ON s.formula_id = f.id
        WHERE s.customer_id = :customer_id";

    $salesParams = ['customer_id' => $customer_id];

    if ($type === 'cash') {
        $salesSql .= ' AND (s.remaining_amount = 0 OR s.remaining_amount IS NULL)';
    } elseif ($type === 'debt' || $type === 'has_remaining') {
        $salesSql .= ' AND s.remaining_amount > 0';
    }

    if ($month !== 'all' && $month !== '') {
        $salesSql .= ' AND MONTH(s.order_date) = :month';
        $salesParams['month'] = $month;
    }
    if ($date_from !== '') {
        $salesSql .= ' AND s.order_date >= :date_from';
        $salesParams['date_from'] = $date_from;
    }
    if ($date_to !== '') {
        $salesSql .= ' AND s.order_date <= :date_to';
        $salesParams['date_to'] = $date_to;
    }

    if ($location === 'none') {
        $salesSql .= ' AND 1=0';
    } elseif ($location !== 'all' && $location !== '') {
        if (strpos($location, ',') !== false) {
            $locations = array_values(array_filter(array_map('trim', explode(',', $location))));
            $placeholders = [];
            foreach ($locations as $index => $loc) {
                $param = 'location_' . $index;
                $placeholders[] = ':' . $param;
                $salesParams[$param] = $loc;
            }
            if ($placeholders) {
                $salesSql .= ' AND s.location IN (' . implode(',', $placeholders) . ')';
            }
        } else {
            $salesSql .= ' AND s.location = :location';
            $salesParams['location'] = $location;
        }
    }

    if ($recipient === 'none') {
        $salesSql .= ' AND 1=0';
    } elseif ($recipient !== 'all' && $recipient !== '') {
        if (strpos($recipient, ',') !== false) {
            $recipientArray = array_map('trim', explode(',', $recipient));
            $includeEmpty = in_array('__EMPTY__', $recipientArray, true);
            $recipientArray = array_values(array_filter($recipientArray, fn($v) => $v !== '' && $v !== '__EMPTY__'));
            $conditions = [];
            if ($recipientArray) {
                $placeholders = [];
                foreach ($recipientArray as $index => $recv) {
                    $param = 'recipient_' . $index;
                    $placeholders[] = ':' . $param;
                    $salesParams[$param] = $recv;
                }
                $conditions[] = 's.recipient IN (' . implode(',', $placeholders) . ')';
            }
            if ($includeEmpty) {
                $conditions[] = "(s.recipient IS NULL OR TRIM(s.recipient) = '')";
            }
            if ($conditions) {
                $salesSql .= ' AND (' . implode(' OR ', $conditions) . ')';
            } else {
                $salesSql .= ' AND 1=0';
            }
        } elseif (trim($recipient) === '__EMPTY__') {
            $salesSql .= " AND (s.recipient IS NULL OR TRIM(s.recipient) = '')";
        } else {
            $salesSql .= ' AND s.recipient = :recipient_filter';
            $salesParams['recipient_filter'] = $recipient;
        }
    }

    $salesSql .= ' GROUP BY s.order_date, s.location, f.strength_mpa, f.strength_kg, f.type, s.price_per_unit';
    $salesSql .= ' ORDER BY s.order_date ASC';

    $salesStmt = $pdo->prepare($salesSql);
    $salesStmt->execute($salesParams);
    $salesRaw = $salesStmt->fetchAll(PDO::FETCH_ASSOC);

    $sales = [];
    $totalQty = 0;
    $totalPrice = 0;
    $totalRemaining = 0;
    foreach ($salesRaw as $i => $row) {
        $qty = (float)($row['total_quantity'] ?? 0);
        $price = (float)($row['total_price_sum'] ?? 0);
        $remaining = (float)($row['total_remaining_amount'] ?? 0);
        $ppu = (float)($row['price_per_unit'] ?? 0);
        $totalQty += $qty;
        $totalPrice += $price;
        $totalRemaining += $remaining;

        $sales[] = [
            '#' => $i + 1,
            'شوێن' => $row['location'] ?? '',
            'پێوانە (م³)' => round($qty, 2),
            'ڕێژە' => format_strength($row['strength_mpa'] ?? '', $row['strength_kg'] ?? ''),
            'جۆر' => $row['formula_type'] ?? '',
            'نرخی 1 م³' => round($ppu, 2),
            'کۆی نرخ' => round($price, 2),
            'پارەی ماوە' => round($remaining, 2),
            'ژمارەی پسووڵە' => $row['invoice_numbers'] ?? '',
            'بەروار' => $row['order_date'] ?? '',
        ];
    }

    $debtSql = "SELECT cdp.paid_usd, cdp.paid_iqd, cdp.date, cdp.discount, cdp.note,
            COALESCE(cdp.change_back_usd, 0) AS change_back_usd,
            COALESCE(cdp.change_back_iq, 0) AS change_back_iq
        FROM customer_debt_payments cdp
        WHERE cdp.customer_id = :customer_id";
    $debtParams = ['customer_id' => $customer_id];

    if ($invoice_number !== '') {
        $invoiceNumbers = array_values(array_filter(array_map('trim', explode(',', $invoice_number))));
        if ($invoiceNumbers) {
            $invoiceConditions = [];
            foreach ($invoiceNumbers as $index => $inv) {
                $param = 'invoice_number_' . $index;
                $invoiceConditions[] = "s.invoice_number LIKE :$param";
                $debtParams[$param] = '%' . $inv . '%';
            }
            $invoiceConditionSql = implode(' OR ', $invoiceConditions);
            if ($invoice_filter_mode === 'exclude') {
                $debtSql .= " AND NOT EXISTS (
                    SELECT 1 FROM customer_payment_allocations cpa
                    INNER JOIN sales s ON cpa.sale_id = s.id
                    WHERE cpa.debt_payment_id = cdp.id AND ($invoiceConditionSql)
                )";
            } else {
                $debtSql .= " AND EXISTS (
                    SELECT 1 FROM customer_payment_allocations cpa
                    INNER JOIN sales s ON cpa.sale_id = s.id
                    WHERE cpa.debt_payment_id = cdp.id AND ($invoiceConditionSql)
                )";
            }
        }
    }

    if ($month !== 'all' && $month !== '' && $paid_date_from === '' && $paid_date_to === '') {
        $debtSql .= ' AND MONTH(cdp.date) = :debt_month';
        $debtParams['debt_month'] = $month;
    }
    if ($paid_date_from !== '') {
        $debtSql .= ' AND cdp.date >= :paid_date_from';
        $debtParams['paid_date_from'] = $paid_date_from;
    }
    if ($paid_date_to !== '') {
        $debtSql .= ' AND cdp.date <= :paid_date_to';
        $debtParams['paid_date_to'] = $paid_date_to;
    }
    $debtSql .= ' ORDER BY cdp.date ASC';

    $debtStmt = $pdo->prepare($debtSql);
    $debtStmt->execute($debtParams);
    $debtRaw = $debtStmt->fetchAll(PDO::FETCH_ASSOC);

    $payments = [];
    $sumPaidUsd = 0;
    $sumPaidIqd = 0;
    foreach ($debtRaw as $i => $row) {
        $paidUsd = (float)($row['paid_usd'] ?? 0);
        $paidIqd = (float)($row['paid_iqd'] ?? 0);
        $sumPaidUsd += $paidUsd;
        $sumPaidIqd += $paidIqd;

        $payments[] = [
            '#' => $i + 1,
            'پارەی واسڵ کراو (USD)' => round($paidUsd, 2),
            'پارەی واسڵ کراو (د.ع)' => round($paidIqd, 0),
            'باقی پێدراوەتەوە (USD)' => round((float)($row['change_back_usd'] ?? 0), 2),
            'باقی پێدراوەتەوە (د.ع)' => round((float)($row['change_back_iq'] ?? 0), 0),
            'داشکاندن' => round((float)($row['discount'] ?? 0), 2),
            'بەرواری پارەدان' => $row['date'] ?? '',
            'تێبینی' => $row['note'] ?? '',
        ];
    }

    echo json_encode([
        'success' => true,
        'customer' => [
            'name' => $customer['name'] ?? '',
            'mobile' => $customer['mobile1'] ?? '',
            'opening_debt_usd' => (float)($customer['opening_debt_usd'] ?? 0),
        ],
        'sales' => $sales,
        'sales_totals' => [
            'quantity' => round($totalQty, 2),
            'total_price' => round($totalPrice, 2),
            'remaining' => round($totalRemaining, 2),
        ],
        'payments' => $payments,
        'payments_totals' => [
            'paid_usd' => round($sumPaidUsd, 2),
            'paid_iqd' => round($sumPaidIqd, 0),
        ],
        'exported_at' => date('Y-m-d H:i'),
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    error_log('Receipt Excel export failed: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'هەڵە لە ئیکسپۆرت: ' . $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
