<?php

namespace App\Services\Ascends\Shared\InventoryAnalysis;

use Carbon\Carbon;
use RuntimeException;
use Throwable;
use XMLReader;

class KursiAdjustmentReportService
{
    private const TITLE = 'Laporan Adjustment Selisih Kursi';

    private const TEMP_ALM_MAP = [
        'KC2601 HIJAU APEL --> KC2601 BIRU' => 'A1',
        'KC2601 HIJAU APEL --> KC2601 ORANGE' => 'A2',
        'KC2601 HIJAU APEL --> KC2601 MERAH' => 'A3',
        'KC2601 HIJAU APEL --> KC2601 HITAM' => 'A4',
        'KC2601 HIJAU APEL --> KC2601 PUTIH' => 'A5',
        'KC2601 BIRU --> KC2601 HIJAU APEL' => 'B1',
        'KC2601 BIRU --> KC2601 ORANGE' => 'B2',
        'KC2601 BIRU --> KC2601 MERAH' => 'B3',
        'KC2601 BIRU --> KC2601 HITAM' => 'B4',
        'KC2601 BIRU --> KC2601 PUTIH' => 'B5',
        'KC2601 ORANGE --> KC2601 HIJAU APEL' => 'C1',
        'KC2601 ORANGE --> KC2601 BIRU' => 'C2',
        'KC2601 ORANGE --> KC2601 MERAH' => 'C3',
        'KC2601 ORANGE --> KC2601 HITAM' => 'C4',
        'KC2601 ORANGE --> KC2601 PUTIH' => 'C5',
        'KC2601 MERAH --> KC2601 HIJAU APEL' => 'D1',
        'KC2601 MERAH --> KC2601 BIRU' => 'D2',
        'KC2601 MERAH --> KC2601 ORANGE' => 'D3',
        'KC2601 MERAH --> KC2601 HITAM' => 'D4',
        'KC2601 MERAH --> KC2601 PUTIH' => 'D5',
        'KC2601 HITAM --> KC2601 HIJAU APEL' => 'E1',
        'KC2601 HITAM --> KC2601 BIRU' => 'E2',
        'KC2601 HITAM --> KC2601 ORANGE' => 'E3',
        'KC2601 HITAM --> KC2601 MERAH' => 'E4',
        'KC2601 HITAM --> KC2601 PUTIH' => 'E5',
        'KC2601 PUTIH --> KC2601 HIJAU APEL' => 'F1',
        'KC2601 PUTIH --> KC2601 BIRU' => 'F2',
        'KC2601 PUTIH --> KC2601 ORANGE' => 'F3',
        'KC2601 PUTIH --> KC2601 MERAH' => 'F4',
        'KC2601 PUTIH --> KC2601 HITAM' => 'F5',
        'KM2401A MERAH --> KM2401A BIRU' => 'G1',
        'KM2401A MERAH --> KM2401A HIJAU' => 'G2',
    ];

    private const NAME_GROUP_MAP = [
        'A1' => 'HIJAU APEL --> BIRU',
        'A2' => 'HIJAU APEL --> ORANGE',
        'A3' => 'HIJAU APEL --> MERAH',
        'A4' => 'HIJAU APEL --> HITAM',
        'A5' => 'HIJAU APEL --> PUTIH',
        'B1' => 'BIRU --> HIJAU APEL',
        'B2' => 'BIRU --> ORANGE',
        'B3' => 'BIRU --> MERAH',
        'B4' => 'BIRU --> HITAM',
        'B5' => 'BIRU --> PUTIH',
        'C1' => 'ORANGE --> HIJAU APEL',
        'C2' => 'ORANGE --> BIRU',
        'C3' => 'ORANGE --> MERAH',
        'C4' => 'ORANGE --> HITAM',
        'C5' => 'ORANGE --> KC2601 PUTIH',
        'D1' => 'MERAH --> HIJAU APEL',
        'D2' => 'MERAH --> BIRU',
        'D3' => 'MERAH --> ORANGE',
        'D4' => 'MERAH --> HITAM',
        'D5' => 'MERAH --> PUTIH',
        'E1' => 'HITAM --> HIJAU APEL',
        'E2' => 'HITAM --> BIRU',
        'E3' => 'HITAM --> ORANGE',
        'E4' => 'HITAM --> MERAH',
        'E5' => 'HITAM --> PUTIH',
        'F1' => 'PUTIH --> HIJAU APEL',
        'F2' => 'PUTIH --> BIRU',
        'F3' => 'PUTIH --> ORANGE',
        'F4' => 'PUTIH --> MERAH',
        'F5' => 'PUTIH --> HITAM',
        'G1' => 'KM2401A MERAH --> KM2401A BIRU',
        'G2' => 'KM2401A MERAH --> KM2401A HIJAU',
    ];

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

