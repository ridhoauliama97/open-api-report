<?php

namespace App\Services\Ascends\Shared\InventoryAnalysis;

use Carbon\Carbon;
use RuntimeException;
use Throwable;
use XMLReader;

class JangkaWaktuPoPiReportService
{
    private const TITLE = 'Laporan Jangka Waktu P.Order Ke P.Invoice';

    public function buildReportDataFromXml(string $xmlContents, string $sourceLabel = 'request xml payload', array $filters = []): array
    {
        if (trim($xmlContents) === '') {
            throw new RuntimeException('XML data kosong.');
        }

        $records = $this->parseXml($xmlContents, $sourceLabel);
        $rows = $records['rows'];

        if ($rows === []) {
            throw new RuntimeException('Data purchase order tidak ditemukan di XML.');
        }

        $period = self::resolvePeriod($filters) ?? self::resolvePeriodFromRows($rows);

        if ($period !== null) {
            $p = $period;
            $rows = array_values(array_filter($rows, static function (array $row) use ($p): bool {
                $date = $row['order_date'] ?? null;

                return $date !== null && $date->betweenIncluded($p['start'], $p['end']);
            }));
        }

        if ($rows === []) {
            throw new RuntimeException('Data purchase order tidak ditemukan di periode tersebut.');
        }

        $approveRows = array_values(array_filter(
            $rows,
            static fn (array $row): bool => ($row['jangka_waktu_approve_po'] ?? null) !== null
                && $row['jangka_waktu_approve_po'] > 2
        ));
        $poPiRows = array_values(array_filter(
            $rows,
            static fn (array $row): bool => ($row['jangka_waktu_po_pi'] ?? null) !== null
                && $row['jangka_waktu_po_pi'] > 5
        ));
        $prPiRows = array_values(array_filter(
            $rows,
            static fn (array $row): bool => ($row['jangka_waktu_pr_pi'] ?? null) !== null
                && $row['jangka_waktu_pr_pi'] > 7
        ));

        $dateRangeText = '';
        if ($period !== null) {
            $dateRangeText = 'Dari '.$period['start']->locale('id')->isoFormat('DD-MMM-YY').' s/d '.$period['end']->locale('id')->isoFormat('DD-MMM-YY');
        }

        return [
            'title' => self::TITLE,
            'source_file' => $sourceLabel,
            'period_label' => $dateRangeText,
            'printed_at' => Carbon::now()->locale('id')->translatedFormat('d F Y H:i'),
            'printed_by' => $records['printed_by'],
            'summary_groups' => self::buildSummaryGroups($rows),
            'summary_totals' => self::buildSummaryTotals($rows),
            'approve_po_groups' => self::buildDetailGroups($approveRows),
            'po_pi_groups' => self::buildDetailGroups($poPiRows),
            'pr_pi_groups' => self::buildDetailGroups($prPiRows),
            'total_rows' => count($rows),
        ];
    }

    private function parseXml(string $xmlContents, string $sourceLabel): array
    {
        $reader = new XMLReader;

        if (! @$reader->XML($xmlContents, null, LIBXML_NOCDATA | LIBXML_NONET)) {
            throw new RuntimeException("XML tidak valid: {$sourceLabel}");
        }

        $rows = [];
        $printedBy = '';

        while ($reader->read()) {
            if ($reader->nodeType !== XMLReader::ELEMENT || strtolower($reader->name) !== 'table') {
                continue;
            }

            $recordXml = $reader->readOuterXML();

            if (! is_string($recordXml) || trim($recordXml) === '') {
                continue;
            }

            $node = @simplexml_load_string($recordXml, 'SimpleXMLElement', LIBXML_NOCDATA);

            if ($node === false) {
                continue;
            }

            $row = $this->extractRecord($node);

            if ($row === null) {
                continue;
            }

            if ($printedBy === '') {
                $printedBy = self::resolvePrintedBy($node);
            }

            $rows[] = $row;
        }

        $reader->close();

        return [
            'rows' => $rows,
            'printed_by' => $printedBy,
        ];
    }

