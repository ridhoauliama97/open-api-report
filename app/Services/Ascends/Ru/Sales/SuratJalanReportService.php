<?php

namespace App\Services\Ascends\Ru\Sales;

use App\Services\XmlDataSourceService;
use Carbon\Carbon;
use RuntimeException;

class SuratJalanReportService
{
    private const TITLE = 'Surat Jalan (RU)';

    public function __construct(
        private readonly XmlDataSourceService $xmlDataSourceService,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function buildReportData(): array
    {
        $reportData = $this->xmlDataSourceService->loadSubReport('RU', 'sales', 'surat_jalan');

        return $this->shapeReportData($reportData, 'storage/app/xml_sources/RU/Sales/Sales Invoice.xml');
    }

    /**
     * @return array<string, mixed>
     */
    public function buildReportDataFromXml(string $xmlContents, string $sourceLabel = 'request xml payload'): array
    {
        $reportData = $this->xmlDataSourceService->loadSubReportFromXmlContents(
            'RU',
            'sales',
            'surat_jalan',
            $xmlContents,
            $sourceLabel
        );

        return $this->shapeReportData($reportData, $sourceLabel);
    }

    /**
     * @param  array<string, mixed>  $reportData
     * @return array<string, mixed>
     */
    private function shapeReportData(array $reportData, string $sourceLabel): array
    {
        $rawRows = array_values($reportData['rows'] ?? []);
        if ($rawRows === []) {
            throw new RuntimeException('Data XML Surat Jalan tidak memiliki record Invoice.');
        }

        $documents = self::groupDocuments($rawRows);

        return array_merge($reportData, [
            'title' => $reportData['label'] ?? self::TITLE,
            'source_file' => $sourceLabel,
            'printed_at' => now()->locale('id')->translatedFormat('d-M-y H:i'),
            'printed_by' => self::resolvePrintedBy($rawRows),
            'headers' => [
                'No',
                'Kode Barang',
                'Nama Barang',
                'Qty',
                'Satuan',
            ],
            'rows' => $rawRows,
            'documents' => $documents,
            'total_rows' => count($rawRows),
            'total_documents' => count($documents),
        ]);
    }

    /**
     * @param  array<int, array<string, string>>  $rows
     * @return array<int, array<string, mixed>>
     */
    private static function groupDocuments(array $rows): array
    {
        $grouped = [];

        foreach ($rows as $row) {
            $invoiceId = trim((string) ($row['Invoice ID'] ?? ''));
            $documentKey = $invoiceId !== '' ? $invoiceId : trim((string) ($row['No Surat Jalan'] ?? ''));
            if ($documentKey === '') {
                $documentKey = 'surat_jalan_' . count($grouped);
            }

            if (! isset($grouped[$documentKey])) {
                $grouped[$documentKey] = self::headerFromRow($row);
            }

            $grouped[$documentKey]['items'][] = self::itemFromRow($row);
        }

        foreach ($grouped as &$document) {
            $document['item_count'] = count($document['items']);
            $document['total_quantity'] = array_sum(array_map(
                static fn (array $item): float => (float) ($item['qty_raw'] ?? 0),
                $document['items']
            ));
        }
        unset($document);

        return array_values($grouped);
    }

    /**
     * @param  array<string, string>  $row
     * @return array<string, mixed>
     */
    private static function headerFromRow(array $row): array
    {
        $dropShip = self::dropShipAddress($row);
        $deliveryDateSource = (string) ($row['Tanggal Kirim POS'] ?? $row['Tanggal Invoice'] ?? $row['Tanggal Kirim'] ?? '');

        return [
            'invoice_id' => (string) ($row['Invoice ID'] ?? ''),
            'invoice_number' => (string) ($row['No Invoice'] ?? ''),
            'document_number' => (string) ($row['No Surat Jalan'] ?? ''),
            'invoice_date' => self::formatDate((string) ($row['Tanggal Invoice'] ?? '')),
            'delivery_date' => self::formatDate($deliveryDateSource),
            'delivery_date_numeric' => self::formatNumericDate($deliveryDateSource),
            'customer_name' => (string) ($row['Customer'] ?? ''),
            'billing_address' => self::addressText($row, ['Alamat Tagih 1', 'Alamat Tagih 2', 'Kota Tagih']),
            'shipping_name' => $dropShip['name'] !== '' ? $dropShip['name'] : (string) ($row['Nama Kirim'] ?? ''),
            'shipping_address' => $dropShip['address'] !== ''
                ? $dropShip['address']
                : self::addressText($row, ['Alamat Kirim 1', 'Alamat Kirim 2', 'Kota Kirim']),
            'customer_address' => self::addressText($row, ['Alamat Customer 1', 'Alamat Customer 2', 'Kota']),
            'sales_order_number' => (string) ($row['No SO'] ?? ''),
            'vehicle_no' => (string) ($row['No Kendaraan'] ?? ''),
            'shipper' => (string) ($row['Pengirim'] ?? ''),
            'salesman' => (string) ($row['Salesman'] ?? ''),
            'remarks' => self::normalizeText((string) ($row['Keterangan'] ?? '')),
            'created_by' => (string) ($row['Dibuat Oleh'] ?? ''),
        ];
    }

    /**
     * @param  array<string, string>  $row
     * @return array<string, mixed>
     */
    private static function itemFromRow(array $row): array
    {
        $qty = self::decimalValue((string) ($row['Qty'] ?? ''));

        return [
            'item_code' => (string) ($row['Kode Barang'] ?? ''),
            'item_name' => (string) ($row['Nama Barang'] ?? ''),
            'qty' => self::formatDecimal($qty, 4),
            'qty_raw' => $qty,
            'uom' => (string) ($row['Satuan'] ?? ''),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     */
    private static function resolvePrintedBy(array $rows): string
    {
        foreach ($rows as $row) {
            $value = trim((string) ($row['Dibuat Oleh'] ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    /**
     * @param  array<string, string>  $row
     * @param  string[]  $keys
     */
    private static function addressText(array $row, array $keys): string
    {
        $parts = [];
        foreach ($keys as $key) {
            $value = trim((string) ($row[$key] ?? ''));
            if ($value !== '') {
                $parts[] = $value;
            }
        }

        return implode("\n", $parts);
    }

    /**
     * @param  array<string, string>  $row
     * @return array{name: string, address: string}
     */
    private static function dropShipAddress(array $row): array
    {
        $value = trim((string) ($row['Alamat Drop Ship'] ?? ''));
        if ($value === '') {
            return ['name' => '', 'address' => ''];
        }

        $lines = preg_split('/\R+/', $value) ?: [];
        $lines = array_values(array_filter(array_map(
            static fn (string $line): string => trim($line),
            $lines
        ), static fn (string $line): bool => $line !== ''));

        return [
            'name' => (string) ($lines[0] ?? ''),
            'address' => implode("\n", array_slice($lines, 1)),
        ];
    }

    private static function normalizeText(string $value): string
    {
        return trim(str_replace(["\r\n", "\r"], "\n", $value));
    }

    private static function formatDate(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        try {
            return Carbon::parse($value)->locale('id')->translatedFormat('d-M-y');
        } catch (\Throwable) {
            return $value;
        }
    }

    private static function formatNumericDate(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        try {
            return Carbon::parse($value)->format('d-m-y');
        } catch (\Throwable) {
            return $value;
        }
    }

    private static function decimalValue(string $value): float
    {
        $value = trim($value);
        if ($value === '') {
            return 0.0;
        }

        return (float) str_replace(',', '', $value);
    }

    private static function formatDecimal(float $value, int $decimals): string
    {
        $formatted = number_format($value, $decimals, '.', ',');

        return rtrim(rtrim($formatted, '0'), '.');
    }
}
