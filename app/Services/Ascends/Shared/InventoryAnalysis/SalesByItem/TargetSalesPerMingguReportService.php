<?php

namespace App\Services\Ascends\Shared\InventoryAnalysis\SalesByItem;

use Carbon\Carbon;
use RuntimeException;
use Throwable;
use XMLReader;

class TargetSalesPerMingguReportService
{
    private const TITLE = 'Laporan Rekap Penjualan';

    private const GSU_FAMILIES = ['ENAMEL', 'PLASTIK FURNITURE 1', 'PLASTIK FURNITURE 2', 'SAPU', 'STAINLESS', 'PLASTIK KABINET', 'FURNITURE LIPAT'];

    private const FAMILY_ORDER = ['ENAMEL', 'FURNITURE LIPAT', 'PLASTIK FURNITURE 1', 'PLASTIK FURNITURE 2', 'PLASTIK KABINET 1', 'PLASTIK KABINET 2'];

    private const MONTHLY_TARGETS = [
        1 => 8785500000, 2 => 8556500000, 3 => 8693000000,
        4 => 8480000000, 5 => 8895500000, 6 => 8984325000,
        7 => 9127650000, 8 => 8216500000, 9 => 8711500000,
        10 => 8509000000, 11 => 8555500000, 12 => 8478400000,
    ];

    private const BIWEEKLY_TARGETS = [
        'ENAMEL' => [1 => 790695000, 2 => 770085000, 3 => 782370000, 4 => 763200000, 5 => 800595000, 6 => 808589250, 7 => 821488500, 8 => 739485000, 9 => 769995000, 10 => 751770000, 11 => 739485000, 12 => 732465000],
        'PLASTIK FURNITURE 1' => [1 => 702840000, 2 => 684520000, 3 => 695440000, 4 => 678400000, 5 => 711640000, 6 => 718746000, 7 => 730212000, 8 => 800000000, 9 => 900000000, 10 => 913300000, 11 => 694120000, 12 => 787040000],
        'PLASTIK FURNITURE 2' => [1 => 1054260000, 2 => 1026780000, 3 => 1043160000, 4 => 1017600000, 5 => 1067460000, 6 => 1078119000, 7 => 1095318000, 8 => 925465000, 9 => 967100000, 10 => 913300000, 11 => 1289080000, 12 => 1180560000],
        'PLASTIK KABINET 1' => [1 => 3997402500, 2 => 3893207500, 3 => 3955315000, 4 => 3858400000, 5 => 4047452500, 6 => 4087867875, 7 => 4153080750, 8 => 3738507500, 9 => 3892752500, 10 => 3800615000, 11 => 3738507000, 12 => 3703017500],
        'PLASTIK KABINET 2' => [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 2236274250, 7 => 2236274250, 8 => 0, 9 => 0, 10 => 0, 11 => 0, 12 => 0],
        'FURNITURE LIPAT' => [1 => 87855000, 2 => 85565000, 3 => 86930000, 4 => 84800000, 5 => 88955000, 6 => 89843250, 7 => 91276500, 8 => 0, 9 => 85555000, 10 => 83530000, 11 => 82165000, 12 => 81385000],
    ];

    private const WEEKLY_TARGETS = [
        'ENAMEL' => 821488500,
        'PLASTIK FURNITURE 1' => 730212000,
        'PLASTIK FURNITURE 2' => 1095318000,
        'PLASTIK KABINET 1' => 4153080750,
        'PLASTIK KABINET 2' => 2236274250,
        'FURNITURE LIPAT' => 91276500,
    ];

    public function buildReportDataFromXml(string $xmlContents, string $sourceLabel = 'request xml payload', array $filters = []): array
    {
        if (trim($xmlContents) === '') {
            throw new RuntimeException('Data XML kosong.');
        }

        $company = $filters['company'] ?? 'GSU';
        $records = $this->parseXml($xmlContents, $sourceLabel, $company);

        if ($records === []) {
            throw new RuntimeException('Data tidak ditemukan pada XML.');
        }

        $startDate = isset($filters['start_date']) ? $this->parseDateValue($filters['start_date']) : null;
        if ($startDate === null) {
            $startDate = $this->determineStartDateFromRecords($records);
        }

        $month = (int) $startDate->format('n');
        $monthStart = $startDate->copy()->startOfMonth();
        $monthEnd = $startDate->copy()->endOfMonth();
        $monthlyTarget = self::MONTHLY_TARGETS[$month] ?? 0;

        $periodLabel = 'Dari '.$monthStart->locale('id')->isoFormat('DD-MMM-YY').' s/d '.$monthEnd->locale('id')->isoFormat('DD-MMM-YY');

        $weekly = $this->buildWeekly($records, $month);
        $cumulative = $this->buildCumulative($records, $month);

        return [
            'title' => self::TITLE,
            'period_label' => $periodLabel,
            'printed_by' => '',
            'monthly_target' => $monthlyTarget,
            'month' => $month,
            'weekly' => $weekly,
            'cumulative' => $cumulative,
        ];
    }

