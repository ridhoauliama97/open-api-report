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
            margin: 10px 0 5px 0;
            font-size: 12px;
            font-weight: bold;
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

        td.label {
            white-space: nowrap;
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

        .totals-row td {
            font-weight: bold;
            font-size: 11px;
            border: 1.5px solid #000;
        }
    </style>
</head>

<body>
    @php
        $rowsData =
            isset($rows) && is_iterable($rows) ? (is_array($rows) ? $rows : collect($rows)->values()->all()) : [];
        $columns = array_keys($rowsData[0] ?? []);
        $generatedByName = $generatedBy?->name ?? 'sistem';
        $generatedAtText = $generatedAt->copy()->locale('id')->translatedFormat('d M Y H:i');

        $isNumericColumn = static function (string $column, array $rows): bool {
            foreach ($rows as $row) {
                $value = $row[$column] ?? null;
                if ($value === null || $value === '') {
                    continue;
                }

                return is_numeric($value);
            }

            return false;
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

            // Handle 1,234.56 and 1.234,56 formats.
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

        $normalizeColumnName = static function (string $column): string {
            return strtolower(str_replace([' ', '_'], '', trim($column)));
        };

        $findColumnByNames = static function (array $availableColumns, array $candidateNames) use (
            $normalizeColumnName,
        ): ?string {
            foreach ($candidateNames as $candidateName) {
                $candidateNormalized = $normalizeColumnName($candidateName);
                foreach ($availableColumns as $column) {
                    if ($normalizeColumnName($column) === $candidateNormalized) {
                        return $column;
                    }
                }
            }

            return null;
        };

        $findGroupColumn = static function (array $availableColumns): ?string {
            $priorities = ['ket', 'keterangan', 'namagroup', 'group', 'namaproses'];

            foreach ($priorities as $candidate) {
                foreach ($availableColumns as $column) {
                    $normalized = strtolower(str_replace([' ', '_'], '', trim($column)));
                    if ($normalized === $candidate) {
                        return $column;
                    }
                }
            }

            foreach ($availableColumns as $column) {
                $normalized = strtolower(str_replace([' ', '_'], '', trim($column)));
                if (in_array($normalized, $priorities, true)) {
                    return $column;
                }
            }

            return null;
        };

        $groupColumn = $findGroupColumn($columns);
        $columns = array_values(
            array_filter($columns, static fn(string $column): bool => $normalizeColumnName($column) !== 'rendemen'),
        );
        $jmlhBatangColumn = $findColumnByNames($columns, ['JmlhBatang', 'Jmlh Btg', 'JmlBatang', 'JumlahBatang']);
        $lokasiColumn = $findColumnByNames($columns, ['Description', 'Lokasi']);

        $jmlhBatangSwapIndex = $jmlhBatangColumn !== null ? array_search($jmlhBatangColumn, $columns, true) : false;
        $lokasiSwapIndex = $lokasiColumn !== null ? array_search($lokasiColumn, $columns, true) : false;
        if (is_int($jmlhBatangSwapIndex) && is_int($lokasiSwapIndex)) {
            $tempColumn = $columns[$jmlhBatangSwapIndex];
            $columns[$jmlhBatangSwapIndex] = $columns[$lokasiSwapIndex];
            $columns[$lokasiSwapIndex] = $tempColumn;
        }

        $totalColumn = $findColumnByNames($columns, ['Total']);
        $jmlhBatangColumnIndex = $jmlhBatangColumn !== null ? array_search($jmlhBatangColumn, $columns, true) : false;
        $totalColumnIndex = $totalColumn !== null ? array_search($totalColumn, $columns, true) : false;
        $summaryStartIndex = collect([$jmlhBatangColumnIndex, $totalColumnIndex])
            ->filter(static fn($index): bool => is_int($index))
            ->min();
        $summaryStartIndex = is_int($summaryStartIndex) ? $summaryStartIndex : null;
        $hasSummaryColumns = $summaryStartIndex !== null;

        $tableGroups = [];

        if ($groupColumn !== null) {
            $groupedRows = collect($rowsData)
                ->sortBy(static function (array $row) use ($groupColumn): string {
                    return strtolower((string) ($row[$groupColumn] ?? 'Tanpa Group'));
                })
                ->groupBy(static function (array $row) use ($groupColumn): string {
                    $name = trim((string) ($row[$groupColumn] ?? ''));

                    return $name !== '' ? $name : 'Tanpa Group';
                });

            foreach ($groupedRows as $groupName => $items) {
                $tableGroups[] = [
                    'name' => (string) $groupName,
                    'rows' => $items->values()->all(),
                ];
            }

            $columns = array_values(
                array_filter($columns, static fn(string $column): bool => $column !== $groupColumn),
            );
        } else {
            $currentGroup = 'Tanpa Group';

            foreach ($rowsData as $row) {
                $firstColumn = $columns[0] ?? null;
                $firstValue = $firstColumn !== null ? trim((string) ($row[$firstColumn] ?? '')) : '';
                $hasNumericValue = false;

                foreach ($columns as $column) {
                    if ($isNumericColumn($column, $rowsData) && ($row[$column] ?? '') !== '') {
                        $hasNumericValue = true;
                        break;
                    }
                }

                $isGroupMarkerRow = $firstValue !== '' && !$hasNumericValue;

                if ($isGroupMarkerRow) {
                    $currentGroup = $firstValue;
                    continue;
                }

                $tableGroups[$currentGroup][] = $row;
            }

            $tableGroups = collect($tableGroups)
                ->sortKeys()
                ->map(
                    static fn(array $items, string $groupName): array => [
                        'name' => $groupName,
                        'rows' => array_values($items),
                    ],
                )
                ->values()
                ->all();
        }

        $sumColumn = static function (array $rows, ?string $column) use ($toFloat): float {
            if ($column === null) {
                return 0.0;
            }

            $sum = 0.0;
            foreach ($rows as $row) {
                $value = $toFloat($row[$column] ?? null);
                if ($value !== null) {
                    $sum += $value;
                }
            }

            return $sum;
        };

    @endphp

    <h1 class="report-title">Laporan Label Nyangkut</h1>
    <p class="report-subtitle">Per-{{ $generatedAtText }}</p>

    @forelse ($tableGroups as $group)
        <div class="section-title">
            {{ $group['name'] }}
        </div>
        <table>
            <thead>
                <tr class="headers-row">
                    <th style="width: 34px; text-align:center">No</th>
                    @foreach ($columns as $column)
                        <th>{{ $normalizeColumnName($column) === 'description' ? 'Lokasi' : $column }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse ($group['rows'] as $row)
                    <tr class="{{ $loop->odd ? 'row-odd' : 'row-even' }}">
                        <td class="center">{{ $loop->iteration }}</td>
                        @foreach ($columns as $column)
                            @php
                                $value = $row[$column] ?? null;
                                $numeric = $isNumericColumn($column, $group['rows']);
                                $isLabelOutColumn = in_array(
                                    $normalizeColumnName($column),
                                    ['labelout', 'labeloutput'],
                                    true,
                                );
                            @endphp
                            @if ($isLabelOutColumn)
                                <td class="number">
                                    {{ is_numeric($value) ? number_format((float) $value, 0, '.', ',') : '' }}</td>
                            @elseif ($totalColumn !== null && $column === $totalColumn)
                                <td class="number">
                                    {{ is_numeric($value) ? number_format((float) $value, 4, '.', '') : '' }}</td>
                            @elseif ($numeric)
                                <td class="number">
                                    {{ is_numeric($value) ? number_format((float) $value, 0, '.', ',') : '' }}</td>
                            @else
                                <td class="label">{{ (string) $value }}</td>
                            @endif
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($columns) + 1 }}" class="center">Tidak ada data.</td>
                    </tr>
                @endforelse
                @if ($hasSummaryColumns)
                    @php
                        $groupJmlhBatang = $sumColumn($group['rows'], $jmlhBatangColumn);
                        $groupTotalValue = $sumColumn($group['rows'], $totalColumn);
                    @endphp
                    <tr class="totals-row">
                        <td colspan="{{ $summaryStartIndex + 1 }}" class="number" style="font-weight:bold;">
                            Total {{ $group['name'] }}
                        </td>
                        @for ($columnIndex = $summaryStartIndex; $columnIndex < count($columns); $columnIndex++)
                            @php
                                $summaryColumn = $columns[$columnIndex];
                            @endphp
                            @if ($jmlhBatangColumn !== null && $summaryColumn === $jmlhBatangColumn)
                                <td class="number" style="font-weight:bold;">
                                    {{ number_format($groupJmlhBatang, 0, '.', ',') }}
                                </td>
                            @elseif ($totalColumn !== null && $summaryColumn === $totalColumn)
                                <td class="number" style="font-weight:bold;">
                                    {{ number_format($groupTotalValue, 4, '.', '') }}
                                </td>
                            @else
                                <td></td>
                            @endif
                        @endfor
                    </tr>
                @endif
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

    <htmlpagefooter name="reportFooter">
        <div class="footer-wrap">
            <div class="footer-left">Dicetak oleh: {{ $generatedByName }} pada {{ $generatedAtText }}</div>
            <div class="footer-right">Halaman {PAGENO} dari {nbpg}</div>
        </div>
    </htmlpagefooter>
    <sethtmlpagefooter name="reportFooter" value="on" />
</body>

</html>
