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
            margin: 12mm 10mm 14mm 10mm;
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

        .section-title {
            margin: 10px 0 4px 0;
            font-size: 11px;
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
        $machines = is_array($data['machines'] ?? null) ? $data['machines'] : [];
        $hkSummary = is_array($data['hk_summary'] ?? null) ? $data['hk_summary'] : [];
        $grandTotals = is_array($data['grand_totals'] ?? null) ? $data['grand_totals'] : [];

        $generatedByName = $generatedBy?->name ?? 'sistem';
        $generatedAtText = $generatedAt->copy()->locale('id')->translatedFormat('d-M-y H:i');

        $start = \Carbon\Carbon::parse((string) ($data['start_date'] ?? ''))->locale('id')->translatedFormat('d-M-y');
        $end = \Carbon\Carbon::parse((string) ($data['end_date'] ?? ''))->locale('id')->translatedFormat('d-M-y');

        $eps = 0.0000001;
        $fmtDate = static fn(string $v): string => $v === '' ? '' : \Carbon\Carbon::parse($v)->format('d-M-y');
        // User request: if value is 0 / "-" / null, show blank.
        $fmtBlank = static fn(?float $v): string => $v === null || abs($v) < $eps ? '' : number_format($v, 1, '.', '');
        $fmtIntBlank = static fn(?int $v): string => $v === null || $v <= 0 ? '' : (string) $v;
        $fmtRatioBlank = static fn(?float $v): string => $v === null || !is_finite($v) || abs($v) < $eps
            ? ''
            : number_format($v, 1, '.', '');
        $fmtPercentBlank = static fn(?float $v): string => $v === null || !is_finite($v) || abs($v) < $eps
            ? ''
            : number_format($v, 1, '.', '');
    @endphp

    <h1 class="report-title">Laporan Rekap Produksi S4S Consolidated</h1>
    <p class="report-subtitle">Periode {{ $start }} s/d {{ $end }}</p>

    @foreach ($machines as $machine)
        @php
            $namaMesin = (string) ($machine['nama_mesin'] ?? '');
            $rows = is_array($machine['rows'] ?? null) ? $machine['rows'] : [];
            $totals = is_array($machine['totals'] ?? null) ? $machine['totals'] : [];
            $hk = (int) ($data['hk'] ?? ($machine['hk'] ?? 0));
        @endphp

        <div class="section-title">Nama Mesin : {{ $namaMesin }}</div>

        <table>
            <thead>
                <tr>
                    <th rowspan="2" style="width: 66px;">Tanggal</th>
                    <th rowspan="2" style="width: 34px;">Shift</th>
                    <th colspan="5">Input</th>
                    <th rowspan="2" style="width: 60px;">Total Input</th>
                    <th rowspan="2" style="width: 60px;">Output S4S</th>
                    <th rowspan="2" style="width: 44px;">Jam</th>
                    <th rowspan="2" style="width: 40px;">Org</th>
                    <th rowspan="2" style="width: 54px;">M<sup>3</sup>/Jam</th>
                    <th rowspan="2" style="width: 60px;">M<sup>3</sup>/jam/Org</th>
                    <th rowspan="2" style="width: 54px;">Rend (%)</th>
                </tr>
                <tr>
                    <th style="width: 52px;">CCAkhir</th>
                    <th style="width: 44px;">FJ</th>
                    <th style="width: 60px;">Reproses</th>
                    <th style="width: 44px;">S4S</th>
                    <th style="width: 44px;">ST</th>
                </tr>
            </thead>
            {{-- IMPORTANT (mPDF): place tfoot before tbody so the footer-group is repeated on each page break. --}}
            <tfoot>
                <tr class="table-end-line">
                    <td colspan="14"></td>
                </tr>
            </tfoot>
            <tbody>
                @php $rowIndex = 0; @endphp
                @foreach ($rows as $row)
                    @php
                        $rowIndex++;
                        $row = is_array($row) ? $row : (array) $row;
                    @endphp
                    <tr class="{{ $rowIndex % 2 === 1 ? 'row-odd' : 'row-even' }}">
                        <td class="center">{{ $fmtDate((string) ($row['Tanggal'] ?? '')) }}</td>
                        <td class="center">{{ (int) ($row['Shift'] ?? 0) }}</td>
                        <td class="number">{{ $fmtBlank($row['CCAkhir'] ?? null) }}</td>
                        <td class="number">{{ $fmtBlank($row['FJ'] ?? null) }}</td>
                        <td class="number">{{ $fmtBlank($row['Reproses'] ?? null) }}</td>
                        <td class="number">{{ $fmtBlank($row['S4S'] ?? null) }}</td>
                        <td class="number">{{ $fmtBlank($row['ST'] ?? null) }}</td>
                        <td class="number">{{ $fmtBlank($row['TotalInput'] ?? null) }}</td>
                        <td class="number">{{ $fmtBlank($row['OutputS4S'] ?? null) }}</td>
                        <td class="number">{{ $fmtBlank($row['Jam'] ?? null) }}</td>
                        <td class="center">{{ $fmtIntBlank($row['Org'] ?? null) }}</td>
                        <td class="number">{{ $fmtRatioBlank($row['M3Jam'] ?? null) }}</td>
                        <td class="number">{{ $fmtRatioBlank($row['M3JamOrg'] ?? null) }}</td>
                        <td class="number">{{ $fmtPercentBlank($row['Rend'] ?? null) }}</td>
                    </tr>
                @endforeach

                @if ($rows !== [] && $totals !== [])
                    @php
                        $hkText = $hk > 0 ? 'HK : ' . $hk : 'HK : -';
                        $jmlhPerHk = static fn(float $v) => $hk > 0 ? $v / $hk : 0.0;
                    @endphp
                    <tr class="totals-row">
                        <td colspan="2" class="center">{{ $hkText }}</td>
                        <td class="number">{{ $fmtBlank($totals['CCAkhir'] ?? null) }}</td>
                        <td class="number">{{ $fmtBlank($totals['FJ'] ?? null) }}</td>
                        <td class="number">{{ $fmtBlank($totals['Reproses'] ?? null) }}</td>
                        <td class="number">{{ $fmtBlank($totals['S4S'] ?? null) }}</td>
                        <td class="number">{{ $fmtBlank($totals['ST'] ?? null) }}</td>
                        <td class="number">{{ $fmtBlank($totals['TotalInput'] ?? null) }}</td>
                        <td class="number">{{ $fmtBlank($totals['OutputS4S'] ?? null) }}</td>
                        <td class="number">{{ $fmtBlank($totals['Jam'] ?? null) }}</td>
                        <td class="center">{{ $fmtIntBlank((int) round((float) ($totals['Org'] ?? 0.0))) }}</td>
                        <td class="number">{{ $fmtRatioBlank($totals['M3Jam'] ?? null) }}</td>
                        <td class="number">{{ $fmtRatioBlank($totals['M3JamOrg'] ?? null) }}</td>
                        <td class="number">{{ $fmtPercentBlank($totals['Rend'] ?? null) }}</td>
                    </tr>

                    <tr class="totals-row">
                        <td colspan="2" class="center"><strong>Jmlh/HK</strong></td>
                        {{-- Match reference: only show per-HK averages for main flow columns, leave others blank. --}}
                        <td class="number"></td>
                        <td class="number"></td>
                        <td class="number"></td>
                        <td class="number">{{ $fmtBlank($jmlhPerHk((float) ($totals['S4S'] ?? 0.0))) }}</td>
                        <td class="number">{{ $fmtBlank($jmlhPerHk((float) ($totals['ST'] ?? 0.0))) }}</td>
                        <td class="number">{{ $fmtBlank($jmlhPerHk((float) ($totals['TotalInput'] ?? 0.0))) }}</td>
                        <td class="number">{{ $fmtBlank($jmlhPerHk((float) ($totals['OutputS4S'] ?? 0.0))) }}</td>
                        <td class="number">{{ $fmtBlank($jmlhPerHk((float) ($totals['Jam'] ?? 0.0))) }}</td>
                        <td class="center"></td>
                        <td class="number"></td>
                        <td class="number"></td>
                        <td class="number"></td>
                    </tr>
                @endif
            </tbody>
        </table>
    @endforeach

    @if ($hkSummary !== [])
        <div class="section-title" style="margin-top: 14px;">Rangkuman HK Per Mesin</div>

        <table>
            <thead>
                <tr>
                    <th style="width: 200px;">Nama Mesin</th>
                    <th style="width: 44px;">HK</th>
                    <th style="width: 52px;">CCAkhir</th>
                    <th style="width: 44px;">FJ</th>
                    <th style="width: 60px;">Reproses</th>
                    <th style="width: 44px;">S4S</th>
                    <th style="width: 44px;">ST</th>
                    <th style="width: 60px;">Total Input</th>
                    <th style="width: 60px;">Output S4S</th>
                    <th style="width: 44px;">Jam</th>
                    <th style="width: 40px;">Org</th>
                    <th style="width: 54px;">M<sup>3</sup>/Jam</th>
                    <th style="width: 60px;">M<sup>3</sup>/jam/Org</th>
                    <th style="width: 54px;">Rend (%)</th>
                </tr>
            </thead>
            <tfoot>
                <tr class="table-end-line">
                    <td colspan="14"></td>
                </tr>
            </tfoot>
            <tbody>
                @php $rowIndex = 0; @endphp
                @foreach ($hkSummary as $item)
                    @php
                        $rowIndex++;
                        $item = is_array($item) ? $item : (array) $item;
                        $t = is_array($item['totals'] ?? null) ? $item['totals'] : [];
                    @endphp
                    <tr class="{{ $rowIndex % 2 === 1 ? 'row-odd' : 'row-even' }}">
                        <td>{{ (string) ($item['nama_mesin'] ?? '') }}</td>
                        <td class="center">{{ $fmtIntBlank((int) ($item['hk'] ?? 0)) }}</td>
                        <td class="number">{{ $fmtBlank($t['CCAkhir'] ?? null) }}</td>
                        <td class="number">{{ $fmtBlank($t['FJ'] ?? null) }}</td>
                        <td class="number">{{ $fmtBlank($t['Reproses'] ?? null) }}</td>
                        <td class="number">{{ $fmtBlank($t['S4S'] ?? null) }}</td>
                        <td class="number">{{ $fmtBlank($t['ST'] ?? null) }}</td>
                        <td class="number">{{ $fmtBlank($t['TotalInput'] ?? null) }}</td>
                        <td class="number">{{ $fmtBlank($t['OutputS4S'] ?? null) }}</td>
                        <td class="number">{{ $fmtBlank($t['Jam'] ?? null) }}</td>
                        <td class="center">{{ $fmtIntBlank((int) round((float) ($t['Org'] ?? 0.0))) }}</td>
                        <td class="number">{{ $fmtRatioBlank($t['M3Jam'] ?? null) }}</td>
                        <td class="number">{{ $fmtRatioBlank($t['M3JamOrg'] ?? null) }}</td>
                        <td class="number">{{ $fmtPercentBlank($t['Rend'] ?? null) }}</td>
                    </tr>
                @endforeach

                @if ($grandTotals !== [])
                    <tr class="totals-row">
                        <td colspan="2" class="center">Grand Total</td>
                        <td class="number">{{ $fmtBlank($grandTotals['CCAkhir'] ?? null) }}</td>
                        <td class="number">{{ $fmtBlank($grandTotals['FJ'] ?? null) }}</td>
                        <td class="number">{{ $fmtBlank($grandTotals['Reproses'] ?? null) }}</td>
                        <td class="number">{{ $fmtBlank($grandTotals['S4S'] ?? null) }}</td>
                        <td class="number">{{ $fmtBlank($grandTotals['ST'] ?? null) }}</td>
                        <td class="number">{{ $fmtBlank($grandTotals['TotalInput'] ?? null) }}</td>
                        <td class="number">{{ $fmtBlank($grandTotals['OutputS4S'] ?? null) }}</td>
                        <td class="number">{{ $fmtBlank($grandTotals['Jam'] ?? null) }}</td>
                        <td class="center">{{ $fmtIntBlank((int) round((float) ($grandTotals['Org'] ?? 0.0))) }}</td>
                        <td class="number">{{ $fmtRatioBlank($grandTotals['M3Jam'] ?? null) }}</td>
                        <td class="number">{{ $fmtRatioBlank($grandTotals['M3JamOrg'] ?? null) }}</td>
                        <td class="number">{{ $fmtPercentBlank($grandTotals['Rend'] ?? null) }}</td>
                    </tr>
                @endif
            </tbody>
        </table>
    @endif

    @include('reports.partials.pdf-footer-table')
</body>

</html>
