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
            margin: 20mm 8mm 20mm 8mm;
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

        .section-title {
            margin: 20px 0 4px;
            font-size: 12px;
            font-weight: bold;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
            page-break-inside: auto;
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
            display: table-row-group;
        }

        tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 2px 3px;
            vertical-align: middle;
        }

        th {
            text-align: center;
            font-weight: bold;
            font-size: 10px;
        }

        td.label {
            white-space: nowrap;
        }

        td.number {
            text-align: right;
            white-space: nowrap;
            font-family: "Calibri", "DejaVu Sans", sans-serif;
        }

        .row-odd td {
            background: #c9d1df;
        }

        .row-even td {
            background: #eef2f8;
        }

        .headers-row th {
            font-weight: bold;
            font-size: 11px;
            border-top: 1px solid #000;
            border-right: 1px solid #000;
            border-bottom: 1px solid #000;
            border-left: 0;
        }

        .report-table thead tr.headers-row:first-child th[rowspan] {
            border-bottom: 1px solid #000;
        }

        .report-table thead tr.headers-row:first-child th[colspan] {
            border-bottom: 0;
        }

        .report-table thead tr.headers-row:last-child th {
            border-top: 1px solid #000;
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
            background: transparent !important;
        }

        .highlight-col {
            background: #8fe9e8;
        }

        .footer-wrap {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }

        .footer-left,
        .footer-right {
            font-size: 8px;
            font-style: italic;
        }

        .footer-right {
            text-align: right;
        }

        .summary-table .totals-row td {
            border: 1px solid #000;
        }
    </style>
</head>

<body>
    @php
        $reportData = is_array($reportData ?? null) ? $reportData : [];
        $columns = is_array($reportData['columns'] ?? null) ? $reportData['columns'] : [];
        $rows = is_array($reportData['rows'] ?? null) ? $reportData['rows'] : [];
        $sAkhirByColumn = is_array($reportData['s_akhir_by_column'] ?? null) ? $reportData['s_akhir_by_column'] : [];
        $percentByColumn = is_array($reportData['percent_by_column'] ?? null) ? $reportData['percent_by_column'] : [];
        $ctrByColumn = is_array($reportData['ctr_by_column'] ?? null) ? $reportData['ctr_by_column'] : [];
        $totals = is_array($reportData['totals'] ?? null) ? $reportData['totals'] : ['s_akhir' => 0, 'ctr' => 0];
        $highlightColumns = [];

        $startText = \Carbon\Carbon::parse($startDate)->locale('id')->translatedFormat('d-M-y');
        $endText = \Carbon\Carbon::parse($endDate)->locale('id')->translatedFormat('d-M-y');
        $generatedByName = $generatedBy?->name ?? 'sistem';
        $generatedAtText = $generatedAt->copy()->locale('id')->translatedFormat('d-M-y H:i');

        $fmt1 = static fn($v): string => number_format((float) ($v ?? 0), 1, '.', ',');
        $fmt2 = static fn($v): string => number_format((float) ($v ?? 0), 2, '.', ',');
        $fmtPct = static fn($v): string => number_format((float) ($v ?? 0), 1, '.', ',') . '%';
        $totalCtrDisplay = ceil((float) ($totals['ctr'] ?? 0) * 100) / 100;
    @endphp

    <h1 class="report-title">Laporan Dashboard Sanding</h1>
    <p class="report-subtitle">Dari {{ $startText }} s/d {{ $endText }}</p>

    <table class="report-table">
        <thead>
            <tr class="headers-row">
                <th rowspan="2" style="width: 58px;">Tanggal</th>
                @foreach ($columns as $column)
                    <th colspan="2" class="{{ in_array($column, $highlightColumns, true) ? 'highlight-col' : '' }}">
                        {{ $column }}</th>
                @endforeach
            </tr>
            <tr class="headers-row">
                @foreach ($columns as $column)
                    <th>Masuk</th>
                    <th>Keluar</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr class="data-row {{ $loop->odd ? 'row-odd' : 'row-even' }}">
                    <td class="data-cell label" style="text-align: center;">
                        {{ \Carbon\Carbon::parse((string) ($row['date'] ?? now()))->locale('id')->translatedFormat('d-M-y') }}
                    </td>
                    @foreach ($columns as $column)
                        @php
                            $inflow = (float) ($row['cells'][$column]['in'] ?? 0 ?: 0);
                            $outflow = (float) ($row['cells'][$column]['out'] ?? 0 ?: 0);
                            $isHighlight = in_array($column, $highlightColumns, true);
                        @endphp
                        <td
                            class="data-cell number {{ $isHighlight && abs($inflow) >= 0.000001 ? 'highlight-col' : '' }}">
                            {{ abs($inflow) < 0.000001 ? '' : $fmt1($inflow) }}</td>
                        <td
                            class="data-cell number {{ $isHighlight && abs($outflow) >= 0.000001 ? 'highlight-col' : '' }}">
                            {{ abs($outflow) < 0.000001 ? '' : $fmt1($outflow) }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ 1 + count($columns) * 2 }}" style="text-align: center;">Data tidak tersedia.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="totals-row">
                <td class="label">S Akhir</td>
                @foreach ($columns as $column)
                    @php
                        $sAkhirValue = (float) ($sAkhirByColumn[$column] ?? 0);
                        $pctValue = (float) ($percentByColumn[$column] ?? 0);
                    @endphp
                    <td class="number">{{ abs($sAkhirValue) < 0.000001 ? '' : $fmt1($sAkhirValue) }}</td>
                    <td class="number">{{ abs($sAkhirValue) < 0.000001 ? '' : $fmtPct($pctValue) }}</td>
                @endforeach
            </tr>
            <tr class="totals-row">
                <td class="label"># Ctr</td>
                @foreach ($columns as $column)
                    @php $ctrValue = (float) ($ctrByColumn[$column] ?? 0); @endphp
                    <td class="number" colspan="2" style="text-align: center">
                        {{ abs($ctrValue) < 0.000001 ? '' : $fmt2($ctrValue) }}</td>
                @endforeach
            </tr>
        </tfoot>
    </table>

    <p class="section-title">Total</p>
    <table class="summary-table" style="width: 230px;">
        <tr class="totals-row">
            <td class="label" style="width: 90px;">S Akhir</td>
            <td class="number" style="width: 70px;">{{ $fmt2($totals['s_akhir'] ?? 0) }}</td>
        </tr>
        <tr class="totals-row">
            <td class="label"># Ctr</td>
            <td class="number">{{ $fmt2($totalCtrDisplay) }}</td>
        </tr>
    </table>

    <htmlpagefooter name="reportFooter">
        <div class="footer-wrap">
            <div class="footer-left">Dicetak oleh: {{ $generatedByName }} pada {{ $generatedAtText }}</div>
            <div class="footer-right">Halaman {PAGENO} dari {nbpg}</div>
        </div>
    </htmlpagefooter>
    <sethtmlpagefooter name="reportFooter" value="on" />
</body>

</html>
