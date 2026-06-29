<?php

namespace App\Services\Ascends\Shared\Hrm;

use Carbon\Carbon;
use RuntimeException;
use XMLReader;

class PendapatanLainLainReportService
{
    private const TITLE = 'Laporan Pendapatan Lain-Lain';

    /**
     * @return array<string, mixed>
     */
    public function buildReportDataFromXml(string $xmlContents, string $sourceLabel = 'request xml payload'): array
    {
        $rows = $this->parseXml($xmlContents, $sourceLabel);
        $rows = array_values(array_filter(
            $rows,
            static fn (array $row): bool => strtolower(trim((string) ($row['approved_by'] ?? ''))) !== 'employeeother'
        ));

        usort($rows, static function (array $left, array $right): int {
            return [
                strtolower(preg_replace('/\s+/', ' ', trim((string) ($left['full_name'] ?? ''))) ?? ''),
                (string) ($left['date'] ?? ''),
                (string) ($left['remarks'] ?? ''),
            ] <=> [
                strtolower(preg_replace('/\s+/', ' ', trim((string) ($right['full_name'] ?? ''))) ?? ''),
                (string) ($right['date'] ?? ''),
                (string) ($right['remarks'] ?? ''),
            ];
        });

        $totalAmount = array_sum(array_map(static fn (array $row): float => (float) ($row['amount'] ?? 0), $rows));

        return [
            'title' => self::TITLE,
            'section_title' => 'Penambahan',
            'source_file' => $sourceLabel,
            'printed_at' => Carbon::now()->locale('id')->translatedFormat('d F Y H:i'),
            'printed_by' => '',
            'headers' => ['Nama Lengkap', 'Tanggal', 'Keterangan', 'Disetujui Oleh', 'Jumlah'],
            'rows' => array_map(static function (array $row): array {
                return [
                    'Nama Lengkap' => preg_replace('/\s+/', ' ', trim((string) ($row['full_name'] ?? ''))) ?? '',
                    'Tanggal' => self::formatDate((string) ($row['date'] ?? '')),
                    'Keterangan' => (string) ($row['remarks'] ?? ''),
                    'Disetujui Oleh' => (string) ($row['approved_by'] ?? ''),
                    'Jumlah' => self::formatNumber((float) ($row['amount'] ?? 0)),
                ];
            }, $rows),
            'total_rows' => count($rows),
            'total_amount' => self::formatNumber($totalAmount),
            'period' => [
                'label' => self::resolvePeriodLabel($rows),
            ],
        ];
    }

    /**
     * @return array<int, array<string, string|float>>
     */
    private function parseXml(string $xmlContents, string $sourceLabel): array
    {
        if (trim($xmlContents) === '') {
            throw new RuntimeException('XML Other Income Deduction kosong.');
        }

        $reader = new XMLReader;
        if (! @$reader->XML($xmlContents, null, LIBXML_NOCDATA | LIBXML_NONET)) {
            throw new RuntimeException("XML Other Income Deduction tidak valid: {$sourceLabel}");
        }

        $rows = [];
        while ($reader->read()) {
            if ($reader->nodeType !== XMLReader::ELEMENT || strtolower($reader->name) !== 'income') {
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

            $rows[] = [
                'full_name' => trim((string) ($node->{'Full_x0020_Name'} ?? '')),
                'date' => trim((string) ($node->Date ?? '')),
                'remarks' => trim((string) ($node->Remarks ?? '')),
                'approved_by' => trim((string) ($node->{'Approved_x0020_By'} ?? '')),
                'amount' => (float) trim((string) ($node->Amount ?? '0')),
                'payroll_start' => trim((string) ($node->{'Payroll_x0020_Period_x0020__x0028_Start_x0029_'} ?? '')),
            ];
        }

        $reader->close();

        if ($rows === []) {
            throw new RuntimeException('Data income tidak ditemukan di XML.');
        }

        return $rows;
    }

    private static function resolvePeriodLabel(array $rows): string
    {
        foreach ($rows as $row) {
            $period = trim((string) ($row['payroll_start'] ?? ''));
            if ($period === '') {
                continue;
            }

            $date = Carbon::createFromFormat('M-Y', strtoupper($period));
            if ($date !== false) {
                return 'Per : '.$date->addMonth()->locale('id')->translatedFormat('F-Y');
            }
        }

        $dates = array_filter(array_map(static fn (array $row): ?Carbon => self::parseDate((string) ($row['date'] ?? '')), $rows));
        if ($dates === []) {
            return '';
        }

        return 'Per : '.min($dates)->copy()->addMonth()->locale('id')->translatedFormat('F-Y');
    }

    private static function formatDate(string $value): string
    {
        $date = self::parseDate($value);

        return $date?->locale('id')->translatedFormat('d-M-y') ?? '';
    }

    private static function parseDate(string $value): ?Carbon
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    private static function formatNumber(float $value): string
    {
        return number_format($value, 0, '.', ',');
    }
}
