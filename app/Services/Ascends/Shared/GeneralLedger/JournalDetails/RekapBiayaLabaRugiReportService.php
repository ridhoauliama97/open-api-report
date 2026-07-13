<?php

namespace App\Services\Ascends\Shared\GeneralLedger\JournalDetails;

use Carbon\Carbon;
use RuntimeException;
use Throwable;
use XMLReader;

class RekapBiayaLabaRugiReportService
{
    private const TITLE = 'Laporan Rekap Biaya Laba Rugi';

    private const PREFIX_TO_AKM = [
        '411.000' => 'PENJUALAN',
        '412.000' => 'PENJUALAN',
        '451.000' => 'RETUR PENJUALAN',
        '452.000' => 'RETUR PENJUALAN',
        '431.000' => 'POTONGAN PENJUALAN',
        '516.000' => 'HPP PENJUALAN',
        '621.000' => 'PEMBELIAN BARANG DAGANG',
        '641.000' => 'BEBAN PEMBELIAN',
        '642.000' => 'BEBAN PEMBELIAN',
        '711.000' => 'BEBAN PENJUALAN',
        '721.000' => 'BEBAN UMUM',
        '800.000' => 'PENDAPATAN LAINNYA (PL)',
        '900.000' => 'BEBAN LAINNYA (BL)',
        '421.001' => 'PENDAPATAN JASA TENAGA AHLI',
        '421.002' => 'PENDAPATAN JASA SEWA',
        '421.003' => 'PENDAPATAN JASA PRODUKSI',
        '421.004' => 'PENDAPATAN JASA PEMBELIAN',
    ];

    private const BEBAN_AKM = ['BEBAN PENJUALAN', 'BEBAN UMUM'];

    public function buildReportDataFromXml(string $xmlContents, string $sourceLabel = 'request xml payload', array $filters = []): array
    {
        $allRows = $this->parseXml($xmlContents, $sourceLabel);

        if ($allRows === []) {
            throw new RuntimeException('Data jurnal tidak ditemukan pada XML.');
        }

        $rawStartDate = trim((string) ($filters['Date.StartDate'] ?? $filters['Date_StartDate'] ?? $filters['StartDate'] ?? $filters['start_date'] ?? ''));
        $rawEndDate = trim((string) ($filters['Date.EndDate'] ?? $filters['Date_EndDate'] ?? $filters['EndDate'] ?? $filters['end_date'] ?? ''));

        if ($rawStartDate === '' && $rawEndDate === '') {
            throw new RuntimeException('Parameter Date.StartDate dan Date.EndDate wajib dikirim.');
        }

        $startDate = Carbon::parse($rawStartDate);
        $endDate = Carbon::parse($rawEndDate);

        $filtered = $this->filterRows($allRows, $startDate, $endDate);

        if ($filtered === []) {
            throw new RuntimeException('Tidak ada data yang memenuhi kriteria.');
        }

        $grouped = $this->groupByAccount($filtered);

        $grandTotal = array_sum(array_map(static fn (array $g): float => $g['total'], $grouped));

        return [
            'title' => self::TITLE,
            'company' => '',
            'period_label' => 'Dari '.$startDate->locale('id')->isoFormat('DD-MMM-YY').' s/d '.$endDate->locale('id')->isoFormat('DD-MMM-YY'),
            'printed_at' => Carbon::now()->locale('id')->translatedFormat('d F Y H:i'),
            'printed_by' => '',
            'rows' => $grouped,
            'grand_total' => $grandTotal,
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

    private function filterRows(array $rows, Carbon $startDate, Carbon $endDate): array
    {
        $result = [];

        foreach ($rows as $row) {
            $accountCode = (string) ($row['Account Code'] ?? '');

            $akm = $this->resolveAkm($accountCode);

            if (! in_array($akm, self::BEBAN_AKM, true)) {
                continue;
            }

            $voucherDate = trim((string) ($row['Voucher Date'] ?? ''));
            if ($voucherDate === '') {
                continue;
            }

            try {
                $vd = Carbon::parse($voucherDate);
            } catch (Throwable) {
                continue;
            }

            if ($vd->lessThan($startDate) || $vd->greaterThan($endDate)) {
                continue;
            }

            $result[] = $row;
        }

        return $result;
    }

    private function resolveAkm(string $accountCode): string
    {
        if ($accountCode === '711.000.091') {
            return 'BEBAN UMUM';
        }

        $prefix = substr($accountCode, 0, 7);

        return self::PREFIX_TO_AKM[$prefix] ?? 'A';
    }

    private function groupByAccount(array $rows): array
    {
        $grouped = [];

        foreach ($rows as $row) {
            $code = (string) ($row['Account Code'] ?? '');
            $name = (string) ($row['Account Name'] ?? '');
            $amountDb = (float) ($row['Amount DB'] ?? 0);
            $amountCr = (float) ($row['Amount CR'] ?? 0);

            $raw = $amountCr - $amountDb;
            $amount = abs($raw);

            if ($amount < 0.01) {
                continue;
            }

            if (! isset($grouped[$code])) {
                $grouped[$code] = [
                    'account_code' => $code,
                    'account_name' => $name,
                    'total' => 0,
                ];
            }

            $grouped[$code]['total'] += $amount;
        }

        $values = array_values($grouped);

        usort($values, static fn (array $a, array $b): int => strcmp($a['account_code'], $b['account_code']));

        $index = 1;
        foreach ($values as &$v) {
            $v['no'] = $index++;
        }

        return $values;
    }
}
