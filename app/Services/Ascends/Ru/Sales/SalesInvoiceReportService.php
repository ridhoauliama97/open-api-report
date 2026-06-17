<?php

namespace App\Services\Ascends\Ru\Sales;

use App\Services\XmlDataSourceService;
use Carbon\Carbon;
use RuntimeException;

class SalesInvoiceReportService
{
    private const TITLE = 'Sales Invoice (RU)';

    public function __construct(
        private readonly XmlDataSourceService $xmlDataSourceService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function buildReportData(): array
    {
        $reportData = $this->xmlDataSourceService->loadSubReport('RU', 'sales', 'sales_invoice');

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
            'sales_invoice',
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
            throw new RuntimeException('Data XML Sales Invoice tidak memiliki record Invoice.');
        }

        $invoices = self::groupInvoices($rawRows);
        $printedBy = self::resolvePrintedBy($rawRows);

        return array_merge($reportData, [
            'title' => $reportData['label'] ?? self::TITLE,
            'source_file' => $sourceLabel,
            'printed_at' => now()->locale('id')->translatedFormat('d-M-y H:i'),
            'printed_by' => $printedBy,
            'headers' => [
                'No',
                'Kode Barang',
                'Nama Barang',
                'QTY',
                '@Harga (Rp)',
                'Disc %',
                'Nilai (Rp)',
            ],
            'rows' => $rawRows,
            'invoices' => $invoices,
            'total_rows' => count($rawRows),
            'total_invoices' => count($invoices),
        ]);
    }

    /**
     * @param  array<int, array<string, string>>  $rows
     * @return array<int, array<string, mixed>>
     */
    private static function groupInvoices(array $rows): array
    {
        $grouped = [];

        foreach ($rows as $row) {
            $invoiceId = trim((string) ($row['Invoice ID'] ?? ''));
            $invoiceKey = $invoiceId !== '' ? $invoiceId : trim((string) ($row['No SI'] ?? ''));
            if ($invoiceKey === '') {
                $invoiceKey = 'invoice_'.count($grouped);
            }

            if (! isset($grouped[$invoiceKey])) {
                $grouped[$invoiceKey] = self::headerFromRow($row);
            }

            $grouped[$invoiceKey]['items'][] = self::itemFromRow($row);
        }

        foreach ($grouped as &$invoice) {
            $invoice['item_count'] = count($invoice['items']);
            $invoice['total_quantity'] = array_sum(array_map(
                static fn (array $item): float => (float) ($item['qty_raw'] ?? 0),
                $invoice['items']
            ));
            $invoice['subtotal'] = array_sum(array_map(
                static fn (array $item): float => (float) ($item['line_total_raw'] ?? 0),
                $invoice['items']
            ));
        }
        unset($invoice);

        return array_values($grouped);
    }

    /**
     * @param  array<string, string>  $row
     * @return array<string, mixed>
     */
    private static function headerFromRow(array $row): array
    {
        $dropShip = self::dropShipAddress($row);

        return [
            'invoice_id' => (string) ($row['Invoice ID'] ?? ''),
            'invoice_number' => (string) ($row['No SI'] ?? ''),
            'invoice_date' => self::formatDate((string) ($row['Tgl Faktur'] ?? '')),
            'delivery_date' => self::formatDate((string) ($row['Tgl Kirim POS'] ?? $row['Tgl Kirim'] ?? '')),
            'due_date' => self::formatDate((string) ($row['Tgl Kirim'] ?? '')),
            'customer_name' => (string) ($row['Customer'] ?? ''),
            'billing_address' => self::addressText($row, ['Alamat Tagih 1', 'Alamat Tagih 2', 'Kota Tagih']),
            'shipping_name' => $dropShip['name'] !== '' ? $dropShip['name'] : (string) ($row['Nama Kirim'] ?? ''),
            'shipping_address' => $dropShip['address'] !== ''
                ? $dropShip['address']
                : self::addressText($row, ['Alamat Kirim 1', 'Alamat Kirim 2', 'Kota Kirim']),
            'customer_address' => self::addressText($row, ['Alamat Customer 1', 'Alamat Customer 2', 'Kota']),
            'do_number' => (string) ($row['No DO'] ?? ''),
            'payment_term' => (string) ($row['Jatuh Tempo'] ?? ''),
            'salesman' => (string) ($row['Salesman'] ?? ''),
            'vehicle_no' => (string) ($row['No Kendaraan'] ?? ''),
            'shipper' => (string) ($row['Pengirim'] ?? ''),
            'remarks' => self::normalizeText((string) ($row['Keterangan'] ?? '')),
            'created_by' => (string) ($row['Dibuat Oleh'] ?? ''),
            'net_total' => self::decimalValue((string) ($row['Total'] ?? '')),
            'discount' => self::decimalValue((string) ($row['Discount'] ?? '')),
            'net_total_words' => (string) ($row['Terbilang'] ?? ''),
        ];
    }

    /**
     * @param  array<string, string>  $row
     * @return array<string, mixed>
     */
    private static function itemFromRow(array $row): array
    {
        $qty = self::decimalValue((string) ($row['Qty'] ?? ''));
        $price = self::decimalValue((string) ($row['Harga'] ?? ''));
        $lineTotal = self::decimalValue((string) ($row['Nilai'] ?? ''));

        return [
            'item_code' => (string) ($row['Kode Barang'] ?? ''),
            'item_name' => (string) ($row['Nama Barang'] ?? ''),
            'qty' => self::formatDecimal($qty, 4),
            'qty_raw' => $qty,
            'uom' => (string) ($row['Satuan'] ?? ''),
            'price' => self::formatMoney($price),
            'price_raw' => $price,
            'discount' => (string) ($row['Disc'] ?? ''),
            'line_total' => self::formatMoney($lineTotal),
            'line_total_raw' => $lineTotal,
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

    private static function formatMoney(float $value): string
    {
        return number_format($value, 0, '.', ',');
    }
}
