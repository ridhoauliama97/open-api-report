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
        $machines = is_array($data['machines'] ?? null) ? $data['machines'] : [];
        $grandTotals = is_array($data['grand_totals'] ?? null) ? $data['grand_totals'] : [];

        $start = \Carbon\Carbon::parse((string) ($data['start_date'] ?? ''))->locale('id')->translatedFormat('d-M-y');
        $end = \Carbon\Carbon::parse((string) ($data['end_date'] ?? ''))->locale('id')->translatedFormat('d-M-y');

        $eps = 0.0000001;
        $fmtDate = static fn(string $v): string => $v === '' ? '' : \Carbon\Carbon::parse($v)->format('d-M-y');
        $fmtBlank = static fn(?float $v): string => $v === null || abs($v) < $eps ? '' : number_format($v, 1, '.', '');
        $fmtIntBlank = static fn(?int $v): string => $v === null || $v <= 0 ? '' : (string) $v;
        $fmtRatioBlank = static fn(?float $v): string => $v === null || !is_finite($v) || abs($v) < $eps
            ? ''
            : number_format($v, 1, '.', '');
        $fmtPercentBlank = static fn(?float $v): string => $v === null || !is_finite($v) || abs($v) < $eps
            ? ''
            : number_format($v, 1, '.', '');

        // Grand Total: sum of the "HK" totals row from each machine table.
        // For ratio columns (M3/Jam, M3/jam/Org), we keep the same convention as the per-machine totals row:
        // sum of per-row ratios, so summing per-machine totals is consistent.
        $grandHk = 0;
        $grandHkTotals = [
            'CCAkhir' => 0.0,
            'S4S' => 0.0,
            'TotalInput' => 0.0,
            'OutputFJ' => 0.0,
            'Jam' => 0.0,
            'Org' => 0.0,
            'M3Jam' => 0.0,
            'M3JamOrg' => 0.0,
        ];

        foreach ($machines as $m) {
            $grandHk += (int) ($m['hk'] ?? 0);
            $tot = is_array($m['totals'] ?? null) ? $m['totals'] : [];
            foreach (array_keys($grandHkTotals) as $k) {
                $grandHkTotals[$k] += (float) ($tot[$k] ?? 0.0);
            }
        }

        $grandRend =
            abs($grandHkTotals['TotalInput']) > $eps
                ? ($grandHkTotals['OutputFJ'] / $grandHkTotals['TotalInput']) * 100.0
                : 0.0;
    @endphp

    <h1 class="report-title">Laporan Rekap Produksi Finger Joint Consolidated</h1>
    <p class="report-subtitle">Periode {{ $start }} s/d {{ $end }}</p>

    @foreach ($machines as $machine)
        @php
            $namaMesin = (string) ($machine['nama_mesin'] ?? '');
            $rows = is_array($machine['rows'] ?? null) ? $machine['rows'] : [];
            $totals = is_array($machine['totals'] ?? null) ? $machine['totals'] : [];
            $hk = (int) ($machine['hk'] ?? 0);
            $hkWorking = (int) ($machine['hk_working'] ?? 0);
        @endphp

        <div class="section-title">Nama Mesin : {{ $namaMesin }}</div>

        <table>
            <thead>
                <tr>
                    <th rowspan="2" style="width: 9%;">Tanggal</th>
                    <th rowspan="2" style="width: 9%;">Shift</th>
                    <th colspan="2">Input</th>
                    <th rowspan="2" style="width: 9%;">Total Input</th>
                    <th rowspan="2" style="width: 9%;">Output FJ</th>
                    <th rowspan="2" style="width: 9%;">Jam</th>
                    <th rowspan="2" style="width: 9%;">Org</th>
                    <th rowspan="2" style="width: 9%;">M<sup>3</sup>/Jam</th>
                    <th rowspan="2" style="width: 9%;">M<sup>3</sup>/jam/Org</th>
                    <th rowspan="2" style="width: 9%;">Rend (%)</th>
                </tr>
                <tr>
                    <th style="width: 9%;">CCAkhir</th>
                    <th style="width: 9%;">S4S</th>
                </tr>
            </thead>
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
                        <td class="number">{{ $fmtBlank($row['S4S'] ?? null) }}</td>
                        <td class="number" style="font-weight: bold;">{{ $fmtBlank($row['TotalInput'] ?? null) }}</td>
                        <td class="number" style="font-weight: bold;">{{ $fmtBlank($row['OutputFJ'] ?? null) }}</td>
                        <td class="center">{{ $fmtIntBlank($row['Jam'] ?? null) }}</td>
                        <td class="center">{{ $fmtIntBlank($row['Org'] ?? null) }}</td>
                        <td class="number">{{ $fmtRatioBlank($row['M3Jam'] ?? null) }}</td>
                        <td class="number">{{ $fmtRatioBlank($row['M3JamOrg'] ?? null) }}</td>
                        <td class="number" style="font-weight: bold;">{{ $fmtPercentBlank($row['Rend'] ?? null) }}</td>
                    </tr>
                @endforeach

                @if ($rows !== [] && $totals !== [])
                    @php
                        $hkText = $hk > 0 ? 'HK : ' . $hk : 'HK : -';
                        // Reference report: Jmlh/HK uses working days (hari kerja), not full calendar days.
                        $denom = $hkWorking > 0 ? $hkWorking : $hk;
                        $jmlhPerHk = static fn(float $v) => $denom > 0 ? $v / $denom : 0.0;
                    @endphp
                    <tr class="totals-row">
                        <td colspan="2" class="center">{{ $hkText }}</td>
                        <td class="number">{{ $fmtBlank($totals['CCAkhir'] ?? null) }}</td>
                        <td class="number">{{ $fmtBlank($totals['S4S'] ?? null) }}</td>
                        <td class="number" style="font-weight: bold;">{{ $fmtBlank($totals['TotalInput'] ?? null) }}
                        </td>
                        <td class="number" style="font-weight: bold;">{{ $fmtBlank($totals['OutputFJ'] ?? null) }}</td>
                        <td class="center">{{ $fmtIntBlank($totals['Jam'] ?? null) }}</td>
                        <td class="center">{{ $fmtIntBlank((int) round((float) ($totals['Org'] ?? 0.0))) }}</td>
                        <td class="number">{{ $fmtRatioBlank($totals['M3Jam'] ?? null) }}</td>
                        <td class="number">{{ $fmtRatioBlank($totals['M3JamOrg'] ?? null) }}</td>
                        <td class="number" style="font-weight: bold;">{{ $fmtPercentBlank($totals['Rend'] ?? null) }}
                        </td>
                    </tr>

                    <tr class="totals-row">
                        <td colspan="2" class="center"><strong>Jmlh/HK</strong></td>
                        {{-- Reference: show per-HK averages for main flow columns, leave others blank. --}}
                        <td class="number"></td>
                        <td class="number">{{ $fmtBlank($jmlhPerHk((float) ($totals['S4S'] ?? 0.0))) }}</td>
                        <td class="number">{{ $fmtBlank($jmlhPerHk((float) ($totals['TotalInput'] ?? 0.0))) }}</td>
                        <td class="number">{{ $fmtBlank($jmlhPerHk((float) ($totals['OutputFJ'] ?? 0.0))) }}</td>
                        <td class="number"></td>
                        <td class="center"></td>
                        <td class="number"></td>
                        <td class="number"></td>
                        <td class="number"></td>
                    </tr>
                @endif
            </tbody>
        </table>
    @endforeach

    @if ($machines !== [])
        @php
            $fmtDashBlank = static fn(?float $v): string => $v === null || abs($v) < $eps
                ? ''
                : number_format($v, 1, '.', '');
            $fmtIntDashBlank = static fn(?float $v): string => $v === null || abs($v) < $eps
                ? ''
                : (string) (int) round($v);
        @endphp

        <table style="margin-top: 8px;">
            <tbody>
                <tr class="totals-row">
                    <td colspan="2" class="center" style="width: 18%;">
                        <strong>Grand Total </strong>
                    </td>
                    <td class="number" style="width: 9%;">{{ $fmtDashBlank($grandHkTotals['CCAkhir'] ?? null) }}</td>
                    <td class="number" style="width: 9%;">{{ $fmtDashBlank($grandHkTotals['S4S'] ?? null) }}</td>
                    <td class="number" style="width: 9%;">{{ $fmtDashBlank($grandHkTotals['TotalInput'] ?? null) }}
                    </td>
                    <td class="number" style="width: 9%;">{{ $fmtDashBlank($grandHkTotals['OutputFJ'] ?? null) }}
                    </td>
                    <td class="number" style="width: 9%; text-align: center;">
                        {{ $fmtIntDashBlank($grandHkTotals['Jam'] ?? null) }}</td>
                    <td class="number" style="width: 9%; text-align: center;">
                        {{ $fmtIntDashBlank($grandHkTotals['Org'] ?? null) }}</td>
                    <td class="number" style="width: 9%;">
                        {{ $fmtRatioBlank($grandHkTotals['M3Jam'] ?? null) }}</td>
                    <td class="number" style="width: 9%;">
                        {{ $fmtRatioBlank($grandHkTotals['M3JamOrg'] ?? null) }}</td>
                    <td class="number" style="width: 9%;">{{ $fmtPercentBlank($grandRend) }}</td>
                </tr>
            </tbody>
        </table>
    @endif

    @include('reports.partials.pdf-footer-table')
</body>

</html>
