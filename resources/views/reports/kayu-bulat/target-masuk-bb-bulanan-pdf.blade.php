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
            border-collapse: separate;
            border-spacing: 0;
            margin-bottom: 6px;
            page-break-inside: auto;
            table-layout: fixed;
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

        .report-table {
            border: 1px solid #000;
        }

        .chart-wrap {
            margin-top: 8px;
            border: 1px solid #000;
            padding: 8px 8px 6px 8px;
        }

        .chart-title {
            margin: 0 0 6px 0;
            text-align: center;
            font-size: 11px;
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

        .headers-row th {
            font-weight: bold;
            font-size: 11px;
            border-top: 0;
            border-bottom: 1px solid #000;
        }

        .totals-row td {
            font-weight: bold;
            font-size: 11px;
            border: 1px solid #000;
        }

        .report-table tbody tr.data-row td.data-cell {
            border-top: 0 !important;
            border-bottom: 0 !important;
            border-left: 1px solid #000 !important;
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
    </style>
</head>

<body>
    @php
        $generatedByName = $generatedBy?->name ?? 'sistem';
        $generatedAtText = $generatedAt->copy()->locale('id')->translatedFormat('d-M-y H:i');
        $safeDateText = static function ($value): ?string {
            if ($value === null || is_array($value) || (is_object($value) && !$value instanceof \DateTimeInterface)) {
                return null;
            }

            try {
                return \Carbon\Carbon::parse($value)->locale('id')->translatedFormat('d-M-y');
            } catch (\Throwable $exception) {
                return null;
            }
        };
        $formatMonthYearLabel = static function ($value): string {
            $text = trim((string) $value);
            if ($text === '') {
                return '';
            }

            $normalized = str_replace(['/', '.', ' '], '-', strtoupper($text));
            foreach (['M-y', 'M-Y', 'm-y', 'm-Y', 'Y-m', 'Y-M'] as $pattern) {
                try {
                    return \Carbon\Carbon::createFromFormat($pattern, $normalized)
                        ->locale('id')
                        ->translatedFormat('M y');
                } catch (\Throwable $exception) {
                    // try next pattern
                }
            }

            try {
                return \Carbon\Carbon::parse($text)->locale('id')->translatedFormat('M y');
            } catch (\Throwable $exception) {
                $months = [
                    'JAN' => 'Jan',
                    'FEB' => 'Feb',
                    'MAR' => 'Mar',
                    'APR' => 'Apr',
                    'MAY' => 'Mei',
                    'MEI' => 'Mei',
                    'JUN' => 'Jun',
                    'JUL' => 'Jul',
                    'AUG' => 'Agu',
                    'AGU' => 'Agu',
                    'SEP' => 'Sep',
                    'OCT' => 'Okt',
                    'OKT' => 'Okt',
                    'NOV' => 'Nov',
                    'DEC' => 'Des',
                    'DES' => 'Des',
                ];
                $parts = preg_split('/[-\s]+/', strtoupper($text)) ?: [];
                $monthPart = $parts[0] ?? '';
                $yearPart = $parts[1] ?? '';
                $monthText = $months[$monthPart] ?? ucfirst(strtolower($monthPart));
                $yearText = preg_match('/^\d{4}$/', $yearPart) ? substr($yearPart, -2) : $yearPart;

                return trim($monthText . ' ' . $yearText);
            }
        };
        $startText = $safeDateText($startDate ?? null);
        $endText = $safeDateText($endDate ?? null);
        $periodSubtitle =
            $startText && $endText ? "Periode {$startText} s/d {$endText}" : $reportData['period_text'] ?? '';
        $monthColumns = $reportData['month_columns'] ?? [];
        $monthColumnsDisplay = array_map(static function ($month) use ($formatMonthYearLabel) {
            $item = is_array($month) ? $month : ['label' => (string) $month];
            $item['label'] = $formatMonthYearLabel($item['label'] ?? '');
            return $item;
        }, $monthColumns);
        $tableRows = $reportData['table_rows'] ?? [];
        $summaryRows = $reportData['summary_rows'] ?? [];
        $chartLabels = $reportData['chart_labels'] ?? [];
        $chartLabelsDisplay = array_map($formatMonthYearLabel, $chartLabels);
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
        $countLabels = count($chartLabelsDisplay);
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
    <p class="report-subtitle">{{ $periodSubtitle }}</p>

    <table class="report-table" style="margin-bottom: 20px">
        <thead>
            <tr class="headers-row">
                <th>Nama Group</th>
                <th>Target Bulan</th>
                @foreach ($monthColumnsDisplay as $month)
                    <th>{{ $month['label'] }}</th>
                @endforeach
                <th style="font-weight: bold">Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($tableRows as $row)
                <tr class="data-row">
                    <td class="row-label data-cell">{{ $row['jenis'] }}</td>
                    <td class="data-cell">{{ number_format((float) $row['target_bulanan'], 0, '.', ',') }}</td>
                    @foreach ($row['monthly_values'] as $value)
                        <td class="data-cell">{{ number_format((float) $value, 0, '.', ',') }}</td>
                    @endforeach
                    <td class="data-cell" style="font-weight: bold">
                        {{ number_format((float) $row['total'], 0, '.', ',') }}</td>
                </tr>
            @empty
                <tr class="data-row">
                    <td class="data-cell" colspan="99">Tidak ada data.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="table-end-line">
                <td colspan="{{ count($monthColumnsDisplay) + 3 }}"></td>
            </tr>
        </tfoot>
    </table>

    <table class="report-table summary-table" style="margin-bottom: 20px">
        <thead>
            <tr class="headers-row">
                <th>Nama Group</th>
                <th>Avg</th>
                <th>Min</th>
                <th>Max</th>
                <th>Bulan Capai</th>
                <th style="font-weight: bold">% Capai</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($summaryRows as $summary)
                <tr class="data-row">
                    <td class="row-label data-cell">{{ $summary['jenis'] }}</td>
                    <td class="data-cell">{{ number_format((float) $summary['avg'], 0, '.', ',') }}</td>
                    <td class="data-cell">{{ number_format((float) $summary['min'], 0, '.', ',') }}</td>
                    <td class="data-cell">{{ number_format((float) $summary['max'], 0, '.', ',') }}</td>
                    <td class="data-cell">{{ $summary['bulan_capai'] }}/{{ $summary['total_bulan_target'] }}</td>
                    <td class="data-cell" style="font-weight: bold">
                        {{ number_format((float) $summary['persen_capai_group'], 2, '.', ',') }}%</td>
                </tr>
            @empty
                <tr class="data-row">
                    <td class="data-cell" colspan="6">-</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="table-end-line">
                <td colspan="6"></td>
            </tr>
        </tfoot>
    </table>

    <div class="chart-wrap">
        <p class="chart-title">Grafik Target Masuk Bahan Baku Bulanan</p>
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

            @foreach ($chartLabelsDisplay as $index => $label)
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
