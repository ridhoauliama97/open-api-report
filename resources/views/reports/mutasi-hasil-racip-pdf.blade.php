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
            font-size: 14px;
            font-weight: bold;
        }

        .report-subtitle {
            text-align: center;
            margin: 2px 0 10px 0;
            font-size: 10px;
            color: #636466;
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

        .totals-row td {
            background: #dde4f2;
            font-weight: 700;
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
        $columns = array_keys($rowsData[0] ?? []);
        $start = \Carbon\Carbon::parse($startDate)->locale('id')->translatedFormat('d M Y');
        $end = \Carbon\Carbon::parse($endDate)->locale('id')->translatedFormat('d M Y');
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
        $kubikInputColumn = $findColumnByNames($columns, ['Kubik Input', 'kubik_input', 'KubikIN', 'Kubik In']);
        $kubikOutputColumn = $findColumnByNames($columns, ['Kubik Output', 'kubik_output', 'KubikOut', 'Kubik Out']);

        $tableGroups = [];

        if ($groupColumn !== null) {
            $groupedRows = collect($rowsData)
                ->sortBy(static function (array $row) use ($groupColumn): string {
                    return strtolower((string) ($row[$groupColumn] ?? ''));
                })
                ->groupBy(static function (array $row) use ($groupColumn): string {
                    $name = trim((string) ($row[$groupColumn] ?? ''));

                    return $name;
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
            $currentGroup = '';

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

        $columns = array_values(
            array_filter($columns, static fn(string $column): bool => $normalizeColumnName($column) !== 'rendemen'),
        );
        $numericColumns = [];
        $grandTotals = [];

        foreach ($columns as $column) {
            $isNumeric = $isNumericColumn($column, $rowsData);
            $numericColumns[$column] = $isNumeric;

            if ($isNumeric) {
                $grandTotals[$column] = 0.0;
            }
        }

        $formatByColumn = static function (string $column, ?float $value) use ($normalizeColumnName): string {
            if ($value === null) {
                return '';
            }

            $isLabelOutColumn = in_array($normalizeColumnName($column), ['labelout', 'labeloutput'], true);

            return $isLabelOutColumn ? number_format($value, 0, '.', '') : number_format($value, 4, '.', '');
        };
    @endphp

    <h1 class="report-title">Laporan Mutasi Hasil Racip</h1>
    <p class="report-subtitle">Dari {{ $start }} s/d {{ $end }}</p>

    @forelse ($tableGroups as $group)
        @php
            $isLastGroup = $loop->last;
        @endphp
        @if (trim($group['name']) !== '')
            <p style="margin: 8px 0 4px 0; font-size: 11px; font-weight: 700; text-transform: uppercase;">
                {{ $group['name'] }}
            </p>
        @endif
        <table>
            <thead>
                <tr>
                    <th style="width: 34px; text-align:center">No</th>
                    @foreach ($columns as $column)
                        <th>{{ $column }}</th>
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
                                $numeric = $numericColumns[$column] ?? false;
                                $floatValue = $toFloat($value);

                                if ($numeric && $floatValue !== null) {
                                    $grandTotals[$column] += $floatValue;
                                }
                            @endphp
                            @if ($numeric)
                                <td class="number">
                                    {{ $formatByColumn($column, $floatValue) }}</td>
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

                @if ($isLastGroup && $rowsData !== [])
                    <tr class="totals-row">
                        <td colspan="2" class="center">Total</td>
                        @for ($index = 1; $index < count($columns); $index++)
                            @php
                                $column = $columns[$index];
                            @endphp
                            @if (($numericColumns[$column] ?? false) === true)
                                <td class="number">{{ $formatByColumn($column, $grandTotals[$column] ?? null) }}</td>
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
