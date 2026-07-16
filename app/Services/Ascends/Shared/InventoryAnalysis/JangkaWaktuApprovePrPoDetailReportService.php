<?php

namespace App\Services\Ascends\Shared\InventoryAnalysis;

use Carbon\Carbon;
use RuntimeException;
use Throwable;
use XMLReader;

class JangkaWaktuApprovePrPoDetailReportService
{
    private const TITLE = 'Laporan Jangka Waktu Approved P.Request Dan P.Order';

    private const DAY_NAMES = [
        1 => 'Minggu',
        2 => 'Senin',
        3 => 'Selasa',
        4 => 'Rabu',
        5 => 'Kamis',
        6 => 'Jumat',
        7 => 'Sabtu',
    ];

    public function buildReportDataFromXml(string $xmlContents, string $sourceLabel = 'request xml payload', array $filters = []): array
    {
        if (trim($xmlContents) === '') {
            throw new RuntimeException('XML data kosong.');
        }

        $records = $this->parseXml($xmlContents, $sourceLabel);
        $allRows = $records['rows'];

        if ($allRows === []) {
            throw new RuntimeException('Data purchase request tidak ditemukan di XML.');
        }

        $period = self::resolvePeriod($filters)
            ?? self::resolvePeriodFromRows($allRows);

        if ($period !== null) {
            $p = $period;
            $allRows = array_values(array_filter($allRows, static function (array $row) use ($p): bool {
                $date = $row['pr_date'] ?? null;

                return $date !== null && $date->betweenIncluded($p['start'], $p['end']);
            }));
        }

        if ($allRows === []) {
            throw new RuntimeException('Data purchase request tidak ditemukan di periode tersebut.');
        }

        $detailGroups = self::buildDetailGroups($allRows);
        $summaryGroups = self::buildSummaryGroups($allRows);
        $summaryTotals = self::buildSummaryTotals($summaryGroups);

        $dateRangeText = '';
        if ($period !== null) {
            $dateRangeText = 'Dari '.$period['start']->locale('id')->isoFormat('DD-MMM-YY').' s/d '.$period['end']->locale('id')->isoFormat('DD-MMM-YY');
        }

        $firstRow = $allRows[0] ?? [];

        return [
            'title' => self::TITLE,
            'source_file' => $sourceLabel,
            'period_label' => $dateRangeText,
            'printed_at' => Carbon::now()->locale('id')->translatedFormat('d F Y H:i'),
            'printed_by' => $records['printed_by'],
            'summary_groups' => $summaryGroups,
            'summary_totals' => $summaryTotals,
            'summary_info' => [
                'pr_create_date' => $firstRow['pr_create_date_display'] ?? '',
                'pr_approve_date' => $firstRow['pr_approve_date_display'] ?? '',
                'pr_approve_by' => $firstRow['pr_approve_by'] ?? '',
                'pr_approval_time_days' => $firstRow['total_approve_time'] ?? 0,
            ],
            'detail_groups' => $detailGroups,
            'total_rows' => count($allRows),
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
        $elementNames = ['purchaserequest', 'purchaseRequest', 'PurchaseRequest'];

        while ($reader->read()) {
            if ($reader->nodeType !== XMLReader::ELEMENT) {
                continue;
            }

            $nameLower = strtolower($reader->name);
            if (! in_array($nameLower, ['purchaserequest'], true) && ! in_array($reader->name, $elementNames, true)) {
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
        $prDate = self::parseDate((string) ($node->PR_x0020_Date ?? ''));
        if ($prDate === null) {
            return null;
        }

        $approvedDateTime = self::parseDate((string) ($node->Approved_x0020_Date_x002F_Time ?? ''));
        $poDate = self::parseDate((string) ($node->PO_x0020_Date ?? ''));
        $poApprovedDateTime = self::parseDate((string) ($node->PO_x0020_Approved_x0020_Date_x002F_Time ?? ''));
        $createDate = self::parseDate((string) ($node->Create_x0020_Date ?? ''));

        $prNumber = trim((string) ($node->PR_x0020_Number ?? ''));
        $poNumber = trim((string) ($node->PO_x0020_Number ?? ''));
        $openClosed = trim((string) ($node->Open_x002F_Closed ?? ''));
        $approvedBy = trim((string) ($node->Approved_x0020_By ?? ''));

        $qtyRequested = (float) ($node->{'Qty._x0020_Requested'} ?? 0);
        $qtyShipped = (float) ($node->{'Qty._x0020_Shipped'} ?? 0);
        $qtyOnOrder = (float) ($node->{'Qty._x0020_On_x0020_Order'} ?? 0);

        $estimasiAprvPR = null;
        if ($approvedDateTime !== null) {
            $estimasiAprvPR = (int) $prDate->copy()->startOfDay()->diffInDays($approvedDateTime->copy()->startOfDay(), false);
        }

        $jangkaWaktuAprovePR = null;
        if ($approvedDateTime !== null && $createDate !== null) {
            $jangkaWaktuAprovePR = (int) $createDate->copy()->startOfDay()->diffInDays($approvedDateTime->copy()->startOfDay(), false);
        }

        $estimasiAprvPRPO = null;
        if ($poApprovedDateTime !== null && $poDate !== null) {
            $estimasiAprvPRPO = (int) $poDate->copy()->startOfDay()->diffInDays($poApprovedDateTime->copy()->startOfDay(), false);
        }

        $jangkaWaktuPRPO = null;
        if ($poDate !== null && $approvedDateTime !== null) {
            $jangkaWaktuPRPO = (int) $approvedDateTime->copy()->startOfDay()->diffInDays($poDate->copy()->startOfDay(), false);
        }

        $nameDay = $createDate !== null
            ? (self::DAY_NAMES[(int) $createDate->format('N')] ?? 'klp')
            : 'klp';
        $nameDay2 = $approvedDateTime !== null
            ? (self::DAY_NAMES[(int) $approvedDateTime->format('N')] ?? 'klp')
            : 'klp';
        $nameDay3 = self::DAY_NAMES[(int) $prDate->format('N')] ?? 'klp';

        $poCreate = null;
        if ($poDate !== null && $approvedDateTime !== null) {
            $poCreate = (int) $approvedDateTime->copy()->startOfDay()->diffInDays($poDate->copy()->startOfDay(), false);
        }

        $prPo = null;
        if ($poDate !== null && $approvedDateTime !== null) {
            $prPo = (int) $approvedDateTime->copy()->startOfDay()->diffInDays($poDate->copy()->startOfDay(), false);
        }

        $status = '-';
        if ($openClosed === 'Closed') {
            $status = 'Closed';
        } elseif (abs($qtyShipped - $qtyRequested) < 0.001) {
            $status = '-';
        } elseif ($poNumber === '') {
            $status = 'Need PO';
        } elseif (abs($qtyOnOrder - $qtyRequested) >= 0.001) {
            $status = 'Pending';
        }

        $onPo = $status === 'Pending' ? ($qtyRequested - $qtyOnOrder) : 0;

        $tesPo = ($poNumber === '' || $poNumber === null) ? ' - ' : $poNumber;

        $totalApproveTime = null;
        if ($poApprovedDateTime !== null) {
            $zeroDate = Carbon::parse('0001-01-01');
            if ($poApprovedDateTime->isBefore($zeroDate)) {
                $totalApproveTime = 0;
            } else {
                $totalApproveTime = (int) $prDate->copy()->startOfDay()->diffInDays($poApprovedDateTime->copy()->startOfDay(), false);
            }
        }

        $tampil = 'NOT';
        if (($jangkaWaktuPRPO !== null && $jangkaWaktuPRPO > 2) || ($jangkaWaktuAprovePR !== null && $jangkaWaktuAprovePR > 1)) {
            $tampil = 'Tampil';
        }

        $itemName = trim((string) ($node->Item_x0020_Name ?? ''));
        $itemCode = trim((string) ($node->Item_x0020_Code ?? ''));
        $prItemRemarks = trim((string) ($node->PR_x0020_Item_x0020_Remarks ?? ''));
        $uom = trim((string) ($node->UOM ?? ''));
        $supplierName = trim((string) ($node->Supplier_x0020_Name ?? ''));

        return [
            'pr_date' => $prDate,
            'pr_date_sort' => $prDate->format('Y-m-d'),
            'pr_date_display' => self::formatDate($prDate),
            'pr_approve_date_carbon' => $approvedDateTime,
            'pr_create_date_carbon' => $createDate,
            'pr_approve_date_display' => $approvedDateTime !== null ? self::formatDate($approvedDateTime) : '',
            'pr_create_date_display' => $createDate !== null ? self::formatDate($createDate) : '',
            'pr_number' => $prNumber,
            'po_number' => $poNumber,
            'has_po' => $poNumber !== '' && $poNumber !== null,
            'item_name' => $itemName,
            'item_code' => $itemCode,
            'qty_requested' => $qtyRequested,
            'qty_shipped' => $qtyShipped,
            'qty_on_order' => $qtyOnOrder,
            'uom' => $uom,
            'approved_by' => $approvedBy,
            'pr_approve_by' => $approvedBy,
            'open_closed' => $openClosed,
            'status' => $status,
            'supplier_name' => $supplierName,
            'pr_item_remarks' => $prItemRemarks,
            'estimasi_aprv_pr' => $estimasiAprvPR,
            'jangka_waktu_aprove_pr' => $jangkaWaktuAprovePR,
            'estimasi_aprv_pr_po' => $estimasiAprvPRPO,
            'jangka_waktu_pr_po' => $jangkaWaktuPRPO,
            'name_day' => $nameDay,
            'name_day2' => $nameDay2,
            'name_day3' => $nameDay3,
            'po_create' => $poCreate,
            'pr_po' => $prPo,
            'on_po' => $onPo,
            'tes_po' => $tesPo,
            'total_approve_time' => $totalApproveTime,
            'tampil' => $tampil,
        ];
    }

    private static function buildDetailGroups(array $rows): array
    {
        $prGroups = [];
        $itemIndex = 0;

        foreach ($rows as $row) {
            $prNum = (string) ($row['pr_number'] ?? '');

            if (! isset($prGroups[$prNum])) {
                $createDate = $row['pr_create_date_carbon'] ?? null;
                $approveDate = $row['pr_approve_date_carbon'] ?? null;
                $approvalTime = $row['total_approve_time'] ?? null;

                $prGroups[$prNum] = [
                    'pr_number' => $prNum,
                    'pr_create_date_display' => $createDate !== null
                        ? $createDate->locale('id')->translatedFormat('l, d-M-Y H:i')
                        : '',
                    'pr_approve_date_display' => $approveDate !== null
                        ? $approveDate->locale('id')->translatedFormat('l, d-M-Y H:i')
                        : '',
                    'pr_approve_by' => (string) ($row['pr_approve_by'] ?? ''),
                    'pr_approval_time' => $approvalTime !== null ? $approvalTime.' Day' : '-',
                    'items' => [],
                ];
            }

            $itemIndex++;
            $qtyRequested = (float) ($row['qty_requested'] ?? 0);
            $qtyOnOrder = (float) ($row['qty_on_order'] ?? 0);
            $qtyShipped = (float) ($row['qty_shipped'] ?? 0);
            $sisa = $qtyRequested - $qtyShipped - $qtyOnOrder;

            $prGroups[$prNum]['items'][] = [
                'no' => $itemIndex,
                'item_name' => (string) ($row['item_name'] ?? ''),
                'qty_pr' => $qtyRequested > 0 ? number_format($qtyRequested, 0, '.', ',') : '-',
                'qty_po' => $qtyOnOrder > 0 ? number_format($qtyOnOrder, 0, '.', ',') : '-',
                'on_order' => $qtyOnOrder > 0 ? number_format($qtyOnOrder, 0, '.', ',') : '-',
                'po_number' => (string) ($row['tes_po'] ?? ' - '),
                'po_create' => $row['po_create'] !== null ? $row['po_create'].' Day' : '-',
                'po_approv' => $row['estimasi_aprv_pr_po'] !== null ? $row['estimasi_aprv_pr_po'].' Day' : '-',
                'total_time' => $row['total_approve_time'] !== null ? $row['total_approve_time'].' Day' : '-',
                'status' => (string) ($row['status'] ?? '-'),
                'sisa' => $sisa > 0 ? number_format($sisa, 0, '.', ',') : '-',
                'keterangan' => (string) ($row['pr_item_remarks'] ?? ''),
            ];
        }

        return array_values($prGroups);
    }

    private static function buildSummaryGroups(array $rows): array
    {
        $approverData = [];

        foreach ($rows as $row) {
            $approvedBy = (string) ($row['approved_by'] ?? 'Unknown');
            if (! isset($approverData[$approvedBy])) {
                $approverData[$approvedBy] = [
                    'approved_by' => $approvedBy,
                    'seen_prs' => [],
                    'total_items' => 0,
                    'prpo_0_2' => 0,
                    'prpo_gt2' => 0,
                ];
            }

            $data = &$approverData[$approvedBy];
            $data['total_items']++;

            $prNum = (string) ($row['pr_number'] ?? '');
            if (! isset($data['seen_prs'][$prNum])) {
                $data['seen_prs'][$prNum] = [
                    'apr' => $row['jangka_waktu_aprove_pr'],
                    'has_po' => (bool) ($row['has_po'] ?? false),
                ];
            }

            $prpo = $row['jangka_waktu_pr_po'];
            if ($prpo !== null) {
                if ($prpo <= 2) {
                    $data['prpo_0_2']++;
                } else {
                    $data['prpo_gt2']++;
                }
            }
            unset($data);
        }

        $result = [];
        foreach ($approverData as $d) {
            $totalPr = count($d['seen_prs']);
            $totalItems = $d['total_items'];

            $apr01 = 0;
            $aprGt1 = 0;
            foreach ($d['seen_prs'] as $prData) {
                if ($prData['apr'] !== null) {
                    if ($prData['apr'] <= 1) {
                        $apr01++;
                    } else {
                        $aprGt1++;
                    }
                }
            }

            $result[] = [
                'approved_by' => $d['approved_by'],
                'total_pr' => $totalPr,
                'po_count' => $totalItems,
                'apr_0_1' => $apr01,
                'apr_gt1' => $aprGt1,
                'apr_0_1_pct' => $totalPr > 0 ? round($apr01 / $totalPr * 100) : 0,
                'apr_gt1_pct' => $totalPr > 0 ? round($aprGt1 / $totalPr * 100) : 0,
                'prpo_0_2' => $d['prpo_0_2'],
                'prpo_gt2' => $d['prpo_gt2'],
                'prpo_0_2_pct' => $totalItems > 0 ? round($d['prpo_0_2'] / $totalItems * 100) : 0,
                'prpo_gt2_pct' => $totalItems > 0 ? round($d['prpo_gt2'] / $totalItems * 100) : 0,
            ];
        }

        usort($result, static fn (array $a, array $b): int => strcmp($a['approved_by'], $b['approved_by']));

        return $result;
    }

    private static function buildSummaryTotals(array $groups): array
    {
        $totalPr = 0;
        $apr01 = 0;
        $aprGt1 = 0;
        $prpo02 = 0;
        $prpoGt2 = 0;
        $totalItems = 0;

        foreach ($groups as $g) {
            $totalPr += $g['total_pr'];
            $totalItems += $g['po_count'];
            $apr01 += $g['apr_0_1'];
            $aprGt1 += $g['apr_gt1'];
            $prpo02 += $g['prpo_0_2'];
            $prpoGt2 += $g['prpo_gt2'];
        }

        return [
            'total_pr' => $totalPr,
            'po_count' => $totalItems,
            'apr_0_1' => $apr01,
            'apr_gt1' => $aprGt1,
            'prpo_0_2' => $prpo02,
            'prpo_gt2' => $prpoGt2,
            'apr_0_1_pct' => $totalPr > 0 ? round($apr01 / $totalPr * 100) : 0,
            'apr_gt1_pct' => $totalPr > 0 ? round($aprGt1 / $totalPr * 100) : 0,
            'prpo_0_2_pct' => $totalItems > 0 ? round($prpo02 / $totalItems * 100) : 0,
            'prpo_gt2_pct' => $totalItems > 0 ? round($prpoGt2 / $totalItems * 100) : 0,
        ];
    }

    private static function formatDate(?Carbon $date): string
    {
        if ($date === null) {
            return '';
        }

        return $date->locale('id')->translatedFormat('d-M-y');
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
            static fn (array $row): ?Carbon => $row['pr_date'] ?? null,
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