    private function extractRecord(\SimpleXMLElement $node): ?array
    {
        $orderDate = self::parseDate((string) ($node->Order_x0020_Date ?? ''));
        if ($orderDate === null) {
            return null;
        }

        $createdDateTime = self::parseDate((string) ($node->Created_x0020_Date_x002F_Time ?? ''));
        $approvedDateTime = self::parseDate((string) ($node->Approved_x0020_Date_x002F_Time ?? ''));
        $lastPurchaseDate = self::parseDate((string) ($node->Last_x0020_Purchase_x0020_Date ?? ''));
        $prDate = self::parseDate((string) ($node->PR_x0020_Date ?? ''));

        $approveDays = $approvedDateTime !== null
            ? (int) $orderDate->copy()->startOfDay()->diffInDays($approvedDateTime->copy()->startOfDay(), false)
            : null;
        $poPiDays = $lastPurchaseDate !== null
            ? (int) $orderDate->copy()->startOfDay()->diffInDays($lastPurchaseDate->copy()->startOfDay(), false)
            : null;
        $prPiDays = ($lastPurchaseDate !== null && $prDate !== null)
            ? (int) $prDate->copy()->startOfDay()->diffInDays($lastPurchaseDate->copy()->startOfDay(), false)
            : null;

        $status = trim((string) ($node->Status ?? '')) === 'Active'
            ? '-'
            : trim((string) ($node->Closed_x0020_Reason ?? ''));

        $lastPurchaseNumber = trim((string) ($node->Last_x0020_Purchase_x0020_Number ?? ''));
        $purchaseInvoice = trim((string) ($node->Purchase_x0020_Invoice ?? ''));
        $piNumber = $purchaseInvoice !== '' ? $purchaseInvoice : $lastPurchaseNumber;

        return [
            'order_date' => $orderDate,
            'order_date_sort' => $orderDate->format('Y-m-d'),
            'order_date_display' => self::formatDate($orderDate),
            'created_date_time' => $createdDateTime,
            'created_date_time_display' => self::formatDateTime($createdDateTime),
            'approved_date_time' => $approvedDateTime,
            'approved_date_time_display' => self::formatDateTime($approvedDateTime),
            'approved_by' => trim((string) ($node->Approved_x0020_By ?? '')),
            'order_number' => trim((string) ($node->Order_x0020_Number ?? '')),
            'pr_number' => trim((string) ($node->PR_x0020_Number ?? '')),
            'last_purchase_date' => $lastPurchaseDate,
            'last_purchase_number' => $lastPurchaseNumber,
            'pi_number' => $piNumber !== '' ? $piNumber : ' - ',
            'supplier_name' => trim((string) ($node->Supplier_x0020_Name ?? '')),
            'item_name' => trim((string) ($node->Item_x0020_Name ?? '')),
            'item_category' => trim((string) ($node->Item_x0020_Category ?? '')),
            'qty_ordered' => (float) ($node->{'Qty._x0020_Ordered'} ?? 0),
            'qty_delivered' => (float) ($node->{'Qty._x0020_Delivered_x0020__x0028_Smallest_x0029_'} ?? 0),
            'status' => $status !== '' ? $status : '-',
            'jangka_waktu_approve_po' => $approveDays,
            'jangka_waktu_po_pi' => $poPiDays,
            'jangka_waktu_pr_pi' => $prPiDays,
        ];
    }

