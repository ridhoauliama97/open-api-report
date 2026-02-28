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

        thead {
            display: table-header-group;
        }

        tfoot {
            display: table-row-group;
        }

        tr {
            border: 1px solid #000;
            page-break-inside: avoid;
            page-break-after: auto;
        }

        th,
        td {
            border: 1px solid #666;
            padding: 2px 3px;
            vertical-align: middle;
        }

        th {
            text-align: center;
            font-weight: 700;
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
            border: 1.5px solid #000;
            font-weight: bold;
            font-size: 11px;
        }

        .totals-row td {
            font-weight: bold;
            font-size: 11px;
            border: 1.5px solid #000;
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
        $highlightColumns = ['JABON NISOBO', 'PULAI NISOBO'];

        $startText = \Carbon\Carbon::parse($startDate)->translatedFormat('d M Y');
        $endText = \Carbon\Carbon::parse($endDate)->translatedFormat('d M Y');
        $generatedByName = $generatedBy?->name ?? 'sistem';
        $generatedAtText = $generatedAt->copy()->locale('id')->translatedFormat('d M Y H:i');

        $fmt1 = static fn($v): string => number_format((float) ($v ?? 0), 1, '.', ',');
        $fmt2 = static fn($v): string => number_format((float) ($v ?? 0), 2, '.', ',');
        $fmtPct = static fn($v): string => number_format((float) ($v ?? 0), 1, '.', ',') . '%';
    @endphp

    <h1 class="report-title">Laporan Dashboard Cross Cut Akhir</h1>
    <p class="report-subtitle">Dari {{ $startText }} s/d {{ $endText }}</p>

    <table>
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
                <tr class="{{ $loop->odd ? 'row-odd' : 'row-even' }}">
                    <td class="label" style="text-align: center;">
                        {{ \Carbon\Carbon::parse((string) ($row['date'] ?? now()))->format('d M Y') }}</td>
                    @foreach ($columns as $column)
                        @php
                            $inflow = (float) ($row['cells'][$column]['in'] ?? 0 ?: 0);
                            $outflow = (float) ($row['cells'][$column]['out'] ?? 0 ?: 0);
                            $isHighlight = in_array($column, $highlightColumns, true);
                        @endphp
                        <td class="number {{ $isHighlight && abs($inflow) >= 0.000001 ? 'highlight-col' : '' }}">
                            {{ abs($inflow) < 0.000001 ? '' : $fmt1($inflow) }}</td>
                        <td class="number {{ $isHighlight && abs($outflow) >= 0.000001 ? 'highlight-col' : '' }}">
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
                    <td class="number">{{ $fmt1($sAkhirByColumn[$column] ?? 0) }}</td>
                    <td class="number">{{ $fmtPct($percentByColumn[$column] ?? 0) }}</td>
                @endforeach
            </tr>
            <tr class="totals-row">
                <td class="label"># Ctr</td>
                @foreach ($columns as $column)
                    <td class="number" colspan="2" style="text-align: center;">
                        {{ $fmt2($ctrByColumn[$column] ?? 0) }}</td>
                @endforeach
            </tr>
        </tfoot>
    </table>

    <p class="section-title">Total</p>
    <table style="width: 230px;">
        <tr class="totals-row">
            <td class="label" style="width: 90px;">S Akhir</td>
            <td class="number" style="width: 70px;">{{ $fmt1($totals['s_akhir'] ?? 0) }}</td>
        </tr>
        <tr class="totals-row">
            <td class="label"># Ctr</td>
            <td class="number">{{ $fmt2($totals['ctr'] ?? 0) }}</td>
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
