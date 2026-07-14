<?php

namespace App\Services\Ascends\Shared\Finance\ReceivableDetails;

use Carbon\Carbon;
use RuntimeException;
use XMLReader;

class PiutangSemuaReportService
{
    private const TITLE = 'Laporan Umur Piutang Semua';

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

        $detailItems = [];
        foreach ($allRows as $row) {
            $ageDays = max(0, (int) ($row['Age (Days)'] ?? 0));
            $balance = (float) ($row['Balance'] ?? 0);

            $detailItems[] = [
                'customer_name' => trim((string) ($row['Customer Name'] ?? '')),
                'item_ref' => trim((string) ($row['Item Ref'] ?? '')),
                'umur' => $ageDays,
                'bucket_001_044' => ($ageDays >= 0 && $ageDays <= 44) ? $balance : 0.0,
                'bucket_045_060' => ($ageDays >= 45 && $ageDays <= 60) ? $balance : 0.0,
                'bucket_061_090' => ($ageDays >= 61 && $ageDays <= 90) ? $balance : 0.0,
                'bucket_091_120' => ($ageDays >= 91 && $ageDays <= 120) ? $balance : 0.0,
                'bucket_over_120' => ($ageDays > 120) ? $balance : 0.0,
                'saldo' => $balance,
                'salesman_name' => trim((string) ($row['Sales Person Name'] ?? 'TANPA SALESMAN')),
            ];
        }

        usort($detailItems, function (array $a, array $b): int {
            return strcmp($a['customer_name'], $b['customer_name']);
        });

        $customerTotals = $this->calculateCustomerTotals($detailItems);

        $grand001_044 = (float) array_sum(array_column($customerTotals, 'total_001_044'));
        $grand045_060 = (float) array_sum(array_column($customerTotals, 'total_045_060'));
        $grand061_090 = (float) array_sum(array_column($customerTotals, 'total_061_090'));
        $grand091_120 = (float) array_sum(array_column($customerTotals, 'total_091_120'));
        $grandOver120 = (float) array_sum(array_column($customerTotals, 'total_over_120'));
        $grandSaldo = $grand001_044 + $grand045_060 + $grand061_090 + $grand091_120 + $grandOver120;

        $rasio = $this->calculateRasio([
            $grand001_044, $grand045_060, $grand061_090, $grand091_120, $grandOver120,
        ], $grandSaldo);

        $salesmanSummary = $this->calculateSalesmanSummary($detailItems);
        $grandS001_044 = (float) array_sum(array_column($salesmanSummary, 'total_001_044'));
        $grandS045_060 = (float) array_sum(array_column($salesmanSummary, 'total_045_060'));
        $grandS061_090 = (float) array_sum(array_column($salesmanSummary, 'total_061_090'));
        $grandS091_120 = (float) array_sum(array_column($salesmanSummary, 'total_091_120'));
        $grandSOver120 = (float) array_sum(array_column($salesmanSummary, 'total_over_120'));
        $grandSTotal = $grandS001_044 + $grandS045_060 + $grandS061_090 + $grandS091_120 + $grandSOver120;

        return [
            'title' => self::TITLE,
            'company' => '',
            'period_label' => $periodLabel,
            'detail_items' => $detailItems,
            'customer_totals' => $customerTotals,
            'grand_total_001_044' => $grand001_044,
            'grand_total_045_060' => $grand045_060,
            'grand_total_061_090' => $grand061_090,
            'grand_total_091_120' => $grand091_120,
            'grand_total_over_120' => $grandOver120,
            'grand_total_saldo' => $grandSaldo,
            'rasio' => $rasio,
            'salesman_summary' => $salesmanSummary,
            'grand_s_001_044' => $grandS001_044,
            'grand_s_045_060' => $grandS045_060,
            'grand_s_061_090' => $grandS061_090,
            'grand_s_091_120' => $grandS091_120,
            'grand_s_over_120' => $grandSOver120,
            'grand_s_total' => $grandSTotal,
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
                    'total_001_044' => 0.0,
                    'total_045_060' => 0.0,
                    'total_061_090' => 0.0,
                    'total_091_120' => 0.0,
                    'total_over_120' => 0.0,
                    'total_saldo' => 0.0,
                ];
                $order[] = $name;
            }

            $groups[$name]['total_001_044'] += $item['bucket_001_044'];
            $groups[$name]['total_045_060'] += $item['bucket_045_060'];
            $groups[$name]['total_061_090'] += $item['bucket_061_090'];
            $groups[$name]['total_091_120'] += $item['bucket_091_120'];
            $groups[$name]['total_over_120'] += $item['bucket_over_120'];
            $groups[$name]['total_saldo'] += $item['saldo'];
        }

        $result = [];
        foreach ($order as $name) {
            $g = $groups[$name];
            $g['total_001_044'] = round($g['total_001_044'], 2);
            $g['total_045_060'] = round($g['total_045_060'], 2);
            $g['total_061_090'] = round($g['total_061_090'], 2);
            $g['total_091_120'] = round($g['total_091_120'], 2);
            $g['total_over_120'] = round($g['total_over_120'], 2);
            $g['total_saldo'] = round($g['total_saldo'], 2);
            $result[] = $g;
        }

        return $result;
    }

    private function calculateRasio(array $bucketTotals, float $grandSaldo): array
    {
        $sumAging = 0.0;
        for ($i = 1; $i < 5; $i++) {
            $sumAging += abs($bucketTotals[$i] ?? 0);
        }

        $rasio = [];
        foreach ($bucketTotals as $i => $total) {
            if ($i === 0) {
                $rasio[] = null;
            } elseif ($sumAging > 0) {
                $pct = (abs($total) / $sumAging) * 100;
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
                    'total_001_044' => 0.0,
                    'total_045_060' => 0.0,
                    'total_061_090' => 0.0,
                    'total_091_120' => 0.0,
                    'total_over_120' => 0.0,
                    'total_all' => 0.0,
                    '_customers' => [],
                ];
                $order[] = $name;
            }

            $groups[$name]['total_001_044'] += $item['bucket_001_044'];
            $groups[$name]['total_045_060'] += $item['bucket_045_060'];
            $groups[$name]['total_061_090'] += $item['bucket_061_090'];
            $groups[$name]['total_091_120'] += $item['bucket_091_120'];
            $groups[$name]['total_over_120'] += $item['bucket_over_120'];
            $groups[$name]['total_all'] += $item['saldo'];
            $groups[$name]['_customers'][$item['customer_name']] = true;
        }

        $result = [];
        foreach ($order as $name) {
            $g = $groups[$name];
            $g['total_cust'] = count($g['_customers']);
            unset($g['_customers']);
            $g['total_001_044'] = round($g['total_001_044'], 2);
            $g['total_045_060'] = round($g['total_045_060'], 2);
            $g['total_061_090'] = round($g['total_061_090'], 2);
            $g['total_091_120'] = round($g['total_091_120'], 2);
            $g['total_over_120'] = round($g['total_over_120'], 2);
            $g['total_all'] = round($g['total_all'], 2);
            $result[] = $g;
        }

        return $result;
    }
}
