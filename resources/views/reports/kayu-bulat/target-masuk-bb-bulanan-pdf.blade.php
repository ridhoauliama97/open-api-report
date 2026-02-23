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
            margin: 16mm 8mm 18mm 8mm;
            footer: html_reportFooter;
        }

        body {
            margin: 0;
            font-family: "Noto Serif", serif;
            font-size: 8px;
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
            font-size: 12px;
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
            border: 1px solid #000;
            padding: 2px 2px;
            text-align: center;
            vertical-align: middle;
            white-space: nowrap;
        }

        th {
            background: #f3f3f3;
            font-weight: 700;
        }

        tbody tr:nth-child(odd) td {
            background: #c9d1df;
        }

        tbody tr:nth-child(even) td {
            background: #eef2f8;
        }

        .row-label {
            text-align: left;
            font-weight: 700;
            padding-left: 3px;
        }

        .summary-table {
            width: 360px;
        }

        .chart-wrap {
            margin-top: 8px;
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
    </style>
</head>

<body>
    @php
        $generatedByName = $generatedBy?->name ?? 'sistem';
        $generatedAtText = $generatedAt->copy()->locale('id')->translatedFormat('d M Y H:i');
        $monthColumns = $reportData['month_columns'] ?? [];
        $tableRows = $reportData['table_rows'] ?? [];
        $summaryRows = $reportData['summary_rows'] ?? [];
        $chartLabels = $reportData['chart_labels'] ?? [];
        $chartSeries = $reportData['chart_series'] ?? [];

        $resolveSeriesColor = static function (string $seriesName): string {
            $key = strtoupper($seriesName);
            if (str_contains($key, 'JABON')) {
                return '#0d6efd';
            }
            if (str_contains($key, 'PULAI')) {
                return '#198754';
            }
            if (str_contains($key, 'RAMBUNG')) {
                return '#dc3545';
            }
            return '#4b5563';
        };

        $maxChartValue = 0;
        foreach ($chartSeries as $seriesValues) {
            foreach ((array) $seriesValues as $value) {
                $maxChartValue = max($maxChartValue, (int) round((float) $value));
            }
        }
        $yStep = 500;
        $maxChartValue = max($yStep, (int) ceil($maxChartValue / $yStep) * $yStep);

        $svgWidth = 980;
        $svgHeight = 330;
        $padLeft = 36;
        $padRight = 10;
        $padTop = 8;
        $padBottom = 40;
        $plotWidth = $svgWidth - $padLeft - $padRight;
        $plotHeight = $svgHeight - $padTop - $padBottom;
        $countLabels = count($chartLabels);
        $xStep = $countLabels > 1 ? $plotWidth / ($countLabels - 1) : 0;
        $yScale = $maxChartValue > 0 ? $plotHeight / $maxChartValue : 1;

        $legendItems = array_keys($chartSeries);
        $legendGap = 18;
        $legendBoxWidth = 8;
        $legendFontWidth = 4.5;
        $legendTotalWidth = 0;
        foreach ($legendItems as $idx => $legendItem) {
            $legendTotalWidth += $legendBoxWidth + 4 + strlen($legendItem) * $legendFontWidth;
            if ($idx < count($legendItems) - 1) {
                $legendTotalWidth += $legendGap;
            }
        }
        $legendStartX = $padLeft + max(0, ($plotWidth - $legendTotalWidth) / 2);
    @endphp

    <h1 class="report-title">Laporan Target Masuk Bahan Baku Bulanan</h1>
    <p class="report-subtitle">{{ $reportData['period_text'] ?? '' }}</p>

    <table>
        <thead>
            <tr>
                <th>Nama Group</th>
                <th>Tgt Bulan</th>
                @foreach ($monthColumns as $month)
                    <th>{{ $month['label'] }}</th>
                @endforeach
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($tableRows as $row)
                <tr>
                    <td class="row-label">{{ $row['jenis'] }}</td>
                    <td>{{ number_format((float) $row['target_bulanan'], 0, ',', '.') }}</td>
                    @foreach ($row['monthly_values'] as $value)
                        <td>{{ number_format((float) $value, 0, ',', '.') }}</td>
                    @endforeach
                    <td>{{ number_format((float) $row['total'], 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="99">Tidak ada data.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <table class="summary-table">
        <thead>
            <tr>
                <th></th>
                <th>Avg</th>
                <th>Min</th>
                <th>Max</th>
                <th>Bulan Capai</th>
                <th>% Capai</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($summaryRows as $summary)
                <tr>
                    <td class="row-label">{{ $summary['jenis'] }}</td>
                    <td>{{ number_format((float) $summary['avg'], 0, ',', '.') }}</td>
                    <td>{{ number_format((float) $summary['min'], 0, ',', '.') }}</td>
                    <td>{{ number_format((float) $summary['max'], 0, ',', '.') }}</td>
                    <td>{{ $summary['bulan_capai'] }}/{{ $summary['total_bulan_target'] }}</td>
                    <td>{{ number_format((float) $summary['persen_capai_group'], 2, ',', '.') }}%</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">-</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="chart-wrap">
        <svg width="{{ $svgWidth }}" height="{{ $svgHeight }}" xmlns="http://www.w3.org/2000/svg">
            @for ($y = 0; $y <= $maxChartValue; $y += $yStep)
                @php
                    $yPos = $padTop + $plotHeight - $y * $yScale;
                @endphp
                <line x1="{{ $padLeft }}" y1="{{ $yPos }}" x2="{{ $padLeft + $plotWidth }}"
                    y2="{{ $yPos }}" stroke="#d1d5db" stroke-width="1" />
                <text x="{{ $padLeft - 4 }}" y="{{ $yPos + 3 }}" font-size="7" text-anchor="end"
                    fill="#111827">{{ $y }}</text>
            @endfor

            <line x1="{{ $padLeft }}" y1="{{ $padTop + $plotHeight }}" x2="{{ $padLeft + $plotWidth }}"
                y2="{{ $padTop + $plotHeight }}" stroke="#111827" stroke-width="1" />
            <line x1="{{ $padLeft }}" y1="{{ $padTop }}" x2="{{ $padLeft }}"
                y2="{{ $padTop + $plotHeight }}" stroke="#111827" stroke-width="1" />

            @foreach ($chartLabels as $index => $label)
                @php $xPos = $padLeft + $index * $xStep; @endphp
                <text x="{{ $xPos }}" y="{{ $padTop + $plotHeight + 12 }}" font-size="7" text-anchor="middle"
                    fill="#111827">{{ $label }}</text>
            @endforeach

            @foreach ($chartSeries as $seriesName => $seriesValues)
                @php
                    $color = $resolveSeriesColor((string) $seriesName);
                    $points = [];
                    $labelPoints = [];
                    foreach ((array) $seriesValues as $i => $rawValue) {
                        $value = (int) round((float) $rawValue);
                        $x = $padLeft + $i * $xStep;
                        $y = $padTop + $plotHeight - $value * $yScale;
                        $points[] = $x . ',' . $y;
                        if ($value > 0) {
                            $labelPoints[] = ['x' => $x, 'y' => $y, 'value' => $value];
                        }
                    }
                @endphp
                @if (!empty($points))
                    <polyline points="{{ implode(' ', $points) }}" fill="none" stroke="{{ $color }}"
                        stroke-width="1.2" />
                    @foreach ($labelPoints as $lp)
                        <circle cx="{{ $lp['x'] }}" cy="{{ $lp['y'] }}" r="1.8"
                            fill="{{ $color }}" />
                        <text x="{{ $lp['x'] }}" y="{{ $lp['y'] - 4 }}" font-size="7" text-anchor="middle"
                            fill="#111827">{{ $lp['value'] }}</text>
                    @endforeach
                @endif
            @endforeach

            @php
                $legendX = $legendStartX;
                $legendY = $svgHeight - 6;
            @endphp
            @foreach ($chartSeries as $seriesName => $seriesValues)
                @php
                    $color = $resolveSeriesColor((string) $seriesName);
                    $itemWidth = $legendBoxWidth + 4 + strlen((string) $seriesName) * $legendFontWidth;
                @endphp
                <rect x="{{ $legendX }}" y="{{ $legendY - 8 }}" width="8" height="8"
                    fill="{{ $color }}" />
                <text x="{{ $legendX + 12 }}" y="{{ $legendY - 1 }}" font-size="7"
                    fill="{{ $color }}">{{ $seriesName }}</text>
                @php
                    $legendX += $itemWidth + $legendGap;
                @endphp
            @endforeach
        </svg>
    </div>

    <htmlpagefooter name="reportFooter">
        <div class="footer-wrap">
            <div class="footer-left">Dicetak oleh: {{ $generatedByName }} pada {{ $generatedAtText }}</div>
            <div class="footer-right">Halaman {PAGENO} dari {nbpg}</div>
        </div>
    </htmlpagefooter>
</body>

</html>
