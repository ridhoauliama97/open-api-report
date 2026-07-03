<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta charset="utf-8">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif:ital,wght@0,100..900;1,100..900&display=swap"
        rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        @page {
            margin: 14mm 10mm 14mm 10mm;
            footer: html_reportFooter;
        }

        body {
            margin: 0;
            font-family: "Noto Serif", serif;
            font-size: 10px;
            line-height: 1.2;
            color: #000;
        }

        .report-companyTitle {
            text-align: center;
            margin: 0 0 4px 0;
            font-size: 18px;
            font-weight: bold;
        }

        .report-title {
            text-align: center;
            margin: 0;
            font-size: 16px;
            font-weight: bold;
        }

        .report-subtitle {
            text-align: center;
            margin: 2px 0 12px 0;
            font-size: 12px;
            color: #636466;
        }

        .chart-container {
            text-align: center;
            margin: 0 auto;
        }

        .chart-legend {
            text-align: center;
            margin-top: 8px;
            font-size: 9px;
        }

        .chart-legend-item {
            display: inline;
            margin: 0 8px;
        }
    </style>
</head>

<body>
    @php
        $monthlyData = $reportData['monthly_chart_data'] ?? [];
        $periodLabel = (string) ($reportData['period']['label'] ?? '');
        $generatedAtText = \Carbon\Carbon::parse($generatedAt ?? now())
            ->locale('id')
            ->translatedFormat('d-M-y H:i');
        $generatedByName = trim((string) ($reportData['printed_by'] ?? ''));

        $barColors = ['#2196F3', '#FF9800', '#4CAF50',
                    '#E91E63', '#9C27B0', '#00BCD4',
                    '#FF5722', '#607D8B', '#CDDC39',
                    '#795548', '#03A9F4', '#8BC34A'];

        $deptColor = static function (string $dept) use ($barColors): string {
            static $assigned = [];
            static $idx = 0;
            if (!isset($assigned[$dept])) {
                $assigned[$dept] = $barColors[$idx % count($barColors)];
                $idx++;
            }
            return $assigned[$dept];
        };

        $monthCount = count($monthlyData);
        $barGap = 2;
        $groupGap = 8;
        $marginLeft = 50;
        $marginRight = 20;
        $marginTop = 15;
        $marginBottom = 45;
        $svgWidth = 1100;
        $chartLeft = $marginLeft;
        $chartRight = $svgWidth - $marginRight;
        $chartWidth = $chartRight - $chartLeft;
        $chartTop = $marginTop;
        $chartBottom = 300;
        $chartHeight = $chartBottom - $chartTop;

        $barWidth = 30;
        $barHeightFactor = $chartHeight / 100;
    @endphp

    @include('ascends.shared.partials.report-header', ['subtitle' => $periodLabel])

    @if ($monthlyData === [])
        <div style="text-align: center; font-style: italic; font-weight: bold; padding: 40px 0;">Tidak Ada Data</div>
    @else
        <div class="chart-container">
            <svg width="{{ $svgWidth }}" height="360" xmlns="http://www.w3.org/2000/svg" style="font-family: 'Noto Serif', serif; font-size: 9px;">
                {{-- Y-axis gridlines + labels per 10% --}}
                @for ($i = 0; $i <= 10; $i++)
                    @php
                        $y = $chartBottom - ($i / 10) * $chartHeight;
                        $isZero = $i === 0;
                    @endphp
                    <line x1="{{ $chartLeft }}" y1="{{ $y }}" x2="{{ $chartRight }}" y2="{{ $y }}"
                        stroke="#000" stroke-width="{{ $isZero ? 1 : 0.5 }}" stroke-dasharray="{{ $isZero ? 'none' : '3,3' }}" />
                    <text x="{{ $chartLeft - 6 }}" y="{{ $y + 3 }}" text-anchor="end" font-size="9">{{ $i * 10 }}%</text>
                @endfor

                {{-- Y-axis line --}}
                <line x1="{{ $chartLeft }}" y1="{{ $chartTop }}" x2="{{ $chartLeft }}" y2="{{ $chartBottom }}"
                    stroke="#000" stroke-width="1" />

                {{-- X-axis line --}}
                <line x1="{{ $chartLeft }}" y1="{{ $chartBottom }}" x2="{{ $chartRight }}" y2="{{ $chartBottom }}"
                    stroke="#000" stroke-width="1" />

                {{-- Bars + month labels --}}
                @php
                    $currentX = $chartLeft;
                @endphp
                @foreach ($monthlyData as $monthIdx => $month)
                    @php
                        $groupStartX = $currentX;
                        $groupWidth = 0;
                        $deptBars = $month['departments'];
                    @endphp
                    @foreach ($deptBars as $barIdx => $dept)
                        @php
                            $pct = (float) ($dept['percentage'] ?? 0);
                            $barHeight = $chartHeight * ($pct / 100);
                            $barY = $chartBottom - $barHeight;
                            $color = $deptColor($dept['name']);
                        @endphp
                        <rect x="{{ $currentX }}" y="{{ $barY }}" width="{{ $barWidth }}" height="{{ $barHeight }}"
                            fill="{{ $color }}" stroke="#000" stroke-width="0.5" />
                        @php
                            $currentX += $barWidth + $barGap;
                            $groupWidth += $barWidth + $barGap;
                        @endphp
                    @endforeach

                    {{-- Month label --}}
                    @php
                        $groupCenterX = $groupStartX + ($groupWidth / 2) - ($barGap / 2);
                        $monthLabelFull = explode(' ', $month['month_label'] ?? '');
                        $monthMap = ['Januari'=>'Jan','Februari'=>'Feb','Maret'=>'Mar','April'=>'Apr','Mei'=>'Mei','Juni'=>'Jun',
                            'Juli'=>'Jul','Agustus'=>'Agu','September'=>'Sep','Oktober'=>'Okt','November'=>'Nov','Desember'=>'Des'];
                        $monthShort = $monthMap[$monthLabelFull[0] ?? ''] ?? ($monthLabelFull[0] ?? '');
                        $yearShort = $monthLabelFull[1] ?? '';
                    @endphp
                    <text x="{{ $groupCenterX }}" y="{{ $chartBottom + 18 }}" text-anchor="middle" font-size="9" font-weight="bold">{{ $monthShort }} {{ $yearShort }}</text>

                    {{-- Total hours below month label --}}
                    <text x="{{ $groupCenterX }}" y="{{ $chartBottom + 30 }}" text-anchor="middle" font-size="8" fill="#636466">{{ number_format((float) $month['total_hours'], 1) }} Jam</text>

                    @php
                        $currentX += $groupGap;
                    @endphp
                @endforeach
            </svg>

            {{-- Legend --}}
            <div class="chart-legend">
                @php
                    $legendDepts = [];
                    foreach ($monthlyData as $month) {
                        foreach ($month['departments'] as $dept) {
                            $legendDepts[$dept['name']] = $deptColor($dept['name']);
                        }
                    }
                @endphp
                @foreach ($legendDepts as $name => $color)
                    <span class="chart-legend-item">
                        <svg width="12" height="12" style="vertical-align: middle;">
                            <rect x="0" y="0" width="12" height="12" fill="{{ $color }}" stroke="#000" stroke-width="0.5" />
                        </svg>
                        {{ $name }}
                    </span>
                @endforeach
            </div>

            {{-- Cost summary card --}}
            @php
                $deptCosts = $reportData['department_costs'] ?? [];
            @endphp
            @if (!empty($deptCosts))
                <div style="margin-top: 14px; border: 1px solid #000; padding: 0;">
                    <div style="font-weight: bold; font-size: 12px; padding: 5px 6px; background: #eef2f8; border-bottom: 1px solid #000;">Rekapitulasi Biaya Lembur</div>
                    <table style="width: 100%; border-collapse: collapse;">
                        @foreach ($deptCosts as $dept => $cost)
                            <tr>
                                <td style="width: 50%; padding: 4px 6px; border-bottom: 1px solid #000;">{{ $dept }}</td>
                                <td style="width: 50%; padding: 4px 6px; border-bottom: 1px solid #000; text-align: right;">Rp {{ number_format((float) $cost, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </table>
                </div>
            @endif
        </div>
    @endif

    <htmlpagefooter name="reportFooter">
        @include('reports.partials.pdf-footer-table', [
            'generatedByName' => $generatedByName,
            'generatedAtText' => $generatedAtText,
        ])
    </htmlpagefooter>
</body>

</html>
