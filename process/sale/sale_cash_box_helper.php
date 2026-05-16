<?php
/**
 * Cash box sync for cash (نەقد) sales: deposits for amounts received, withdrawals for change returned.
 * Notes include [sale:{id}] for reliable update/delete; legacy trigger notes are removed on sync.
 */

function sale_delete_cash_box_for_sale(PDO $pdo, int $saleId, ?string $invoiceNumber = null): void
{
    if ($saleId > 0) {
        $stmt = $pdo->prepare('DELETE FROM cash_box WHERE note LIKE ?');
        $stmt->execute(['%[sale:' . $saleId . ']%']);
    }
    if ($invoiceNumber !== null && $invoiceNumber !== '') {
        $prefix = 'فرۆشتن: invoice ' . $invoiceNumber;
        $stmt = $pdo->prepare('DELETE FROM cash_box WHERE note = ? OR note LIKE ?');
        $stmt->execute([$prefix, $prefix . '%']);
    }
}

/**
 * @param array $sale keys: id, payment_type, order_date, invoice_number, amount_paid_usd, amount_paid_iq, change_back_usd, change_back_iq
 */
function sale_sync_cash_box(PDO $pdo, array $sale, ?int $createdBy): void
{
    $saleId = (int) ($sale['id'] ?? 0);
    if ($saleId <= 0) {
        return;
    }

    $invoice = (string) ($sale['invoice_number'] ?? '');
    sale_delete_cash_box_for_sale($pdo, $saleId, $invoice);

    $oldInvoice = $sale['_old_invoice_number'] ?? null;
    if ($oldInvoice !== null && $oldInvoice !== '' && $oldInvoice !== $invoice) {
        sale_delete_cash_box_for_sale($pdo, $saleId, (string) $oldInvoice);
    }

    if (($sale['payment_type'] ?? '') !== 'نەقد') {
        return;
    }

    $date = $sale['order_date'] ?? date('Y-m-d');
    if (preg_match('/^\d{4}-\d{2}$/', (string) $date)) {
        $date .= '-01';
    }
    $tag = ' [sale:' . $saleId . ']';
    $baseNote = 'فرۆشتن: invoice ' . $invoice . $tag;

    $paidUsd = round((float) ($sale['amount_paid_usd'] ?? 0), 2);
    $paidIqd = round((float) ($sale['amount_paid_iq'] ?? 0), 2);
    $changeUsd = round((float) ($sale['change_back_usd'] ?? 0), 2);
    $changeIqd = round((float) ($sale['change_back_iq'] ?? 0), 2);

    $ins = $pdo->prepare(
        'INSERT INTO cash_box (`date`, `type`, `amount_iqd`, `amount_usd`, `currency`, `note`, `created_by`)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );

    if ($paidUsd > 0) {
        $ins->execute([$date, 'deposit', 0, $paidUsd, 'دۆلار', $baseNote . ' — وەرگرتن ($)', $createdBy]);
    }
    if ($paidIqd > 0) {
        $ins->execute([$date, 'deposit', $paidIqd, 0, 'دینار', $baseNote . ' — وەرگرتن (د.ع)', $createdBy]);
    }
    if ($changeUsd > 0) {
        $ins->execute([$date, 'withdraw', 0, $changeUsd, 'دۆلار', $baseNote . ' — باقی ($)', $createdBy]);
    }
    if ($changeIqd > 0) {
        $ins->execute([$date, 'withdraw', $changeIqd, 0, 'دینار', $baseNote . ' — باقی (د.ع)', $createdBy]);
    }
}

function sale_validate_cash_payment(
    string $paymentType,
    float $totalPrice,
    float $discount,
    float $paidUsd,
    float $paidIqd,
    float $changeUsd,
    float $changeIqd,
    float $dolarRate
): ?string {
    if ($paymentType !== 'نەقد') {
        return null;
    }

    $ratePerUsd = $dolarRate > 0 ? $dolarRate / 100 : 1;
    if ($ratePerUsd <= 0) {
        $ratePerUsd = 1;
    }

    $paidGrossUsd = $paidUsd + ($paidIqd / $ratePerUsd);
    $changeGrossUsd = $changeUsd + ($changeIqd / $ratePerUsd);
    $netPaidUsd = $paidGrossUsd - $changeGrossUsd;
    $remaining = ($totalPrice - $discount) - $netPaidUsd;

    if ($remaining > 0.05) {
        return 'کاتێک جۆری پارەدان نەقدە، نابێت پارەی ماوە بێت!';
    }
    if ($remaining < -0.05) {
        return 'کۆی پارەی دراو (دوای باقی) زیاترە لە کۆی نرخ!';
    }

    return null;
}
