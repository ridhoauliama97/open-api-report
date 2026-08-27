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
        $start = \Carbon\Carbon::parse($startDate)->locale('id')->translatedFormat('d-M-y');
        $end = \Carbon\Carbon::parse($endDate)->locale('id')->translatedFormat('d-M-y');
        $generatedByName = $generatedBy?->name ?? 'sistem';
        $generatedAtText = $generatedAt->copy()->locale('id')->translatedFormat('d-M-y H:i');

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

            if (is_string($value)) {
                $normalized = str_replace(',', '.', trim($value));
                if (is_numeric($normalized)) {
                    return (float) $normalized;
                }
            }

            return null;
        };

        $normalizeColumnName = static function (string $column): string {
            return strtolower(str_replace([' ', '_'], '', trim($column)));
        };

        $isNamaMesinColumn = static function (string $column) use ($normalizeColumnName): bool {
            return $normalizeColumnName($column) === 'namamesin';
        };

        $headerLabelMap = [
            'NoProduksi' => 'Nomor Produksi',
            'NamaMesin' => 'Nama Mesin',
            'LabelIn' => 'Label In',
            'KubikIn' => 'Kubik In',
            'LabelOut' => 'Label Out',
            'KubikOut' => 'Kubik Out',
        ];

        $findColumnByNames = static function (array $availableColumns, array $candidateNames) use (
            $normalizeColumnName,
        ): ?string {
            $normalizedCandidates = array_map($normalizeColumnName, $candidateNames);

            foreach ($availableColumns as $column) {
                if (in_array($normalizeColumnName($column), $normalizedCandidates, true)) {
                    return $column;
                }
            }

            return null;
        };

        $findGroupColumn = static function (array $availableColumns): ?string {
            foreach ($availableColumns as $column) {
                $normalized = strtolower(trim($column));
                if (in_array($normalized, ['nama group', 'nama_group', 'group', 'nama proses'], true)) {
                    return $column;
                }
            }

            return null;
        };

        $groupColumn = $findGroupColumn($columns);
        $rendemenColumn = $findColumnByNames($columns, ['Rendemen']);
        $kubikInputColumn = $findColumnByNames($columns, ['Kubik Input', 'kubik_input', 'KubikIN', 'Kubik In']);
        $kubikOutputColumn = $findColumnByNames($columns, ['Kubik Output', 'kubik_output', 'KubikOut', 'Kubik Out']);

        $tableGroups = [];

        if ($groupColumn !== null) {
            $groupedRows = collect($rowsData)->groupBy(static function (array $row) use ($groupColumn): string {
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
                ->map(
                    static fn(array $items, string $groupName): array => [
                        'name' => $groupName,
                        'rows' => array_values($items),
                    ],
                )
                ->values()
                ->all();
        }

        if ($rendemenColumn === null) {
            $columns[] = 'Rendemen';
            $rendemenColumn = 'Rendemen';
        }

        $machineColumnCount = count(array_filter($columns, $isNamaMesinColumn));
        $noWeight = 0.5; // narrower No column
        $machineWeight = 1.5; // slightly wider Nama Mesin column
        $effectiveUnits = max(
            1.0,
            $noWeight + (count($columns) - $machineColumnCount) * 1.0 + $machineColumnCount * $machineWeight,
        );
        $uniformWidth = 100 / $effectiveUnits;
        $noWidth = $uniformWidth * $noWeight;
        $machineWidth = $uniformWidth * $machineWeight;
        $widthText = static function (float $width): string {
            return number_format($width, 4, '.', ',') . '%';
        };

        foreach ($tableGroups as &$tableGroup) {
            foreach ($tableGroup['rows'] as &$tableRow) {
                $rendemenValue = null;

                if ($kubikInputColumn !== null && $kubikOutputColumn !== null) {
                    $inputValue = $toFloat($tableRow[$kubikInputColumn] ?? null);
                    $outputValue = $toFloat($tableRow[$kubikOutputColumn] ?? null);

                    if ($inputValue !== null && $inputValue !== 0.0 && $outputValue !== null) {
                        $rendemenValue = ($outputValue / $inputValue) * 100;
                    }
                }

                if ($rendemenValue === null) {
                    $rawRendemen = $tableRow[$rendemenColumn] ?? null;

                    if (is_numeric($rawRendemen)) {
                        $rendemenValue = (float) $rawRendemen;
                    } elseif (is_string($rawRendemen) && preg_match('/-?\d+(\.\d+)?/', $rawRendemen, $matches) === 1) {
                        $rendemenValue = (float) $matches[0];
                    }
                }

                $tableRow[$rendemenColumn] = $rendemenValue;
            }
            unset($tableRow);
        }
        unset($tableGroup);
    @endphp

    <h1 class="report-title">Laporan Rangkuman Jumlah Label Input</h1>
    <p class="report-subtitle">Dari {{ $start }} s/d {{ $end }}</p>

    @forelse ($tableGroups as $group)
        <p class="group-title">
            {{ $group['name'] }}
        </p>
        <table class="report-table">
            <colgroup>
                <col style="width: {{ $widthText($noWidth) }};">
                @foreach ($columns as $column)
                    <col style="width: {{ $widthText($isNamaMesinColumn($column) ? $machineWidth : $uniformWidth) }};">
                @endforeach
            </colgroup>
            <thead>
                <tr class="headers-row">
                    <th style="text-align:center; width: {{ $widthText($noWidth) }};">No</th>
                    @foreach ($columns as $column)
                        <th
                            style="width: {{ $widthText($isNamaMesinColumn($column) ? $machineWidth : $uniformWidth) }};">
                            {{ $headerLabelMap[$column] ?? $column }}
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse ($group['rows'] as $row)
                    <tr class="data-row {{ $loop->odd ? 'row-odd' : 'row-even' }}">
                        <td class="data-cell center" style="width: {{ $widthText($noWidth) }};">
                            {{ $loop->iteration }}
                        </td>
                        @foreach ($columns as $column)
                            @php
                                $value = $row[$column] ?? null;
                                $normalizedColumn = $normalizeColumnName($column);
                                $numeric = $isNumericColumn($column, $group['rows']);
                                $isRendemenColumn = $normalizedColumn === 'rendemen';
                                $isBoldSummaryColumn = in_array(
                                    $normalizedColumn,
                                    ['kubikin', 'kubikout', 'rendemen'],
                                    true,
                                );
                                $isLabelOutColumn = in_array($normalizedColumn, ['labelout', 'labeloutput'], true);
                                $cellWidth = $widthText($isNamaMesinColumn($column) ? $machineWidth : $uniformWidth);
                                $cellStyle =
                                    'width: ' . $cellWidth . ';' . ($isBoldSummaryColumn ? ' font-weight: bold;' : '');
                            @endphp
                            @if ($isRendemenColumn)
                                <td class="data-cell number" style="{{ $cellStyle }}">
                                    {{ is_numeric($value) ? number_format((float) $value, 1, '.', ',') . '%' : '' }}
                                </td>
                            @elseif ($isLabelOutColumn)
                                <td class="data-cell number" style="{{ $cellStyle }}">
                                    {{ is_numeric($value) ? number_format((float) $value, 0, '.', ',') : '' }}</td>
                            @elseif ($numeric)
                                <td class="data-cell number" style="{{ $cellStyle }}">
                                    {{ is_numeric($value) ? number_format((float) $value, 4, '.', ',') : '' }}</td>
                            @else
                                <td class="data-cell label" style="{{ $cellStyle }}">{{ (string) $value }}
                                </td>
                            @endif
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($columns) + 1 }}" class="center">Tidak ada data.</td>
                    </tr>
                @endforelse
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

    </body>

</html>
