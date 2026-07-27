<?php

namespace App\Services\Ascends\Shared\GeneralLedger\JournalDetails;

use Carbon\Carbon;
use RuntimeException;
use Throwable;
use XMLReader;

class LabaKotorRuReportService
{
    private const TITLE = 'Laporan Laba Kotor';

    private const PENJUALAN_PREFIXES = ['411.', '412.', '451.', '516.', '621.'];

    public function buildReportDataFromXml(string $xmlContents, string $sourceLabel = 'request xml payload', array $filters = []): array
    {
        $allRows = $this->parseXml($xmlContents, $sourceLabel);

        if ($allRows === []) {
            throw new RuntimeException('Data jurnal tidak ditemukan pada XML.');
        }

        $rawStartDate = trim((string) ($filters['Date.StartDate'] ?? $filters['Date_StartDate'] ?? $filters['StartDate'] ?? $filters['start_date'] ?? ''));
        $rawEndDate = trim((string) ($filters['Date.EndDate'] ?? $filters['Date_EndDate'] ?? $filters['EndDate'] ?? $filters['end_date'] ?? ''));

        if ($rawStartDate === '') {
            throw new RuntimeException('Parameter Date.StartDate wajib dikirim.');
        }

        $startDate = Carbon::parse($rawStartDate)->startOfDay();
        $bulanA = $startDate->copy()->startOfMonth();
        $bulanB = $bulanA->copy()->addMonth()->startOfMonth();

        $filtered = $this->applyFilters($allRows, $bulanA, $bulanB);

        if ($filtered === []) {
            throw new RuntimeException('Tidak ada data yang memenuhi kriteria.');
        }

        $dataB = $this->filterByMonth($filtered, $bulanB);
        $dataA = $this->filterByMonth($filtered, $bulanA);

        $groupsB = $this->aggregatePeriodData($dataB, $bulanB);
        $groupsA = $this->aggregatePeriodData($dataA, $bulanA);

        $merged = $this->mergePeriods($groupsB, $groupsA);

        $grandTotalB = array_sum(array_map(static fn (array $g): float => $g['amount_b'], $merged));
        $grandTotalA = array_sum(array_map(static fn (array $g): float => $g['amount_a'], $merged));

        return [
            'title' => self::TITLE,
            'company' => '',
            'period_label' => 'Dari '.$bulanB->copy()->startOfMonth()->locale('id')->isoFormat('DD-MMM-YY').' s/d '.$bulanB->copy()->endOfMonth()->locale('id')->isoFormat('DD-MMM-YY'),
            'bulan_b_label' => $bulanB->locale('id')->isoFormat('MMM-YYYY'),
            'bulan_a_label' => $bulanA->locale('id')->isoFormat('MMM-YYYY'),
            'printed_at' => Carbon::now()->locale('id')->translatedFormat('d F Y H:i'),
            'printed_by' => '',
            'groups' => $merged,
            'grand_total_b' => $grandTotalB,
            'grand_total_a' => $grandTotalA,
        ];
    }

