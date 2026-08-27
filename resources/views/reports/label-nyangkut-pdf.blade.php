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
    </style>
</head>

<body>
    @php
        $rowsData =
            isset($rows) && is_iterable($rows) ? (is_array($rows) ? $rows : collect($rows)->values()->all()) : [];
        $columns = array_keys($rowsData[0] ?? []);
        $generatedByName = $generatedBy?->name ?? 'sistem';
        $generatedAtText = $generatedAt->copy()->locale('id')->translatedFormat('d-M-y H:i');
        $generatedDateText = $generatedAt->copy()->locale('id')->translatedFormat('d-M-y');

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

        $findColumnByNames = static function (array $availableColumns, array $candidateNames) use ($normalizeColumnName, ): ?string {
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

        $resolveTotalUnit = static function (string $groupName): string {
            return preg_match('/\bst\b/i', $groupName) === 1 ? 'Ton' : 'm3';
        };

    @endphp

    <h1 class="report-title">Laporan Label Nyangkut</h1>
    <p class="report-subtitle">Per Tanggal : {{ $generatedDateText }}</p>

    @forelse ($tableGroups as $group)
        @php
            $groupRows = $group['rows'] ?? [];
            $totalUnit = $resolveTotalUnit($group['name']);
        @endphp
        <div class="section-title">
            {{ $group['name'] }}
        </div>
        @php
            $groupJmlhBatang = $hasSummaryColumns ? $sumColumn($groupRows, $jmlhBatangColumn) : 0.0;
            $groupTotalValue = $hasSummaryColumns ? $sumColumn($groupRows, $totalColumn) : 0.0;
        @endphp
        <table class="report-table">
            <thead>
                <tr class="headers-row">
                    <th style="width: 34px; text-align:center">No</th>
                    @foreach ($columns as $column)
                                <th>
                                    {{ match ($normalizeColumnName($column)) {
                            'description' => 'Lokasi',
                            'nonyangkut' => 'No Nyangkut',
                            'nolabel' => 'No Label',
                            'jmlhbatang', 'jmlhbtg', 'jumlahbatang' => 'Jumlah Batang',
                            default => $column,
                        } }}
                                </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse ($groupRows as $row)
                    <tr class="data-row {{ $loop->odd ? 'row-odd' : 'row-even' }}">
                        <td class="data-cell center">
                            {{ $loop->iteration }}
                        </td>
                        @foreach ($columns as $column)
                            @php
                                $value = $row[$column] ?? null;
                                $numeric = $isNumericColumn($column, $groupRows);
                                $isLabelOutColumn = in_array(
                                    $normalizeColumnName($column),
                                    ['labelout', 'labeloutput'],
                                    true,
                                );
                            @endphp
                            @if ($isLabelOutColumn)
                                <td class="data-cell number">
                                    {{ is_numeric($value) ? number_format((float) $value, 0, '.', ',') : '' }}
                                </td>
                            @elseif ($totalColumn !== null && $column === $totalColumn)
                                <td class="data-cell number" style="font-weight: bold;">
                                    {!! is_numeric($value) ? number_format((float) $value, 4, '.', ',') . ' ' . $totalUnit : '' !!}
                                </td>
                            @elseif ($jmlhBatangColumn !== null && $column === $jmlhBatangColumn)
                                <td class="data-cell number" style="font-weight: bold;">
                                    {{ is_numeric($value) ? number_format((float) $value, 0, '.', ',') : '' }}
                                </td>
                            @elseif ($numeric)
                                <td class="data-cell number">
                                    {{ is_numeric($value) ? number_format((float) $value, 0, '.', ',') : '' }}
                                </td>
                            @else
                                <td class="data-cell label">{{ (string) $value }}</td>
                            @endif
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($columns) + 1 }}" class="center">Tidak ada data.</td>
                    </tr>
                @endforelse
                @if ($hasSummaryColumns && count($groupRows) > 0)
                    <tr class="totals-row">
                        <td colspan="{{ $summaryStartIndex + 1 }}" class="center" style="font-weight:bold;">
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
                                    {!! number_format($groupTotalValue, 4, '.', ',') . ' ' . $totalUnit !!}
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
        <table class="report-table">
            <thead>
                <tr class="headers-row">
                    <th style="width: 34px; text-align:center">No</th>
                    @foreach ($columns as $column)
                                <th>
                                    {{ match ($normalizeColumnName($column)) {
                            'description' => 'Lokasi',
                            'nonyangkut' => 'No Nyangkut',
                            'nolabel' => 'No Label',
                            'jmlhbatang', 'jmlhbtg', 'jumlahbatang' => 'Jumlah Batang',
                            default => $column,
                        } }}
                                </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td colspan="{{ count($columns) + 1 }}" class="center">Tidak ada data.</td>
                </tr>
            </tbody>
        </table>
    @endforelse

    </body>

</html>
