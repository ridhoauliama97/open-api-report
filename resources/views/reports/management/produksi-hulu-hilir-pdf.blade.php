<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif:ital,wght@0,100..900;1,100..900&display=swap"
        rel="stylesheet">
    @include('reports.partials.pdf-reference-style')
    <style>
        table {
            width: 100%;
            line-height: inherit;
            border-collapse: collapse;
            border-spacing: 0;
            border: 1px solid #000;
        }

        th,
        td {
            border: 1px solid #000;
            word-wrap: break-word;
            padding: 2px 2px;
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
        }

        .total-row td,
        .target-row td,
        .grand-total-row td {
            font-weight: bold;
        }

        .output-below-target {
            color: #c00000;
            font-weight: bold;
            font-style: italic;
        }

        .sub-header {
            font-size: 8px;
        }
    </style>


</head>

<body>
    @php
        $data = is_array($reportData ?? null) ? $reportData : [];
        $columns = is_array($data['columns'] ?? null) ? $data['columns'] : [];
        $rows = is_array($data['rows'] ?? null) ? $data['rows'] : [];
        $statRows = is_array($data['stat_rows'] ?? null) ? $data['stat_rows'] : [];
        $targetRow = is_array($data['target_row'] ?? null) ? $data['target_row'] : ['label' => 'Target', 'cells' => []];
        $summaryRows = is_array($data['summary_rows'] ?? null) ? $data['summary_rows'] : [];
        $grandTotals = is_array($data['grand_totals'] ?? null) ? $data['grand_totals'] : [];
        $start = \Carbon\Carbon::parse($startDate)->locale('id')->isoFormat('DD-MMM-YY');
        $end = \Carbon\Carbon::parse($endDate)->locale('id')->isoFormat('DD-MMM-YY');

        $fmtNumber = static function ($value, int $decimals = 2, bool $blankWhenZero = true): string {
            if ($value === null || !is_numeric($value)) {
                return '';
            }

            $float = (float) $value;
            if ($blankWhenZero && abs($float) < 0.0000001) {
                return '-';
            }

            return number_format($float, $decimals, '.', ',');
        };

        $fmtPercent = static function ($value): string {
            if ($value === null || !is_numeric($value)) {
                return '-%';
            }

            $float = (float) $value;
            if (abs($float) < 0.0000001) {
                return '-%';
            }

            return number_format($float, 1, '.', ',') . '%';
        };

        $fmtBlank = static function ($value, int $decimals = 4): string {
            if ($value === null || !is_numeric($value)) {
                return '';
            }

            $float = (float) $value;
            if (abs($float) < 0.0000001) {
                return '';
            }

            return number_format($float, $decimals, '.', ',');
        };

        $isOutputBelowTarget = static function ($output, $target): bool {
            return $output !== null &&
                $target !== null &&
                is_numeric($output) &&
                is_numeric($target) &&
                (float) $output < (float) $target;
        };
    @endphp

    <h1 class="report-title">Laporan Produksi Hulu Hilir</h1>
    <div class="report-subtitle">Periode {{ $start }} s/d {{ $end }}</div>

    <table class="report-table">
        <colgroup>
            <col style="width: 32px;">
            @foreach ($columns as $column)
                <col style="width: 38px;">
                <col style="width: 47px;">
                <col style="width: 49px;">
            @endforeach
        </colgroup>
        <thead>
            <tr>
                <th rowspan="2">No</th>
                @foreach ($columns as $column)
                    <th colspan="3">{{ $column['label'] ?? '' }}</th>
                @endforeach
            </tr>
            <tr class="sub-header">
                @foreach ($columns as $column)
                    <th>Tbl</th>
                    <th>Output</th>
                    <th>Rend</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $index => $row)
                <tr class="{{ ($index + 1) % 2 === 1 ? 'row-odd' : 'row-even' }}">
                    <td class="center">{{ $row['label'] ?? '' }}</td>
                    @foreach ($columns as $column)
                        @php
                            $cell = is_array($row['cells'][$column['key']] ?? null)
                                ? $row['cells'][$column['key']]
                                : [];
                        @endphp
                        <td class="center">{{ $fmtNumber($cell['tebal'] ?? null, 0) }}</td>
                        <td
                            class="number {{ $isOutputBelowTarget($cell['output'] ?? null, $targetRow['cells'][$column['key']] ?? null) ? 'output-below-target' : '' }}">
                            {{ $fmtNumber($cell['output'] ?? null, 2) }}
                        </td>
                        <td class="number">{{ $fmtPercent($cell['rend'] ?? null) }}</td>
                    @endforeach
                </tr>
            @endforeach

            @foreach ($statRows as $statRow)
                <tr class="total-row">
                    <td class="center">{{ $statRow['label'] ?? '' }}</td>
                    @foreach ($columns as $column)
                        @php
                            $cell = is_array($statRow['cells'][$column['key']] ?? null)
                                ? $statRow['cells'][$column['key']]
                                : [];
                        @endphp
                        <td></td>
                        <td class="number">{{ $fmtNumber($cell['output'] ?? null, 2) }}</td>
                        <td class="number">{{ $fmtPercent($cell['rend'] ?? null) }}</td>
                    @endforeach
                </tr>
            @endforeach

            <tr class="target-row">
                <td class="center">{{ $targetRow['label'] ?? 'Target' }}</td>
                @foreach ($columns as $column)
                    <td class="center" colspan="3">
                        {{ $fmtNumber($targetRow['cells'][$column['key']] ?? null, 0, false) }}
                    </td>
                @endforeach
            </tr>
        </tbody>
    </table>
</body>

</html>