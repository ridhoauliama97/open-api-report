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

        .report-table {
            border-collapse: collapse;
            border-spacing: 0;
            border-top: 0;
            border-right: 0;
            border-bottom: 1px solid #000;
            border-left: 1px solid #000;
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
            border: 1px solid #000;
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

        td.duration-bold {
            font-weight: bold;
        }

        .row-odd td {
            background: #c9d1df;
        }

        .row-even td {
            background: #eef2f8;
        }

        .headers-row th {
            font-weight: bold;
            font-size: 11px;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            border-left: 0;
            border-right: 1px solid #000;
        }

        .totals-row td {
            font-weight: bold;
            font-size: 11px;
            border-top: 1px solid #000;
            border-right: 1px solid #000;
            border-bottom: 0;
            border-left: 0;
            background: #fff;
        }

        .group-note {
            width: 100%;
            margin: 4px 0 14px 0;
            font-size: 11px;
        }

        .group-note td {
            border: 0;
            padding: 0 4px;
            background: transparent !important;
            vertical-align: top;
            white-space: nowrap;
        }

        .group-note .left {
            text-align: left;
        }

        .group-note .center {
            text-align: center;
        }

        .group-note .right {
            text-align: right;
        }

        .report-table tbody tr.data-row td.data-cell {
            border-top: 0 !important;
            border-bottom: 0 !important;
            border-left: 0 !important;
            border-right: 1px solid #000 !important;
        }

        @include('reports.partials.pdf-footer-table-style')
    </style>
</head>

<body>
    @php
        $rowsData =
            isset($rows) && is_iterable($rows) ? (is_array($rows) ? $rows : collect($rows)->values()->all()) : [];
        $columns = array_keys($rowsData[0] ?? []);
        $start = isset($startDate) ? \Carbon\Carbon::parse($startDate)->locale('id')->translatedFormat('d-M-y') : null;
        $end = isset($endDate) ? \Carbon\Carbon::parse($endDate)->locale('id')->translatedFormat('d-M-y') : null;
        $generatedAtText = $generatedAt->copy()->locale('id')->translatedFormat('d-M-y H:i');
        $reportEndReference = isset($endDate) ? \Carbon\Carbon::parse($endDate) : $generatedAt->copy();

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
            return match ($normalize($column)) {
                'nokayubulat', 'nokb' => 'No KB',
                'datecreate' => 'Tanggal',
                'nmsupplier', 'namasupplier' => 'Nama Supplier',
                'notruk', 'truk', 'mtruk' => 'No Truk',
                'tonkbkg', 'ton' => 'Berat Muatan (Ton)',
                'tanggallamaracip' => 'Lama Racip',
                default => $column,
            };
        };

        $statusColumn = $findColumnByCandidates($columns, ['Status']);
        $noKayuBulatColumn = $findColumnByCandidates($columns, ['NoKayuBulat', 'No KB', 'NoKB']);
        $dateCreateColumn = $findColumnByCandidates($columns, ['DateCreate', 'Tanggal']);
        $jenisColumn = $findColumnByCandidates($columns, ['Jenis', 'JenisKayu', 'Jenis Kayu']);
        $supplierColumn = $findColumnByCandidates($columns, ['NmSupplier', 'NamaSupplier', 'Nama Supplier']);
        $truckColumn = $findColumnByCandidates($columns, ['NoTruk', 'No Truk', 'Truk']);
        $tonColumn = $findColumnByCandidates($columns, ['TonKBKG', 'Ton', 'JmlhTon', 'JumlahTon', 'Berat']);
        $tanggalRacipColumn = $findColumnByCandidates($columns, ['TanggalRacip', 'Tanggal Racip']);
        $tanggalLamaRacipColumn = $findColumnByCandidates($columns, ['TanggalLamaRacip']);
        $dateUsageColumn = $findColumnByCandidates($columns, ['DateUsage', 'Date Usage']);

        $displayColumns = array_values(
            array_filter(
                [
                    $noKayuBulatColumn,
                    $dateCreateColumn,
                    $supplierColumn,
                    $jenisColumn,
                    $tanggalRacipColumn,
                    $tanggalLamaRacipColumn,
                    'LAMA_TUNGGU_VIRTUAL',
                    $truckColumn,
                    $tonColumn,
                ],
                static fn($column): bool => is_string($column) && $column !== '',
            ),
        );

        $columnWidths = [];
        foreach ($displayColumns as $column) {
            $normalizedColumn = $normalize($column);
            $columnWidths[$column] = match (true) {
                str_contains($normalizedColumn, 'nokayu') || str_contains($normalizedColumn, 'nokb') => 10,
                str_contains($normalizedColumn, 'datecreate') => 12,
                str_contains($normalizedColumn, 'supplier') => 15,
                str_contains($normalizedColumn, 'jenis') => 11,
                str_contains($normalizedColumn, 'truk') => 7,
                str_contains($normalizedColumn, 'ton') => 8,
                str_contains($normalizedColumn, 'tanggalracip') => 11,
                str_contains($normalizedColumn, 'tanggallamaracip') => 11,
                str_contains($normalizedColumn, 'virtual') => 10,
                default => 10,
            };
        }

        $fullWidthTotal = array_sum($columnWidths);
        $finalColumnWidths = [];
        foreach ($displayColumns as $column) {
            $base = (float) ($columnWidths[$column] ?? 0);
            $finalColumnWidths[$column] = $fullWidthTotal > 0 ? ($base / $fullWidthTotal) * 96.0 : 0.0;
        }

        $formatCellValue = static function (mixed $value, string $column) use ($normalize, $tonColumn): string {
            if ($value === null) {
                return '';
            }

            $textValue = trim((string) $value);
            if ($textValue === '') {
                return '';
            }

            $normalizedColumn = $normalize($column);
            if ($tonColumn !== null && $normalize($tonColumn) === $normalizedColumn && is_numeric($textValue)) {
                return number_format((float) $textValue, 4, '.', ',');
            }

            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $textValue) === 1) {
                try {
                    if (str_contains($normalizedColumn, 'racip')) {
                        return \Carbon\Carbon::parse($textValue)->format('d-M-y');
                    }

                    return \Carbon\Carbon::parse($textValue)->locale('id')->translatedFormat('d-M-y');
                } catch (\Throwable $exception) {
                    return $textValue;
                }
            }

            return $textValue;
        };

        $hasDayText = static function (string $value): bool {
            $normalized = strtolower(trim($value));

            return $normalized !== '' && (str_contains($normalized, 'hari') || str_contains($normalized, 'hr'));
        };

        $parseDateValue = static function (mixed $value): ?\Carbon\Carbon {
            if ($value === null) {
                return null;
            }

            $text = trim((string) $value);
            if ($text === '') {
                return null;
            }

            try {
                return \Carbon\Carbon::parse($text);
            } catch (\Throwable $exception) {
                return null;
            }
        };

        $diffDays = static function (?\Carbon\Carbon $from, ?\Carbon\Carbon $to): ?int {
            if ($from === null || $to === null) {
                return null;
            }

            return max(0, (int) $from->diffInDays($to, false));
        };

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

            $distinct = [];
            foreach ($rows as $row) {
                $truck = trim((string) ($row[$truckColumn] ?? ''));
                if ($truck === '') {
                    continue;
                }

                $distinct[$truck] = true;
            }

            return count($distinct);
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

        $totalKeseluruhanTon = $sumTon($rowsData, $tonColumn);
        $totalKeseluruhanTruck = $countTruck($rowsData, $truckColumn);
        $visualDisplayColumnsCount = count($displayColumns) + 2;
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
        <table class="report-table">
            <colgroup>
                <col style="width: 4%">
                @foreach ($displayColumns as $column)
                    @php
                        $baseWidth = (float) ($finalColumnWidths[$column] ?? 0);
                    @endphp
                    @if ($column === $tanggalRacipColumn || $column === $tanggalLamaRacipColumn)
                        <col style="width: {{ number_format($baseWidth * 0.72, 4, '.', ',') }}%">
                        <col style="width: {{ number_format($baseWidth * 0.28, 4, '.', ',') }}%">
                    @else
                        <col style="width: {{ number_format($baseWidth, 4, '.', ',') }}%">
                    @endif
                @endforeach
            </colgroup>
            <thead>
                <tr class="headers-row">
                    <th>No</th>
                    @foreach ($displayColumns as $column)
                        @if ($column === $tanggalRacipColumn || $column === $tanggalLamaRacipColumn)
                            <th colspan="2">{{ $formatHeaderLabel($column) }}</th>
                        @elseif ($column === 'LAMA_TUNGGU_VIRTUAL')
                            <th>Lama Tunggu</th>
                        @else
                            <th>{{ $formatHeaderLabel($column) }}</th>
                        @endif
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($groupRows as $row)
                    @php
                        $dateCreateValue = $parseDateValue(
                            $dateCreateColumn !== null ? $row[$dateCreateColumn] ?? null : null,
                        );
                        $tanggalRacipValue = $parseDateValue(
                            $tanggalRacipColumn !== null ? $row[$tanggalRacipColumn] ?? null : null,
                        );
                        $tanggalLamaRacipValue = $parseDateValue(
                            $tanggalLamaRacipColumn !== null ? $row[$tanggalLamaRacipColumn] ?? null : null,
                        );
                        $dateUsageValue = $parseDateValue(
                            $dateUsageColumn !== null ? $row[$dateUsageColumn] ?? null : null,
                        );

                        $lamaAwalHari = $diffDays($dateCreateValue, $tanggalRacipValue);
                        $lamaAwalText = $lamaAwalHari !== null ? $lamaAwalHari . ' hari' : '';

                        $lamaRacipHari = $diffDays($tanggalRacipValue, $tanggalLamaRacipValue);
                        $lamaRacipText = $lamaRacipHari !== null ? $lamaRacipHari . ' hari' : '';

                        if ($dateUsageValue !== null && $dateCreateValue !== null) {
                            $lamaTungguHari = $diffDays($dateCreateValue, $dateUsageValue);
                        } elseif ($tanggalLamaRacipValue !== null && $dateCreateValue !== null) {
                            $lamaTungguHari = $diffDays($dateCreateValue, $tanggalLamaRacipValue);
                        } elseif ($tanggalRacipValue !== null && $dateCreateValue !== null) {
                            $lamaTungguHari = $diffDays($dateCreateValue, $tanggalRacipValue);
                        } elseif ($dateCreateValue !== null) {
                            $lamaTungguHari = $diffDays($dateCreateValue, $reportEndReference);
                        } else {
                            $lamaTungguHari = null;
                        }
                        $lamaTungguText = $lamaTungguHari !== null ? $lamaTungguHari . ' hari' : '';
                    @endphp
                    <tr class="data-row {{ $loop->odd ? 'row-odd' : 'row-even' }}">
                        <td class="center data-cell">{{ $loop->iteration }}</td>
                        @foreach ($displayColumns as $column)
                            @php
                                $value = $row[$column] ?? null;
                                $isTonCell = $tonColumn !== null && $column === $tonColumn;
                            @endphp
                            @if ($column === $tanggalRacipColumn)
                                <td class="data-cell center">{{ $formatCellValue($value, $column) }}</td>
                                <td class="data-cell center {{ $hasDayText($lamaAwalText) ? 'duration-bold' : '' }}">
                                    @if ($hasDayText($lamaAwalText))
                                        <strong>{{ $lamaAwalText }}</strong>
                                    @else
                                        {{ $lamaAwalText }}
                                    @endif
                                </td>
                            @elseif ($column === $tanggalLamaRacipColumn)
                                <td class="data-cell center">{{ $formatCellValue($value, $column) }}</td>
                                <td class="data-cell center {{ $hasDayText($lamaRacipText) ? 'duration-bold' : '' }}">
                                    @if ($hasDayText($lamaRacipText))
                                        <strong>{{ $lamaRacipText }}</strong>
                                    @else
                                        {{ $lamaRacipText }}
                                    @endif
                                </td>
                            @elseif ($column === 'LAMA_TUNGGU_VIRTUAL')
                                <td class="data-cell center {{ $hasDayText($lamaTungguText) ? 'duration-bold' : '' }}">
                                    @if ($hasDayText($lamaTungguText))
                                        <strong>{{ $lamaTungguText }}</strong>
                                    @else
                                        {{ $lamaTungguText }}
                                    @endif
                                </td>
                            @else
                                <td class="data-cell {{ $isTonCell ? 'number' : 'center' }}">
                                    {{ $formatCellValue($value, $column) }}
                                </td>
                            @endif
                        @endforeach
                    </tr>
                @endforeach
                <tr class="totals-row">
                    @php
                        $hasTruckColumn = $truckColumn !== null && in_array($truckColumn, $displayColumns, true);
                        $hasTonColumn = $tonColumn !== null && in_array($tonColumn, $displayColumns, true);
                        $tailColumnsCount = ($hasTruckColumn ? 1 : 0) + ($hasTonColumn ? 1 : 0);
                        $totalLabelColspan = max(1, 1 + $visualDisplayColumnsCount - $tailColumnsCount);
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
        @if ($groupName === 'Masih Hidup')
            @php
                $sudahMasukMejaRows = [];
                $belumMasukMejaRows = [];

                foreach ($groupRows as $groupRow) {
                    $tanggalRacipText = trim(
                        (string) ($tanggalRacipColumn !== null ? $groupRow[$tanggalRacipColumn] ?? '' : ''),
                    );

                    if ($tanggalRacipText !== '') {
                        $sudahMasukMejaRows[] = $groupRow;
                    } else {
                        $belumMasukMejaRows[] = $groupRow;
                    }
                }

                $belumMasukMejaTon = $sumTon($belumMasukMejaRows, $tonColumn);
                $sudahMasukMejaTon = $sumTon($sudahMasukMejaRows, $tonColumn);
                $jumlahMasihHidupTon = $sumTon($groupRows, $tonColumn);
                $jumlahMasihHidupTruck = $countTruck($groupRows, $truckColumn);
            @endphp
            <table class="group-note">
                <tr>
                    <td class="left" style="width:33.33%">
                        Jmlh Belum Masuk Meja : {{ number_format($belumMasukMejaTon, 4, '.', ',') }} Ton
                    </td>
                    <td class="center" style="width:33.33%">
                        Jmlh Sudah Masuk Meja : {{ number_format($sudahMasukMejaTon, 4, '.', ',') }} Ton
                    </td>
                    <td class="right" style="width:33.33%">
                        Jmlh : {{ number_format($jumlahMasihHidupTon, 4, '.', ',') }} Ton
                        ({{ $jumlahMasihHidupTruck }} Truk)
                    </td>
                </tr>
            </table>
        @elseif ($groupName === 'Sudah Mati')
            <table class="group-note">
                <tr>
                    <td class="right">
                        Jmlh : {{ number_format($totalKeseluruhanTon, 4, '.', ',') }} Ton
                        ({{ $totalKeseluruhanTruck }} Truk)
                    </td>
                </tr>
            </table>
        @endif
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
