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
            line-height: 1.15;
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
            margin: 2px 0 10px 0;
            font-size: 12px;
            color: #636466;
        }

        .chart-wrapper {
            text-align: center;
            margin: 10px 0;
        }

        .chart-wrapper img {
            width: 320px;
            height: 320px;
        }

        .dept-labels {
            width: 100%;
            margin: 8px 0;
            font-size: 10px;
        }

        .dept-labels td {
            padding: 1px 6px;
            vertical-align: top;
            width: 50%;
        }

        .dept-label-checkbox {
            display: attachment-block;
            width: 45px;
            height: 45px;
            margin-right: 4px;
            border: 1px solid #000;
            vertical-align: middle;
            overflow: hidden;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            border-spacing: 0;
            border: 1px solid #000;
            margin-top: 6px;
        }

        .data-table th,
        .data-table td {
            border-left: 1px solid #000;
            border-right: 1px solid #000;
            padding: 2px 4px;
            vertical-align: middle;
            word-wrap: break-word;
        }

        .data-table th {
            font-weight: bold;
            font-size: 11px;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            text-align: center;
        }

        .data-table td {
            font-size: 10px;
        }

        .row-odd td {
            background: #c9d1df;
        }

        .row-even td {
            background: #eef2f8;
        }

        .data-table tr.total-row td {
            font-weight: bold;
            border-top: 1px solid #000;
            font-size: 11px;
        }

        .center {
            text-align: center;
        }

        .right {
            text-align: right;
        }

        .nowrap {
            white-space: nowrap;
        }
    </style>
</head>

<body>
    @php
        $departments = $reportData['departments'] ?? [];
        $tableRows = $reportData['tableRows'] ?? $departments;
        $total = (int) ($reportData['total'] ?? 0);
        $pieChart = (string) ($reportData['pie_chart_base64'] ?? '');
        $perDate = \Carbon\Carbon::parse($reportData['per_date'] ?? now())
            ->locale('id')
            ->translatedFormat('d-M-y');
        $generatedAtText = \Carbon\Carbon::parse($generatedAt ?? now())
            ->locale('id')
            ->translatedFormat('d-M-y H:i');
        $generatedByName = trim((string) ($reportData['printed_by'] ?? ''));
        $mid = (int) ceil(count($departments) / 2);
        $leftDepts = array_slice($departments, 0, $mid);
        $rightDepts = array_slice($departments, $mid);
    @endphp

    @include('ascends.shared.partials.report-header', ['subtitle' => 'Per ' . $perDate])

    @if ($pieChart !== '')
        <div class="chart-wrapper">
            <img src="{{ $pieChart }}" alt="Diagram Pie Usia Generasi">
        </div>
    @endif

    @if (count($leftDepts) > 0 || count($rightDepts) > 0)
        <table class="dept-labels">
            <tr>
                <td>
                    @foreach ($leftDepts as $i => $dept)
                        @php
                            $color = \App\Services\Ascends\Shared\Hrm\DiagramKaryawanPerUsiaGenerasiReportService
                                ::CHART_COLORS[$i % 12] ?? [0, 0, 0];
                        @endphp
                        <div>
                            <span class="dept-label-checkbox"
                                style="background: rgb({{ $color[0] }},{{ $color[1] }},{{ $color[2] }});">&nbsp;</span>
                            {{ $dept['name'] }} ({{ $dept['count'] }}) - {{ number_format($dept['percent'], 1) }}%
                        </div>
                    @endforeach
                </td>
                <td>
                    @foreach ($rightDepts as $i => $dept)
                        @php
                            $color = \App\Services\Ascends\Shared\Hrm\DiagramKaryawanPerUsiaGenerasiReportService
                                ::CHART_COLORS[($mid + $i) % 12] ?? [0, 0, 0];
                        @endphp
                        <div>
                            <span class="dept-label-checkbox"
                                style="background: rgb({{ $color[0] }},{{ $color[1] }},{{ $color[2] }});">&nbsp;</span>
                            {{ $dept['name'] }} ({{ $dept['count'] }}) - {{ number_format($dept['percent'], 1) }}%
                        </div>
                    @endforeach
                </td>
            </tr>
        </table>
    @endif

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 8%;">No.</th>
                <th style="width: 52%;">Generasi</th>
                <th style="width: 20%;">Jumlah</th>
                <th style="width: 20%;">%</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($tableRows as $dept)
                <tr class="{{ $loop->odd ? 'row-odd' : 'row-even' }}">
                    <td class="center">{{ $loop->iteration }}</td>
                    <td>{{ $dept['name'] }}</td>
                    <td class="center">{{ $dept['count'] }}</td>
                    <td class="center nowrap">{{ number_format($dept['percent'], 1) }}%</td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td class="center" colspan="2">Total :</td>
                <td class="center">{{ $total }}</td>
                <td class="center nowrap">{{ $total > 0 ? '100.0%' : '0.0%' }}</td>
            </tr>
        </tbody>
    </table>

    @include('reports.partials.pdf-footer-table')
</body>

</html>