    private static function buildSummaryGroups(array $rows): array
    {
        $groups = [];

        foreach ($rows as $row) {
            $approvedBy = (string) ($row['approved_by'] ?? '');
            $approvedBy = $approvedBy !== '' ? $approvedBy : 'Unknown';
            $category = (string) ($row['item_category'] ?? '');
            $category = $category !== '' ? $category : '-';

            if (! isset($groups[$approvedBy])) {
                $groups[$approvedBy] = [
                    'approved_by' => $approvedBy,
                    'categories' => [],
                ];
            }

            if (! isset($groups[$approvedBy]['categories'][$category])) {
                $groups[$approvedBy]['categories'][$category] = self::emptySummaryCategory($category);
            }

            self::accumulateSummary($groups[$approvedBy]['categories'][$category], $row);
        }

        foreach ($groups as &$group) {
            $group['categories'] = array_values(array_map(
                static fn (array $category): array => self::finalizeSummaryCategory($category),
                $group['categories'],
            ));
            usort($group['categories'], static fn (array $a, array $b): int => strcmp($a['category'], $b['category']));
            $group['rowspan'] = max(1, count($group['categories']));
        }
        unset($group);

        return array_values($groups);
    }

    private static function buildSummaryTotals(array $rows): array
    {
        $total = self::emptySummaryCategory('Total');

        foreach ($rows as $row) {
            self::accumulateSummary($total, $row);
        }

        return self::finalizeSummaryCategory($total);
    }

    private static function emptySummaryCategory(string $category): array
    {
        return [
            'category' => $category,
            'approve_po_keys' => [],
            'popi_keys' => [],
            'prpi_keys' => [],
        ];
    }

    private static function accumulateSummary(array &$summary, array $row): void
    {
        $approveDays = $row['jangka_waktu_approve_po'] ?? null;
        $orderNumber = (string) ($row['order_number'] ?? '');
        if ($approveDays !== null && $orderNumber !== '') {
            $summary['approve_po_keys'][$orderNumber] = $approveDays;
        }

        $poPiDays = $row['jangka_waktu_po_pi'] ?? null;
        $piKey = (string) ($row['pi_number'] ?? '');
        if ($poPiDays !== null && $piKey !== '' && $piKey !== ' - ') {
            $summary['popi_keys'][$piKey] = $poPiDays;
        }

        $prPiDays = $row['jangka_waktu_pr_pi'] ?? null;
        $prNumber = (string) ($row['pr_number'] ?? '');
        if ($prPiDays !== null && $prNumber !== '') {
            $summary['prpi_keys'][$prNumber] = $prPiDays;
        }
    }

    private static function finalizeSummaryCategory(array $summary): array
    {
        $approveDays = array_values($summary['approve_po_keys'] ?? []);
        $poPiDays = array_values($summary['popi_keys'] ?? []);
        $prPiDays = array_values($summary['prpi_keys'] ?? []);

        $apr02 = count(array_filter($approveDays, static fn (int $days): bool => $days <= 2));
        $aprGt2 = count(array_filter($approveDays, static fn (int $days): bool => $days > 2));
        $poPi05 = count(array_filter($poPiDays, static fn (int $days): bool => $days <= 5));
        $poPiGt5 = count(array_filter($poPiDays, static fn (int $days): bool => $days > 5));
        $prPi07 = count(array_filter($prPiDays, static fn (int $days): bool => $days <= 7));
        $prPiGt7 = count(array_filter($prPiDays, static fn (int $days): bool => $days > 7));

        return [
            'category' => $summary['category'],
            'apr_0_2' => $apr02,
            'apr_gt2' => $aprGt2,
            'apr_0_2_pct' => self::formatPercent($apr02, count($approveDays)),
            'apr_gt2_pct' => self::formatPercent($aprGt2, count($approveDays)),
            'popi_0_5' => $poPi05,
            'popi_gt5' => $poPiGt5,
            'popi_0_5_pct' => self::formatPercent($poPi05, count($poPiDays)),
            'popi_gt5_pct' => self::formatPercent($poPiGt5, count($poPiDays)),
            'prpi_0_7' => $prPi07,
            'prpi_gt7' => $prPiGt7,
            'prpi_0_7_pct' => self::formatPercent($prPi07, count($prPiDays)),
            'prpi_gt7_pct' => self::formatPercent($prPiGt7, count($prPiDays)),
        ];
    }

