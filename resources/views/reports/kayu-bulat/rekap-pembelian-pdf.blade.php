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
            font-size: 9px;
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
            margin: 2px 0 8px 0;
            font-size: 10px;
            color: #636466;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
            page-break-inside: auto;
        }

        th,
        td {
            border: 1px solid #666;
            padding: 3px 4px;
            vertical-align: middle;
        }

        th {
            text-align: center;
            font-weight: 700;
            background: #fff;
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

        .footer-wrap {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }

        .footer-left {
            font-size: 7px;
            font-style: italic;
        }

        .footer-right {
            font-size: 7px;
            font-style: italic;
            text-align: right;
        }

        .chart-wrap {
            border: 1px solid #666;
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
    </style>
</head>

<body>
    @php
        $reportData = is_array($reportData ?? null) ? $reportData : [];
        $tableRows = is_array($reportData['table_rows'] ?? null) ? $reportData['table_rows'] : [];
        $yearlyTotals = is_array($reportData['yearly_totals'] ?? null) ? $reportData['yearly_totals'] : [];
        $monthLabels = is_array($reportData['chart_month_labels'] ?? null) ? $reportData['chart_month_labels'] : [];
        $years = is_array($reportData['chart_years'] ?? null) ? $reportData['chart_years'] : [];
        $seriesByYear = is_array($reportData['chart_series_by_year'] ?? null)
            ? $reportData['chart_series_by_year']
            : [];
        $grandTotal = (float) ($reportData['grand_total'] ?? 0);
        $rawCount = count($reportData['rows'] ?? []);
        $generatedByName = $generatedBy?->name ?? 'sistem';
        $generatedAtText = $generatedAt->copy()->locale('id')->translatedFormat('d M Y H:i');

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
            return number_format($num, 0, ',', '.');
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
        $maxValue = $maxValue > 0 ? $maxValue : 1.0;
        $monthCount = max(count($monthLabels), 1);
        $xStep = $monthCount > 1 ? $plotWidth / ($monthCount - 1) : 0;
        $yTicks = 4;
    @endphp

    <h1 class="report-title">Rekap Pembelian Kayu Bulat</h1>
    <p class="report-subtitle">Rentang Periode Tahun {{ $startYear }} s/d {{ $endYear }}</p>

    <table style="margin-top: 15px;">
        <thead>
            <tr>
                <th>Tahun</th>
                @foreach ($monthLabels as $month)
                    <th>{{ $month }}</th>
                @endforeach
                <th style="font-weight: bold;">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($years as $year)
                @php
                    $monthly = is_array($seriesByYear[$year] ?? null) ? $seriesByYear[$year] : [];
                @endphp
                <tr class="{{ $loop->odd ? 'row-odd' : 'row-even' }}">
                    <td class="label" style="text-align: center">{{ $year }}</td>
                    @foreach ($monthLabels as $index => $month)
                        <td class="number">{{ $fmt4($monthly[$index] ?? 0) }}</td>
                    @endforeach
                    <td class="number" style="font-weight: bold;">
                        {{ $fmt4($yearlyTotals[$year] ?? 0) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="chart-wrap">
        <p class="chart-title">Chart Pembelian Bulanan per Tahun</p>
        <svg width="{{ $svgWidth }}" height="{{ $svgHeight }}"
            viewBox="0 0 {{ $svgWidth }} {{ $svgHeight }}" xmlns="http://www.w3.org/2000/svg">
            <rect x="0" y="0" width="{{ $svgWidth }}" height="{{ $svgHeight }}" fill="#fff" />
            <line x1="{{ $padLeft }}" y1="{{ $padTop + $plotHeight }}" x2="{{ $padLeft + $plotWidth }}"
                y2="{{ $padTop + $plotHeight }}" stroke="#333" stroke-width="1" />
            <line x1="{{ $padLeft }}" y1="{{ $padTop }}" x2="{{ $padLeft }}"
                y2="{{ $padTop + $plotHeight }}" stroke="#333" stroke-width="1" />

            @for ($i = 0; $i <= $yTicks; $i++)
                @php
                    $tickVal = ($maxValue / $yTicks) * $i;
                    $y = $padTop + $plotHeight - $plotHeight * ($i / $yTicks);
                @endphp
                <line x1="{{ $padLeft }}" y1="{{ $y }}" x2="{{ $padLeft + $plotWidth }}"
                    y2="{{ $y }}" stroke="#ddd" stroke-width="1" />
                <text x="{{ $padLeft - 6 }}" y="{{ $y + 3 }}" font-size="9" text-anchor="end"
                    fill="#444">{{ number_format($tickVal, 4, '.', '') }}</text>
            @endfor

            @foreach ($monthLabels as $idx => $month)
                @php $x = $padLeft + ($idx * $xStep); @endphp
                <text x="{{ $x }}" y="{{ $padTop + $plotHeight + 14 }}" font-size="9" text-anchor="middle"
                    fill="#444">{{ $month }}</text>
            @endforeach

            @foreach ($years as $yearIndex => $year)
                @php
                    $series = is_array($seriesByYear[$year] ?? null) ? $seriesByYear[$year] : [];
                    $points = [];
                    foreach ($monthLabels as $idx => $month) {
                        $val = (float) ($series[$idx] ?? 0);
                        $x = $padLeft + $idx * $xStep;
                        $y = $padTop + $plotHeight - ($val / $maxValue) * $plotHeight;
                        $points[] = round($x, 2) . ',' . round($y, 2);
                    }
                    $color = $palette[$yearIndex % count($palette)];
                @endphp
                @if (!empty($points))
                    <polyline fill="none" stroke="{{ $color }}" stroke-width="1.8"
                        points="{{ implode(' ', $points) }}" />
                @endif
            @endforeach
        </svg>
    </div>


    <p class="section-summary">Summary</p>
    <ul class="summary-list">
        <li><span class="summary-label">Jumlah Baris Raw Data Berjumlah</span> {{ $fmtInt($rawCount) }}</li>
        <li><span class="summary-label">Seluruh Total Pembelian (Ton) Berjumlah </span> {{ $fmt4($grandTotal) }} Ton
        </li>
    </ul>

    <htmlpagefooter name="reportFooter">
        <div class="footer-wrap">
            <div class="footer-left">Dicetak oleh: {{ $generatedByName }} pada {{ $generatedAtText }}</div>
            <div class="footer-right">Halaman {PAGENO} dari {nbpg}</div>
        </div>
    </htmlpagefooter>
    <sethtmlpagefooter name="reportFooter" value="on" />
</body>

</html>
