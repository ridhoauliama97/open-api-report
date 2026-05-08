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
            margin: 14px 0 6px 0;
            font-size: 12px;
            font-weight: bold;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
            page-break-inside: auto;
            table-layout: fixed;
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
            padding: 2px 4px;
            vertical-align: middle;
        }

        th {
            text-align: center;
            font-weight: bold;
            background: #ffffff;
            color: #000;
        }

        td.center {
            text-align: center;
        }

        td.right {
            text-align: right;
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
        }

        .report-table {
            border-collapse: collapse;
            border-spacing: 0;
            border-top: 0;
            border-right: 0;
            border-bottom: 1px solid #000;
            border-left: 1px solid #000;
        }

        .report-table tbody tr td {
            border-top: 0 !important;
            border-bottom: 0 !important;
            border-left: 0 !important;
            border-right: 1px solid #000 !important;
        }

        /* Fixed column widths */
        .col-no {
            width: 30px;
        }

        .col-supplier {
            width: auto;
        }

        .col-truk {
            width: 60px;
        }

        .col-group {
            width: 70px;
        }

        .col-kb {
            width: 70px;
        }

        .col-st {
            width: 70px;
        }

        .col-pct {
            width: 60px;
        }
    </style>
</head>

<body>
    @php
        $rowsData = isset($rows) ? (is_array($rows) ? $rows : $rows->toArray()) : [];
        $subRowsData = isset($subRows) ? (is_array($subRows) ? $subRows : $subRows->toArray()) : [];

        $start = \Carbon\Carbon::parse($startDate)->locale('id')->translatedFormat('d-M-y');
        $end = \Carbon\Carbon::parse($endDate)->locale('id')->translatedFormat('d-M-y');

        // Sort Detail Transaksi by Rendemen descending
        usort($rowsData, static function (array $a, array $b): int {
            $kbA = (float) ($a['KBTon'] ?? 0);
            $stA = (float) ($a['STTon'] ?? 0);
            $rendemenA = $kbA > 0 ? ($stA / $kbA) * 100 : 0;

            $kbB = (float) ($b['KBTon'] ?? 0);
            $stB = (float) ($b['STTon'] ?? 0);
            $rendemenB = $kbB > 0 ? ($stB / $kbB) * 100 : 0;

            return $rendemenB <=> $rendemenA;
        });

        // Sort Rangkuman Per Supplier by Total Rendemen descending
        usort($subRowsData, static function (array $a, array $b): int {
            $totKbA = (float) ($a['SLPKBton'] ?? 0) + (float) ($a['BansawKBton'] ?? 0);
            $totStA = (float) ($a['SLPstton'] ?? 0) + (float) ($a['Bansawstton'] ?? 0);
            $rendemenA = $totKbA > 0 ? ($totStA / $totKbA) * 100 : 0;

            $totKbB = (float) ($b['SLPKBton'] ?? 0) + (float) ($b['BansawKBton'] ?? 0);
            $totStB = (float) ($b['SLPstton'] ?? 0) + (float) ($b['Bansawstton'] ?? 0);
            $rendemenB = $totKbB > 0 ? ($totStB / $totKbB) * 100 : 0;

            return $rendemenB <=> $rendemenA;
        });

        $fmtID = function ($val, $precision = 2) {
            return is_numeric($val) && (float) $val != 0 ? number_format((float) $val, $precision, ',', '.') : '';
        };

        $fmtEN = function ($val, $precision = 4) {
            return is_numeric($val) && (float) $val != 0 ? number_format((float) $val, $precision, '.', ',') : '';
        };
    @endphp

    <h1 class="report-title">Laporan Rekap Rendemen Rambung Per Supplier</h1>
    <p class="report-subtitle">Periode: {{ $start }} s/d {{ $end }}</p>

    <div class="section-title">Detail Transaksi</div>
    <table class="report-table">
        <thead>
            <tr class="headers-row">
                <th class="col-no">No</th>
                <th class="col-supplier">Nama Supplier</th>
                <th class="col-truk">No Truk</th>
                <th class="col-group">Group</th>
                <th class="col-kb">KB Ton</th>
                <th class="col-st">ST Ton</th>
                <th class="col-pct">Rendemen</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rowsData as $row)
                @php
                    $kb = (float) ($row['KBTon'] ?? 0);
                    $st = (float) ($row['STTon'] ?? 0);
                    $rendemen = $kb > 0 ? ($st / $kb) * 100 : 0;
                @endphp
                <tr class="{{ $loop->odd ? 'row-odd' : 'row-even' }}">
                    <td class="center">{{ $loop->iteration }}</td>
                    <td>{{ $row['NmSupplier'] ?? ($row['Supplier'] ?? '-') }}</td>
                    <td class="center">{{ $row['NoTruk'] ?? '-' }}</td>
                    <td class="center">{{ $row['Group'] ?? '-' }}</td>
                    <td class="number">{{ $fmtID($kb) }}</td>
                    <td class="number">{{ $fmtID($st) }}</td>
                    <td class="number" style="font-weight: bold;">
                        {{ $kb > 0 ? number_format($rendemen, 2, '.', ',') . '%' : '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @if ($subRowsData !== [])
        <div class="section-title">Rangkuman Per Supplier</div>
        <table class="report-table">
            <thead>
                <tr class="headers-row">
                    <th rowspan="2" style="width: 150px;">Supplier</th>
                    <th colspan="3">SLP</th>
                    <th colspan="3">Bansaw</th>
                    <th colspan="3">Total</th>
                </tr>
                <tr class="headers-row">
                    <th style="width: 65px;">KBTon</th>
                    <th style="width: 65px;">STTon</th>
                    <th style="width: 50px;">%</th>
                    <th style="width: 65px;">KBTon</th>
                    <th style="width: 65px;">STTon</th>
                    <th style="width: 50px;">%</th>
                    <th style="width: 65px;">KBTon</th>
                    <th style="width: 65px;">STTon</th>
                    <th style="width: 60px;">%</th>
                </tr>
            </thead>
            @php
                $gSlpKB = 0;
                $gSlpST = 0;
                $gBsKB = 0;
                $gBsST = 0;
                $gTotKB = 0;
                $gTotST = 0;
            @endphp
            <tbody>
                @foreach ($subRowsData as $row)
                    @php
                        $slpKB = (float) ($row['SLPKBton'] ?? 0);
                        $slpST = (float) ($row['SLPstton'] ?? 0);
                        $slpPct = (float) ($row['SLPPersen'] ?? 0);

                        $bsKB = (float) ($row['BansawKBton'] ?? 0);
                        $bsST = (float) ($row['Bansawstton'] ?? 0);
                        $bsPct = (float) ($row['BansawPersen'] ?? 0);

                        $totKB = $slpKB + $bsKB;
                        $totST = $slpST + $bsST;
                        $totPct = $totKB > 0 ? ($totST / $totKB) * 100 : 0;

                        // Akumulasi Grand Total
                        $gSlpKB += $slpKB;
                        $gSlpST += $slpST;
                        $gBsKB += $bsKB;
                        $gBsST += $bsST;
                        $gTotKB += $totKB;
                        $gTotST += $totST;
                    @endphp
                    <tr class="{{ $loop->odd ? 'row-odd' : 'row-even' }}">
                        <td>{{ trim($row['NmSupplier'] ?? '-') }}</td>
                        <td class="number">{{ $fmtID($slpKB) }}</td>
                        <td class="number">{{ $fmtEN($slpST, 4) }}</td>
                        <td class="number">{{ $slpKB > 0 ? number_format($slpPct, 2, '.', ',') . '%' : '' }}</td>

                        <td class="number">{{ $fmtID($bsKB) }}</td>
                        <td class="number">{{ $fmtEN($bsST, 4) }}</td>
                        <td class="number">{{ $bsKB > 0 ? number_format($bsPct, 2, '.', ',') . '%' : '' }}</td>

                        <td class="number" style="font-weight: bold;">{{ $fmtID($totKB) }}</td>
                        <td class="number" style="font-weight: bold;">{{ $fmtEN($totST, 4) }}</td>
                        <td class="number" style="font-weight: bold;">
                            {{ $totKB > 0 ? number_format($totPct, 2, '.', ',') . '%' : '' }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td style="font-weight: bold; font-size: 11px; text-align: center;">Total</td>
                    <td class="number" style="font-weight: bold; font-size: 11px;">{{ $fmtID($gSlpKB) }}</td>
                    <td class="number" style="font-weight: bold; font-size: 11px;">{{ $fmtEN($gSlpST, 4) }}</td>
                    <td class="number" style="font-weight: bold; font-size: 11px;">
                        {{ $gSlpKB > 0 ? number_format(($gSlpST / $gSlpKB) * 100, 2, '.', ',') . '%' : '' }}</td>

                    <td class="number" style="font-weight: bold; font-size: 11px;">{{ $fmtID($gBsKB) }}</td>
                    <td class="number" style="font-weight: bold; font-size: 11px;">{{ $fmtEN($gBsST, 4) }}</td>
                    <td class="number" style="font-weight: bold; font-size: 11px;">
                        {{ $gBsKB > 0 ? number_format(($gBsST / $gBsKB) * 100, 2, '.', ',') . '%' : '' }}</td>

                    <td class="number" style="font-weight: bold; font-size: 11px;">{{ $fmtID($gTotKB) }}</td>
                    <td class="number" style="font-weight: bold; font-size: 11px;">{{ $fmtEN($gTotST, 4) }}</td>
                    <td class="number" style="font-weight: bold; font-size: 11px;">
                        {{ $gTotKB > 0 ? number_format(($gTotST / $gTotKB) * 100, 2, '.', ',') . '%' : '' }}</td>
                </tr>
            </tfoot>
        </table>
    @endif

    <div style="margin-top: 30px;">
        @include('reports.partials.pdf-footer-table')
    </div>
</body>

</html>