        $allRows = $this->applyRecordSelection($allRows);

        $allRows = $this->computeFormulas($allRows);
        $allRows = $this->sortRows($allRows);

        $groups = $this->buildGroups($allRows);

        return [
            'title' => self::TITLE,
            'source_file' => $sourceLabel,
            'period_label' => $period !== null
                ? 'Periode: '.$period['start']->locale('id')->translatedFormat('d-M-y').' s/d '.$period['end']->locale('id')->translatedFormat('d-M-y')
                : '',
            'printed_at' => Carbon::now()->locale('id')->translatedFormat('d F Y H:i'),
            'printed_by' => $records['printed_by'],
            'groups' => $groups,
        ];
    }

    private function parseXml(string $xmlContents, string $sourceLabel): array
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

            if ($printedBy === '') {
                $printedBy = self::resolvePrintedBy($node);
            }

            $rows[] = [
                'adjustment_date' => $adjustmentDate,
                'adjustment_date_sort' => $adjustmentDate->format('Y-m-d'),
                'adjustment_date_display' => self::formatDate($adjustmentDate),
                'adjustment_type' => trim((string) ($node->Adjustment_x0020_Type ?? '')),
                'memo_number' => trim((string) ($node->Memo_x0020_Number ?? '')),
                'memo_remarks' => trim((string) ($node->Memo_x0020_Remarks ?? '')),
                'item_code' => trim((string) ($node->Item_x0020_Code ?? '')),
                'item_name' => trim((string) ($node->Item_x0020_Name ?? '')),
                'item_full' => trim((string) ($node->Item ?? '')),
                'item_remarks' => trim((string) ($node->Item_x0020_Remarks ?? '')),
                'quantity' => (float) ($node->Quantity ?? 0),
                'uom' => trim((string) ($node->UOM ?? '')),
                'adjusted_value' => (float) ($node->Adjusted_x0020_Value ?? 0),
            ];
        }

        $reader->close();

        if ($rows === []) {
            throw new RuntimeException('Data XML tidak ditemukan.');
        }

        return [
            'rows' => $rows,
            'printed_by' => $printedBy,
        ];
    }

    private function applyRecordSelection(array $rows): array
    {
        $filtered = [];

        foreach ($rows as $row) {
            $typeLemari = self::computeTypeLemari($row);
            if ($typeLemari !== 'TAMPIL') {
                continue;
            }
            $filtered[] = $row;
        }

        return $filtered;
    }

    private static function computeTypeLemari(array $row): string
    {
        $name = $row['item_name'] ?? '';

        if (str_contains($name, 'KURSI CAFE KC') || str_contains($name, 'KURSI MAKAN')) {
            return 'TAMPIL';
        }

        return 'TIDAK';
    }

    private function computeFormulas(array $rows): array
    {
        $result = [];
        foreach ($rows as $row) {
            $row['pintu'] = self::computePintu($row);
            $row['temp_alm'] = self::computeTempAlm($row);
            $row['name_group'] = self::computeNameGroup($row);
            $row['jelas'] = self::computeJelas($row);
            $row['split_toleng'] = self::computeSplitToleng($row);
            $row['name_pk'] = self::computeNamePk($row);
            $result[] = $row;
        }

        return $result;
    }

    private static function computePintu(array $row): string
    {
        $name = $row['item_name'] ?? '';

        if (str_contains($name, 'PINTU')) {
            return 'PINTU 4';
        }

        if (str_contains($name, 'KURSI MAKAN')) {
            return 'KURSI MAKAN';
        }

        return 'KURSI CAFE';
    }

    private static function computeTempAlm(array $row): string
    {
        $remarks = $row['memo_remarks'] ?? '';

        foreach (self::TEMP_ALM_MAP as $pattern => $code) {
            if (str_contains($remarks, $pattern)) {
                return $code;
            }
        }

        return 'Tidak Tergroup';
    }

    private static function computeNameGroup(array $row): string
    {
        $code = $row['temp_alm'] ?? '';

        if (isset(self::NAME_GROUP_MAP[$code])) {
            return self::NAME_GROUP_MAP[$code];
        }

        if ($code === 'Tidak Tergroup') {
            return 'Tidak Tergroup (Cek Tulisan Remarks)';
        }

        return 'Tidak Tergroup (Cek Tulisan Remarks)';
    }

    private static function computeJelas(array $row): string
    {
        $ng = $row['name_group'] ?? '';

        if (str_contains($ng, 'Tidak Terg')) {
            return 'AA';
        }

        return '00';
    }

    private static function computeSplitToleng(array $row): int
    {
        $name = $row['item_name'] ?? '';
        $pos = strpos($name, 'LP');

        if ($pos === false) {
            $nameFull = $row['item_full'] ?? '';
            $pos = strpos($nameFull, 'LP');
        }

        if ($pos === false) {
            return -1;
        }

        return $pos;
    }

    private static function computeNamePk(array $row): string
    {
        $name = $row['item_name'] ?? '';
        $splitToleng = $row['split_toleng'] ?? -1;

        if ($splitToleng <= 10) {
            return $name;
        }

        $length = $splitToleng - 10;
        $result = substr($name, 9, $length);

        return $result !== false ? trim($result) : $name;
    }

    private function sortRows(array $rows): array
    {
        usort($rows, static function (array $a, array $b): int {
            $cmp = $a['pintu'] <=> $b['pintu'];
            if ($cmp !== 0) {
                return $cmp;
            }

            $cmp = ($a['name_group'] ?? '') <=> ($b['name_group'] ?? '');
            if ($cmp !== 0) {
                return $cmp;
            }

            return $a['adjustment_date_sort'] <=> $b['adjustment_date_sort'];
        });

        return $rows;
    }

    private function buildGroups(array $rows): array
    {
        $grouped = [];

        foreach ($rows as $row) {
            $pintu = $row['pintu'];
            $nameGroup = $row['name_group'];
            $grouped[$pintu][$nameGroup][] = $row;
        }

        $pintuGroups = [];

        foreach ($grouped as $pintu => $nameGroups) {
            $ngList = [];
            $totalSelisihPintu = 0;

            ksort($nameGroups);

            foreach ($nameGroups as $nameGroup => $records) {
                $pairs = [];
                $pairTotal = 0;
                $recordCount = count($records);

                for ($i = 0; $i < $recordCount; $i += 2) {
                    $masukRecord = $records[$i];
                    $keluarRecord = $records[$i + 1] ?? null;

                    $masukValue = (float) ($masukRecord['adjusted_value'] ?? 0);
                    $keluarAbs = $keluarRecord !== null
                        ? abs((float) ($keluarRecord['adjusted_value'] ?? 0))
                        : 0.0;

                    $selisih = $masukValue - $keluarAbs;

                    $pairs[] = [
                        'nama_barang' => $masukRecord['name_pk'] ?? $masukRecord['item_name'] ?? '',
                        'unit' => $masukRecord['uom'] ?? '',
                        'masuk' => $masukValue,
                        'keluar' => $keluarRecord !== null
                            ? (float) ($keluarRecord['adjusted_value'] ?? 0)
                            : 0.0,
                        'selisih' => $selisih,
                    ];

                    $pairTotal += $selisih;
                }

                $ngList[] = [
                    'name_group' => $nameGroup,
                    'jelas' => $records[0]['jelas'] ?? '00',
                    'pairs' => $pairs,
                    'subtotal_selisih' => $pairTotal,
                ];

                $totalSelisihPintu += $pairTotal;
            }

            $pintuGroups[] = [
                'pintu' => $pintu,
                'name_groups' => $ngList,
                'total_selisih' => $totalSelisihPintu,
            ];
        }

        $grandTotal = 0;
        foreach ($pintuGroups as $pg) {
            $grandTotal += $pg['total_selisih'];
        }

        return [
            'pintu_groups' => $pintuGroups,
            'grand_total' => $grandTotal,
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
