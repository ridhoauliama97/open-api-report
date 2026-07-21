<?php

namespace App\Services\Ascends\Shared\Finance\ReceivableDetails;

use Carbon\Carbon;
use RuntimeException;
use XMLReader;

class PiutangDiatas45HariReportService
{
    private const TITLE = 'Laporan Umur Piutang Diatas 45 Hari';

    public function buildReportDataFromXml(string $xmlContents, string $sourceLabel = 'request xml payload', array $filters = []): array
    {
        $allRows = $this->parseXml($xmlContents, $sourceLabel);

        if ($allRows === []) {
            throw new RuntimeException('Data tidak ditemukan pada XML.');
        }

        $perDate = trim((string) ($filters['PerDate'] ?? ''));
        $periodLabel = $perDate !== ''
            ? 'Per Tgl : '.Carbon::parse($perDate)->locale('id')->isoFormat('DD-MMM-YY')
            : '';

        $filtered = $this->applySelectionFormula($allRows);

        if ($filtered === []) {
            throw new RuntimeException('Tidak ada data dengan umur piutang diatas 45 hari.');
        }

        $salesmenGroups = $this->groupBySalesman($filtered);

        usort($salesmenGroups, static fn (array $a, array $b): int => strcasecmp($a['salesman_name'], $b['salesman_name']));

        $grand045_060 = 0.0;
        $grand061_090 = 0.0;
        $grand091_120 = 0.0;
        $grandOver120 = 0.0;

        foreach ($salesmenGroups as &$salesmanGroup) {
            $salesmanGroup['customer_groups'] = $this->groupByCustomer($salesmanGroup['items']);
            unset($salesmanGroup['items']);

            usort($salesmanGroup['customer_groups'], static fn (array $a, array $b): int => strcasecmp($a['customer_name'], $b['customer_name']));

            $salesman045_060 = 0.0;
            $salesman061_090 = 0.0;
            $salesman091_120 = 0.0;
            $salesmanOver120 = 0.0;

            foreach ($salesmanGroup['customer_groups'] as &$customerGroup) {
                $customer045_060 = 0.0;
                $customer061_090 = 0.0;
                $customer091_120 = 0.0;
                $customerOver120 = 0.0;

                usort($customerGroup['items'], static fn (array $a, array $b): int => ((int) ($a['Age (Days)'] ?? 0)) <=> ((int) ($b['Age (Days)'] ?? 0)) ?: strcmp($a['Item Ref'] ?? '', $b['Item Ref'] ?? ''));

                foreach ($customerGroup['items'] as &$item) {
                    $ageDays = (int) ($item['Age (Days)'] ?? 0);
                    $balance = (float) ($item['Balance'] ?? 0);

                    $item['umur'] = $ageDays;
                    $item['bucket_045_060'] = ($ageDays >= 45 && $ageDays <= 60) ? $balance : 0.0;
                    $item['bucket_061_090'] = ($ageDays >= 61 && $ageDays <= 90) ? $balance : 0.0;
                    $item['bucket_091_120'] = ($ageDays >= 91 && $ageDays <= 120) ? $balance : 0.0;
                    $item['bucket_over_120'] = ($ageDays > 120) ? $balance : 0.0;

                    $customer045_060 += $item['bucket_045_060'];
                    $customer061_090 += $item['bucket_061_090'];
                    $customer091_120 += $item['bucket_091_120'];
                    $customerOver120 += $item['bucket_over_120'];
                }

                $customerTotal = $customer045_060 + $customer061_090 + $customer091_120 + $customerOver120;

                $customerGroup['total_045_060'] = round($customer045_060, 2);
                $customerGroup['total_061_090'] = round($customer061_090, 2);
                $customerGroup['total_091_120'] = round($customer091_120, 2);
                $customerGroup['total_over_120'] = round($customerOver120, 2);
                $customerGroup['total_saldo'] = round($customerTotal, 2);

                $salesman045_060 += $customerGroup['total_045_060'];
                $salesman061_090 += $customerGroup['total_061_090'];
                $salesman091_120 += $customerGroup['total_091_120'];
                $salesmanOver120 += $customerGroup['total_over_120'];
            }

            $salesmanTotal = $salesman045_060 + $salesman061_090 + $salesman091_120 + $salesmanOver120;

            $salesmanGroup['total_045_060'] = round($salesman045_060, 2);
            $salesmanGroup['total_061_090'] = round($salesman061_090, 2);
            $salesmanGroup['total_091_120'] = round($salesman091_120, 2);
            $salesmanGroup['total_over_120'] = round($salesmanOver120, 2);
            $salesmanGroup['total_saldo'] = round($salesmanTotal, 2);

            $grand045_060 += $salesmanGroup['total_045_060'];
            $grand061_090 += $salesmanGroup['total_061_090'];
            $grand091_120 += $salesmanGroup['total_091_120'];
            $grandOver120 += $salesmanGroup['total_over_120'];
        }

        $grandSaldo = $grand045_060 + $grand061_090 + $grand091_120 + $grandOver120;

        return [
            'title' => self::TITLE,
            'company' => '',
            'period_label' => $periodLabel,
            'salesmen_groups' => $salesmenGroups,
            'grand_total_045_060' => round($grand045_060, 2),
            'grand_total_061_090' => round($grand061_090, 2),
            'grand_total_091_120' => round($grand091_120, 2),
            'grand_total_over_120' => round($grandOver120, 2),
            'grand_total_saldo' => round($grandSaldo, 2),
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
            $ageDays = (int) ($row['Age (Days)'] ?? 0);

            return $ageDays > 44;
        }));
    }

    private function groupBySalesman(array $rows): array
    {
        $groups = [];
        $salesmanOrder = [];

        foreach ($rows as $row) {
            $salesmanName = trim((string) ($row['Sales Person Name'] ?? ''));
            if ($salesmanName === '') {
                $salesmanName = 'TANPA SALESMAN';
            }

            if (! isset($groups[$salesmanName])) {
                $groups[$salesmanName] = [
                    'salesman_name' => $salesmanName,
                    'items' => [],
                ];
                $salesmanOrder[] = $salesmanName;
            }

            $groups[$salesmanName]['items'][] = $row;
        }

        $result = [];
        foreach ($salesmanOrder as $name) {
            $result[] = $groups[$name];
        }

        return $result;
    }

    private function groupByCustomer(array $rows): array
    {
        $groups = [];
        $customerOrder = [];

        foreach ($rows as $row) {
            $customerName = trim((string) ($row['Customer Name'] ?? ''));
            if ($customerName === '') {
                $customerName = 'TANPA NAMA';
            }

            if (! isset($groups[$customerName])) {
                $groups[$customerName] = [
                    'customer_name' => $customerName,
                    'items' => [],
                ];
                $customerOrder[] = $customerName;
            }

            $groups[$customerName]['items'][] = $row;
        }

        $result = [];
        foreach ($customerOrder as $name) {
            $result[] = $groups[$name];
        }

        return $result;
    }
}
