<!DOCTYPE html>
<html lang="id">

<head>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif:ital,wght@0,100..900;1,100..900&display=swap"
        rel="stylesheet">
    <meta charset="utf-8">
    <style>
        * {
            box-sizing: border-box;
        }

        @page {
            margin: 20mm 10mm 20mm 10mm;
            footer: html_reportFooter;
        }

        body {
            margin: 0;
            font-family: "Noto Serif", serif;
            font-size: 10px;
            line-height: 1.15;
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

        table {
            width: 100%;
            border-collapse: collapse;
            page-break-inside: auto;
            border: 1px solid #000;
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
            /* Default: hanya garis vertikal antar kolom. */
            border: 0;
            border-left: 1px solid #000;
            padding: 2px 3px;
            vertical-align: middle;
        }

        th:first-child,
        td:first-child {
            border-left: 0;
        }

        th {
            text-align: center;
            font-weight: bold;
            font-size: 11px;
            border-bottom: 1px solid #000;
        }

        /* Hilangkan garis horizontal antar baris data. */
        tbody td {
            border-top: 0;
            border-bottom: 0;
        }

        .row-odd td {
            background: #c9d1df;
        }

        .row-even td {
            background: #eef2f8;
        }

        .number {
            text-align: right;
            white-space: nowrap;
            font-family: "Calibri", "DejaVu Sans", sans-serif;
        }

        .center {
            text-align: center;
        }

        .totals-row td {
            font-weight: bold;
            font-size: 11px;
            border-top: 1px solid #000;
        }


        tfoot {
            display: table-footer-group;
        }

        .table-end-line td {
            border-top: 1px solid #000 !important;
            border-right: 0 !important;
            border-bottom: 0 !important;
            border-left: 0 !important;
            padding: 0 !important;
            height: 0 !important;
            line-height: 0 !important;
            background: #fff !important;
        }

        @include('reports.partials.pdf-footer-table-style')
    </style>
</head>

<body>
    @php
        $data = is_array($reportData ?? null) ? $reportData : [];
        $rows = is_array($data['rows'] ?? null) ? $data['rows'] : [];
        $totals = is_array($data['totals'] ?? null) ? $data['totals'] : null;

        $generatedByName = $generatedBy?->name ?? 'sistem';
        $generatedAtText = $generatedAt->copy()->locale('id')->translatedFormat('d-M-y H:i');

        $eps = 0.0000001;
        $fmt = static fn(float $v): string => abs($v) < $eps ? '' : number_format($v, 4, '.', ',');
        $fmtTotal = static fn(float $v): string => number_format($v, 4, '.', ',');

        $periodCols = ['<= 2 Minggu', '2 - 4 Minggu', '4 - 6 Minggu', '6 - 8 Minggu', '> 8 Minggu', 'Total'];
    @endphp

    <h1 class="report-title">Laporan ST Basah Hidup Per-Umur Kayu (Ton)</h1>
    <p class="report-subtitle"></p>

    <table>
        <thead>
            <tr>
                <th style="width: 34px;">No</th>
                <th style="width: 190px;">Group</th>
                <th>&le; 2 Minggu</th>
                <th>2 - 4 Minggu</th>
                <th>4 - 6 Minggu</th>
                <th>6 - 8 Minggu</th>
                <th>&gt; 8 Minggu</th>
                <th style="width: 78px;">Total</th>
            </tr>
        </thead>

        <tbody>
            @php $rowIndex = 0; @endphp

            @forelse ($rows as $row)
                @php $rowIndex++; @endphp
                <tr class="{{ $rowIndex % 2 === 1 ? 'row-odd' : 'row-even' }}">
                    <td class="center">{{ $rowIndex }}</td>
                    <td>{{ (string) ($row['Group'] ?? '') }}</td>
                    @foreach ($periodCols as $col)
                        @php $val = (float) ($row[$col] ?? 0.0); @endphp
                        <td class="number">{{ $fmt($val) }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="center">Tidak ada data.</td>
                </tr>
            @endforelse

            @if ($rows !== [] && is_array($totals))
                <tr class="totals-row">
                    <td></td>
                    <td class="center" style="font-weight: bold;">Total</td>
                    @foreach ($periodCols as $col)
                        @php $val = (float) ($totals[$col] ?? 0.0); @endphp
                        <td class="number" style="font-weight: bold;">{{ $fmtTotal($val) }}</td>
                    @endforeach
                </tr>
            @endif
        </tbody>
        <tfoot>
            <tr class="table-end-line">
                <td colspan="8"></td>
            </tr>
        </tfoot>
    </table>

    @include('reports.partials.pdf-footer-table')
</body>

</html>
