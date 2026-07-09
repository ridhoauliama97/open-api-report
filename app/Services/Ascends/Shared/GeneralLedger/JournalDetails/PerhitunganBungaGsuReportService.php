<?php

namespace App\Services\Ascends\Shared\GeneralLedger\JournalDetails;

use Carbon\Carbon;
use RuntimeException;
use Throwable;
use XMLReader;

class PerhitunganBungaGsuReportService
{
    private const TITLE = 'Laporan Piutang & Perhitungan Bunga GSU';

    private const TARGET_ACCOUNT_PREFIXES = [
        '111.200.101',
        '112.100.112',
    ];

    private const INTEREST_RATE = 0.01; // 1% per bulan

    public function buildReportDataFromXml(string $xmlContents, string $sourceLabel = 'request xml payload', array $filters = []): array
    {
        $allRows = $this->parseXml($xmlContents, $sourceLabel);

        if ($allRows === []) {
            throw new RuntimeException('Data jurnal tidak ditemukan pada XML.');
        }

        $company = trim((string) ($filters['company'] ?? ''));
        $startDate = trim((string) ($filters['Date.StartDate'] ?? $filters['Date_StartDate'] ?? $filters['StartDate'] ?? $filters['start_date'] ?? ''));
        $endDate = trim((string) ($filters['Date.EndDate'] ?? $filters['Date_EndDate'] ?? $filters['EndDate'] ?? $filters['end_date'] ?? ''));
        $saldoAwal = (float) ($filters['SaldoAwal'] ?? $filters['saldo_awal'] ?? 0);

        $filtered = $this->applyFilters($allRows, $startDate, $endDate);

        if ($filtered === []) {
            throw new RuntimeException('Tidak ada data yang memenuhi kriteria.');
        }

        $sorted = $this->sortByDate($filtered);

        $piutangRows = $this->buildPiutangRows($sorted, $saldoAwal);

        $bungaRows = $this->buildBungaRows($piutangRows, $startDate);

        $totalDebet = array_sum(array_map(static fn (array $r): float => $r['debet'], $piutangRows));
        $totalKredit = array_sum(array_map(static fn (array $r): float => $r['kredit'], $piutangRows));
        $totalBunga = array_sum(array_map(static fn (array $r): float => $r['bunga'], $bungaRows));
        $totalHari = array_sum(array_map(static fn (array $r): int => $r['hari'], $bungaRows));

        $period = $this->resolvePeriod($startDate, $endDate);
        $periodLabel = 'Piutang GSU Bulan '.Carbon::parse($startDate)->locale('id')->isoFormat('MMMM YYYY');
        $bungaLabel = 'Perhitungan Bunga GSU Bulan '.Carbon::parse($startDate)->locale('id')->isoFormat('MMMM YYYY');
        $monthName = Carbon::parse($startDate)->locale('id')->isoFormat('MMMM');
        $yearName = Carbon::parse($startDate)->format('Y');

        return [
            'title' => self::TITLE,
            'company' => $company,
            'period_label' => $periodLabel,
            'period_range' => $period['label'],
            'bunga_label' => $bungaLabel,
            'month_name' => $monthName,
            'year_name' => $yearName,
            'saldo_awal' => $saldoAwal,
            'piutang' => $piutangRows,
            'total_debet' => $totalDebet,
            'total_kredit' => $totalKredit,
            'bunga' => $bungaRows,
            'total_bunga' => $totalBunga,
            'total_hari' => $totalHari,
            'printed_by' => '',
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
        $key = str_replace('_x002F_', '/', $key);

        return str_replace('_x003A_', ':', $key);
    }

    private function applyFilters(array $rows, string $startDate, string $endDate): array
    {
        $start = null;
        $end = null;

        if ($startDate !== '') {
            try {
                $start = Carbon::parse($startDate)->startOfDay();
            } catch (Throwable) {
            }
        }

        if ($endDate !== '') {
            try {
                $end = Carbon::parse($endDate)->endOfDay();
            } catch (Throwable) {
            }
        }

        $result = [];

        foreach ($rows as $row) {
            $accountCode = (string) ($row['Account Code'] ?? '');

            if (! $this->accountFilter($accountCode)) {
                continue;
            }

            if ($start !== null || $end !== null) {
                $voucherDate = trim((string) ($row['Voucher Date'] ?? ''));
                if ($voucherDate === '') {
                    continue;
                }

                try {
                    $vd = Carbon::parse($voucherDate);

                    if ($start !== null && $vd->lessThan($start)) {
                        continue;
                    }

                    if ($end !== null && $vd->greaterThan($end)) {
                        continue;
                    }
                } catch (Throwable) {
                    continue;
                }
            }

            $result[] = $row;
        }

        return $result;
    }

    private function accountFilter(string $accountCode): bool
    {
        foreach (self::TARGET_ACCOUNT_PREFIXES as $prefix) {
            if (str_starts_with($accountCode, $prefix)) {
                return true;
            }
        }

        return false;
    }

    private function sortByDate(array $rows): array
    {
        usort($rows, function (array $a, array $b): int {
            $dateA = (string) ($a['Voucher Date'] ?? '');
            $dateB = (string) ($b['Voucher Date'] ?? '');

            $cmp = strcmp($dateA, $dateB);
            if ($cmp !== 0) {
                return $cmp;
            }

            $numA = (string) ($a['Voucher Number'] ?? '');
            $numB = (string) ($b['Voucher Number'] ?? '');

            return strcmp($numA, $numB);
        });

        return $rows;
    }

    private function buildPiutangRows(array $rows, float $saldoAwal): array
    {
        $piutang = [];
        $runningBalance = $saldoAwal;

        foreach ($rows as $row) {
            $amountDb = (float) ($row['Amount DB'] ?? 0);
            $amountCr = (float) ($row['Amount CR'] ?? 0);

            $runningBalance += $amountDb - $amountCr;

            $voucherDate = (string) ($row['Voucher Date'] ?? '');
            $formattedDate = '';
            if ($voucherDate !== '') {
                try {
                    $formattedDate = Carbon::parse($voucherDate)->locale('id')->isoFormat('DD-MMM-YY');
                } catch (Throwable) {
                    $formattedDate = $voucherDate;
                }
            }

            $description = (string) ($row['Description'] ?? '');
            $voucherRemarks = (string) ($row['Voucher Remarks'] ?? '');
            $remark = $description !== '' ? $description : $voucherRemarks;

            $piutang[] = [
                'date' => $formattedDate,
                'voucher_date_raw' => $voucherDate,
                'voucher_number' => (string) ($row['Voucher Number'] ?? ''),
                'remark' => $remark,
                'debet' => $amountDb,
                'kredit' => $amountCr,
                'saldo' => $runningBalance,
            ];
        }

        return $piutang;
    }

    private function buildBungaRows(array $piutangRows, string $startDate): array
    {
        if ($piutangRows === []) {
            return [];
        }

        try {
            $periodStart = Carbon::parse($startDate)->startOfDay();
        } catch (Throwable) {
            return [];
        }

        $bungaRows = [];

        // Group piutang rows by date (unique transaction dates)
        $groupedByDate = [];
        foreach ($piutangRows as $row) {
            $dateKey = $row['voucher_date_raw'];
            if (! isset($groupedByDate[$dateKey])) {
                $groupedByDate[$dateKey] = [
                    'date_raw' => $row['voucher_date_raw'],
                    'last_saldo' => 0.0,
                ];
            }
            $groupedByDate[$dateKey]['last_saldo'] = $row['saldo'];
        }

        $groupedByDate = array_values($groupedByDate);

        $previousDate = null;

        foreach ($groupedByDate as $idx => $group) {
            try {
                $currentDate = Carbon::parse($group['date_raw'])->startOfDay();
            } catch (Throwable) {
                continue;
            }

            if ($previousDate === null) {
                // First transaction date: days = day of month
                $hari = (int) $currentDate->format('d');
            } else {
                $hari = (int) $previousDate->diffInDays($currentDate);
            }

            $saldo = $group['last_saldo'];
            $bunga = $saldo * ($hari / 30) * self::INTEREST_RATE;

            $bungaRows[] = [
                'date' => $currentDate->locale('id')->isoFormat('DD-MMM-YY'),
                'hari' => $hari,
                'saldo' => $saldo,
                'bunga' => $bunga,
            ];

            $previousDate = $currentDate;
        }

        return $bungaRows;
    }

    private function resolvePeriod(string $startDate, string $endDate): array
    {
        $startLabel = '';
        $endLabel = '';

        if ($startDate !== '') {
            try {
                $startLabel = Carbon::parse($startDate)->locale('id')->isoFormat('DD-MMM-YY');
            } catch (Throwable) {
                $startLabel = $startDate;
            }
        }

        if ($endDate !== '') {
            try {
                $endLabel = Carbon::parse($endDate)->locale('id')->isoFormat('DD-MMM-YY');
            } catch (Throwable) {
                $endLabel = $endDate;
            }
        }

        return [
            'start' => $startLabel,
            'end' => $endLabel,
            'label' => '('.$startLabel.' - '.$endLabel.')',
        ];
    }
}
