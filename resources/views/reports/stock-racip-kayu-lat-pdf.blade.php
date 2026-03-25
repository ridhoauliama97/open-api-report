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
            background: #fff;
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
            border-collapse: collapse;
            margin-bottom: 6px;
            page-break-inside: auto;
            background: #fff;
            table-layout: fixed;
        }

        .report-table {
            border-collapse: separate;
            border-spacing: 0;
            border: 1px solid #000;
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
            padding: 2px 2px;
            text-align: center;
            vertical-align: middle;
            white-space: nowrap;
        }

        th {
            background: #fff;
            font-weight: 700;
        }

        .row-label {
            text-align: left;
            font-weight: 700;
            padding-left: 3px;
        }

        .group-title {
            font-size: 10px;
            font-weight: bold;
        }

        .cell-right {
            text-align: right;
            padding-right: 4px;
        }

        .zebra tbody tr:nth-child(odd) td {
            background: #c9d1df;
        }

        .zebra tbody tr:nth-child(even) td {
            background: #eef2f8;
        }

        .zebra tbody tr:last-child td {
            background: #ffffff;
        }

        table tbody tr:last-child td {
            background: #ffffff;
        }
@include('reports.partials.pdf-footer-table-style')

        .headers-row th {
            font-weight: bold;
            font-size: 11px;
            border-top: 0;
            border-bottom: 1px solid #000;
        }

        .center td {
            text-align: center;
        }

        .totals-row td {
            font-weight: bold;
            font-size: 11px;
            border: 1px solid #000;
            background: #ffffff !important;
        }

        .zebra tbody tr.totals-row td {
            background: #ffffff !important;
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

        .group-cols col.col-no {
            width: 6%;
        }

        .group-cols col.col-tebal,
        .group-cols col.col-lebar {
            width: 14%;
        }

        .group-cols col.col-panjang {
            width: 14%;
        }

        .group-cols col.col-jumlah {
            width: 24%;
        }

        .group-cols col.col-hasil {
            width: 28%;
        }

        .equal-cols-2 col {
            width: 50%;
        }
    </style>
</head>

<body>
    @php
        $generatedByName = $generatedBy?->name ?? 'sistem';
        $generatedAtText = $generatedAt->copy()->locale('id')->translatedFormat('d-M-y H:i');
        $groupedRows = $reportData['grouped_rows'] ?? [];
        $summary = $reportData['summary'] ?? [];
        $endDateText = $reportData['end_date_text'] ?? $endDate;
        $fmt4 = static function ($value): string {
            $num = (float) ($value ?? 0);
            return abs($num) < 0.0000001 ? '' : number_format($num, 4, '.', ',');
        };
        $fmtInt = static function ($value): string {
            return number_format((float) ($value = 0 ? '' : $value), 0, '.', ',');
        };
    @endphp

    <h1 class="report-title">Laporan Stok Racip Kayu Lat</h1>
    <p class="report-subtitle">Per Tanggal : {{ $endDateText }}</p>

    @if (!empty($groupedRows))
        @foreach ($groupedRows as $group)
            @php
                $groupRows = $group['rows'] ?? [];
                $sumBatang = 0.0;
                $sumHasil = 0.0;
                foreach ($groupRows as $r) {
                    $sumBatang += (float) ($r['JmlhBatang'] ?? 0);
                    $sumHasil += (float) ($r['Hasil'] ?? 0);
                }
            @endphp
            <div class="section">
                <p class="group-title">{{ $group['jenis'] }}</p>
                <table class="report-table zebra group-cols">
                    <colgroup class="group-cols">
                        <col class="col-no" style="width: 6%;">
                        <col class="col-tebal" style="width: 14%;">
                        <col class="col-lebar" style="width: 14%;">
                        <col class="col-panjang" style="width: 14%;">
                        <col class="col-jumlah" style="width: 24%;">
                        <col class="col-hasil" style="width: 28%;">
                    </colgroup>
                    <thead>
                        <tr class="headers-row">
                            <th style="width: 6%;">No</th>
                            <th style="width: 14%;">Tebal (mm)</th>
                            <th style="width: 14%;">Lebar (mm)</th>
                            <th style="width: 14%;">Panjang (ft)</th>
                            <th style="width: 24%;">Jumlah Batang (pcs)</th>
                            <th style="width: 28%;">Hasil</th>
                        </tr>
                    </thead>
                    <tfoot>
                        <tr class="table-end-line">
                            <td colspan="6"></td>
                        </tr>
                    </tfoot>
<tbody>
                        @foreach ($groupRows as $row)
                            <tr class="data-row center">
                                <td class="data-cell">{{ $loop->iteration }}</td>
                                <td class="data-cell">{{ $fmtInt($row['Tebal'] ?? 0) }}</td>
                                <td class="data-cell">{{ $fmtInt($row['Lebar'] ?? 0) }}</td>
                                <td class="data-cell">{{ $fmtInt($row['Panjang'] ?? 0) }}</td>
                                <td class="data-cell">{{ $fmtInt($row['JmlhBatang'] ?? 0) }}</td>
                                <td class="data-cell">{{ $fmt4($row['Hasil'] ?? 0) }}</td>
                            </tr>
                        @endforeach
                        <tr class="totals-row">
                            <td colspan="4">Jumlah</td>
                            <td>{{ $fmtInt($sumBatang) }}</td>
                            <td>{{ $fmt4($sumHasil) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        @endforeach
    @else
        <table>
            <tbody>
                <tr>
                    <td>Tidak ada data.</td>
                </tr>
            </tbody>
        </table>
    @endif

    <div class="section">
        <h4 class="group-title">Summary</h4>
        <table class="equal-cols-2" style="width: 45%;">
            <colgroup>
                <col>
                <col>
            </colgroup>
            <tbody>
                <tr>
                    <td class="row-label">Total Data</td>
                    <td style="text-align: right; font-weight: bold;">
                        {{ number_format((int) ($summary['total_rows'] ?? 0), 0, '.', ',') }} Data
                    </td>
                </tr>
                <tr>
                    <td class="row-label">Total Jumlah Batang</td>
                    <td style="text-align: right; font-weight: bold;">{{ $fmtInt($summary['total_batang'] ?? 0) }} pcs
                    </td>
                </tr>
                <tr>
                    <td class="row-label">Grand Total Hasil</td>
                    <td style="text-align: right; font-weight: bold;">{{ $fmt4($summary['total_hasil'] ?? 0) }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    @include('reports.partials.pdf-footer-table')
</body>

</html>