    private function parseXml(string $xmlContents, string $sourceLabel): array
    {
        if (trim($xmlContents) === '') {
            throw new RuntimeException('Data XML kosong.');
        }

        $reader = new XMLReader;
        if (! @$reader->XML($xmlContents, null, LIBXML_NOCDATA | LIBXML_NONET)) {
            throw new RuntimeException("File XML tidak valid: {$sourceLabel}");
        }

        $rows = [];
        while ($reader->read()) {
            if ($reader->nodeType !== XMLReader::ELEMENT || strtolower($reader->name) !== 'invoices') {
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

            $row = [];
            foreach ($node->children() as $key => $value) {
                $cleanKey = $this->cleanXmlKey((string) $key);
                $row[$cleanKey] = trim((string) $value);
            }

            if (($row['Account Code'] ?? '') !== '') {
                $rows[] = $row;
            }
        }

        $reader->close();

        return $rows;
    }

    private function cleanXmlKey(string $key): string
    {
        $key = str_replace('_x0020_', ' ', $key);
        $key = str_replace('_x0028_', '(', $key);
        $key = str_replace('_x0029_', ')', $key);

        return str_replace('_x002F_', '/', $key);
    }

    private function applyFilters(array $rows, Carbon $bulanA, Carbon $bulanB): array
    {
        $start = $bulanA->copy()->startOfMonth();
        $end = $bulanB->copy()->endOfMonth();

        $result = [];

        foreach ($rows as $row) {
            $voucherDate = trim((string) ($row['Voucher Date'] ?? ''));
            if ($voucherDate === '') {
                continue;
            }

            try {
                $vd = Carbon::parse($voucherDate);
            } catch (Throwable) {
                continue;
            }

            if ($vd->lessThan($start) || $vd->greaterThan($end)) {
                continue;
            }

            $accountCode = (string) ($row['Account Code'] ?? '');
            $accountName = (string) ($row['Account Name'] ?? '');
            $description = (string) ($row['Description'] ?? '');
            $voucherRef = (string) ($row['Voucher Ref'] ?? '');

            $adjResult = $this->applyAdj($accountCode, $voucherRef, $description);
            if ($adjResult !== 'tampil') {
                continue;
            }

            $groupName2 = $this->applyGroupName2($accountCode, $accountName, $description, $voucherRef, $row);
            if ($groupName2 === '') {
                continue;
            }

            $row['_groupName2'] = $groupName2;
            $row['_assembly'] = $this->applyAssembly($description);
            $result[] = $row;
        }

        return $result;
    }

    private function applyAdj(string $accountCode, string $voucherRef, string $description): string
    {
        if (str_contains($voucherRef, 'ADJ/') || str_contains($voucherRef, 'JP/AP')) {
            return 'ADJ';
        }

        if (str_contains($description, 'ADJUSMENT:') || str_contains($description, 'ADJUSTMENT:')) {
            return 'ADJ';
        }

        if ($accountCode === '111.400.304') {
            return 'ADJ';
        }

        return 'tampil';
    }

    private function applyGroupName2(string $accountCode, string $accountName, string $description, string $voucherRef, array $row): string
    {
        $nameUpper = strtoupper($accountName);
        $descUpper = strtoupper($description);
        $voucherNumber = (string) ($row['Voucher Number'] ?? '');

        // GroupName2 — match persis formula Crystal
        if (str_contains($nameUpper, 'JABON')) {
            return 'JABON BJ';
        }

        if (str_contains($nameUpper, 'PULAI')) {
            return 'PULAI BJ';
        }

        if (str_contains($nameUpper, 'RAMBUNG')) {
            return 'RAMBUNG BJ';
        }

        if (str_contains($nameUpper, 'ABU SEKAM') || str_contains($nameUpper, 'KAYU SEMPENGAN')) {
            return 'KAYU SEMPENGAN';
        }

        if (str_contains($nameUpper, 'RETUR PENJ. KAYU LAT')) {
            return 'RETUR KAYU LAT';
        }

        if (str_contains($nameUpper, 'KAYU LAT')) {
            return 'KAYU LAT';
        }

        if (str_contains($nameUpper, 'SAWN TIMBER')) {
            return 'KAYU LAT';
        }

        if ($accountCode === '111.400.202' && str_contains($descUpper, 'SALES')) {
            return 'KAYU LAT';
        }

        if (str_contains($nameUpper, 'POTONGAN PEMBELIAN')) {
            return 'POTONGAN';
        }

        // HARGA POKOK + SR- di Voucher Number (bukan Description)
        if (str_contains($nameUpper, 'HARGA POKOK') && str_contains($voucherNumber, 'SR-')) {
            return 'RETUR KAYU LAT';
        }

        // SPAREPART — specific accounts + SALES in Description
        if ($accountCode === '112.200.009' && str_contains($descUpper, 'SALES')) {
            return 'HPP PENJ. SPAREPART';
        }

        if ($accountCode === '111.400.906' && str_contains($descUpper, 'SALES')) {
            return 'HPP PENJ. SPAREPART';
        }

        if ($accountCode === '111.400.903' && str_contains($descUpper, 'SALES')) {
            return 'HPP PENJ. SPAREPART';
        }

        if ($accountCode === '111.400.904' && str_contains($descUpper, 'SALES')) {
            return 'HPP PENJ. SPAREPART';
        }

        // 111.400.905: LEFT(Description,5)='SALES' — persis formula
        if ($accountCode === '111.400.905' && str_starts_with($descUpper, 'SALES')) {
            return 'HPP PENJ. SPAREPART';
        }

        // 111.400.101 + SALES → KAYU BULAT
        if ($accountCode === '111.400.101' && str_contains($descUpper, 'SALES')) {
            return 'KAYU BULAT';
        }

        // SALES 3.1.6.16.0071 → STRAPPING BAND (di GroupName2)
        if (str_contains($descUpper, 'SALES 3.1.6.16.0071')) {
            return 'STRAPPING BAND';
        }

        // NameGroupKayu entries yang TIDAK ada di GroupName2:
        // 111.400.404 + SALES → LEM
        if ($accountCode === '111.400.404' && str_contains($descUpper, 'SALES')) {
            return 'LEM';
        }

        // 111.400.407 + 'SALES 3.1.6.16.0071: TAL' → STRAPPING BAND (dari NameGroupKayu)
        if ($accountCode === '111.400.407' && str_contains($descUpper, 'SALES 3.1.6.16.0071: TAL')) {
            return 'STRAPPING BAND';
        }

        return '';
    }

    private function applyAssembly(string $description): int
    {
        $descUpper = strtoupper($description);

        if (str_contains($descUpper, 'ASSEMBLY')) {
            return 1;
        }

        if (str_contains($descUpper, 'IU:')) {
            return 1;
        }

        return 2;
    }

    private function filterByMonth(array $rows, Carbon $month): array
    {
        $start = $month->copy()->startOfMonth();
        $end = $month->copy()->endOfMonth();

        return array_values(array_filter($rows, static function (array $row) use ($start, $end): bool {
            $dateStr = trim((string) ($row['Voucher Date'] ?? ''));
            if ($dateStr === '') {
                return false;
            }

            try {
                $date = Carbon::parse($dateStr);

                return $date->greaterThanOrEqualTo($start) && $date->lessThanOrEqualTo($end);
            } catch (Throwable) {
                return false;
            }
        }));
    }

    private function isPenjualanAccount(string $accountCode): bool
    {
        foreach (self::PENJUALAN_PREFIXES as $prefix) {
            if (str_starts_with($accountCode, $prefix)) {
                return true;
            }
        }

        return false;
    }

    private function aggregatePeriodData(array $rows, Carbon $bulan): array
    {
        $groups = [];
        $targetMonth = (int) $bulan->format('n');

        foreach ($rows as $row) {
            $groupName2 = (string) ($row['_groupName2'] ?? '');
            $assembly = (int) ($row['_assembly'] ?? 2);
            $accountCode = (string) ($row['Account Code'] ?? '');
            $accountName = (string) ($row['Account Name'] ?? '');
            $description = (string) ($row['Description'] ?? '');
            $amountDb = (float) ($row['Amount DB'] ?? 0);
            $amountCr = (float) ($row['Amount CR'] ?? 0);
            $raw = $amountCr - $amountDb;

            try {
                $voucherMonth = (int) Carbon::parse($row['Voucher Date'] ?? '')->format('n');
            } catch (Throwable) {
                continue;
            }

            if ($voucherMonth !== $targetMonth) {
                continue;
            }

            if ($assembly !== 2) {
                continue;
            }

            if (! isset($groups[$groupName2])) {
                $groups[$groupName2] = [
                    'name' => $groupName2,
                    'penjualan_items' => [],
                    'hpp_items' => [],
                    'sort_order' => $this->getNameGroupSortOrder($accountCode, $accountName, $description, $row),
                ];
            }

            $nameUpper = strtoupper($accountName);

            // Month formula: tentukan Amount vs Amount CR
            // PENJUALAN, Retur, Potongan Pembe, KAYU BULAT → pakai Amount
            // Sisa → pakai Amount CR
            $isPenjualan = $this->isPenjualanAccount($accountCode)
                || str_contains($nameUpper, 'RETUR')
                || str_contains($nameUpper, 'POTONGAN PEMBE')
                || str_contains($nameUpper, 'KAYU BULAT');

            $amount = $isPenjualan ? $raw : $amountCr;

            if ($isPenjualan) {
                // Penjualan items (Amount)
                $itemKey = $accountCode.'|||'.$accountName;
                if (! isset($groups[$groupName2]['penjualan_items'][$itemKey])) {
                    $groups[$groupName2]['penjualan_items'][$itemKey] = [
                        'account_code' => $accountCode,
                        'account_name' => $accountName,
                        'no_urut' => 1,
                        'amount' => 0,
                    ];
                }
                $groups[$groupName2]['penjualan_items'][$itemKey]['amount'] += $amount;
            } else {
                // HPP items (Amount CR)
                $itemKey = $accountCode.'|||'.$accountName.'|||'.$description;
                if (! isset($groups[$groupName2]['hpp_items'][$itemKey])) {
                    $groups[$groupName2]['hpp_items'][$itemKey] = [
                        'account_code' => $accountCode,
                        'account_name' => $accountName,
                        'no_urut' => 2,
                        'amount' => 0,
                    ];
                }
                $groups[$groupName2]['hpp_items'][$itemKey]['amount'] += $amount;
            }
        }

        foreach ($groups as &$group) {
            $group['penjualan_items'] = array_values($group['penjualan_items']);
            $group['hpp_items'] = array_values($group['hpp_items']);

            usort($group['penjualan_items'], static fn (array $a, array $b): int => self::sortByUrutAndName($a, $b));
            usort($group['hpp_items'], static fn (array $a, array $b): int => self::sortByUrutAndName($a, $b));

            $totalSales = array_sum(array_map(static fn (array $i): float => $i['amount'], $group['penjualan_items']));
            $totalHpp = array_sum(array_map(static fn (array $i): float => $i['amount'], $group['hpp_items']));

            $group['total_sales'] = $totalSales;
            $group['total_hpp'] = $totalHpp;
            $group['amount'] = $totalSales - $totalHpp;
        }

        return $groups;
    }

    private function getNameGroupSortOrder(string $accountCode, string $accountName, string $description, array $row): int
    {
        $nameUpper = strtoupper($accountName);
        $descUpper = strtoupper($description);
        $voucherNumber = (string) ($row['Voucher Number'] ?? '');

        // NameGroupKayu prefixes — match persis formula Crystal
        if (str_contains($nameUpper, 'JABON')) {
            return 1;
        }
        if (str_contains($nameUpper, 'RAMBUNG')) {
            return 2;
        }
        if (str_contains($nameUpper, 'PULAI')) {
            return 3;
        }
        if (str_contains($nameUpper, 'KAYU LAT') || str_contains($nameUpper, 'SAWN TIMBER')) {
            return 4;
        }
        if ($accountCode === '111.400.202' && str_contains($descUpper, 'SALES')) {
            return 4;
        }
        if (str_contains($nameUpper, 'RETUR PENJ. KAYU LAT')) {
            return 5;
        }
        if (str_contains($nameUpper, 'HARGA POKOK') && str_contains($voucherNumber, 'SR-')) {
            return 5;
        }
        if (str_contains($nameUpper, 'ABU SEKAM') || str_contains($nameUpper, 'KAYU SEMPENGAN')) {
            return 6;
        }
        if ($accountCode === '111.400.101' && str_contains($descUpper, 'SALES')) {
            return 7;
        }
        if (in_array($accountCode, ['112.200.009', '111.400.903', '111.400.904', '111.400.905', '111.400.906'], true)
            && (str_contains($descUpper, 'SALES') || str_starts_with($descUpper, 'SALES'))) {
            return 20;
        }
        if ($accountCode === '111.400.404' && str_contains($descUpper, 'SALES')) {
            return 21;
        }
        if (str_contains($nameUpper, 'POTONGAN PEMBELIAN')) {
            return 22;
        }
        if ($accountCode === '111.400.407' && str_contains($descUpper, 'SALES 3.1.6.16.0071: TAL')) {
            return 23;
        }
        if (str_contains($descUpper, 'SALES 3.1.6.16.0071')) {
            return 23;
        }

        return 99;
    }

    private function mergePeriods(array $groupsB, array $groupsA): array
    {
        $allKeys = array_unique(array_merge(array_keys($groupsB), array_keys($groupsA)));

        $merged = [];

        foreach ($allKeys as $key) {
            $b = $groupsB[$key] ?? null;
            $a = $groupsA[$key] ?? null;

            $sortOrder = 99;
            if ($b !== null) {
                $sortOrder = $b['sort_order'];
            } elseif ($a !== null) {
                $sortOrder = $a['sort_order'];
            }

            $penjualanItems = $this->mergeItems(
                $b['penjualan_items'] ?? [],
                $a['penjualan_items'] ?? []
            );

            $hppItems = $this->mergeItems(
                $b['hpp_items'] ?? [],
                $a['hpp_items'] ?? []
            );

            $totalSalesB = $b['total_sales'] ?? 0;
            $totalSalesA = $a['total_sales'] ?? 0;
            $totalHppB = $b['total_hpp'] ?? 0;
            $totalHppA = $a['total_hpp'] ?? 0;

            $amountB = $totalSalesB - $totalHppB;
            $amountA = $totalSalesA - $totalHppA;

            $rasioB = $totalSalesB != 0 ? round(abs($amountB) / $totalSalesB * 100, 2) : 0;
            $rasioA = $totalSalesA != 0 ? round(abs($amountA) / $totalSalesA * 100, 2) : 0;

            $selisih = $this->computeSelisih($amountB, $amountA);

            $merged[] = [
                'name' => $key,
                'sort_order' => $sortOrder,
                'penjualan_items' => $penjualanItems,
                'hpp_items' => $hppItems,
                'total_sales_b' => $totalSalesB,
                'total_sales_a' => $totalSalesA,
                'amount_b' => $amountB,
                'amount_a' => $amountA,
                'rasio_b' => $rasioB,
                'rasio_a' => $rasioA,
                'selisih' => $selisih,
            ];
        }

        usort($merged, static fn (array $a, array $b): int => $a['sort_order'] <=> $b['sort_order']);

        return $merged;
    }

    private function mergeItems(array $itemsB, array $itemsA): array
    {
        $merged = [];

        foreach ($itemsB as $item) {
            $key = $item['account_name'];
            $merged[$key] = [
                'account_name' => $item['account_name'],
                'no_urut' => $item['no_urut'] ?? 1,
                'amount_b' => $item['amount'],
                'amount_a' => 0,
            ];
        }

        foreach ($itemsA as $item) {
            $key = $item['account_name'];
            if (isset($merged[$key])) {
                $merged[$key]['amount_a'] = $item['amount'];
            } else {
                $merged[$key] = [
                    'account_name' => $item['account_name'],
                    'no_urut' => $item['no_urut'] ?? 1,
                    'amount_b' => 0,
                    'amount_a' => $item['amount'],
                ];
            }
        }

        $merged = array_values($merged);
        usort($merged, static fn (array $a, array $b): int => self::sortByUrutAndName($a, $b));

        return $merged;
    }

    private static function sortByUrutAndName(array $a, array $b): int
    {
        $urut = (int) ($a['no_urut'] ?? 1) <=> (int) ($b['no_urut'] ?? 1);
        if ($urut !== 0) {
            return $urut;
        }

        return strcasecmp(
            (string) ($a['account_name'] ?? ''),
            (string) ($b['account_name'] ?? ''),
        );
    }

    private function computeSelisih(float $b, float $a): float
    {
        if (abs($a) < 0.01) {
            return $b >= 0 ? 100 : -100;
        }

        return round(($b - $a) / $a * 100, 2);
    }
}
