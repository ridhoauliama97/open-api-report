<?php

namespace App\Services\Ascends\Shared\Finance\ReceivableDetails;

use Carbon\Carbon;
use RuntimeException;
use XMLReader;

class PiutangTakTertagih90HariReportService
{
    private const TITLE = 'Laporan Piutang Tak Tertagih Di Atas 90 Hari';

    public function buildReportDataFromXml(string $xmlContents, string $sourceLabel = 'request xml payload', array $filters = []): array
    {
        $allRows = $this->parseXml($xmlContents, $sourceLabel);

        if ($allRows === []) {
            throw new RuntimeException('Data tidak ditemukan pada XML.');
        }

        $perDate = trim((string) ($filters['PerDate'] ?? ''));
        $periodLabel = $perDate !== ''
            ? 'Per Tanggal : '.Carbon::parse($perDate)->locale('id')->isoFormat('DD-MMM-YY')
            : '';

        $filtered = $this->applySelectionFormula($allRows);

        if ($filtered === []) {
            throw new RuntimeException('Tidak ada data dengan umur piutang diatas 90 hari.');
        }

        $balances = array_map(function (array $row): float {
            return (float) ($row['Balance (Local)'] ?? 0);
        }, $filtered);
        $totalBalance = (float) array_sum($balances);

        $items = [];
        foreach ($filtered as $row) {
            $balanceLocal = (float) ($row['Balance (Local)'] ?? 0);
            $persen = ($totalBalance != 0) ? (abs($balanceLocal) / abs($totalBalance)) * 100 : 0;

            $items[] = [
                'customer_name' => trim((string) ($row['Customer Name'] ?? '')),
                'umur' => (int) ($row['Age (Days)'] ?? 0),
                'status' => trim((string) ($row['Age'] ?? '')),
                'persen' => round($persen, 1),
                'balance_local' => $balanceLocal,
            ];
        }

        usort($items, function (array $a, array $b): int {
            $cmp = strcmp($a['customer_name'], $b['customer_name']);
            if ($cmp !== 0) {
                return $cmp;
            }

            return $b['umur'] - $a['umur'];
        });

        return [
            'title' => self::TITLE,
            'company' => '',
            'period_label' => $periodLabel,
            'items' => $items,
            'total_balance' => round($totalBalance, 2),
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
            if ($reader->nodeType !== XMLReader::ELEMENT || $reader->name !== 'ar') {
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

            if (($row['Customer Name'] ?? '') !== '') {
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

    private function applySelectionFormula(array $rows): array
    {
        return array_values(array_filter($rows, function (array $row): bool {
            return (int) ($row['Age (Days)'] ?? 0) > 90;
        }));
    }
}
