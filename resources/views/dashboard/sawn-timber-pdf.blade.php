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

        .title {
            text-align: center;
            margin: 0;
            font-size: 16px;
            font-weight: bold;
        }

        .subtitle {
            text-align: center;
            margin: 2px 0 20px 0;
            font-size: 12px;
            color: #636466;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }

        .report-table {
            border-collapse: separate;
            border-spacing: 0;
            border: 1px solid #000;
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
            font-size: 11px;
        }

        td.label {
            white-space: nowrap;
        }

        td.number {
            text-align: right;
            font-family: "Calibri", "DejaVu Sans", sans-serif;
            white-space: nowrap;
        }

        .row-odd td {
            background: #c9d1df;
        }

        .row-even td {
            background: #eef2f8;
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
            min-width: 190px;
            font-weight: 700;
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
            border: 0 !important;
            border-top: 1px solid #000 !important;
            padding: 0 !important;
            height: 0 !important;
            line-height: 0 !important;
            background: transparent !important;
        }
    </style>
</head>

<body>
    @php
        $chartData = is_array($chartData ?? null) ? $chartData : [];
        $dates = is_array($chartData['dates'] ?? null) ? $chartData['dates'] : [];
        $types = is_array($chartData['types'] ?? null) ? $chartData['types'] : [];
        $totalsByType = is_array($chartData['totals_by_type'] ?? null) ? $chartData['totals_by_type'] : [];
        $stockByType = is_array($chartData['stock_by_type'] ?? null) ? $chartData['stock_by_type'] : [];
        $stockTotals = is_array($chartData['stock_totals'] ?? null)
            ? $chartData['stock_totals']
            : ['s_akhir' => 0, 'ctr' => 0];
        $dailyIn = is_array($chartData['daily_in_totals'] ?? null) ? $chartData['daily_in_totals'] : [];
        $dailyOut = is_array($chartData['daily_out_totals'] ?? null) ? $chartData['daily_out_totals'] : [];
        $rawRowCount = (int) ($chartData['raw_row_count'] ?? 0);
        $pdfTruncatedTypes = (bool) ($chartData['pdf_truncated_types'] ?? false);
        $pdfOriginalTypeCount = (int) ($chartData['pdf_original_type_count'] ?? count($types));
        $generatedByName = $generatedBy?->name ?? 'sistem';
        $generatedAtText = $generatedAt->copy()->locale('id')->translatedFormat('d-M-y H:i');

        $fmt1 = static fn($v): string => number_format((float) ($v ?? 0), 1, '.', ',');
        $fmt2 = static fn($v): string => number_format((float) ($v ?? 0), 2, '.', ',');

        $dailyNet = [];
        $totalInAll = 0.0;
        $totalOutAll = 0.0;
        foreach ($dailyIn as $idx => $value) {
            $inVal = (float) ($value ?? 0);
            $outVal = (float) ($dailyOut[$idx] ?? 0);
            $dailyNet[] = $inVal - $outVal;
            $totalInAll += $inVal;
            $totalOutAll += $outVal;
        }
        $netAll = $totalInAll - $totalOutAll;

    @endphp

    <h1 class="title">Dashboard Sawn Timber</h1>
    <p class="subtitle">Dari {{ \Carbon\Carbon::parse($startDate)->locale('id')->translatedFormat('d-M-y') }} s/d
        {{ \Carbon\Carbon::parse($endDate)->locale('id')->translatedFormat('d-M-y') }}</p>
    @if ($pdfTruncatedTypes)
        <p style="margin: 0 0 10px 0; font-size: 9px; text-align: center;">
            Menampilkan {{ count($types) }} dari {{ $pdfOriginalTypeCount }} jenis ST teratas untuk menjaga render PDF tetap stabil.
        </p>
    @endif

    <table class="report-table">
        <thead>
            <tr class="headers-row">
                <th style="width: 7%;">No</th>
                <th style="width: 33%;">Jenis</th>
                <th style="width: 20%;">Total Masuk</th>
                <th style="width: 20%;">Total Keluar</th>
                <th style="width: 20%;">Net</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($types as $idx => $type)
                @php
                    $inVal = (float) ($totalsByType[$type]['in'] ?? 0);
                    $outVal = (float) ($totalsByType[$type]['out'] ?? 0);
                @endphp
                <tr class="data-row {{ $loop->odd ? 'row-odd' : 'row-even' }}">
                    <td class="data-cell number" style="text-align: center">{{ $idx + 1 }}</td>
                    <td class="data-cell label">{{ $type }}</td>
                    <td class="data-cell number">{{ $fmt1($inVal) }}</td>
                    <td class="data-cell number">{{ $fmt1($outVal) }}</td>
                    <td class="data-cell number">{{ $fmt1($inVal - $outVal) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="totals-row">
                <td colspan="2" class="number" style="text-align: center;">Total</td>
                <td class="number">{{ $fmt1($totalInAll) }}</td>
                <td class="number">{{ $fmt1($totalOutAll) }}</td>
                <td class="number">{{ $fmt1($netAll) }}</td>
            </tr>
            <tr class="table-end-line">
                <td colspan="5"></td>
            </tr>
        </tfoot>
    </table>

    <table class="report-table">
        <thead>
            <tr class="headers-row">
                <th style="width: 7%;">No</th>
                <th style="width: 43%;">Jenis</th>
                <th style="width: 25%;">S Akhir</th>
                <th style="width: 25%;">#Ctr</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($types as $idx => $type)
                @php $stockRow = $stockByType[$type] ?? []; @endphp
                <tr class="data-row {{ $loop->odd ? 'row-odd' : 'row-even' }}">
                    <td class="data-cell number" style="text-align: center">{{ $idx + 1 }}</td>
                    <td class="data-cell label">{{ $type }}</td>
                    <td class="data-cell number">{{ $fmt1($stockRow['s_akhir'] ?? 0) }}</td>
                    <td class="data-cell number">{{ $fmt2($stockRow['ctr'] ?? 0) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="totals-row">
                <td colspan="2" class="number" style="text-align: center;">Total</td>
                <td class="number">{{ $fmt1($stockTotals['s_akhir'] ?? 0) }}</td>
                <td class="number">{{ $fmt2($stockTotals['ctr'] ?? 0) }}</td>
            </tr>
            <tr class="table-end-line">
                <td colspan="4"></td>
            </tr>
        </tfoot>
    </table>

    <p style="font-size: 10px; margin-bottom: 5px; font-weight: bold; text-decoration: underline;">Summary :</p>
    <ul class="summary-list">
        <li><span class="summary-label">Jumlah Seluruh Hari :</span> {{ number_format(count($dates), 0, '.', ',') }}
            Hari</li>
        <li><span class="summary-label">Jumlah Seluruh Jenis ST :</span>
            {{ number_format(count($types), 0, '.', ',') }} Jenis</li>
        <li><span class="summary-label">Jumlah Baris Raw Data Terhitung :</span>
            {{ number_format($rawRowCount, 0, '.', ',') }} Baris Data</li>
        <li><span class="summary-label">Total Masuk Keseluruhan (Semua Jenis ST) :</span> {{ $fmt1($totalInAll) }}</li>
        <li><span class="summary-label">Total Keluar Keseluruhan (Semua Jenis ST) :</span> {{ $fmt1($totalOutAll) }}
        </li>
        <li><span class="summary-label">Total Net Keseluruhan (Semua Jenis ST) :</span> {{ $fmt1($netAll) }}</li>
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
