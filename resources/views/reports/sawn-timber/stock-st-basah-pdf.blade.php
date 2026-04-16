<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif:ital,wght@0,100..900;1,100..900&display=swap"
        rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
        }

        @page {
            margin: 18mm 10mm 18mm 10mm;
            footer: html_reportFooter;
        }

        body {
            margin: 0;
            font-family: "Noto Serif", serif;
            font-size: 10px;
            line-height: 1.2;
            color: #000;
        }

        .report-title {
            text-align: center;
            margin: 0;
            font-size: 16px;
            font-weight: bold;
        }

        .report-subtitle {
            text-align: center;
            margin: 2px 0 20px 0;
            font-size: 12px;
            color: #636466;
        }

        table {
            width: 65%;
            border-collapse: collapse;
            page-break-inside: auto;
            border: 1;
            table-layout: fixed;
            margin: 2px 0 6px 10px;
        }

        /* .report-table {
            border-collapse: collapse;
            border-spacing: 0;
            border: 1px solid #000;
            margin: 2px 0 0 10px;
        } */

        thead {
            display: table-header-group;
        }

        tfoot {
            display: table-row-group;
        }

        tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 3px 4px;
            vertical-align: middle;
        }

        th {
            text-align: center;
            font-weight: bold;
            background: #fff;
        }

        td.center {
            text-align: center;
        }

        td.number {
            text-align: right;
            font-family: "Calibri", "DejaVu Sans", sans-serif;
        }

        .row-odd td {
            background: #c9d1df;
        }

        .row-even td {
            background: #eef2f8;
        }

        .jenis-title {
            margin: 10px 0 0 0;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .produk-title {
            margin: 6px 0 0 10px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .subtotal-row td {
            font-weight: 700;
            background: #f6f7fb !important;
        }

        .summary-title {
            margin: 12px 0 4px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .summary-table {
            width: 55%;
        }

        .jenis-summary {
            width: 70%;
            margin: 0 0 10px -4px;
            font-size: 11px;
            font-weight: bold;
            border: 0 !important;
            border-left: 0 ! important;
            border-right: 0 ! important;
            border-collapse: collapse;
            background: transparent;
        }

        table.jenis-summary tbody tr td {
            border: 0;
            border-collapse: collapse;
            border-spacing: 0;
        }


        tfoot,
        .table-end-line {
            display: none !important;
        }

        table.data-table,
        table.report-table,
        table {
            border-bottom: 0 !important;
        }

        table.data-table tbody td,
        table.report-table tbody td,
        tbody td {
            border-top: 0 !important;
            border-bottom: 0 !important;
        }

        table.data-table tbody tr td,
        table.report-table tbody tr td,
        tbody tr td {
            border-top: 0 !important;
        }

        table.data-table tbody tr.subtotal-row td,
        table.data-table tbody tr.total-row td,
        table.data-table tbody tr.totals-row td,
        table.data-table tbody tr.group-total-row td,
        table.data-table tbody tr.group-subtotal-row td,
        table.report-table tbody tr.subtotal-row td,
        table.report-table tbody tr.total-row td,
        table.report-table tbody tr.totals-row td,
        table.report-table tbody tr.group-total-row td,
        table.report-table tbody tr.group-subtotal-row td,
        tbody tr.subtotal-row td,
        tbody tr.total-row td,
        tbody tr.totals-row td,
        tbody tr.group-total-row td,
        tbody tr.group-subtotal-row td {
            border-top: 1px solid #000 !important;
            border-bottom: 1px solid #000 !important;
            font-weight: bold;
            background: #fff !important;
        }

        table.data-table tbody tr:last-child td,
        table.report-table tbody tr:last-child td,
        tbody tr:last-child td {
            border-bottom: 1px solid #000 !important;
        }

        table.data-table tbody td:nth-child(7),
        table.report-table tbody td:nth-child(7),
        tbody td:nth-child(7) {
            text-align: center !important;
        }

        @include('reports.partials.pdf-footer-table-style') .headers-row th {
            font-weight: bold;
            font-size: 11px;
            border-top: 0;
            border-bottom: 1px solid #000;
        }

        .totals-row td {
            font-weight: bold;
            font-size: 11px;
            border: 1px solid #000;
        }

        .report-table tbody tr.data-row td.data-cell {
            border-top: 0 !important;
            border-bottom: 0 !important;
            border-left: 1px solid #000 !important;
            border-right: 1px solid #000 !important;
        }

        .table-end-line td {
            border: 0 !important;
            border-top: 1px solid #000 !important;
            padding: 0 !important;
            height: 0 !important;
            line-height: 0 !important;
            background: transparent !important;
        }
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
            'jlhbtg' => 'Jmlh<br/>Batang',
            'jmlhbatang' => 'Jmlh<br/>Batang',
            'jumlahbatang' => 'Jmlh<br/>Batang',
            'pcs' => 'Jmlh<br/>Batang',
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
                $normalized = str_replace(',', '.');
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
            $columnHeaderOverrides[$pcsColumn] = 'Jmlh Batang';
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

        $maxSortRows = (int) config('reports.stock_st_basah.max_sort_rows', 3000);
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

        $pcsIndex = $pcsColumn !== null ? array_search($pcsColumn, $tableColumns, true) : false;
        $tonIndex = $tonColumn !== null ? array_search($tonColumn, $tableColumns, true) : false;
        $equalColumnWidth = 100 / max(1, count($tableColumns) + 1);
    @endphp

    <h1 class="report-title">Laporan Stock ST Basah</h1>
    <p class="report-subtitle">
        Per {{ \Carbon\Carbon::parse((string) $endDate)->locale('id')->translatedFormat('d-M-y') }}
    </p>

    @forelse ($grouped as $jenisName => $produkGroups)
        @php
            $jenisTotalPcs = 0.0;
            $jenisTotalTon = 0.0;
            foreach ($produkGroups as $produkData) {
                $jenisTotalPcs += (float) ($produkData['subtotal_pcs'] ?? 0.0);
                $jenisTotalTon += (float) ($produkData['subtotal_ton'] ?? 0.0);
            }

            $firstSummaryIndexForJenis = collect([$pcsIndex, $tonIndex])
                ->filter(static fn($index): bool => is_int($index))
                ->min();
            $firstSummaryIndexForJenis = is_int($firstSummaryIndexForJenis)
                ? $firstSummaryIndexForJenis
                : count($tableColumns);
        @endphp
        <p class="jenis-title">{{ $jenisName }}</p>
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
                        <th>No</th>
                        @foreach ($tableColumns as $column)
                            <th>{{ $formatHeaderLabel($column) }}</th>
                        @endforeach
                    </tr>
                </thead>

                <tbody>
                    @foreach ($produkRows as $row)
                        <tr class="data-row {{ $loop->odd ? 'row-odd' : 'row-even' }}">
                            <td class="data-cell center">{{ $loop->iteration }}</td>
                            @foreach ($tableColumns as $column)
                                @php
                                    $value = $row[$column] ?? null;
                                    $floatValue = $toFloat($value);
                                    $numeric = (bool) ($numericColumns[$column] ?? false);
                                    $isTonColumn = $tonColumn !== null && $column === $tonColumn;
                                    $isPcsColumn = $pcsColumn !== null && $column === $pcsColumn;
                                    $isDateColumn = $dateColumn !== null && $column === $dateColumn;
                                @endphp
                                @if ($isDateColumn)
                                    <td class="data-cell center">{{ $formatDate($value) }}</td>
                                @elseif ($isTonColumn)
                                    <td class="data-cell number">
                                        {{ $floatValue !== null ? number_format($floatValue, 4, '.', ',') : '' }}
                                    </td>
                                @elseif ($isPcsColumn)
                                    <td class="data-cell number">
                                        {{ $floatValue !== null ? number_format($floatValue, 0, '.', ',') : '' }}
                                    </td>
                                @elseif ($numeric)
                                    <td class="data-cell number">
                                        {{ $floatValue !== null ? number_format($floatValue, 0, '.', ',') : '' }}
                                    </td>
                                @else
                                    <td class="data-cell">{{ (string) $value }}</td>
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
                                <td colspan="{{ $firstSummaryIndex + 1 }}" class="center" style="font-weight: bold">
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
                                <td colspan="{{ count($tableColumns) + 1 }}" class="number">Jumlah {{ $produkName }}
                                    : {{ count($produkRows) }} baris</td>
                            @endif
                        </tr>
                    @endif
                </tbody>
            </table>
        @endforeach
        <table class="jenis-summary">
            <tbody>
                <tr>
                    <td class="left" style="font-weight: bold; width: 70%">
                        Total {{ $jenisName }}
                    </td>
                    <td class="number">{{ number_format($jenisTotalPcs, 0, '.', ',') }}</td>
                    <td class="number" style="text-align: left;">{{ number_format($jenisTotalTon, 4, '.', ',') }}</td>
                </tr>
            </tbody>
        </table>
    @empty
        <table>
            <tbody>
                <tr>
                    <td class="center">Tidak ada data.</td>
                </tr>
            </tbody>
        </table>
    @endforelse

    @include('reports.partials.pdf-footer-table')
</body>

</html>
