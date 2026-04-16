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
            margin: 18mm 10mm 18mm 10mm;
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

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
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

        tfoot {
            display: table-footer-group;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 3px 4px;
            vertical-align: middle;
        }

        th {
            text-align: center;
            font-weight: bold;
            font-weight: 11px;
        }

        td.label {
            white-space: nowrap;
        }

        td.number {
            text-align: right;
            white-space: nowrap;
            font-family: "Calibri", sans-serif;
        }

        .row-odd td {
            background: #c9d1df;
        }

        .row-even td {
            background: #eef2f8;
        }

        p.section-summary {
            font-size: 12px;
            font-weight: 700;
            margin-top: 15px;
            text-decoration: underline;
        }

        .chart-wrap {
            border: 1px solid #000;
            padding: 6px;
            margin-top: 10px;
            margin-bottom: 10px;
        }

        .chart-title {
            text-align: center;
            font-weight: 700;
            margin: 0 0 5px 0;
        }

        .summary-list {
            margin: 4px 0 10px 0;
            padding-left: 16px;
        }

        .summary-list li {
            margin: 2px 0;
            font-size: 10px;
        }

        .summary-label {
            display: inline-block;
            min-width: 170px;
            font-weight: 700;
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
        }

        .report-table tbody tr.data-row td.data-cell {
            border-top: 0 !important;
            border-bottom: 0 !important;
            border-left: 0 !important;
            border-right: 1px solid #000 !important;
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

        @include('reports.partials.pdf-footer-table-style')
    </style>
</head>

<body>
    @php
        $reportData = is_array($reportData ?? null) ? $reportData : [];
        $tableRows = is_array($reportData['table_rows'] ?? null) ? $reportData['table_rows'] : [];
        $yearlyTotals = is_array($reportData['yearly_totals'] ?? null) ? $reportData['yearly_totals'] : [];
        $monthLabels = is_array($reportData['chart_month_labels'] ?? null) ? $reportData['chart_month_labels'] : [];
        $monthHeaderLabels = range(1, max(count($monthLabels), 12));
        $years = is_array($reportData['chart_years'] ?? null) ? $reportData['chart_years'] : [];
        $seriesByYear = is_array($reportData['chart_series_by_year'] ?? null)
            ? $reportData['chart_series_by_year']
            : [];
        $grandTotal = (float) ($reportData['grand_total'] ?? 0);
        $rawCount = count($reportData['rows'] ?? []);
        $generatedByName = $generatedBy?->name ?? 'sistem';
        $generatedAtText = $generatedAt->copy()->locale('id')->translatedFormat('d-M-y H:i');

        $fmt4 = static function ($value): string {
            $num = (float) ($value ?? 0);
            if (abs($num) < 0.0000001) {
                return '';
            }
            return number_format($num, 4, '.', ',');
        };

        $fmtInt = static function ($value): string {
            $num = (float) ($value ?? 0);
            if (abs($num) < 0.0000001) {
                return '';
            }
            return number_format($num, 0, '.', ',');
        };

        $svgWidth = 960;
        $svgHeight = 260;
        $padLeft = 52;
        $padRight = 14;
        $padTop = 14;
        $padBottom = 34;
        $plotWidth = $svgWidth - $padLeft - $padRight;
        $plotHeight = $svgHeight - $padTop - $padBottom;
        $palette = ['#0d6efd', '#198754', '#dc3545', '#fd7e14', '#6f42c1', '#20c997', '#d63384', '#6c757d'];
        $maxValue = 0.0;
        foreach ($years as $year) {
            $yearSeries = is_array($seriesByYear[$year] ?? null) ? $seriesByYear[$year] : [];
            foreach ($yearSeries as $val) {
                $num = (float) $val;
                if ($num > $maxValue) {
                    $maxValue = $num;
                }
            }
        }
        $yStep = 10000.0;
        $maxValue = $maxValue > 0 ? ceil($maxValue / $yStep) * $yStep : $yStep;
        $monthCount = max(count($monthLabels), 1);
        $xStep = $monthCount > 1 ? $plotWidth / ($monthCount - 1) : 0;
        $yTicks = max((int) ($maxValue / $yStep), 1);
    @endphp

    <h1 class="report-title">Laporan Rekap Pembelian Kayu Bulat (Ton)</h1>
    <p class="report-subtitle">Periode {{ $startYear }} s/d {{ $endYear }}</p>

    <table class="report-table" style="margin-top: 15px;">
        <thead>
            <tr class="headers-row">
                <th style="font-weight: bold; font-size:11px">Tahun</th>
                @foreach ($monthHeaderLabels as $month)
                    <th style="font-weight: bold; font-size:11px">{{ $month }}</th>
                @endforeach
                <th style="font-weight: bold; font-size:11px">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($years as $year)
                @php
                    $monthly = is_array($seriesByYear[$year] ?? null) ? $seriesByYear[$year] : [];
                @endphp
                <tr class="data-row {{ $loop->odd ? 'row-odd' : 'row-even' }}">
                    <td class="label data-cell" style="text-align: center; font-weight: bold; font-size:11px;">
                        {{ $year }}</td>
                    @foreach ($monthHeaderLabels as $index => $month)
                        <td class="number data-cell">{{ $fmt4($monthly[$index] ?? 0) }}</td>
                    @endforeach
                    <td class="number data-cell" style="font-weight: bold;">{{ $fmt4($yearlyTotals[$year] ?? 0) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @include('reports.partials.pdf-footer-table')
</body>

</html>
