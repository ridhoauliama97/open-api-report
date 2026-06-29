<?php

namespace App\Services\Ascends\Shared\InventoryAnalysis;

use Carbon\Carbon;
use RuntimeException;
use Throwable;
use XMLReader;

class PenyesuaianPersediaanReportService
{
    private const TITLE = 'Laporan Penyesuaian Persediaan';

    public function buildReportDataFromXml(string $xmlContents, string $sourceLabel = 'request xml payload', array $filters = []): array
    {
        if (trim($xmlContents) === '') {
            throw new RuntimeException('XML data kosong.');
        }

        $records = $this->parseXml($xmlContents, $sourceLabel);
        $allRows = $records['rows'];

        $period = self::resolvePeriod($filters)
            ?? self::resolvePeriodFromRows($allRows);

        if ($period !== null) {
            $p = $period;
            $allRows = array_values(array_filter($allRows, static function (array $row) use ($p): bool {
                $date = $row['adjustment_date'] ?? null;

                return $date !== null && $date->betweenIncluded($p['start'], $p['end']);
            }));
        }

        if ($allRows === []) {
            throw new RuntimeException('Data penyesuaian persediaan tidak ditemukan di XML.');
        }

        $sortedRows = self::sortRows($allRows);
        $headers = [
            'No',
            'Tanggal',
            'Nama',
            'Keterangan',
            'Jumlah',
            'Nilai yang Disesuaikan',
        ];

        $totalQty = 0;
        $totalAdjVal = 0;
        $uoms = [];
        foreach ($sortedRows as $row) {
            $totalQty += (float) ($row['quantity'] ?? 0);
            $totalAdjVal += (float) ($row['adjusted_value'] ?? 0);
            $uom = trim((string) ($row['uom'] ?? ''));
            if ($uom !== '') {
                $uoms[$uom] = ($uoms[$uom] ?? 0) + (float) ($row['quantity'] ?? 0);
            }
        }

        return [
            'title' => self::TITLE,
            'source_file' => $sourceLabel,
            'period_label' => $period !== null
                ? 'Periode: '.$period['start']->locale('id')->translatedFormat('d-M-y').' s/d '.$period['end']->locale('id')->translatedFormat('d-M-y')
                : '',
            'printed_at' => Carbon::now()->locale('id')->translatedFormat('d F Y H:i'),
            'printed_by' => $records['printed_by'],
            'headers' => $headers,
            'rows' => array_map(static fn (array $row): array => self::publicRow($row), $sortedRows),
            'total_rows' => count($sortedRows),
            'totals' => [
                'quantity' => $totalQty,
                'adjusted_value' => $totalAdjVal,
                'uoms' => $uoms,
            ],
        ];
    }

    private function parseXml(string $xmlContents, string $sourceLabel, ?array $period = null): array
    {
        $reader = new XMLReader;

        if (! @$reader->XML($xmlContents, null, LIBXML_NOCDATA | LIBXML_NONET)) {
            throw new RuntimeException("XML tidak valid: {$sourceLabel}");
        }

        $rows = [];
        $printedBy = '';

        while ($reader->read()) {
            if ($reader->nodeType !== XMLReader::ELEMENT || strtolower($reader->name) !== 'cb') {
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

            $adjustmentDate = self::parseDate((string) ($node->Adjustment_x0020_Date ?? ''));

            if ($adjustmentDate === null) {
                continue;
            }

            if ($period !== null && ! $adjustmentDate->betweenIncluded($period['start'], $period['end'])) {
                continue;
            }

            if ($printedBy === '') {
                $printedBy = self::resolvePrintedBy($node);
            }

            $rows[] = [
                'adjustment_date' => $adjustmentDate,
                'adjustment_date_sort' => $adjustmentDate->format('Y-m-d'),
                'adjustment_date_display' => self::formatDate($adjustmentDate),
                'item_name' => trim((string) ($node->Item_x0020_Name ?? '')),
                'item_remarks' => trim((string) ($node->Item_x0020_Remarks ?? '')),
                'quantity' => (float) ($node->Quantity ?? 0),
                'uom' => trim((string) ($node->UOM ?? '')),
                'adjusted_value' => (float) ($node->Adjusted_x0020_Value ?? 0),
            ];
        }

        $reader->close();

        if ($rows === []) {
            throw new RuntimeException('Data penyesuaian persediaan tidak ditemukan di XML.');
        }

        return [
            'rows' => $rows,
            'printed_by' => $printedBy,
        ];
    }

    private static function sortRows(array $rows): array
    {
        usort($rows, static fn (array $left, array $right): int => $left['adjustment_date_sort'] <=> $right['adjustment_date_sort']);

        return $rows;
    }

    private static function publicRow(array $row): array
    {
        $quantity = (float) ($row['quantity'] ?? 0);
        $uom = (string) ($row['uom'] ?? '');
        $jumlah = $uom !== '' ? number_format($quantity, 0, '.', ',').' '.$uom : number_format($quantity, 0, '.', ',');

        return [
            'Tanggal' => (string) ($row['adjustment_date_display'] ?? ''),
            'Nama' => (string) ($row['item_name'] ?? ''),
            'Keterangan' => (string) ($row['item_remarks'] ?? ''),
            'Jumlah' => $jumlah,
            'Nilai yang Disesuaikan' => number_format((float) ($row['adjusted_value'] ?? 0), 2, '.', ','),
        ];
    }

    private static function formatDate(?Carbon $date): string
    {
        if ($date === null) {
            return '';
        }

        return $date->locale('id')->translatedFormat('d-M-y');
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

    private static function resolvePeriod(array $filters): ?array
    {
        $start = self::parseDate((string) ($filters['start_date'] ?? $filters['AdjustmentDate.StartDate'] ?? ''));
        $end = self::parseDate((string) ($filters['end_date'] ?? $filters['AdjustmentDate.EndDate'] ?? ''));

        if ($start === null && $end === null) {
            return null;
        }

        $start ??= $end?->copy();
        $end ??= $start?->copy();

        if ($start === null || $end === null) {
            return null;
        }

        if ($end->lessThan($start)) {
            [$start, $end] = [$end, $start];
        }

        return [
            'start' => $start->startOfDay(),
            'end' => $end->endOfDay(),
        ];
    }

    private static function resolvePeriodFromRows(array $rows): ?array
    {
        $dates = array_values(array_filter(array_map(
            static fn (array $row): ?Carbon => $row['adjustment_date'] ?? null,
            $rows,
        )));

        if ($dates === []) {
            return null;
        }

        usort($dates, static fn (Carbon $left, Carbon $right): int => $left <=> $right);

        return [
            'start' => $dates[0]->copy()->startOfDay(),
            'end' => $dates[count($dates) - 1]->copy()->endOfDay(),
        ];
    }

    private static function resolvePrintedBy(\SimpleXMLElement $node): string
    {
        $candidateKeys = [
            'Nama_x0020_User',
            'User_x0020_Name',
            'Printed_x0020_By',
            'Created_x0020_By',
        ];

        foreach ($candidateKeys as $key) {
            $value = trim((string) ($node->$key ?? ''));

            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }
}
