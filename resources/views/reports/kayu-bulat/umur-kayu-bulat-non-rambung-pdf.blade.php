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
            margin: 24mm 12mm 20mm 12mm;
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

        .section-title {
            margin: 10px 0 6px 0;
            font-size: 12px;
            font-weight: bold;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
            page-break-inside: auto;
            table-layout: fixed;
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
            border: 1px solid #9ca3af;
            padding: 2px 4px;
            vertical-align: middle;
        }

        th {
            text-align: center;
            font-weight: 700;
            background: #ffffff;
            color: #000;
        }

        td.center {
            text-align: center;
        }

        td.number {
            text-align: right;
            white-space: nowrap;
            font-family: "Calibry", "Calibri", "DejaVu Sans", sans-serif;
        }

        .row-odd td {
            background: #c9d1df;
        }

        .row-even td {
            background: #eef2f8;
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

        .headers-row th {
            font-weight: bold;
            font-size: 11px;
            border: 1.5px solid #000;
        }

        .summary-section {
            margin-top: 12px;
        }

        .group-summary {
            width: 100%;
            text-align: right;
            margin: 2px 0 14px 0;
            font-size: 11px;
        }

        .group-summary .label {
            display: inline-block;
            min-width: 56px;
            text-align: left;
            font-weight: bold;
        }

        .totals-row td {
            font-weight: bold;
            font-size: 11px;
            border: 1.5px solid #000;
            background: #fff;
        }

        .summary-table {
            width: 50%;
            table-layout: auto;
        }

        .summary-table th,
        .summary-table td {
            padding: 4px 6px;
            font-size: 11px;
        }

        .summary-table th {
            text-align: left;
            width: 40%;
        }
    </style>
</head>

<body>
    @php
        $rowsData =
            isset($rows) && is_iterable($rows) ? (is_array($rows) ? $rows : collect($rows)->values()->all()) : [];
        $columns = array_keys($rowsData[0] ?? []);
        $start = isset($startDate) ? \Carbon\Carbon::parse($startDate)->locale('id')->translatedFormat('d-M-y') : null;
        $end = isset($endDate) ? \Carbon\Carbon::parse($endDate)->locale('id')->translatedFormat('d-M-y') : null;
        $generatedByName = $generatedBy?->name ?? 'sistem';
        $generatedAtText = $generatedAt->copy()->locale('id')->translatedFormat('d-M-y H:i');

        $normalize = static function (string $value): string {
            return strtolower(str_replace([' ', '_', '.'], '', trim($value)));
        };

        $findColumnByCandidates = static function (array $availableColumns, array $candidates) use (
            $normalize,
        ): ?string {
            foreach ($candidates as $candidate) {
                $normalizedCandidate = $normalize($candidate);
                foreach ($availableColumns as $column) {
                    if ($normalize($column) === $normalizedCandidate) {
                        return $column;
                    }
                }
            }

            return null;
        };

        $formatHeaderLabel = static function (string $column) use ($normalize): string {
            $normalized = $normalize($column);

            return match ($normalized) {
                'nokb' => 'No KB',
                'notruk', 'truk', 'mtruk' => 'No Truk',
                'ton' => 'Berat Muatan (Ton)',
                default => $column,
            };
        };

        $isNumericColumn = static function (string $column, array $rows): bool {
            foreach ($rows as $row) {
                $value = $row[$column] ?? null;
                if ($value === null || $value === '') {
                    continue;
                }

                if (is_numeric($value)) {
                    return true;
                }

                if (!is_string($value)) {
                    return false;
                }

                $normalized = str_replace([' ', ','], ['', '.'], trim($value));

                return is_numeric($normalized);
            }

            return false;
        };

        $statusColumn = $findColumnByCandidates($columns, ['Status']);
        $tonColumn = $findColumnByCandidates($columns, ['Ton', 'JmlhTon', 'JumlahTon', 'Berat']);
        $truckColumn = $findColumnByCandidates($columns, ['Truk', 'NoTruk', 'No Truk']);
        $supplierColumn = $findColumnByCandidates($columns, ['Supplier', 'NamaSupplier', 'Nama Supplier']);
        $jenisKayuColumn = $findColumnByCandidates($columns, ['JenisKayu', 'Jenis Kayu', 'Jenis']);
        $lamaRacipColumn = $findColumnByCandidates($columns, ['Lama Racip', 'LamaRacip']);
        $lamaTungguColumn = $findColumnByCandidates($columns, ['Lama Tunggu', 'LamaTunggu']);

        if ($tonColumn === null) {
            foreach ($columns as $column) {
                if (str_contains($normalize($column), 'ton')) {
                    $tonColumn = $column;
                    break;
                }
            }
        }

        $displayColumns = array_values(
            array_filter($columns, static fn(string $column): bool => $column !== $statusColumn),
        );
        $displayColumns = array_values(
            array_filter(
                $displayColumns,
                static fn(string $column): bool => $column !== $truckColumn && $column !== $tonColumn,
            ),
        );
        if ($truckColumn !== null && in_array($truckColumn, $columns, true)) {
            $displayColumns[] = $truckColumn;
        }
        if ($tonColumn !== null && in_array($tonColumn, $columns, true)) {
            $displayColumns[] = $tonColumn;
        }

        $columnWidths = [];
        foreach ($displayColumns as $column) {
            $normalizedColumn = $normalize($column);
            $columnWidths[$column] = match (true) {
                str_contains($normalizedColumn, 'nokb') => 10,
                str_contains($normalizedColumn, 'tanggal') && !str_contains($normalizedColumn, 'racip') => 12,
                str_contains($normalizedColumn, 'namasupplier') => 15,
                str_contains($normalizedColumn, 'jeniskayu') => 11,
                str_contains($normalizedColumn, 'truk') => 7,
                str_contains($normalizedColumn, 'ton') => 8,
                str_contains($normalizedColumn, 'tanggalracip') => 14,
                str_contains($normalizedColumn, 'lamaracip') => 11,
                str_contains($normalizedColumn, 'lamatunggu') => 12,
                default => null,
            };
        }

        $knownWidthTotal = 0;
        $unknownColumns = [];
        foreach ($displayColumns as $column) {
            $width = $columnWidths[$column] ?? null;
            if (is_int($width)) {
                $knownWidthTotal += $width;
            } else {
                $unknownColumns[] = $column;
            }
        }

        $remainingWidth = max(0, 100 - $knownWidthTotal);
        $unknownCount = count($unknownColumns);
        if ($unknownCount > 0) {
            $baseWidth = intdiv($remainingWidth, $unknownCount);
            $remainder = $remainingWidth % $unknownCount;

            foreach ($unknownColumns as $index => $column) {
                $columnWidths[$column] = $baseWidth + ($index < $remainder ? 1 : 0);
            }
        }

        $finalColumnWidths = [];
        $fullWidthTotal = array_sum(
            array_map(static fn(string $column): int => (int) ($columnWidths[$column] ?? 0), $displayColumns),
        );
        $targetWidthWithoutNo = 96.0;
        foreach ($displayColumns as $column) {
            $base = (float) ($columnWidths[$column] ?? 0);
            $finalColumnWidths[$column] = $fullWidthTotal > 0 ? ($base / $fullWidthTotal) * $targetWidthWithoutNo : 0.0;
        }

        $formatCellValue = static function (mixed $value, string $column) use (
            $normalize,
            $tonColumn,
            $truckColumn,
            $lamaRacipColumn,
            $lamaTungguColumn,
        ): string {
            if ($value === null) {
                return '';
            }

            $normalizedColumn = $normalize($column);
            $textValue = trim((string) $value);
            $numericValue = is_numeric($value)
                ? (float) $value
                : (is_numeric(str_replace([' ', ','], ['', '.'], $textValue))
                    ? (float) str_replace([' ', ','], ['', '.'], $textValue)
                    : null);

            $isTonColumn =
                ($tonColumn !== null && $normalize($tonColumn) === $normalizedColumn) ||
                str_contains($normalizedColumn, 'ton');

            if ($isTonColumn && $numericValue !== null) {
                return number_format($numericValue, 4, '.', ',');
            }

            if (
                (($truckColumn !== null && $normalize($truckColumn) === $normalizedColumn) ||
                    ($lamaRacipColumn !== null && $normalize($lamaRacipColumn) === $normalizedColumn) ||
                    ($lamaTungguColumn !== null && $normalize($lamaTungguColumn) === $normalizedColumn)) &&
                $numericValue !== null
            ) {
                $formatted = (string) (int) round($numericValue);
                if (
                    ($lamaRacipColumn !== null && $normalize($lamaRacipColumn) === $normalizedColumn) ||
                    ($lamaTungguColumn !== null && $normalize($lamaTungguColumn) === $normalizedColumn)
                ) {
                    return "{$formatted} hari";
                }

                return $formatted;
            }

            if (preg_match('/^\\d{4}-\\d{2}-\\d{2}$/', $textValue) === 1) {
                try {
                    return \Carbon\Carbon::parse($textValue)->locale('id')->translatedFormat('d-M-y');
                } catch (\Throwable $exception) {
                    return $textValue;
                }
            }

            return $textValue;
        };

        $groupedRows = [];
        foreach ($rowsData as $row) {
            $statusRaw = $statusColumn !== null ? $row[$statusColumn] ?? null : null;
            $statusText = is_numeric($statusRaw) ? (string) (int) $statusRaw : trim((string) $statusRaw);

            $groupName = match ($statusText) {
                '0' => 'Masih Hidup',
                '1' => 'Sudah Mati',
                default => $statusText !== '' ? $statusText : 'Tanpa Status',
            };

            $groupedRows[$groupName][] = $row;
        }

        $groupOrder = ['Masih Hidup', 'Sudah Mati'];
        uksort($groupedRows, static function (string $left, string $right) use ($groupOrder): int {
            $leftIndex = array_search($left, $groupOrder, true);
            $rightIndex = array_search($right, $groupOrder, true);

            if ($leftIndex !== false && $rightIndex !== false) {
                return $leftIndex <=> $rightIndex;
            }
            if ($leftIndex !== false) {
                return -1;
            }
            if ($rightIndex !== false) {
                return 1;
            }

            return strcasecmp($left, $right);
        });

        $toFloat = static function (mixed $value): ?float {
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

        $sumTon = static function (array $rows, ?string $tonColumn) use ($toFloat): float {
            if ($tonColumn === null) {
                return 0.0;
            }

            $total = 0.0;
            foreach ($rows as $row) {
                $numeric = $toFloat($row[$tonColumn] ?? null);
                if ($numeric !== null) {
                    $total += $numeric;
                }
            }

            return $total;
        };

        $countTruck = static function (array $rows, ?string $truckColumn): int {
            if ($truckColumn === null) {
                return count($rows);
            }

            return count(
                array_filter($rows, static fn(array $row): bool => trim((string) ($row[$truckColumn] ?? '')) !== ''),
            );
        };

        $countDistinct = static function (array $rows, ?string $column): int {
            if ($column === null) {
                return 0;
            }

            $values = [];
            foreach ($rows as $row) {
                $value = trim((string) ($row[$column] ?? ''));
                if ($value === '') {
                    continue;
                }

                $values[$value] = true;
            }

            return count($values);
        };

        $summaryTotalSupplier = $countDistinct($rowsData, $supplierColumn);
        $summaryTotalJenisKayu = $countDistinct($rowsData, $jenisKayuColumn);
        $summaryTotalTruk = $countDistinct($rowsData, $truckColumn);
        $summaryTotalMuatan = $sumTon($rowsData, $tonColumn);
    @endphp

    <h1 class="report-title">Laporan Umur Kayu Bulat (NON RAMBUNG)</h1>
    <p class="report-subtitle">
        @if ($start && $end)
            Periode {{ $start }} s/d {{ $end }}
        @else
            Per-{{ $generatedAtText }}
        @endif
    </p>

    @forelse ($groupedRows as $groupName => $groupRows)
        <div class="section-title">Status : {{ $groupName }}</div>
        <table>
            <colgroup>
                <col style="width: 4%">
                @foreach ($displayColumns as $column)
                    <col style="width: {{ number_format((float) ($finalColumnWidths[$column] ?? 0), 4, '.', ',') }}%">
                @endforeach
            </colgroup>
            <thead>
                <tr class="headers-row">
                    <th>No</th>
                    @foreach ($displayColumns as $column)
                        <th>{{ $formatHeaderLabel($column) }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($groupRows as $row)
                    <tr class="{{ $loop->odd ? 'row-odd' : 'row-even' }}">
                        <td class="center">{{ $loop->iteration }}</td>
                        @foreach ($displayColumns as $column)
                            @php
                                $value = $row[$column] ?? null;
                                $isTonCell = $tonColumn !== null && $normalize($column) === $normalize($tonColumn);
                            @endphp
                            <td class="{{ $isTonCell ? 'number' : 'center' }}">
                                {{ $formatCellValue($value, $column) }}
                            </td>
                        @endforeach
                    </tr>
                @endforeach
                <tr class="totals-row">
                    @php
                        $hasTruckColumn = $truckColumn !== null && in_array($truckColumn, $displayColumns, true);
                        $hasTonColumn = $tonColumn !== null && in_array($tonColumn, $displayColumns, true);
                        $tailColumnsCount = ($hasTruckColumn ? 1 : 0) + ($hasTonColumn ? 1 : 0);
                        $totalLabelColspan = max(1, 1 + count($displayColumns) - $tailColumnsCount);
                    @endphp
                    <td class="center" colspan="{{ $totalLabelColspan }}">Total :</td>
                    @if ($hasTruckColumn)
                        <td class="center">{{ $countTruck($groupRows, $truckColumn) }} Truk</td>
                    @endif
                    @if ($hasTonColumn)
                        <td class="number">{{ number_format($sumTon($groupRows, $tonColumn), 4, '.', ',') }}</td>
                    @endif
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

    <div class="section-title">Summary :</div>

    <table class="summary-table">
        <tbody>
            <tr>
                <th>Total Supplier</th>
                <td class="center"><strong>{{ $summaryTotalSupplier }} Supplier</strong></td>
            </tr>
            <tr>
                <th>Total Jenis Kayu</th>
                <td class="center"><strong>{{ $summaryTotalJenisKayu }} Jenis Kayu</strong></td>
            </tr>
            <tr>
                <th>Total Semua Truk</th>
                <td class="center"><strong>{{ $summaryTotalTruk }} Truk</strong></td>
            </tr>
            <tr>
                <th>Berat Semua Muatan</th>
                <td class="center"><strong>{{ number_format($summaryTotalMuatan, 4, '.', ',') }} Ton</strong></td>
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
