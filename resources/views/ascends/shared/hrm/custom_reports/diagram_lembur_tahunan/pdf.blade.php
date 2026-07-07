<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
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

        .chart-title {
            text-align: center;
            margin: 10px 0 2px 0;
            font-size: 11px;
            font-weight: bold;
        }

        .cost-table {
            border-collapse: collapse;
            border-spacing: 0;
            margin-top: 10px;
            border: 1px solid #000;
        }

        .cost-table th,
        .cost-table td {
            padding: 3px 5px;
            vertical-align: middle;
        }

        .cost-table th {
            font-weight: bold;
            font-size: 11px;
            text-align: center;
            border: 1px solid #000;
        }

        .cost-table td {
            font-size: 10px;
            border-left: 1px solid #000;
            border-right: 1px solid #000;
        }

        .center {
            text-align: center;
        }

        .cost-table .color-swatch {
            display: inline-block;
            width: 16px;
            height: 14px;
            margin-right: 6px;
            vertical-align: middle;
            border: 1px solid #000;
        }
    </style>
</head>

<body>
    @php
        $monthlyDataSt = $reportData['monthly_chart_data_st'] ?? [];
        $monthlyDataKkKt = $reportData['monthly_chart_data_kk_kt'] ?? [];
        $costTable = $reportData['cost_table'] ?? [];
        $hasSt = $reportData['has_st'] ?? false;
        $hasKkKt = $reportData['has_kk_kt'] ?? false;
        $periodLabel = (string) ($reportData['period']['label'] ?? '');
        $generatedAtText = \Carbon\Carbon::parse($generatedAt ?? now())
            ->locale('id')
            ->translatedFormat('d-M-y H:i');
        $generatedByName = trim((string) ($reportData['printed_by'] ?? ''));

        $barColors = ['#2196F3', '#FF9800', '#4CAF50',
                    '#E91E63', '#9C27B0', '#00BCD4',
                    '#FF5722', '#607D8B', '#CDDC39',
                    '#795548', '#03A9F4', '#8BC34A'];

        $allDeptNames = [];
        foreach ($monthlyDataSt as $m) {
            foreach ($m['departments'] as $d) { $allDeptNames[$d['name']] = true; }
        }
        foreach ($monthlyDataKkKt as $m) {
            foreach ($m['departments'] as $d) { $allDeptNames[$d['name']] = true; }
        }

        $assigned = [];
        $idx = 0;
        foreach (array_keys($allDeptNames) as $dept) {
            $assigned[$dept] = $barColors[$idx % count($barColors)];
            $idx++;
        }

        $deptColor = static function (string $dept) use ($assigned): string {
            return $assigned[$dept] ?? '#CCCCCC';
        };

        $barGap = 2;
        $groupGap = 8;
        $marginLeft = 50;
        $marginRight = 20;
        $marginTop = 15;
        $marginBottom = 45;
        $chartLeft = $marginLeft;
        $chartTop = $marginTop;
        $chartBottom = 140;
        $chartHeight = $chartBottom - $chartTop;
        $barWidth = 30;

        $calcChartWidth = static function (array $chartData) use ($barWidth, $barGap, $groupGap, $marginLeft, $marginRight): float {
            if ($chartData === []) return 0;
            $total = $marginLeft;
            $monthCount = count($chartData);
            foreach ($chartData as $i => $month) {
                $deptCount = is_array($month['departments'] ?? null) ? count($month['departments']) : 0;
                $total += $deptCount * ($barWidth + $barGap);
                if ($i < $monthCount - 1) $total += $groupGap;
            }
            return $total + $marginRight;
        };

        $requiredWidthSt = $calcChartWidth($monthlyDataSt);
        $requiredWidthKkKt = $calcChartWidth($monthlyDataKkKt);
        $svgWidth = (int) max(1100, $requiredWidthSt, $requiredWidthKkKt);
        $chartRight = $svgWidth - $marginRight;
        $chartWidth = $chartRight - $chartLeft;
    @endphp

    @include('ascends.shared.partials.report-header', ['subtitle' => $periodLabel])

    @if ($hasSt)
        @include('ascends.shared.hrm.custom_reports.diagram_lembur_tahunan._chart', [
            'chartTitle' => 'ST',
            'chartData' => $monthlyDataSt,
            'deptColor' => $deptColor,
            'svgWidth' => $svgWidth,
            'chartLeft' => $chartLeft,
            'chartRight' => $chartRight,
            'chartTop' => $chartTop,
            'chartBottom' => $chartBottom,
            'chartHeight' => $chartHeight,
            'barWidth' => $barWidth,
            'barGap' => $barGap,
            'groupGap' => $groupGap,
        ])
    @endif

    @if ($hasKkKt)
        @include('ascends.shared.hrm.custom_reports.diagram_lembur_tahunan._chart', [
            'chartTitle' => 'KK/KT',
            'chartData' => $monthlyDataKkKt,
            'deptColor' => $deptColor,
            'svgWidth' => $svgWidth,
            'chartLeft' => $chartLeft,
            'chartRight' => $chartRight,
            'chartTop' => $chartTop,
            'chartBottom' => $chartBottom,
            'chartHeight' => $chartHeight,
            'barWidth' => $barWidth,
            'barGap' => $barGap,
            'groupGap' => $groupGap,
        ])
    @endif

    @if ($costTable !== [])
        <div style="margin-top: 8px; text-align: center;">
            <table class="cost-table" style="margin: 0 auto;">
                <thead>
                    <tr>
                        <th style="width: 5%;">No</th>
                        <th style="width: 45%;">Departemen</th>
                        <th style="width: 25%;">Staff</th>
                        <th style="width: 25%;">KK/KT</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($costTable as $row)
                        <tr>
                            <td class="center">{{ $loop->iteration }}</td>
                            <td>
                                <span class="color-swatch" style="background: {{ $deptColor($row['department']) }};">&nbsp;</span>
                                {{ $row['department'] }}
                            </td>
                            <td style="text-align: right;">{{ $row['staff_cost'] > 0 ? 'Rp '.number_format($row['staff_cost'], 0, ',', '.') : '-' }}</td>
                            <td style="text-align: right;">{{ $row['kk_kt_cost'] > 0 ? 'Rp '.number_format($row['kk_kt_cost'], 0, ',', '.') : '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
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
