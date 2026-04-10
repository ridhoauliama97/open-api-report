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

        .group-title {
            margin: 10px 0 4px 0;
            font-size: 12px;
            font-weight: bold;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            page-break-inside: auto;
            border-top: 1px solid #000;
            border-left: 1px solid #000;
            border-right: 1px solid #000;
            border-bottom: 0;
            table-layout: fixed;
        }

        thead {
            display: table-header-group;
        }

        tfoot {
            display: table-footer-group;
        }

        tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }

        th,
        td {
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
            background: #fff;
        }

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
            border-bottom: 1px solid #000;
            background: #fff;
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
        $groups = is_array($data['groups'] ?? null) ? $data['groups'] : [];

        $start = \Carbon\Carbon::parse((string) ($data['start_date'] ?? ''))->locale('id')->translatedFormat('d-M-y');
        $end = \Carbon\Carbon::parse((string) ($data['end_date'] ?? ''))->locale('id')->translatedFormat('d-M-y');

        $eps = 0.0000001;
        $fmt = static fn(?float $v): string => $v === null || abs($v) < $eps ? '' : number_format($v, 4, '.', '');

        $grandInS4S = 0.0;
        $grandInCCAkhir = 0.0;
        $grandInWIP = 0.0;
        $grandOutput = 0.0;
        foreach ($groups as $g) {
            $t = is_array($g['totals'] ?? null) ? $g['totals'] : [];
            $grandInS4S += (float) ($t['InS4S'] ?? 0.0);
            $grandInCCAkhir += (float) ($t['InCCAkhir'] ?? 0.0);
            $grandInWIP += (float) ($t['InWIP'] ?? 0.0);
            $grandOutput += (float) ($t['Output'] ?? 0.0);
        }
    @endphp

    <h1 class="report-title">Laporan Rekap Produksi Finger Joint Per-Jenis & Per-Grade (m3)</h1>
    <p class="report-subtitle">Periode {{ $start }} s/d {{ $end }}</p>

    @foreach ($groups as $group)
        @php
            $jenis = (string) ($group['jenis'] ?? '');
            $rows = is_array($group['rows'] ?? null) ? $group['rows'] : [];
            $totals = is_array($group['totals'] ?? null) ? $group['totals'] : [];
        @endphp

        <div class="group-title">{{ $jenis }}</div>

        <table style="margin-bottom: 12px;">
            <thead>
                <tr>
                    <th style="width: 4%;">No</th>
                    <th style="width: 20%;">Jenis Kayu</th>
                    <th style="width: 20%;">Nama Grade</th>
                    <th style="width: 14%;">In S4S</th>
                    <th style="width: 14%;">In CCAkhir</th>
                    <th style="width: 14%;">In WIP</th>
                    <th style="width: 14%;">Output</th>
                </tr>
            </thead>
            <tbody>
                @php $i = 0; @endphp
                @foreach ($rows as $row)
                    @php
                        $i++;
                        $row = is_array($row) ? $row : (array) $row;
                        $cls = $i % 2 === 1 ? 'row-odd' : 'row-even';
                    @endphp
                    <tr class="{{ $cls }}">
                        <td class="center">{{ $i }}</td>
                        <td class="center">{{ (string) ($row['Jenis'] ?? '') }}</td>
                        <td>{{ (string) ($row['NamaGrade'] ?? '') }}</td>
                        <td class="number">{{ $fmt($row['InS4S'] ?? null) }}</td>
                        <td class="number">{{ $fmt($row['InCCAkhir'] ?? null) }}</td>
                        <td class="number">{{ $fmt($row['InWIP'] ?? null) }}</td>
                        <td class="number" style="font-weight: bold;">{{ $fmt($row['Output'] ?? null) }}</td>
                    </tr>
                @endforeach

                <tr class="totals-row">
                    <td colspan="3" class="center">Jumlah</td>
                    <td class="number">{{ $fmt($totals['InS4S'] ?? null) }}</td>
                    <td class="number">{{ $fmt($totals['InCCAkhir'] ?? null) }}</td>
                    <td class="number">{{ $fmt($totals['InWIP'] ?? null) }}</td>
                    <td class="number">{{ $fmt($totals['Output'] ?? null) }}</td>
                </tr>
            </tbody>
        </table>
    @endforeach

    @if ($groups !== [])
        <table style="margin-top: 6px;">
            <tbody>
                <tr class="totals-row">
                    <td colspan="3" class="center">Total </td>
                    <td class="number" style="width: 14%;">{{ $fmt($grandInS4S) }}</td>
                    <td class="number" style="width: 14%;">{{ $fmt($grandInCCAkhir) }}</td>
                    <td class="number" style="width: 14%;">{{ $fmt($grandInWIP) }}</td>
                    <td class="number" style="width: 14%;">{{ $fmt($grandOutput) }}</td>
                </tr>
            </tbody>
        </table>
    @endif

    @include('reports.partials.pdf-footer-table')
</body>

</html>
