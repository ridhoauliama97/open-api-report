<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif:ital,wght@0,100..900;1,100..900&display=swap"
        rel="stylesheet">
    <style>
        body {
            font-family: "Noto Serif", serif;
            font-size: 10px;
            margin: 0;
            padding: 0;
        }
        .report-companyTitle {
            font-size: 18px;
            font-weight: bold;
            text-align: center;
            margin: 0 0 4px;
        }
        .report-title {
            font-size: 16px;
            font-weight: bold;
            text-align: center;
            margin: 0;
        }
        .report-subtitle {
            font-size: 12px;
            color: #636466;
            text-align: center;
            margin: 2px 0 20px;
        }
        .data-table {
            border: 1px solid #000;
            border-collapse: collapse;
            width: calc(100% - 2px);
            table-layout: fixed;
        }
        .data-table th,
        .data-table td {
            border: 1px solid #000;            padding: 1px 2px;
            vertical-align: top;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
        .data-table th {
            background-color: #eef2f8;
            font-weight: bold;
        }
        .section-header td {
            font-weight: bold;
            font-style: italic;
            color: #9c111d;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding-left: 4px;
        }
        .sub-section-header td {
            font-weight: bold;
            color: #9c111d;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding-left: 4px;
        }
        .item-row td {
            padding-left: 4px;
        }
        .row-odd td {
            background-color: #c9d1df;
        }
        .row-even td {
            background-color: #eef2f8;
        }
        .subtotal-row td {
            font-weight: bold;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
        }
        .grand-total-row td {
            font-weight: bold;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
        }
        .empty-row td {
            font-style: italic;
            font-weight: bold;
            color: #9c111d;
            background-color: #c9d1df;
        }
        .number {
            text-align: right;
        }
        .nowrap {
            white-space: nowrap;
        }
        .number-negative {
            color: #9c111d;
        }
    
        /* standardized table borders */
        .report-table {
            border: 1px solid #000;
            border-collapse: collapse;
            width: calc(100% - 2px);
            line-height: inherit;
        }
.report-table th, .report-table td {
            border: 1px solid #000;
            word-wrap: break-word;
            padding: 2px;
            vertical-align: top;
        }

        .report-table th { font-weight: bold; background-color: #eef2f8; }
    </style>
</head>

<body>
    @php
        $rowsData =
            isset($rows) && is_iterable($rows) ? (is_array($rows) ? $rows : collect($rows)->values()->all()) : [];
        $availableColumns = array_keys($rowsData[0] ?? []);
        $generatedByName = $generatedBy?->name ?? 'sistem';
        $generatedAtText = $generatedAt->copy()->locale('id')->translatedFormat('d-M-y H:i');

        $normalizeName = static function (?string $name): string {
            $raw = $name ?? '';

            return strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $raw) ?? '');
        };

        $headerLabelMap = [
            'nost' => 'Nomor ST',
            'nostock' => 'Nomor ST',
            'datecreate' => 'Tanggal',
            'date' => 'Tanggal',
            'tanggal' => 'Tanggal',
            'jlhbtg' => 'Jmlh <br/> Batang',
            'jmlhbatang' => 'Jmlh <br/> Batang',
            'jumlahbatang' => 'Jmlh <br/> Batang',
            'pcs' => 'Jmlh <br/> Batang',
            'idlokasi' => 'Lokasi',
            'lokasi' => 'Lokasi',
            'location' => 'Lokasi',
            'description' => 'Lokasi',
        ];

        $findColumn = static function (array $columns, array $candidates) use ($normalizeName): ?string {
            $candidateSet = [];
            foreach ($candidates as $candidate) {
                $candidateSet[$normalizeName($candidate)] = true;
            }

            foreach ($columns as $column) {
                if (isset($candidateSet[$normalizeName((string) $column)])) {
                    return (string) $column;
                }
            }

            return null;
        };

        $toFloat = static function ($value): ?float {
            if (is_numeric($value)) {
                return (float) $value;
            }

            if (!is_string($value)) {
                return null;
            }

            $normalized = trim($value);
            if ($normalized === '') {
                return null;
            }

            $normalized = str_replace(' ', '', $normalized);

            if (str_contains($normalized, ',') && str_contains($normalized, '.')) {
                if (strrpos($normalized, ',') > strrpos($normalized, '.')) {
                    $normalized = str_replace('.', '', $normalized);
                    $normalized = str_replace(',', '.', $normalized);
                } else {
                    $normalized = str_replace(',', '', $normalized);
                }
            } elseif (str_contains($normalized, ',')) {
                $normalized = str_replace(',', '.', $normalized);
            }

            return is_numeric($normalized) ? (float) $normalized : null;
        };

        $timestampCache = [];
        $toTimestamp = static function ($value) use (&$timestampCache): int {
            if ($value === null || $value === '') {
                return 0;
            }

            $cacheKey = is_scalar($value) ? (string) $value : json_encode($value);
            if ($cacheKey !== false && isset($timestampCache[$cacheKey])) {
                return $timestampCache[$cacheKey];
            }

            $timestamp = 0;
            try {
                $timestamp = \Carbon\Carbon::parse((string) $value)->timestamp;
            } catch (\Throwable $exception) {
                $timestamp = 0;
            }

            if ($cacheKey !== false) {
                $timestampCache[$cacheKey] = $timestamp;
            }

            return $timestamp;
        };

        $formattedDateCache = [];
        $formatDate = static function ($value) use (&$formattedDateCache): string {
            if ($value === null || $value === '') {
                return '';
            }

            $cacheKey = is_scalar($value) ? (string) $value : json_encode($value);
            if ($cacheKey !== false && isset($formattedDateCache[$cacheKey])) {
                return $formattedDateCache[$cacheKey];
            }

            $formatted = '';
            try {
                $formatted = \Carbon\Carbon::parse((string) $value)->locale('id')->translatedFormat('d-M-y');
            } catch (\Throwable $exception) {
                $formatted = (string) $value;
            }

            if ($cacheKey !== false) {
                $formattedDateCache[$cacheKey] = $formatted;
            }

            return $formatted;
        };

        $statusColumn = $findColumn($availableColumns, ['Status']);
        $jenisColumn = $findColumn($availableColumns, ['Jenis', 'JenisKayu', 'Type', 'Tipe', 'Kategori']);
        $produkColumn = $findColumn($availableColumns, ['Produk', 'Product', 'NamaProduk', 'NamaBarang', 'Item']);
        $dateColumn = $findColumn($availableColumns, ['DateCreate', 'Tanggal', 'Date']);
        $noStColumn = $findColumn($availableColumns, ['NoST', 'NoSt']);
        $tebalColumn = $findColumn($availableColumns, ['Tebal']);
        $lebarColumn = $findColumn($availableColumns, ['Lebar']);
        $panjangColumn = $findColumn($availableColumns, ['Panjang']);
        $pcsColumn = $findColumn($availableColumns, ['Pcs', 'JmlhBatang', 'JumlahBatang']);
        $tonColumn = $findColumn($availableColumns, ['Ton', 'JmlhTon', 'JumlahTon']);
        $lokasiColumn = $findColumn($availableColumns, ['IdLokasi', 'Lokasi', 'Location', 'Description']);

        $columnHeaderOverrides = [];
        if ($noStColumn !== null) {
            $columnHeaderOverrides[$noStColumn] = 'Nomor ST';
        }
        if ($dateColumn !== null) {
            $columnHeaderOverrides[$dateColumn] = 'Tanggal';
        }
        if ($pcsColumn !== null) {
            $columnHeaderOverrides[$pcsColumn] = 'Jmlh Batang (Pcs)';
        }
        if ($lokasiColumn !== null) {
            $columnHeaderOverrides[$lokasiColumn] = 'Lokasi';
        }
        if ($tebalColumn !== null) {
            $columnHeaderOverrides[$tebalColumn] = 'Tebal (mm)';
        }
        if ($lebarColumn !== null) {
            $columnHeaderOverrides[$lebarColumn] = 'Lebar (mm)';
        }
        if ($panjangColumn !== null) {
            $columnHeaderOverrides[$panjangColumn] = 'Panjang (ft)';
        }

        $formatHeaderLabel = static function (string $column) use (
            $normalizeName,
            $headerLabelMap,
            $columnHeaderOverrides,
        ): string {
            if (isset($columnHeaderOverrides[$column])) {
                return $columnHeaderOverrides[$column];
            }

            $normalized = $normalizeName($column);

            return $headerLabelMap[$normalized] ?? $column;
        };

        $desiredColumns = [
            $noStColumn,
            $dateColumn,
            $tebalColumn,
            $lebarColumn,
            $panjangColumn,
            $lokasiColumn,
            $pcsColumn,
            $tonColumn,
        ];
        $tableColumns = array_values(
            array_unique(array_filter($desiredColumns, static fn($column): bool => $column !== null)),
        );

        $maxSortRows = (int) config('reports.stock_st_kering.max_sort_rows', 3000);
        if ($maxSortRows > 0 && count($rowsData) > $maxSortRows) {
            $sortedRows = $rowsData;
        } else {
            $sortedRowsDecorated = [];
            foreach ($rowsData as $row) {
                $sortedRowsDecorated[] = [
                    'row' => $row,
                    'jenis_sort' => strtolower((string) ($jenisColumn !== null ? $row[$jenisColumn] ?? '' : '')),
                    'produk_sort' => strtolower((string) ($produkColumn !== null ? $row[$produkColumn] ?? '' : '')),
                    'date_sort' => $toTimestamp($dateColumn !== null ? $row[$dateColumn] ?? null : null),
                    'nost_sort' => strtolower((string) ($noStColumn !== null ? $row[$noStColumn] ?? '' : '')),
                ];
            }

            usort($sortedRowsDecorated, static function (array $a, array $b): int {
                $jenisCompare = $a['jenis_sort'] <=> $b['jenis_sort'];
                if ($jenisCompare !== 0) {
                    return $jenisCompare;
                }

                $produkCompare = $a['produk_sort'] <=> $b['produk_sort'];
                if ($produkCompare !== 0) {
                    return $produkCompare;
                }

                $dateCompare = $a['date_sort'] <=> $b['date_sort'];
                if ($dateCompare !== 0) {
                    return $dateCompare;
                }

                return $a['nost_sort'] <=> $b['nost_sort'];
            });
            $sortedRows = array_map(static fn(array $item): array => $item['row'], $sortedRowsDecorated);
        }

        $numericColumns = [];
        foreach ($tableColumns as $column) {
            $numericColumns[$column] = false;
            foreach ($rowsData as $row) {
                $value = $row[$column] ?? null;
                if ($value === null || $value === '') {
                    continue;
                }

                $numericColumns[$column] = $toFloat($value) !== null;
                break;
            }
        }

        $grouped = [];
        foreach ($sortedRows as $row) {
            $jenis = trim((string) ($jenisColumn !== null ? $row[$jenisColumn] ?? '' : ''));
            $produk = trim((string) ($produkColumn !== null ? $row[$produkColumn] ?? '' : ''));
            $jenis = $jenis !== '' ? $jenis : 'Tanpa Jenis';
            $produk = $produk !== '' ? $produk : 'Tanpa Grade';
            $grouped[$jenis][$produk]['rows'][] = $row;
            $grouped[$jenis][$produk]['subtotal_pcs'] =
                ($grouped[$jenis][$produk]['subtotal_pcs'] ?? 0.0) +
                ($pcsColumn !== null ? $toFloat($row[$pcsColumn] ?? null) ?? 0.0 : 0.0);
            $grouped[$jenis][$produk]['subtotal_ton'] =
                ($grouped[$jenis][$produk]['subtotal_ton'] ?? 0.0) +
                ($tonColumn !== null ? $toFloat($row[$tonColumn] ?? null) ?? 0.0 : 0.0);
        }

        $totalRows = count($sortedRows);
        $totalJenis = count($grouped);
        $totalProduk = 0;
        $totalPcs = 0.0;
        $totalTon = 0.0;
        $allProdukNames = [];
        foreach ($grouped as $jenis => $produkGroups) {
            $totalProduk += count($produkGroups);
            foreach ($produkGroups as $produkName => $produkData) {
                $allProdukNames[$produkName] = true;
                $totalPcs += (float) ($produkData['subtotal_pcs'] ?? 0.0);
                $totalTon += (float) ($produkData['subtotal_ton'] ?? 0.0);
            }
        }

        $summaryTotalRows = (int) ($summaryStats['total_rows'] ?? $totalRows);
        $summaryTotalJenis = (int) ($summaryStats['total_jenis'] ?? $totalJenis);
        $summaryTotalProduk = (int) ($summaryStats['total_produk'] ?? $totalProduk);
        $summaryTotalProdukUnik = (int) ($summaryStats['total_produk_unik'] ?? count($allProdukNames));
        $summaryTotalPcs = (float) ($summaryStats['total_pcs'] ?? $totalPcs);
        $summaryTotalTon = (float) ($summaryStats['total_ton'] ?? $totalTon);

        $pcsIndex = $pcsColumn !== null ? array_search($pcsColumn, $tableColumns, true) : false;
        $tonIndex = $tonColumn !== null ? array_search($tonColumn, $tableColumns, true) : false;

        $columnWidths = [];
        $noColumnWidth = 4.0;
        $dataColumnWidth = count($tableColumns) > 0 ? (100.0 - $noColumnWidth) / count($tableColumns) : 96.0;
        foreach ($tableColumns as $column) {
            $columnWidths[$column] = $dataColumnWidth;
        }
        $columnWidths['__no__'] = $noColumnWidth;
        $noWidthStyle = 'width: ' . number_format((float) ($columnWidths['__no__'] ?? 4.0), 6, '.', ',') . '%;';
        $columnWidthStyles = [];
        foreach ($tableColumns as $column) {
            $columnWidthStyles[$column] =
                'width: ' . number_format((float) ($columnWidths[$column] ?? 0.0), 6, '.', ',') . '%;';
        }
    @endphp

    <h1 class="report-title">Laporan Stock ST Kering</h1>
    <p class="report-subtitle">
        Per {{ \Carbon\Carbon::parse((string) $endDate)->locale('id')->translatedFormat('d-M-y') }}
    </p>

    @forelse ($grouped as $jenisName => $produkGroups)
        <p class="jenis-title">{{ $jenisName }}</p>
        @php
            $jenisTotalPcs = (float) collect($produkGroups)->sum(
                static fn(array $produkData): float => (float) ($produkData['subtotal_pcs'] ?? 0.0),
            );
            $jenisTotalTon = (float) collect($produkGroups)->sum(
                static fn(array $produkData): float => (float) ($produkData['subtotal_ton'] ?? 0.0),
            );
        @endphp
        @foreach ($produkGroups as $produkName => $produkData)
            @php
                $produkRows = $produkData['rows'] ?? [];
                $subtotalPcs = (float) ($produkData['subtotal_pcs'] ?? 0.0);
                $subtotalTon = (float) ($produkData['subtotal_ton'] ?? 0.0);
                $isLastProdukInJenis = $loop->last;
            @endphp
            <p class="produk-title">{{ $produkName }}</p>
            <table class="report-table">
                <thead>
                    <tr class="headers-row">
                        <th style="{{ $noWidthStyle }}">No</th>
                        @foreach ($tableColumns as $column)
                            <th style="{{ $columnWidthStyles[$column] ?? '' }}">{{ $formatHeaderLabel($column) }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($produkRows as $row)
                        <tr class="data-row {{ $loop->odd ? 'row-odd' : 'row-even' }}">
                            <td class="data-cell center" style="{{ $noWidthStyle }}">{{ $loop->iteration }}</td>
                            @foreach ($tableColumns as $column)
                                @php
                                    $value = $row[$column] ?? null;
                                    $floatValue = $toFloat($value);
                                    $numeric = (bool) ($numericColumns[$column] ?? false);
                                    $isTonColumn = $tonColumn !== null && $column === $tonColumn;
                                    $isPcsColumn = $pcsColumn !== null && $column === $pcsColumn;
                                    $isDateColumn = $dateColumn !== null && $column === $dateColumn;
                                    $columnStyle = $columnWidthStyles[$column] ?? '';
                                @endphp
                                @if ($isDateColumn)
                                    <td class="data-cell center" style="{{ $columnStyle }}">{{ $formatDate($value) }}
                                    </td>
                                @elseif ($isTonColumn)
                                    <td class="data-cell number" style="{{ $columnStyle }}">
                                        {{ $floatValue !== null ? number_format($floatValue, 4, '.', ',') : '' }}
                                    </td>
                                @elseif ($isPcsColumn)
                                    <td class="data-cell number" style="{{ $columnStyle }}">
                                        {{ $floatValue !== null ? number_format($floatValue, 0, '.', ',') : '' }}
                                    </td>
                                @elseif ($numeric)
                                    <td class="data-cell number" style="{{ $columnStyle }}">
                                        {{ $floatValue !== null ? number_format($floatValue, 0, '.', ',') : '' }}
                                    </td>
                                @else
                                    <td class="data-cell" style="{{ $columnStyle }}">{{ (string) $value }}</td>
                                @endif
                            @endforeach
                        </tr>
                    @endforeach
                    @if (count($produkRows) > 0)
                        <tr class="subtotal-row totals-row">
                            @if (is_int($pcsIndex) || is_int($tonIndex))
                                @php
                                    $firstSummaryIndex = collect([$pcsIndex, $tonIndex])
                                        ->filter(static fn($index): bool => is_int($index))
                                        ->min();
                                    $firstSummaryIndex = is_int($firstSummaryIndex)
                                        ? $firstSummaryIndex
                                        : count($tableColumns);
                                @endphp
                                @if ($firstSummaryIndex >= 2)
                                    <td colspan="{{ $firstSummaryIndex + 1 }}"
                                        style="font-weight: bold; text-align: center;">
                                        Sub Total {{ $produkName }}
                                    </td>
                                    @for ($idx = $firstSummaryIndex; $idx < count($tableColumns); $idx++)
                                        @php $summaryColumn = $tableColumns[$idx]; @endphp
                                        @if ($pcsColumn !== null && $summaryColumn === $pcsColumn)
                                            <td class="number" style="font-weight: bold">
                                                {{ number_format($subtotalPcs, 0, '.', ',') }}
                                            </td>
                                        @elseif ($tonColumn !== null && $summaryColumn === $tonColumn)
                                            <td class="number" style="font-weight: bold">
                                                {{ number_format($subtotalTon, 4, '.', ',') }}
                                            </td>
                                        @else
                                            <td></td>
                                        @endif
                                    @endfor
                                @else
                                    @php
                                        $compactParts = [];
                                        if ($pcsColumn !== null) {
                                            $compactParts[] = number_format($subtotalPcs, 0, '.', ',').' Pcs';
                                        }
                                        if ($tonColumn !== null) {
                                            $compactParts[] = number_format($subtotalTon, 4, '.', ',').' Ton';
                                        }
                                    @endphp
                                    <td colspan="{{ count($tableColumns) + 1 }}"
                                        style="font-weight: bold; text-align: center;">
                                        Sub Total {{ $produkName }} : {{ implode(' / ', $compactParts) }}
                                    </td>
                                @endif
                            @else
                                <td colspan="{{ count($tableColumns) + 1 }}" style="text-align: center">
                                    Sub Total {{ $produkName }}
                                    : {{ count($produkRows) }} baris</td>
                            @endif
                        </tr>
                    @endif
                    @if ($isLastProdukInJenis)
                        <tr class="subtotal-row totals-row">
                            @if (is_int($pcsIndex) || is_int($tonIndex))
                                @php
                                    $firstSummaryIndex = collect([$pcsIndex, $tonIndex])
                                        ->filter(static fn($index): bool => is_int($index))
                                        ->min();
                                    $firstSummaryIndex = is_int($firstSummaryIndex)
                                        ? $firstSummaryIndex
                                        : count($tableColumns);
                                @endphp
                                @if ($firstSummaryIndex >= 2)
                                    <td colspan="{{ $firstSummaryIndex + 1 }}"
                                        style="font-weight: bold; text-align: center;">
                                        Total {{ $jenisName }}
                                    </td>
                                    @for ($idx = $firstSummaryIndex; $idx < count($tableColumns); $idx++)
                                        @php $summaryColumn = $tableColumns[$idx]; @endphp
                                        @if ($pcsColumn !== null && $summaryColumn === $pcsColumn)
                                            <td class="number" style="font-weight: bold">
                                                {{ number_format($jenisTotalPcs, 0, '.', ',') }}
                                            </td>
                                        @elseif ($tonColumn !== null && $summaryColumn === $tonColumn)
                                            <td class="number" style="font-weight: bold">
                                                {{ number_format($jenisTotalTon, 4, '.', ',') }}
                                            </td>
                                        @else
                                            <td></td>
                                        @endif
                                    @endfor
                                @else
                                    @php
                                        $compactParts = [];
                                        if ($pcsColumn !== null) {
                                            $compactParts[] = number_format($jenisTotalPcs, 0, '.', ',').' Pcs';
                                        }
                                        if ($tonColumn !== null) {
                                            $compactParts[] = number_format($jenisTotalTon, 4, '.', ',').' Ton';
                                        }
                                    @endphp
                                    <td colspan="{{ count($tableColumns) + 1 }}"
                                        style="font-weight: bold; text-align: center;">
                                        Total {{ $jenisName }} : {{ implode(' / ', $compactParts) }}
                                    </td>
                                @endif
                            @else
                                <td colspan="{{ count($tableColumns) + 1 }}" style="text-align: center">
                                    Total {{ $jenisName }}
                                </td>
                            @endif
                        </tr>
                    @endif
                </tbody>
            </table>
        @endforeach
    @empty
        <table>
            <tbody>
                <tr>
                    <td class="center">Tidak ada data.</td>
                </tr>
            </tbody>
        </table>
    @endforelse

    </body>

</html>