    private function parseXml(string $xmlContents, string $sourceLabel, string $company): array
    {
        $reader = new XMLReader;

        if (! @$reader->XML($xmlContents, null, LIBXML_NOCDATA | LIBXML_NONET)) {
            throw new RuntimeException("File XML tidak valid: {$sourceLabel}");
        }

        $rows = [];

        while ($reader->read()) {
            if ($reader->nodeType !== XMLReader::ELEMENT || $reader->name !== 'Invoices') {
                continue;
            }

            $recordXml = $reader->readOuterXml();
            if (! is_string($recordXml) || trim($recordXml) === '') {
                continue;
            }

            $node = @simplexml_load_string($recordXml, 'SimpleXMLElement', LIBXML_NOCDATA);
            if ($node === false) {
                continue;
            }

            $type = trim((string) ($node->Type ?? ''));
            if ($type !== 'Sales Invoice') {
                continue;
            }

            $familyName = trim((string) ($node->Item_x0020_Family_x0020_Name ?? ''));
            if ($familyName === '') {
                continue;
            }

            $matched = false;
            foreach (self::GSU_FAMILIES as $gf) {
                if (strcasecmp($familyName, $gf) === 0 || str_starts_with($familyName, $gf)) {
                    $matched = true;
                    break;
                }
            }

            if (! $matched) {
                continue;
            }

            $lineCOGS = (float) ($node->Line_x0020_COGS ?? 0);
            $lineGrossProfit = (float) ($node->Line_x0020_Gross_x0020_Profit ?? 0);
            $lineTotal = $lineCOGS + $lineGrossProfit;

            $qty = (float) ($node->Smallest_x0020_Quantity ?? $node->Quantity ?? 0);

            $invoiceDate = $this->parseDateValue((string) ($node->Invoice_x0020_Date ?? ''));
            if ($invoiceDate === null) {
                continue;
            }

            $invoiceWeek = (int) ($node->Invoice_x0020_Week ?? 0);
            if ($invoiceWeek === 0) {
                $dayOfMonth = (int) $invoiceDate->format('j');
                $invoiceWeek = (int) ceil($dayOfMonth / 7);
            }

            $familyKey = $this->resolveFamilyKey($familyName);

            $rows[] = [
                'line_total' => $lineTotal,
                'qty' => $qty,
                'family' => $familyKey,
                'invoice_date' => $invoiceDate,
                'week' => $invoiceWeek,
            ];
        }

        $reader->close();

        return $rows;
    }

    private function resolveFamilyKey(string $familyName): string
    {
        $upper = strtoupper($familyName);

        if ($upper === 'ENAMEL') {
            return 'ENAMEL';
        }

        if ($upper === 'PLASTIK FURNITURE 1') {
            return 'PLASTIK FURNITURE 1';
        }

        if ($upper === 'PLASTIK FURNITURE 2') {
            return 'PLASTIK FURNITURE 2';
        }

        if (str_starts_with($familyName, 'PLASTIK KABINET')) {
            return $familyName;
        }

        if ($upper === 'FURNITURE LIPAT') {
            return 'FURNITURE LIPAT';
        }

        return 'OTHER';
    }

    private function buildWeekly(array $records, int $month): array
    {
        $monthlyTarget = self::MONTHLY_TARGETS[$month] ?? 0;
        $weekly = [];

        for ($w = 1; $w <= 5; $w++) {
            $weekRecords = array_filter($records, static fn (array $r) => $r['week'] === $w);
            $weekly[$w] = $this->makeFamilyEntry($weekRecords, $month, false);
        }

        $allRecords = $records;
        $weekly['total'] = $this->makeFamilyEntry($allRecords, $month, false);

        return $weekly;
    }

    private function buildCumulative(array $records, int $month): array
    {
        $cumulative = [];

        for ($w = 2; $w <= 5; $w++) {
            $weekRecords = array_filter($records, static fn (array $r) => $r['week'] <= $w);
            $cumulative['w1_w'.$w] = $this->makeFamilyEntry($weekRecords, $month, true);
        }

        return $cumulative;
    }

    private function makeFamilyEntry(array $records, int $month, bool $isCumulative): array
    {
        $monthlyTarget = self::MONTHLY_TARGETS[$month] ?? 0;

        $totalQty = 0.0;
        $totalRp = 0.0;
        $familyData = [];

        foreach (self::FAMILY_ORDER as $fam) {
            $familyData[$fam] = ['qty' => 0.0, 'rp' => 0.0];
        }

        foreach ($records as $row) {
            $fam = $row['family'];
            $totalQty += $row['qty'];
            $totalRp += $row['line_total'];

            if (isset($familyData[$fam])) {
                $familyData[$fam]['qty'] += $row['qty'];
                $familyData[$fam]['rp'] += $row['line_total'];
            }
        }

        $families = [];
        foreach (self::FAMILY_ORDER as $fam) {
            $rp = $familyData[$fam]['rp'];
            $qty = $familyData[$fam]['qty'];

            if ($isCumulative) {
                $target = self::BIWEEKLY_TARGETS[$fam][$month] ?? 0;
            } else {
                $target = self::WEEKLY_TARGETS[$fam] ?? 0;
            }

            $pct = $target != 0 ? ($rp / $target * 100) : 0;

            $families[$fam] = [
                'qty' => $qty,
                'rp' => $rp,
                'persen' => $pct,
            ];
        }

        $totalPersen = $monthlyTarget != 0 ? ($totalRp / $monthlyTarget * 100) : 0;

        return [
            'total' => [
                'qty' => $totalQty,
                'rp' => $totalRp,
                'persen' => $totalPersen,
            ],
            'families' => $families,
        ];
    }

    private function determineStartDateFromRecords(array $records): Carbon
    {
        $earliest = null;

        foreach ($records as $row) {
            $date = $row['invoice_date'];
            if ($earliest === null || $date->lt($earliest)) {
                $earliest = $date;
            }
        }

        return $earliest ?? now();
    }

    private function parseDateValue(string $value): ?Carbon
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
}
