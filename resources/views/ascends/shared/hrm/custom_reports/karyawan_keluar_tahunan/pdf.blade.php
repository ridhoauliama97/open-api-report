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
            margin: 2px 0 20px 0;
            font-size: 12px;
            color: #636466;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            page-break-inside: auto;
            border-spacing: 0;
            border: 1px solid #000;
        }

        .data-table th,
        .data-table td {
            border-left: 1px solid #000;
            border-right: 1px solid #000;
            padding: 2px 2px;
            vertical-align: middle;
            word-wrap: break-word;
        }

        .data-table th {
            font-weight: bold;
            font-size: 10px;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            text-align: center;
        }

        .row-odd td {
            background: #c9d1df;
        }

        .row-even td {
            background: #eef2f8;
        }

        .total-row td {
            font-weight: bold;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            font-size: 11px;
        }

        .center {
            text-align: center;
        }

        .nowrap {
            white-space: nowrap;
        }

        .empty-row td {
            text-align: center;
            font-style: italic;
            background: #c9d1df;
            color: #9c111d;
            font-weight: bold;
            font-size: 11px;
        }
    </style>
</head>

<body>
    @php
        $monthColumns = $reportData['month_columns'] ?? [];
        $rows = $reportData['rows'] ?? [];
        $totals = $reportData['totals'] ?? [];
        $totalByMonth = $totals['by_month'] ?? [];
        $grandTotal = (int) ($totals['grand_total'] ?? 0);
        $headerCompany = $reportData['headerCompany'] ?? '';
        $headerTitle = $reportData['headerTitle'] ?? 'Laporan Karyawan Keluar Tahunan';
        $subtitle = $reportData['subtitle'] ?? '';
        $generatedAtText = $reportData['printed_at'] ?? '';
        $generatedByName = trim((string) ($reportData['printed_by'] ?? ''));
        $monthCount = count($monthColumns);
        $noWidth = 4;
        $deptWidth = 20;
        $totalWidth = 8;
        $remainingWidth = 100 - $noWidth - $deptWidth - $totalWidth;
        $subWidth = $monthCount > 0 ? (int) floor($remainingWidth / ($monthCount * 2)) : 5;
        $monthWidth = $subWidth * 2;
    @endphp

    <h1 class="report-companyTitle">{{ $headerCompany }}</h1>
    <h1 class="report-title">{{ $headerTitle }}</h1>
    <p class="report-subtitle">{{ $subtitle }}</p>

    <table class="data-table">
        <thead>
            <tr>
                <th rowspan="2" style="width: {{ $noWidth }}%;">No</th>
                <th rowspan="2" style="width: {{ $deptWidth }}%;">Departemen</th>
                @foreach ($monthColumns as $col)
                    <th colspan="2" style="width: {{ $monthWidth }}%;">{{ $col['label'] }}</th>
                @endforeach
                <th rowspan="2" style="width: {{ $totalWidth }}%;">Total</th>
            </tr>
            <tr>
                @foreach ($monthColumns as $col)
                    <th style="width: {{ $subWidth }}%;">Keluar</th>
                    <th style="width: {{ $subWidth }}%;">%</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr class="{{ $loop->odd ? 'row-odd' : 'row-even' }}">
                    <td class="center">{{ $loop->iteration }}</td>
                    <td>{{ (string) ($row['departemen'] ?? '') }}</td>
                    @foreach ($monthColumns as $col)
                        @php
                            $v = $row['values'][(string) $col['period']] ?? null;
                            $keluar = $v ? (int) $v['keluar'] : 0;
                            $persen = $v ? (int) $v['persen'] : 0;
                        @endphp
                        <td class="center">{{ $keluar }}</td>
                        <td class="center">{{ $persen }}%</td>
                    @endforeach
                    <td class="center">{{ (int) ($row['total'] ?? 0) }}</td>
                </tr>
            @empty
                <tr class="empty-row">
                    <td colspan="{{ 3 + $monthCount * 2 }}">Tidak ada data.</td>
                </tr>
            @endforelse

            @if (count($rows) > 0)
                <tr class="total-row">
                    <td colspan="2" class="center">Total</td>
                    @foreach ($monthColumns as $col)
                        @php
                            $tv = $totalByMonth[(string) $col['period']] ?? null;
                            $totalKeluar = $tv ? (int) $tv['keluar'] : 0;
                        @endphp
                        <td class="center" colspan="2">{{ $totalKeluar }}</td>
                    @endforeach
                    <td class="center">{{ $grandTotal }}</td>
                </tr>
            @endif
        </tbody>
    </table>

    <htmlpagefooter name="reportFooter">
        @include('reports.partials.pdf-footer-table', [
            'generatedByName' => $generatedByName,
            'generatedAtText' => $generatedAtText,
        ])
    </htmlpagefooter>
</body>

</html>
