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

        table.umur-rambung-table th:nth-child(1),
        table.umur-rambung-table td:nth-child(1) { width: 4%; }
        table.umur-rambung-table th:nth-child(2),
        table.umur-rambung-table td:nth-child(2) { width: 10%; }
        table.umur-rambung-table th:nth-child(3),
        table.umur-rambung-table td:nth-child(3) { width: 12%; }
        table.umur-rambung-table th:nth-child(4),
        table.umur-rambung-table td:nth-child(4) { width: 15%; }
        table.umur-rambung-table th:nth-child(5),
        table.umur-rambung-table td:nth-child(5) { width: 11%; }
        table.umur-rambung-table th:nth-child(6),
        table.umur-rambung-table td:nth-child(6) { width: 7%; }
        table.umur-rambung-table th:nth-child(7),
        table.umur-rambung-table td:nth-child(7) { width: 8%; }
        table.umur-rambung-table th:nth-child(8),
        table.umur-rambung-table td:nth-child(8) { width: 14%; }
        table.umur-rambung-table th:nth-child(9),
        table.umur-rambung-table td:nth-child(9) { width: 9%; }
        table.umur-rambung-table th:nth-child(10),
        table.umur-rambung-table td:nth-child(10) { width: 10%; }

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
    </style>
</head>

<body>
    @php
        $rowsData =
            isset($rows) && is_iterable($rows) ? (is_array($rows) ? $rows : collect($rows)->values()->all()) : [];
        $columns = array_keys($rowsData[0] ?? []);
        $start = isset($startDate) ? \Carbon\Carbon::parse($startDate)->locale('id')->translatedFormat('d M Y') : null;
        $end = isset($endDate) ? \Carbon\Carbon::parse($endDate)->locale('id')->translatedFormat('d M Y') : null;
        $generatedByName = $generatedBy?->name ?? 'sistem';
        $generatedAtText = $generatedAt->copy()->locale('id')->translatedFormat('d M Y H:i');

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
                    return "{$formatted} hr";
                }

                return $formatted;
            }

            if (preg_match('/^\\d{4}-\\d{2}-\\d{2}$/', $textValue) === 1) {
                try {
                    return \Carbon\Carbon::parse($textValue)->locale('id')->translatedFormat('d M Y');
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
    @endphp

    <h1 class="report-title">Laporan Umur Kayu Bulat (RAMBUNG)</h1>
    <p class="report-subtitle">
        @if ($start && $end)
            Periode {{ $start }} s/d {{ $end }}
        @else
            Per-{{ $generatedAtText }}
        @endif
    </p>

    @forelse ($groupedRows as $groupName => $groupRows)
        <div class="section-title">Status : {{ $groupName }}</div>
        <table class="umur-rambung-table">
            <thead>
                <tr class="headers-row">
                    <th>No</th>
                    @foreach ($displayColumns as $column)
                        <th>{{ $column }}</th>
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
            </tbody>
        </table>
        <div class="group-summary">
            <span class="label">Total :</span>
            {{ number_format($sumTon($groupRows, $tonColumn), 4, '.', ',') }} Ton
            ({{ $truckColumn !== null ? count(array_filter($groupRows, static fn(array $row): bool => trim((string) ($row[$truckColumn] ?? '')) !== '')) : count($groupRows) }}
            Truk)
        </div>
    @empty
        <table>
            <tbody>
                <tr>
                    <td class="center">Tidak ada data.</td>
                </tr>
            </tbody>
        </table>
    @endforelse

    <htmlpagefooter name="reportFooter">
        <div class="footer-wrap">
            <div class="footer-left">Dicetak oleh: {{ $generatedByName }} pada {{ $generatedAtText }}</div>
            <div class="footer-right">Halaman {PAGENO} dari {nbpg}</div>
        </div>
    </htmlpagefooter>
    <sethtmlpagefooter name="reportFooter" value="on" />
</body>

</html>