    private static function buildDetailGroups(array $rows): array
    {
        usort($rows, static function (array $a, array $b): int {
            return [$a['order_date_sort'], $a['order_number'], $a['item_name']]
                <=> [$b['order_date_sort'], $b['order_number'], $b['item_name']];
        });

        $groups = [];

        foreach ($rows as $row) {
            $orderNumber = (string) ($row['order_number'] ?? '');

            if (! isset($groups[$orderNumber])) {
                $groups[$orderNumber] = [
                    'order_number' => $orderNumber,
                    'order_date_display' => (string) ($row['order_date_display'] ?? ''),
                    'created_date_time_display' => (string) ($row['created_date_time_display'] ?? ''),
                    'approved_date_time_display' => (string) ($row['approved_date_time_display'] ?? ''),
                    'items' => [],
                ];
            }

            $groups[$orderNumber]['items'][] = [
                'no' => count($groups[$orderNumber]['items']) + 1,
                'item_name' => (string) ($row['item_name'] ?? ''),
                'qty_po' => self::formatQty((float) ($row['qty_ordered'] ?? 0)),
                'pr_number' => (string) ($row['pr_number'] ?? ''),
                'pi_number' => (string) ($row['pi_number'] ?? ' - '),
                'qty_pi' => self::formatQty((float) ($row['qty_delivered'] ?? 0)),
                'po_pi' => $row['jangka_waktu_po_pi'] ?? 0,
                'pr_pi' => $row['jangka_waktu_pr_pi'] ?? 0,
                'status' => (string) ($row['status'] ?? '-'),
            ];
        }

        return array_values($groups);
    }

    private static function formatQty(float $value): string
    {
        if (abs($value) < 0.001) {
            return '-';
        }

        return number_format($value, 0, '.', ',');
    }

    private static function formatDate(?Carbon $date): string
    {
        if ($date === null) {
            return '';
        }

        return $date->locale('id')->isoFormat('DD-MMM-YY');
    }

    private static function formatDateTime(?Carbon $date): string
    {
        if ($date === null) {
            return '';
        }

        return $date->locale('id')->isoFormat('DD-MMM-YY HH:mm');
    }

    private static function formatPercent(int|float $value, int|float $total): string
    {
        if ($total <= 0) {
            return '0.0%';
        }

        return number_format($value / $total * 100, 1, '.', '').'%';
    }

    private static function parseDate(string $value): ?Carbon
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (Throwable) {
            return null;
        }
    }

    private static function resolvePeriod(array $filters): ?array
    {
        $start = self::parseDate((string) ($filters['start_date'] ?? $filters['PurchaseOrderDate.StartDate'] ?? ''));
        $end = self::parseDate((string) ($filters['end_date'] ?? $filters['PurchaseOrderDate.EndDate'] ?? ''));

        if ($start === null && $end === null) {
            return null;
        }

        $start ??= $end?->copy();
        $end ??= $start?->copy();

        if ($start === null || $end === null) {
            return null;
        }

        if ($end->lessThan($start)) {
            [$start, $end] = [$end, $start];
        }

        return [
            'start' => $start->startOfDay(),
            'end' => $end->endOfDay(),
        ];
    }

    private static function resolvePeriodFromRows(array $rows): ?array
    {
        $dates = array_values(array_filter(array_map(
            static fn (array $row): ?Carbon => $row['order_date'] ?? null,
            $rows,
        )));

        if ($dates === []) {
            return null;
        }

        usort($dates, static fn (Carbon $left, Carbon $right): int => $left <=> $right);

        return [
            'start' => $dates[0]->copy()->startOfDay(),
            'end' => $dates[count($dates) - 1]->copy()->endOfDay(),
        ];
    }

    private static function resolvePrintedBy(\SimpleXMLElement $node): string
    {
        $candidateKeys = [
            'Nama_x0020_User',
            'User_x0020_Name',
            'Printed_x0020_By',
            'Created_x0020_By',
        ];

        foreach ($candidateKeys as $key) {
            $value = trim((string) ($node->$key ?? ''));

            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }
}
