<?php

namespace App\Services\Ascends\Shared\Finance\ReceivableSummary;

use Carbon\Carbon;
use RuntimeException;
use Throwable;
use XMLReader;

class UmurPiutangRuReportService
{
    private const TITLE = 'Laporan Umur Piutang Dagang';

    private const BUCKET_FIELDS = [
        '00-04 days',
        '05-08 days',
        '09-12 days',
        '13-16 days',
        '17-20 days',
        '21-24 days',
        '25-28 days',
        'Over 28 days',
    ];

    public function buildReportDataFromXml(string $xmlContents, string $sourceLabel = 'request xml payload', array $filters = []): array
    {
        if (trim($xmlContents) === '') {
            throw new RuntimeException('Data XML kosong.');
        }

        $rows = $this->parseXml($xmlContents, $sourceLabel);

        if ($rows === []) {
            throw new RuntimeException('Data tidak ditemukan pada XML.');
        }

        usort($rows, static fn(array $a, array $b): int => strcasecmp($a['customer_name'], $b['customer_name']));

        $grandTotals = $this->calculateGrandTotals($rows);

        return [
            'title' => self::TITLE,
            'period_label' => $this->formatPeriodLabel($filters),
            'printed_by' => '',
            'rows' => $rows,
            'grand_totals' => $grandTotals,
        ];
    }

    private function parseXml(string $xmlContents, string $sourceLabel): array
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

            $row = [];
            foreach ($node->children() as $key => $child) {
                $cleanKey = $this->cleanXmlKey((string) $key);
                $row[$cleanKey] = trim((string) $child);
            }

            $customerName = $row['Customer Name'] ?? '';
            if ($customerName === '') {
                continue;
            }

            $buckets = [];
            $totalAkhir = 0.0;
            foreach (self::BUCKET_FIELDS as $bucketField) {
                $value = (float) ($row[$bucketField] ?? 0);
                $buckets[$bucketField] = $value;
                $totalAkhir += $value;
            }

            $rows[] = [
                'customer_name' => $customerName,
                'buckets' => $buckets,
                'total_akhir' => $totalAkhir,
            ];
        }

        $reader->close();

        return $rows;
    }

    private function cleanXmlKey(string $key): string
    {
        $key = preg_replace('/_x0030_/', '0', $key);
        $key = preg_replace('/_x0031_/', '1', $key);
        $key = preg_replace('/_x0032_/', '2', $key);
        $key = str_replace('_x0020_', ' ', $key);
        $key = str_replace('_x0028_', '(', $key);
        $key = str_replace('_x0029_', ')', $key);
        $key = str_replace('_x002F_', '/', $key);

        return $key;
    }

    private function calculateGrandTotals(array $rows): array
    {
        $totals = [];
        foreach (self::BUCKET_FIELDS as $bucketField) {
            $totals[$bucketField] = 0.0;
        }
        $totals['total_akhir'] = 0.0;

        foreach ($rows as $row) {
            foreach (self::BUCKET_FIELDS as $bucketField) {
                $totals[$bucketField] += $row['buckets'][$bucketField];
            }
            $totals['total_akhir'] += $row['total_akhir'];
        }

        return $totals;
    }

    private function formatPeriodLabel(array $filters): string
    {
        $start = self::parseDate($filters['start_date'] ?? '');
        $end = self::parseDate($filters['end_date'] ?? '');

        if ($start === null && $end === null) {
            return '';
        }

        $start ??= $end?->copy();
        $end ??= $start?->copy();

        if ($start === null || $end === null) {
            return '';
        }

        return 'Dari ' . $start->locale('id')->translatedFormat('d-M-y') . ' s/d ' . $end->locale('id')->translatedFormat('d-M-y');
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
}
