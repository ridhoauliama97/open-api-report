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
        $data = is_array($reportData ?? null) ? $reportData : [];
        $columnGroups = is_array($data['column_groups'] ?? null) ? $data['column_groups'] : [];
        $subColumns = is_array($data['sub_columns'] ?? null) ? $data['sub_columns'] : [];
        $rows = is_array($data['rows'] ?? null) ? $data['rows'] : [];
        $summaryLines = is_array($data['summary_lines'] ?? null) ? $data['summary_lines'] : [];
        $periodLabel = (string) ($data['period_label'] ?? '');
        $displayDate = \Carbon\Carbon::parse($reportDate)->locale('id')->translatedFormat('d-M-y');
        $groupStartIndexes = [];
        $groupColumnOffset = 0;
        foreach ($columnGroups as $group) {
            $groupStartIndexes[$groupColumnOffset] = true;
            $groupColumnOffset += max(1, (int) ($group['span'] ?? 1));
        }
        $stockTypeStartIndexes = [];
        $previousStockType = null;
        foreach ($subColumns as $columnIndex => $column) {
            if (($column['group_source'] ?? '') !== 'Stock Kayu Bulat Hidup') {
                continue;
            }

            $label = (string) ($column['label'] ?? '');
            $stockType = explode('-', $label, 2)[0] ?? $label;
            if ($previousStockType !== null && $stockType !== $previousStockType) {
                $stockTypeStartIndexes[$columnIndex] = true;
            }
            $previousStockType = $stockType;
        }
        $parseCellNumber = static function (mixed $value): ?float {
            $normalized = trim((string) ($value ?? ''));

            if ($normalized === '') {
                return null;
            }

            $normalized = str_replace(['>', '<', ' '], '', $normalized);
            $lastComma = strrpos($normalized, ',');
            $lastDot = strrpos($normalized, '.');

            if ($lastComma !== false && $lastDot !== false) {
                $decimalSeparator = $lastComma > $lastDot ? ',' : '.';
                $thousandSeparator = $decimalSeparator === ',' ? '.' : ',';
                $normalized = str_replace($thousandSeparator, '', $normalized);
                $normalized = str_replace($decimalSeparator, '.', $normalized);
            } elseif ($lastComma !== false) {
                $parts = explode(',', $normalized);
                $normalized = count($parts) === 2 && strlen(end($parts)) === 3
                    ? str_replace(',', '', $normalized)
                    : str_replace(',', '.', $normalized);
            } elseif ($lastDot !== false) {
                $parts = explode('.', $normalized);
                $normalized = count($parts) === 2 && strlen(end($parts)) === 3
                    ? str_replace('.', '', $normalized)
                    : $normalized;
            }

            return is_numeric($normalized) ? (float) $normalized : null;
        };
        $cellToneClass = static function (array $column, mixed $value) use ($parseCellNumber): string {
            $group = (string) ($column['group_source'] ?? '');
            $label = (string) ($column['label'] ?? '');
            $displayValue = (string) ($value ?? '');

            if ($group === 'Kiln & Dryer') {
                if (str_contains($displayValue, '>')) {
                    return ' text-blue';
                }

                if (str_contains($displayValue, '<')) {
                    return ' text-red';
                }

                return '';
            }

            if ($group !== 'Stock Kayu Bulat Hidup') {
                return '';
            }

            $number = $parseCellNumber($displayValue);

            if ($number === null) {
                return '';
            }

            if (in_array($label, ['RB-UT', 'JB-UT', 'JTG-UT'], true)) {
                if ($number >= 7) {
                    return ' text-red';
                }

                if ($number >= 5) {
                    return ' text-orange';
                }
            }

            if ($label === 'PL-UT') {
                if ($number >= 19) {
                    return ' text-red';
                }

                if ($number >= 15) {
                    return ' text-orange';
                }
            }

            return '';
        };
    @endphp

    <h1 class="report-title">Laporan Dashboard RU {{ $periodLabel }}</h1>
    <div class="report-subtitle"></div>

    <table class="report-table">
        <colgroup>
            <col style="width: 28px;">
            @foreach ($subColumns as $column)
                <col style="width: 48px;">
            @endforeach
        </colgroup>
        <thead>
            <tr>
                <th rowspan="2" class="no-column">No</th>
                @foreach ($columnGroups as $group)
                    <th colspan="{{ $group['span'] ?? 1 }}" class="group-start">{!! $group['label'] ?? '' !!}</th>
                @endforeach
            </tr>
            <tr>
                @foreach ($subColumns as $columnIndex => $column)
                    <th
                        class="{{ isset($groupStartIndexes[$columnIndex]) ? 'group-start' : '' }}{{ isset($stockTypeStartIndexes[$columnIndex]) ? ' stock-type-start' : '' }}">
                        {{ $column['label'] ?? '' }}
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $index => $row)
                <tr
                    class="{{ !empty($row['is_footer']) ? 'total-row' : (($index + 1) % 2 === 1 ? 'row-odd' : 'row-even') }}">
                    <td class="center no-column">{{ $row['label'] ?? '' }}</td>
                    @foreach ($subColumns as $columnIndex => $column)
                        @php
                            $cellValue = $row['cells'][$column['key']] ?? '';
                            $groupStartClass = isset($groupStartIndexes[$columnIndex]) ? ' group-start' : '';
                            $stockTypeStartClass = isset($stockTypeStartIndexes[$columnIndex]) ? ' stock-type-start' : '';
                        @endphp
                        <td
                            class="number{{ $groupStartClass }}{{ $stockTypeStartClass }}{{ $cellToneClass($column, $cellValue) }}">
                            {{ $cellValue }}
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($subColumns) + 1 }}" class="empty-state">Tidak ada data untuk periode ini.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if ($summaryLines !== [])
        <div class="section-title">Rangkuman</div>
        <table class="summary-table">
            <tbody>
                @foreach ($summaryLines as $line)
                    <tr>
                        <td style="width: 110px;">{{ $line['label'] ?? '' }}</td>
                        <td style="width: 14px;">{{ ($line['label'] ?? '') !== '' ? ':' : '' }}</td>
                        <td>{{ $line['value'] ?? '' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    </body>

</html>