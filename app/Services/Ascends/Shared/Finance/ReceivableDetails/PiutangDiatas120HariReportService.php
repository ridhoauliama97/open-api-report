<?php

namespace App\Services\Ascends\Shared\Finance\ReceivableDetails;

use Carbon\Carbon;
use RuntimeException;
use XMLReader;

class PiutangDiatas120HariReportService
{
    private const TITLE = 'Laporan Umur Piutang Diatas 120 Hari';

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
            throw new RuntimeException('Tidak ada data dengan umur piutang diatas 120 hari.');
        }

        $detailItems = [];
        foreach ($filtered as $row) {
            $ageDays = (int) ($row['Age (Days)'] ?? 0);
            $balance = (float) ($row['Balance'] ?? 0);

            $detailItems[] = [
                'customer_name' => trim((string) ($row['Customer Name'] ?? '')),
                'item_ref' => trim((string) ($row['Item Ref'] ?? '')),
                'umur' => $ageDays,
                'bucket_120_240' => ($ageDays >= 120 && $ageDays <= 240) ? $balance : 0.0,
                'bucket_241_360' => ($ageDays >= 241 && $ageDays <= 360) ? $balance : 0.0,
                'bucket_361_480' => ($ageDays >= 361 && $ageDays <= 480) ? $balance : 0.0,
                'bucket_481_600' => ($ageDays >= 481 && $ageDays <= 600) ? $balance : 0.0,
                'bucket_over_600' => ($ageDays > 600) ? $balance : 0.0,
                'saldo' => $balance,
                'salesman_name' => trim((string) ($row['Sales Person Name'] ?? 'TANPA SALESMAN')),
            ];
        }

        usort($detailItems, function (array $a, array $b): int {
            return strcmp($a['customer_name'], $b['customer_name']);
        });

        $customerTotals = $this->calculateCustomerTotals($detailItems);

        $grand120_240 = (float) array_sum(array_column($customerTotals, 'total_120_240'));
        $grand241_360 = (float) array_sum(array_column($customerTotals, 'total_241_360'));
        $grand361_480 = (float) array_sum(array_column($customerTotals, 'total_361_480'));
        $grand481_600 = (float) array_sum(array_column($customerTotals, 'total_481_600'));
        $grandOver600 = (float) array_sum(array_column($customerTotals, 'total_over_600'));
        $grandSaldo = $grand120_240 + $grand241_360 + $grand361_480 + $grand481_600 + $grandOver600;

        $rasio = $this->calculateRasio([
            $grand120_240, $grand241_360, $grand361_480, $grand481_600, $grandOver600,
        ], $grandSaldo);

        $salesmanSummary = $this->calculateSalesmanSummary($detailItems);
        usort($salesmanSummary, static fn (array $a, array $b): int => strcasecmp($a['salesman_name'], $b['salesman_name']));
        $grandSalesman120_240 = (float) array_sum(array_column($salesmanSummary, 'total_120_240'));
        $grandSalesman241_360 = (float) array_sum(array_column($salesmanSummary, 'total_241_360'));
        $grandSalesman361_480 = (float) array_sum(array_column($salesmanSummary, 'total_361_480'));
        $grandSalesman481_600 = (float) array_sum(array_column($salesmanSummary, 'total_481_600'));
        $grandSalesmanOver600 = (float) array_sum(array_column($salesmanSummary, 'total_over_600'));
        $grandSalesmanTotal = $grandSalesman120_240 + $grandSalesman241_360 + $grandSalesman361_480 + $grandSalesman481_600 + $grandSalesmanOver600;

        return [
            'title' => self::TITLE,
            'company' => '',
            'period_label' => $periodLabel,
            'detail_items' => $detailItems,
            'customer_totals' => $customerTotals,
            'grand_total_120_240' => $grand120_240,
            'grand_total_241_360' => $grand241_360,
            'grand_total_361_480' => $grand361_480,
            'grand_total_481_600' => $grand481_600,
            'grand_total_over_600' => $grandOver600,
            'grand_total_saldo' => $grandSaldo,
            'rasio' => $rasio,
            'salesman_summary' => $salesmanSummary,
            'grand_salesman_120_240' => $grandSalesman120_240,
            'grand_salesman_241_360' => $grandSalesman241_360,
            'grand_salesman_361_480' => $grandSalesman361_480,
            'grand_salesman_481_600' => $grandSalesman481_600,
            'grand_salesman_over_600' => $grandSalesmanOver600,
            'grand_salesman_total' => $grandSalesmanTotal,
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
            return (int) ($row['Age (Days)'] ?? 0) > 120;
        }));
    }

    private function calculateCustomerTotals(array $items): array
    {
        $groups = [];
        $order = [];

        foreach ($items as $item) {
            $name = $item['customer_name'];
            if ($name === '') {
                $name = 'TANPA NAMA';
            }

            if (! isset($groups[$name])) {
                $groups[$name] = [
                    'customer_name' => $name,
                    'total_120_240' => 0.0,
                    'total_241_360' => 0.0,
                    'total_361_480' => 0.0,
                    'total_481_600' => 0.0,
                    'total_over_600' => 0.0,
                    'total_saldo' => 0.0,
                ];
                $order[] = $name;
            }

            $groups[$name]['total_120_240'] += $item['bucket_120_240'];
            $groups[$name]['total_241_360'] += $item['bucket_241_360'];
            $groups[$name]['total_361_480'] += $item['bucket_361_480'];
            $groups[$name]['total_481_600'] += $item['bucket_481_600'];
            $groups[$name]['total_over_600'] += $item['bucket_over_600'];
            $groups[$name]['total_saldo'] += $item['saldo'];
        }

        $result = [];
        foreach ($order as $name) {
            $g = $groups[$name];
            $g['total_120_240'] = round($g['total_120_240'], 2);
            $g['total_241_360'] = round($g['total_241_360'], 2);
            $g['total_361_480'] = round($g['total_361_480'], 2);
            $g['total_481_600'] = round($g['total_481_600'], 2);
            $g['total_over_600'] = round($g['total_over_600'], 2);
            $g['total_saldo'] = round($g['total_saldo'], 2);
            $result[] = $g;
        }

        return $result;
    }

    private function calculateRasio(array $bucketTotals, float $grandSaldo): array
    {
        $rasio = [];
        foreach ($bucketTotals as $total) {
            if ($total < 0 && abs($grandSaldo) > 0) {
                $pct = (abs($total) / abs($grandSaldo)) * 100;
                $rasio[] = round($pct, 1);
            } else {
                $rasio[] = null;
            }
        }

        return $rasio;
    }

    private function calculateSalesmanSummary(array $items): array
    {
        $groups = [];
        $order = [];

        foreach ($items as $item) {
            $name = $item['salesman_name'];
            if ($name === '') {
                $name = 'TANPA SALESMAN';
            }

            if (! isset($groups[$name])) {
                $groups[$name] = [
                    'salesman_name' => $name,
                    'total_cust' => 0,
                    'total_120_240' => 0.0,
                    'total_241_360' => 0.0,
                    'total_361_480' => 0.0,
                    'total_481_600' => 0.0,
                    'total_over_600' => 0.0,
                    'total_all' => 0.0,
                    '_customers' => [],
                ];
                $order[] = $name;
            }

            $groups[$name]['total_120_240'] += $item['bucket_120_240'];
            $groups[$name]['total_241_360'] += $item['bucket_241_360'];
            $groups[$name]['total_361_480'] += $item['bucket_361_480'];
            $groups[$name]['total_481_600'] += $item['bucket_481_600'];
            $groups[$name]['total_over_600'] += $item['bucket_over_600'];
            $groups[$name]['total_all'] += $item['saldo'];
            $groups[$name]['_customers'][$item['customer_name']] = true;
        }

        $result = [];
        foreach ($order as $name) {
            $g = $groups[$name];
            $g['total_cust'] = count($g['_customers']);
            unset($g['_customers']);
            $g['total_120_240'] = round($g['total_120_240'], 2);
            $g['total_241_360'] = round($g['total_241_360'], 2);
            $g['total_361_480'] = round($g['total_361_480'], 2);
            $g['total_481_600'] = round($g['total_481_600'], 2);
            $g['total_over_600'] = round($g['total_over_600'], 2);
            $g['total_all'] = round($g['total_all'], 2);
            $result[] = $g;
        }

        return $result;
    }
}
