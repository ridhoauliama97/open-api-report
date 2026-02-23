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
            background: #dcdcdc;
        }

        .report-title {
            text-align: center;
            margin: 0;
            font-size: 14px;
            ;
            ;
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
            page-break-inside: auto;
            background: #fff;
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
            font-weight: 700;
        }

        .cell-right {
            text-align: right;
            padding-right: 4px;
        }

        .subtotal-label {
            text-align: right;
            font-weight: 700;
            padding-right: 8px;
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
        $groupedRows = $reportData['grouped_rows'] ?? [];
        $summary = $reportData['summary'] ?? [];
        $endDateText = $reportData['end_date_text'] ?? $endDate;
        $fmt4 = static function ($value): string {
            $num = (float) ($value ?? 0);
            return abs($num) < 0.0000001 ? '' : number_format($num, 4, ',', '.');
        };
        $fmtInt = static function ($value): string {
            return number_format((float) ($value ?? 0), 0, ',', '.');
        };
    @endphp

    <h1 class="report-title">Laporan Stok Racip Kayu Lat</h1>
    <p class="report-subtitle">Per Tanggal {{ $endDateText }}</p>

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
                <table class="zebra">
                    <thead>
                        <tr>
                            <th>Tebal</th>
                            <th>Lebar</th>
                            <th>Panjang</th>
                            <th>Jmlh Batang</th>
                            <th>Hasil</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($groupRows as $row)
                            <tr>
                                <td class="cell-right">{{ $fmt4($row['Tebal'] ?? 0) }}</td>
                                <td class="cell-right">{{ $fmt4($row['Lebar'] ?? 0) }}</td>
                                <td class="cell-right">{{ $fmt4($row['Panjang'] ?? 0) }}</td>
                                <td class="cell-right">{{ $fmtInt($row['JmlhBatang'] ?? 0) }}</td>
                                <td class="cell-right">{{ $fmt4($row['Hasil'] ?? 0) }}</td>
                            </tr>
                        @endforeach
                        <tr>
                            <td colspan="3" class="subtotal-label" style="text-align: center; ;;">
                                Jumlah </td>
                            <td class="cell-right" style="font-weight:bold;">{{ $fmtInt($sumBatang) }}</td>
                            <td class="cell-right" style="font-weight:bold;">{{ $fmt4($sumHasil) }}</td>
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

    <p style="text-decoration: underline; font-weight: bold;">Summary</p>
    <table style="width: 45%;">
        <tbody>
            <tr>
                <td class="row-label">Jumlah Baris Data Seluruhnya</td>
                <td style="text-align: right; font-weight: bold;">
                    {{ number_format((int) ($summary['total_rows'] ?? 0), 0, ',', '.') }}
                </td>
            </tr>
            <tr>
                <td class="row-label">Jumlah Batang Seluruhnya</td>
                <td style="text-align: right; font-weight: bold;">{{ $summary['total_batang'] ?? 0 }}</td>
            </tr>
            <tr>
                <td class="row-label">Hasil Seluruhnya</td>
                <td style="text-align: right; font-weight: bold;">{{ $fmt4($summary['total_hasil'] ?? 0) }}</td>
            </tr>
        </tbody>
    </table>

    <htmlpagefooter name="reportFooter">
        <div class="footer-wrap">
            <div class="footer-left">Dicetak oleh: {{ $generatedByName }} pada {{ $generatedAtText }}</div>
            <div class="footer-right">Halaman {PAGENO} dari {nbpg}</div>
        </div>
    </htmlpagefooter>
</body>

</html>
