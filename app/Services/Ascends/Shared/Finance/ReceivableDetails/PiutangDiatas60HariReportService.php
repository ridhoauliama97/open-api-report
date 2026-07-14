<?php

namespace App\Services\Ascends\Shared\Finance\ReceivableDetails;

use Carbon\Carbon;
use RuntimeException;
use XMLReader;

class PiutangDiatas60HariReportService
{
    private const TITLE = 'Laporan Umur Piutang Di Atas 60 Hari';

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
            throw new RuntimeException('Tidak ada data dengan umur piutang diatas 60 hari.');
        }

        $salesmenGroups = $this->groupBySalesman($filtered);

        $grand60_75 = 0.0;
        $grand76_90 = 0.0;
        $grand91_120 = 0.0;
        $grand120 = 0.0;

        foreach ($salesmenGroups as &$salesmanGroup) {
            $salesmanGroup['customer_groups'] = $this->groupByCustomer($salesmanGroup['items']);
            unset($salesmanGroup['items']);

            $salesmanSubtotal60_75 = 0.0;
            $salesmanSubtotal76_90 = 0.0;
            $salesmanSubtotal91_120 = 0.0;
            $salesmanSubtotal120 = 0.0;

            foreach ($salesmanGroup['customer_groups'] as &$customerGroup) {
                $customerTotal60_75 = 0.0;
                $customerTotal76_90 = 0.0;
                $customerTotal91_120 = 0.0;
                $customerTotal120 = 0.0;

                foreach ($customerGroup['items'] as &$item) {
                    $ageDays = (int) ($item['Age (Days)'] ?? 0);
                    $balance = (float) ($item['Balance'] ?? 0);

                    $item['bucket_60_75'] = ($ageDays >= 60 && $ageDays <= 75) ? $balance : 0.0;
                    $item['bucket_76_90'] = ($ageDays >= 76 && $ageDays <= 90) ? $balance : 0.0;
                    $item['bucket_91_120'] = ($ageDays >= 91 && $ageDays <= 120) ? $balance : 0.0;
                    $item['bucket_120'] = ($ageDays > 120) ? $balance : 0.0;

                    $customerTotal60_75 += $item['bucket_60_75'];
                    $customerTotal76_90 += $item['bucket_76_90'];
                    $customerTotal91_120 += $item['bucket_91_120'];
                    $customerTotal120 += $item['bucket_120'];
                }

                $customerGroup['total_60_75'] = round($customerTotal60_75, 2);
                $customerGroup['total_76_90'] = round($customerTotal76_90, 2);
                $customerGroup['total_91_120'] = round($customerTotal91_120, 2);
                $customerGroup['total_120'] = round($customerTotal120, 2);

                $salesmanSubtotal60_75 += $customerGroup['total_60_75'];
                $salesmanSubtotal76_90 += $customerGroup['total_76_90'];
                $salesmanSubtotal91_120 += $customerGroup['total_91_120'];
                $salesmanSubtotal120 += $customerGroup['total_120'];
            }

            $salesmanGroup['total_60_75'] = round($salesmanSubtotal60_75, 2);
            $salesmanGroup['total_76_90'] = round($salesmanSubtotal76_90, 2);
            $salesmanGroup['total_91_120'] = round($salesmanSubtotal91_120, 2);
            $salesmanGroup['total_120'] = round($salesmanSubtotal120, 2);

            $grand60_75 += $salesmanGroup['total_60_75'];
            $grand76_90 += $salesmanGroup['total_76_90'];
            $grand91_120 += $salesmanGroup['total_91_120'];
            $grand120 += $salesmanGroup['total_120'];
        }

        return [
            'title' => self::TITLE,
            'company' => '',
            'period_label' => $periodLabel,
            'salesmen_groups' => $salesmenGroups,
            'grand_total_60_75' => round($grand60_75, 2),
            'grand_total_76_90' => round($grand76_90, 2),
            'grand_total_91_120' => round($grand91_120, 2),
            'grand_total_120' => round($grand120, 2),
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

            return $ageDays > 60;
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
