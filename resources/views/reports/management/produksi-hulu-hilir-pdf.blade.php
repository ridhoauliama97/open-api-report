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
            sheet-size: A4-L;
            margin: 12mm 6mm 12mm 6mm;
            footer: html_reportFooter;
        }

        body {
            margin: 0;
            font-family: "Noto Serif", serif;
            font-size: 10px;
            line-height: 1.1;
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

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .report-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #000;
            table-layout: fixed;
            page-break-inside: auto;
        }

        thead {
            display: table-header-group;
        }

        tfoot {
            display: table-footer-group;
        }

        tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }

        th,
        td {
            border: 0;
            border-left: 1px solid #000;
            padding: 2px 3px;
            vertical-align: middle;
        }

        th:first-child,
        td:first-child {
            border-left: 0;
        }

        th {
            text-align: center;
            font-weight: bold;
            border-bottom: 1px solid #000;
        }

        tbody td {
            border-top: 0;
            border-bottom: 0;
        }

        .row-odd td {
            background: #c9d1df;
        }

        .row-even td {
            background: #eef2f8;
        }

        .total-row td {
            font-weight: bold;
            border-top: 1px solid #000 !important;
            background: #fff !important;
            font-size: 11px;
        }

        .target-row td {
            font-weight: bold;
            border-top: 1px solid #000 !important;
            background: #fff !important;
        }

        .table-end-line td {
            border-top: 1px solid #000 !important;
            border-right: 0 !important;
            border-bottom: 0 !important;
            border-left: 0 !important;
            padding: 0 !important;
            height: 0 !important;
            line-height: 0 !important;
            background: #fff !important;
        }

        .center {
            text-align: center;
        }

        .number {
            text-align: right;
            white-space: nowrap;
            font-family: "Calibri", "DejaVu Sans", sans-serif;
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
        $start = \Carbon\Carbon::parse($startDate)->locale('id')->translatedFormat('d-M-y');
        $end = \Carbon\Carbon::parse($endDate)->locale('id')->translatedFormat('d-M-y');

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
            <col style="width: 36px;">
            @foreach ($columns as $column)
                <col style="width: 42px;">
                <col style="width: 52px;">
                <col style="width: 54px;">
            @endforeach
        </colgroup>
        <thead>
            <tr>
                <th rowspan="2">No</th>
                @foreach ($columns as $column)
                    <th colspan="3">{!! $column['label'] ?? '' !!}</th>
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
                            {{ $fmtNumber($cell['output'] ?? null, 2) }}</td>
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
                        {{ $fmtNumber($targetRow['cells'][$column['key']] ?? null, 0, false) }}</td>
                @endforeach
            </tr>
        </tbody>
    </table>

    <div style="page-break-before: always;">
        <h2 style="text-align: center; margin: 0 0 8px 0; font-size: 12px; font-weight: bold;">Rangkuman</h2>
        <table class="report-table">
            <thead>
                <tr>
                    <th rowspan="2" style="width: 5%;">No</th>
                    <th rowspan="2">Nama Mesin</th>
                    <th colspan="3">Jam Kerja Normal</th>
                    <th colspan="3">Jam Kerja Lembur</th>
                </tr>
                <tr>
                    <th>TK</th>
                    <th>HM</th>
                    <th>mtr3</th>
                    <th>TK</th>
                    <th>HM</th>
                    <th>mtr3</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($summaryRows as $row)
                    <tr class="{{ $loop->index % 2 === 0 ? 'row-odd' : 'row-even' }}">
                        <td class="center">{{ $row['No'] ?? '' }}</td>
                        <td>{{ $row['NamaMesin'] ?? '-' }}</td>
                        <td class="center">{{ $fmtBlank($row['total_tk'] ?? null, 0) }}</td>
                        <td class="center">{{ $fmtBlank($row['total_hm'] ?? null, 0) }}</td>
                        <td class="number">{{ $fmtBlank($row['Output'] ?? null, 4) }}</td>
                        <td class="center">{{ $fmtBlank($row['total_tk_lembur'] ?? null, 0) }}</td>
                        <td class="center">{{ $fmtBlank($row['total_hm_lembur'] ?? null, 0) }}</td>
                        <td class="number">{{ $fmtBlank($row['OutputLembur'] ?? null, 4) }}</td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="2" class="center">Grand Total</td>
                    <td class="center">{{ $fmtBlank($grandTotals['total_tk'] ?? null, 0) }}</td>
                    <td class="center">{{ $fmtBlank($grandTotals['total_hm'] ?? null, 0) }}</td>
                    <td class="number">{{ $fmtBlank($grandTotals['output'] ?? null, 4) }}</td>
                    <td class="center">{{ $fmtBlank($grandTotals['total_tk_lembur'] ?? null, 0) }}</td>
                    <td class="center">{{ $fmtBlank($grandTotals['total_hm_lembur'] ?? null, 0) }}</td>
                    <td class="number">{{ $fmtBlank($grandTotals['output_lembur'] ?? null, 4) }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    @include('reports.partials.pdf-footer-table')
</body>

</html>
