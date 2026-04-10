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
        $rows = is_array($data['rows'] ?? null) ? $data['rows'] : [];
        $totals = is_array($data['totals'] ?? null) ? $data['totals'] : null;
        $generatedByName = $generatedBy?->name ?? 'sistem';
        $generatedAtText = $generatedAt->copy()->locale('id')->translatedFormat('d-M-y H:i');
        $eps = 0.0000001;
        $fmtDim = static fn(?float $v): string => $v === null || abs($v) < $eps ? '' : number_format($v, 0, '.', ',');
        $fmt = static fn(?float $v): string => $v === null || abs($v) < $eps ? '' : number_format($v, 4, '.', ',');
        $fmtTotalZeroBlank = static fn(float $v) => abs($v) < $eps ? '' : number_format($v, 4, '.', ',');
        $umur1 = (int) ($umur1 ?? 15);
        $umur2 = (int) ($umur2 ?? 30);
        $umur3 = (int) ($umur3 ?? 60);
        $umur4 = (int) ($umur4 ?? 90);
        $ageLabels = [
            sprintf('0 - %d', $umur1),
            sprintf('%d - %d', $umur1 + 1, $umur2),
            sprintf('%d - %d', $umur2 + 1, $umur3),
            sprintf('%d - %d', $umur3 + 1, $umur4),
            sprintf('> %d', $umur4),
        ];
        $ageKeys = ['Period1', 'Period2', 'Period3', 'Period4', 'Period5'];
    @endphp

    <h1 class="report-title">Laporan Umur Laminating Detail</h1>
    <p class="report-subtitle"></p>

    <table>
        <thead>
            <tr>
                <th style="width: 34px;">No</th>
                <th style="width: 150px;">Jenis</th>
                <th style="width: 44px;">Tebal</th>
                <th style="width: 44px;">Lebar</th>
                <th style="width: 56px;">Panjang</th>
                @foreach ($ageLabels as $label)
                    <th style="width: 10%;">{{ $label }}</th>
                @endforeach
                <th style="width: 72px;">Total</th>
            </tr>
        </thead>
        <tbody>
            @php $rowIndex = 0; @endphp
            @forelse ($rows as $row)
                @php
                    $rowIndex++;
                    $row = is_array($row) ? $row : (array) $row;
                @endphp
                <tr class="{{ $rowIndex % 2 === 1 ? 'row-odd' : 'row-even' }}">
                    <td class="center">{{ $rowIndex }}</td>
                    <td>{{ (string) ($row['Jenis'] ?? '') }}</td>
                    <td class="center">{{ $fmtDim($row['Tebal'] ?? null) }}</td>
                    <td class="center">{{ $fmtDim($row['Lebar'] ?? null) }}</td>
                    <td class="center">{{ $fmtDim($row['Panjang'] ?? null) }}</td>
                    @foreach ($ageKeys as $key)
                        <td class="number">{{ $fmt($row[$key] ?? null) }}</td>
                    @endforeach
                    <td class="number" style="font-weight:bold;">{{ $fmt($row['Total'] ?? null) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="11" class="center">Tidak ada data.</td>
                </tr>
            @endforelse

            @if ($rows !== [] && is_array($totals))
                <tr class="totals-row">
                    <td colspan="5" class="center">Total</td>
                    @foreach ($ageKeys as $key)
                        @php $val = (float) ($totals[$key] ?? 0.0); @endphp
                        <td class="number">{{ $fmtTotalZeroBlank($val) }}</td>
                    @endforeach
                    @php $valTotal = (float) ($totals['Total'] ?? 0.0); @endphp
                    <td class="number">{{ $fmtTotalZeroBlank($valTotal) }}</td>
                </tr>
            @endif
        </tbody>
    </table>

    @include('reports.partials.pdf-footer-table')
</body>

</html>
