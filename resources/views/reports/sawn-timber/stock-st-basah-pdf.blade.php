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
            font-size: 14px;
            font-weight: bold;
        }

        .report-subtitle {
            text-align: center;
            margin: 2px 0 8px 0;
            font-size: 10px;
            color: #555;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
            page-break-inside: auto;
        }

        thead {
            display: table-header-group;
        }

        tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }

        th,
        td {
            border: 1px solid #666;
            padding: 3px 4px;
            vertical-align: middle;
        }

        th {
            text-align: center;
            font-weight: 700;
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
            margin: 10px 0 4px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .produk-title {
            margin: 6px 0 3px;
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

        .footer-wrap {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }

        .footer-left {
            font-size: 8px;
            font-style: italic;
        }

        .footer-right {
            font-size: 8px;
            font-style: italic;
            text-align: right;
        }
    </style>
</head>

<body>
    @php
        $rowsData =
            isset($rows) && is_iterable($rows) ? (is_array($rows) ? $rows : collect($rows)->values()->all()) : [];
        $availableColumns = array_keys($rowsData[0] ?? []);
        $generatedByName = $generatedBy?->name ?? 'sistem';
        $generatedAtText = $generatedAt->copy()->locale('id')->translatedFormat('d M Y H:i');

        $normalizeName = static function (?string $name): string {
            $raw = $name ?? '';

            return strtolower(preg_replace('/[^a-z0-9]/', '', $raw) ?? '');
        };

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

        $toTimestamp = static function ($value): int {
            if ($value === null || $value === '') {
                return 0;
            }

            try {
                return \Carbon\Carbon::parse((string) $value)->timestamp;
            } catch (\Throwable $exception) {
                return 0;
            }
        };

        $formatDate = static function ($value): string {
            if ($value === null || $value === '') {
                return '';
            }

            try {
                return \Carbon\Carbon::parse((string) $value)->format('d M Y');
            } catch (\Throwable $exception) {
                return (string) $value;
            }
        };

        $isNumericColumn = static function (string $column, array $rows) use ($toFloat): bool {
            foreach ($rows as $row) {
                $value = $row[$column] ?? null;
                if ($value === null || $value === '') {
                    continue;
                }

                return $toFloat($value) !== null;
            }

            return false;
        };

        $statusColumn = $findColumn($availableColumns, ['Status']);
        $jenisColumn = $findColumn($availableColumns, ['Jenis', 'JenisKayu', 'Type', 'Tipe', 'Kategori']);
        $produkColumn = $findColumn($availableColumns, ['Produk', 'Product', 'NamaProduk', 'NamaBarang', 'Item']);
        $dateColumn = $findColumn($availableColumns, ['DateCreate', 'Tanggal', 'Date']);
        $noStColumn = $findColumn($availableColumns, ['NoST', 'NoSt']);
        $pcsColumn = $findColumn($availableColumns, ['Pcs', 'JmlhBatang', 'JumlahBatang']);
        $tonColumn = $findColumn($availableColumns, ['Ton', 'JmlhTon', 'JumlahTon']);
        $lokasiColumn = $findColumn($availableColumns, ['Lokasi', 'Location', 'Description']);

        $excludedColumns = array_filter(
            [$statusColumn, $jenisColumn, $produkColumn],
            static fn($column): bool => $column !== null,
        );

        $preferredOrder = ['NoST', 'DateCreate', 'Tebal', 'Lebar', 'Panjang', 'Pcs', 'Ton', 'Lokasi'];
        $tableColumns = [];
        foreach ($preferredOrder as $candidate) {
            $matched = $findColumn($availableColumns, [$candidate]);
            if (
                $matched !== null &&
                !in_array($matched, $excludedColumns, true) &&
                !in_array($matched, $tableColumns, true)
            ) {
                $tableColumns[] = $matched;
            }
        }
        foreach ($availableColumns as $column) {
            if (!in_array($column, $excludedColumns, true) && !in_array($column, $tableColumns, true)) {
                $tableColumns[] = $column;
            }
        }

        $sortedRows = $rowsData;
        usort($sortedRows, static function (array $a, array $b) use (
            $jenisColumn,
            $produkColumn,
            $dateColumn,
            $noStColumn,
            $toTimestamp,
        ): int {
            $jenisA = strtolower((string) ($jenisColumn !== null ? $a[$jenisColumn] ?? '' : ''));
            $jenisB = strtolower((string) ($jenisColumn !== null ? $b[$jenisColumn] ?? '' : ''));
            $jenisCompare = $jenisA <=> $jenisB;
            if ($jenisCompare !== 0) {
                return $jenisCompare;
            }

            $produkA = strtolower((string) ($produkColumn !== null ? $a[$produkColumn] ?? '' : ''));
            $produkB = strtolower((string) ($produkColumn !== null ? $b[$produkColumn] ?? '' : ''));
            $produkCompare = $produkA <=> $produkB;
            if ($produkCompare !== 0) {
                return $produkCompare;
            }

            $dateCompare =
                $toTimestamp($dateColumn !== null ? $a[$dateColumn] ?? null : null) <=>
                $toTimestamp($dateColumn !== null ? $b[$dateColumn] ?? null : null);
            if ($dateCompare !== 0) {
                return $dateCompare;
            }

            $noA = strtolower((string) ($noStColumn !== null ? $a[$noStColumn] ?? '' : ''));
            $noB = strtolower((string) ($noStColumn !== null ? $b[$noStColumn] ?? '' : ''));

            return $noA <=> $noB;
        });

        $grouped = [];
        foreach ($sortedRows as $row) {
            $jenis = trim((string) ($jenisColumn !== null ? $row[$jenisColumn] ?? '' : ''));
            $produk = trim((string) ($produkColumn !== null ? $row[$produkColumn] ?? '' : ''));
            $jenis = $jenis !== '' ? $jenis : 'Tanpa Jenis';
            $produk = $produk !== '' ? $produk : 'Tanpa Produk';
            $grouped[$jenis][$produk][] = $row;
        }

        $totalRows = count($sortedRows);
        $totalJenis = count($grouped);
        $totalProduk = 0;
        $totalPcs = 0.0;
        $totalTon = 0.0;
        $allProdukNames = [];
        $allLokasi = [];
        foreach ($grouped as $jenis => $produkGroups) {
            $totalProduk += count($produkGroups);
            foreach ($produkGroups as $produkName => $produkRows) {
                $allProdukNames[$produkName] = true;
                foreach ($produkRows as $row) {
                    $pcsValue = $pcsColumn !== null ? $toFloat($row[$pcsColumn] ?? null) : null;
                    $tonValue = $tonColumn !== null ? $toFloat($row[$tonColumn] ?? null) : null;
                    $totalPcs += $pcsValue ?? 0.0;
                    $totalTon += $tonValue ?? 0.0;
                    if ($lokasiColumn !== null) {
                        $lokasiValue = trim((string) ($row[$lokasiColumn] ?? ''));
                        if ($lokasiValue !== '') {
                            $allLokasi[$lokasiValue] = true;
                        }
                    }
                }
            }
        }

        $summaryColspan = max(1, count($tableColumns) - 1);
        $pcsIndex = $pcsColumn !== null ? array_search($pcsColumn, $tableColumns, true) : false;
        $tonIndex = $tonColumn !== null ? array_search($tonColumn, $tableColumns, true) : false;
    @endphp

    <h1 class="report-title">Laporan Stock ST Basah</h1>
    <p class="report-subtitle">
        {{ \Carbon\Carbon::parse((string) $endDate)->locale('id')->translatedFormat('d F Y') }}
    </p>

    @forelse ($grouped as $jenisName => $produkGroups)
        <p class="jenis-title">{{ $jenisName }}</p>
        @foreach ($produkGroups as $produkName => $produkRows)
            @php
                $subtotalPcs = 0.0;
                $subtotalTon = 0.0;
                foreach ($produkRows as $subtotalRow) {
                    $subtotalPcs += $pcsColumn !== null ? $toFloat($subtotalRow[$pcsColumn] ?? null) ?? 0.0 : 0.0;
                    $subtotalTon += $tonColumn !== null ? $toFloat($subtotalRow[$tonColumn] ?? null) ?? 0.0 : 0.0;
                }
            @endphp
            <p class="produk-title">{{ $produkName }}</p>
            <table>
                <thead>
                    <tr>
                        <th style="width: 34px;">No</th>
                        @foreach ($tableColumns as $column)
                            <th>{{ $column }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($produkRows as $row)
                        <tr class="{{ $loop->odd ? 'row-odd' : 'row-even' }}">
                            <td class="center">{{ $loop->iteration }}</td>
                            @foreach ($tableColumns as $column)
                                @php
                                    $value = $row[$column] ?? null;
                                    $numeric = $isNumericColumn($column, $produkRows);
                                    $isTonColumn = $tonColumn !== null && $column === $tonColumn;
                                    $isPcsColumn = $pcsColumn !== null && $column === $pcsColumn;
                                    $isDateColumn = $dateColumn !== null && $column === $dateColumn;
                                @endphp
                                @if ($isDateColumn)
                                    <td class="center">{{ $formatDate($value) }}</td>
                                @elseif ($isTonColumn)
                                    <td class="number">
                                        {{ ($toFloat($value) ?? null) !== null ? number_format((float) $toFloat($value), 4, '.', '') : '' }}
                                    </td>
                                @elseif ($isPcsColumn)
                                    <td class="number">
                                        {{ ($toFloat($value) ?? null) !== null ? number_format((float) $toFloat($value), 0, '.', '') : '' }}
                                    </td>
                                @elseif ($numeric)
                                    <td class="number">
                                        {{ ($toFloat($value) ?? null) !== null ? number_format((float) $toFloat($value), 0, '.', '') : '' }}
                                    </td>
                                @else
                                    <td>{{ (string) $value }}</td>
                                @endif
                            @endforeach
                        </tr>
                    @endforeach
                    @if ($totalRows > 0)
                        <tr class="subtotal-row">
                            @if (is_int($pcsIndex) || is_int($tonIndex))
                                @php
                                    $firstSummaryIndex = collect([$pcsIndex, $tonIndex])
                                        ->filter(static fn($index): bool => is_int($index))
                                        ->min();
                                    $firstSummaryIndex = is_int($firstSummaryIndex)
                                        ? $firstSummaryIndex
                                        : count($tableColumns);
                                @endphp
                                <td colspan="{{ $firstSummaryIndex + 1 }}" class="number">Jumlah {{ $produkName }} :
                                </td>
                                @for ($idx = $firstSummaryIndex; $idx < count($tableColumns); $idx++)
                                    @php $summaryColumn = $tableColumns[$idx]; @endphp
                                    @if ($pcsColumn !== null && $summaryColumn === $pcsColumn)
                                        <td class="number">{{ number_format($subtotalPcs, 0, '.', '') }}</td>
                                    @elseif ($tonColumn !== null && $summaryColumn === $tonColumn)
                                        <td class="number">{{ number_format($subtotalTon, 4, '.', '') }}</td>
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
    @empty
        <table>
            <tbody>
                <tr>
                    <td class="center">Tidak ada data.</td>
                </tr>
            </tbody>
        </table>
    @endforelse

    <p class="summary-title">Summary</p>
    <table class="summary-table">
        <tbody>
            <tr>
                <th style="width: 70%;">Keterangan</th>
                <th>Nilai</th>
            </tr>
            <tr>
                <td>Total jumlah data</td>
                <td class="center">{{ number_format($totalRows, 0, '.', '') }} baris</td>
            </tr>
            <tr>
                <td>Total jenis</td>
                <td class="center">{{ number_format($totalJenis, 0, '.', '') }}</td>
            </tr>
            <tr>
                <td>Total produk (per grup jenis)</td>
                <td class="center">{{ number_format($totalProduk, 0, '.', '') }}</td>
            </tr>
            <tr>
                <td>Total produk unik</td>
                <td class="center">{{ number_format(count($allProdukNames), 0, '.', '') }}</td>
            </tr>
            <tr>
                <td>Total Jumlah Batang</td>
                <td class="center">{{ number_format($totalPcs, 0, '.', '') }} Batang</td>
            </tr>
            <tr>
                <td>Total ton</td>
                <td class="center">{{ number_format($totalTon, 4, '.', '') }} Ton</td>
            </tr>

        </tbody>
    </table>

    <htmlpagefooter name="reportFooter">
        <div class="footer-wrap">
            <div class="footer-left">Dicetak oleh: {{ $generatedByName }} pada {{ $generatedAtText }}</div>
            <div class="footer-right">Halaman {PAGENO} dari {nbpg}</div>
        </div>
    </htmlpagefooter>
    <sethtmlpagefooter name="reportFooter" value="on" />
</body>

</html>
