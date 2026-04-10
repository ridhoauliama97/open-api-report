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
            /* Avoid "double-thick" bottom line (table border + tfoot end-line). */
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
        $rows = is_array($data['rows'] ?? null) ? $data['rows'] : [];
        $totals = is_array($data['totals'] ?? null) ? $data['totals'] : null;

        $generatedByName = $generatedBy?->name ?? 'sistem';
        $generatedAtText = $generatedAt->copy()->locale('id')->translatedFormat('d-M-y H:i');

        $fmtDate = static fn(?string $v): string => $v === null || trim($v) === ''
            ? ''
            : \Carbon\Carbon::parse($v)->format('d-M-y');
        $fmtInt = static fn(mixed $v): string => $v === null || $v === '' ? '' : number_format((int) $v, 0, '.', ',');
        $fmtDim = static fn(mixed $v): string => $v === null || $v === '' ? '' : number_format((float) $v, 0, '.', ',');
        $fmtM3 = static fn(mixed $v): string => $v === null || $v === '' ? '' : number_format((float) $v, 3, '.', ',');
    @endphp

    <h1 class="report-title">Laporan Finger Joint (Hidup) Detail</h1>
    <p class="report-subtitle"></p>

    <table>
        <thead>
            <tr>
                <th style="width: 34px;">No</th>
                <th style="width: 84px;">No FJ</th>
                <th style="width: 84px;">Tanggal</th>
                <th style="width: 74px;">NoSPK</th>
                <th>Jenis</th>
                <th style="width: 44px;">Tebal</th>
                <th style="width: 44px;">Lebar</th>
                <th style="width: 60px;">Panjang</th>
                <th style="width: 72px;">Jmlh Batang</th>
                <th style="width: 52px;">M3</th>
                <th style="width: 54px;">Lokasi</th>
            </tr>
        </thead>
        <tbody>
            @php $rowIndex = 0; @endphp
            @forelse ($rows as $row)
                @php
                    $rowIndex++;
                    // Defensive: some drivers/SPs can yield non-array values; avoid "array offset on int".
                    $row = is_array($row) ? $row : (array) $row;
                @endphp
                <tr class="{{ $rowIndex % 2 === 1 ? 'row-odd' : 'row-even' }}">
                    <td class="center">{{ $rowIndex }}</td>
                    <td class="center">{{ (string) ($row['NoFJ'] ?? '') }}</td>
                    <td class="center">{{ $fmtDate($row['DateCreate'] ?? null) }}</td>
                    <td class="center">{{ (string) ($row['NoSPK'] ?? '') }}</td>
                    <td>{{ (string) ($row['Jenis'] ?? '') }}</td>
                    <td class="center">{{ $fmtDim($row['Tebal'] ?? null) }}</td>
                    <td class="center">{{ $fmtDim($row['Lebar'] ?? null) }}</td>
                    <td class="center">{{ $fmtDim($row['Panjang'] ?? null) }}</td>
                    <td class="number" style="text-align: center; font-weight: bold;">
                        {{ $fmtInt($row['JmlhBatang'] ?? null) }}</td>
                    <td class="number" style="font-weight: bold;">{{ $fmtM3($row['M3'] ?? null) }}</td>
                    <td class="center">{{ (string) ($row['Lokasi'] ?? '') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="11" class="center">Tidak ada data.</td>
                </tr>
            @endforelse

            @if ($rows !== [] && is_array($totals))
                <tr class="totals-row">
                    <td colspan="8" class="center">Total </td>
                    <td class="number" style="text-align: center;"> {{ $fmtInt($totals['JmlhBatang'] ?? null) }}</td>
                    <td class="number">{{ $fmtM3($totals['M3'] ?? null) }}</td>
                    <td></td>
                </tr>
            @endif
        </tbody>
    </table>

    @include('reports.partials.pdf-footer-table')
</body>

</html>
