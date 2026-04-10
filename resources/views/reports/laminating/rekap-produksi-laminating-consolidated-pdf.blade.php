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

        .strong-number {
            font-weight: bold;
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

        $grandTotals = [
            'BJ' => 0.0,
            'CCAkhir' => 0.0,
            'Moulding' => 0.0,
            'Reproses' => 0.0,
            'Sanding' => 0.0,
            'TotalInput' => 0.0,
            'OutputLaminating' => 0.0,
            'Jam' => 0.0,
            'Org' => 0.0,
            'Rend' => 0.0,
        ];
        $grandM3Jam = 0.0;
        $grandM3JamOrg = 0.0;
        $machineCount = count($machines);
        $summaryMachines = [];

        foreach ($machines as $machine) {
            $machineTotals = is_array($machine['totals'] ?? null) ? $machine['totals'] : [];
            $summaryMachines[] = [
                'nama_mesin' => (string) ($machine['nama_mesin'] ?? ''),
                'bj' => (float) ($machineTotals['BJ'] ?? 0.0),
                'cc_akhir' => (float) ($machineTotals['CCAkhir'] ?? 0.0),
                'moulding' => (float) ($machineTotals['Moulding'] ?? 0.0),
                'reproses' => (float) ($machineTotals['Reproses'] ?? 0.0),
                'sanding' => (float) ($machineTotals['Sanding'] ?? 0.0),
                'total_input' => (float) ($machineTotals['TotalInput'] ?? 0.0),
                'output_laminating' => (float) ($machineTotals['OutputLaminating'] ?? 0.0),
                'jam' => (int) round((float) ($machineTotals['Jam'] ?? 0.0)),
                'org' => (int) round((float) ($machineTotals['Org'] ?? 0.0)),
                'm3_jam' => (float) ($machineTotals['M3Jam'] ?? 0.0),
                'm3_jam_org' => (float) ($machineTotals['M3JamOrg'] ?? 0.0),
                'rend' => (float) ($machineTotals['Rend'] ?? 0.0),
            ];
            foreach (array_keys($grandTotals) as $key) {
                $grandTotals[$key] += (float) ($machineTotals[$key] ?? 0.0);
            }
            $grandM3Jam += (float) ($machineTotals['M3Jam'] ?? 0.0);
            $grandM3JamOrg += (float) ($machineTotals['M3JamOrg'] ?? 0.0);
        }
    @endphp

    <h1 class="report-title">Laporan Rekap Produksi Laminating Consolidated</h1>
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
                    <th rowspan="2" style="width: 74px;">Tanggal</th>
                    <th rowspan="2" style="width: 32px;">Shift</th>
                    <th colspan="5">Input</th>
                    <th rowspan="2" style="width: 52px;">Total Input</th>
                    <th rowspan="2" style="width: 62px;">Output Laminating</th>
                    <th rowspan="2" style="width: 34px;">Jam</th>
                    <th rowspan="2" style="width: 34px;">Org</th>
                    <th rowspan="2" style="width: 52px;">M3/Jam</th>
                    <th rowspan="2" style="width: 58px;">M3/jam/Org</th>
                    <th rowspan="2" style="width: 48px;">Rend (%)</th>
                </tr>
                <tr>
                    <th style="width: 42px;">BJ</th>
                    <th style="width: 52px;">CCAkhir</th>
                    <th style="width: 56px;">Moulding</th>
                    <th style="width: 56px;">Reproses</th>
                    <th style="width: 52px;">Sanding</th>
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
                        <td class="number">{{ $fmtBlank($row['BJ'] ?? null) }}</td>
                        <td class="number">{{ $fmtBlank($row['CCAkhir'] ?? null) }}</td>
                        <td class="number">{{ $fmtBlank($row['Moulding'] ?? null) }}</td>
                        <td class="number">{{ $fmtBlank($row['Reproses'] ?? null) }}</td>
                        <td class="number">{{ $fmtBlank($row['Sanding'] ?? null) }}</td>
                        <td class="number strong-number">{{ $fmtBlank($row['TotalInput'] ?? null) }}</td>
                        <td class="number strong-number">{{ $fmtBlank($row['OutputLaminating'] ?? null) }}</td>
                        <td class="center">{{ $fmtIntBlank($row['Jam'] ?? null) }}</td>
                        <td class="center">{{ $fmtIntBlank($row['Org'] ?? null) }}</td>
                        <td class="number">{{ $fmtRatioBlank($row['M3Jam'] ?? null) }}</td>
                        <td class="number">{{ $fmtRatioBlank($row['M3JamOrg'] ?? null) }}</td>
                        <td class="number strong-number">{{ $fmtPercentBlank($row['Rend'] ?? null) }}</td>
                    </tr>
                @endforeach

                @if ($rows !== [] && $totals !== [])
                    @php
                        $hkText = $hk > 0 ? 'HK : ' . $hk : 'HK : -';
                        $denom = $hkWorking > 0 ? $hkWorking : $hk;
                        $jmlhPerHk = static fn(float $v) => $denom > 0 ? $v / $denom : 0.0;
                        $jmlhPerCalendarHk = static fn(float $v) => $hk > 0 ? $v / $hk : 0.0;
                    @endphp
                    <tr class="totals-row">
                        <td colspan="2" class="center">{{ $hkText }}</td>
                        <td class="number">{{ $fmtBlank($totals['BJ'] ?? null) }}</td>
                        <td class="number">{{ $fmtBlank($totals['CCAkhir'] ?? null) }}</td>
                        <td class="number">{{ $fmtBlank($totals['Moulding'] ?? null) }}</td>
                        <td class="number">{{ $fmtBlank($totals['Reproses'] ?? null) }}</td>
                        <td class="number">{{ $fmtBlank($totals['Sanding'] ?? null) }}</td>
                        <td class="number">{{ $fmtBlank($totals['TotalInput'] ?? null) }}</td>
                        <td class="number">{{ $fmtBlank($totals['OutputLaminating'] ?? null) }}</td>
                        <td class="center">{{ $fmtIntBlank((int) round((float) ($totals['Jam'] ?? 0.0))) }}</td>
                        <td class="center">{{ $fmtIntBlank((int) round((float) ($totals['Org'] ?? 0.0))) }}</td>
                        <td class="number">{{ $fmtRatioBlank($totals['M3Jam'] ?? null) }}</td>
                        <td class="number">{{ $fmtRatioBlank($totals['M3JamOrg'] ?? null) }}</td>
                        <td class="number">{{ $fmtPercentBlank($totals['Rend'] ?? null) }}</td>
                    </tr>

                    <tr class="totals-row">
                        <td colspan="2" class="center"><strong>Jmlh/HK</strong></td>
                        <td class="number">{{ $fmtBlank($jmlhPerHk((float) ($totals['BJ'] ?? 0.0))) }}</td>
                        <td class="number">{{ $fmtBlank($jmlhPerHk((float) ($totals['CCAkhir'] ?? 0.0))) }}</td>
                        <td class="number">{{ $fmtBlank($jmlhPerHk((float) ($totals['Moulding'] ?? 0.0))) }}</td>
                        <td class="number">{{ $fmtBlank($jmlhPerHk((float) ($totals['Reproses'] ?? 0.0))) }}</td>
                        <td class="number">{{ $fmtBlank($jmlhPerHk((float) ($totals['Sanding'] ?? 0.0))) }}</td>
                        <td class="number">{{ $fmtBlank($jmlhPerCalendarHk((float) ($totals['TotalInput'] ?? 0.0))) }}
                        </td>
                        <td class="number">{{ $fmtBlank($jmlhPerHk((float) ($totals['OutputLaminating'] ?? 0.0))) }}
                        </td>
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
        <div class="section-title" style="margin-top: 12px;">Rangkuman HK Per Mesin</div>
        <table>
            <thead>
                <tr>
                    <th style="width: 160px;">Nama Mesin</th>
                    <th style="width: 42px;">BJ</th>
                    <th style="width: 56px;">CCAkhir</th>
                    <th style="width: 56px;">Moulding</th>
                    <th style="width: 56px;">Reproses</th>
                    <th style="width: 56px;">Sanding</th>
                    <th style="width: 70px;">Total Input</th>
                    <th style="width: 78px;">Output Laminating</th>
                    <th style="width: 40px;">Jam</th>
                    <th style="width: 40px;">Org</th>
                    <th style="width: 58px;">M3/Jam</th>
                    <th style="width: 68px;">M3/jam/Org</th>
                    <th style="width: 54px;">Rend (%)</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($summaryMachines as $summaryMachine)
                    <tr class="{{ $loop->odd ? 'row-odd' : 'row-even' }}">
                        <td>{{ $summaryMachine['nama_mesin'] }}</td>
                        <td class="number">{{ $fmtBlank($summaryMachine['bj']) }}</td>
                        <td class="number">{{ $fmtBlank($summaryMachine['cc_akhir']) }}</td>
                        <td class="number">{{ $fmtBlank($summaryMachine['moulding']) }}</td>
                        <td class="number">{{ $fmtBlank($summaryMachine['reproses']) }}</td>
                        <td class="number">{{ $fmtBlank($summaryMachine['sanding']) }}</td>
                        <td class="number strong-number">{{ $fmtBlank($summaryMachine['total_input']) }}</td>
                        <td class="number strong-number">{{ $fmtBlank($summaryMachine['output_laminating']) }}</td>
                        <td class="center">{{ $fmtIntBlank($summaryMachine['jam']) }}</td>
                        <td class="center">{{ $fmtIntBlank($summaryMachine['org']) }}</td>
                        <td class="number">{{ $fmtRatioBlank($summaryMachine['m3_jam']) }}</td>
                        <td class="number">{{ $fmtRatioBlank($summaryMachine['m3_jam_org']) }}</td>
                        <td class="number strong-number">{{ $fmtPercentBlank($summaryMachine['rend']) }}</td>
                    </tr>
                @endforeach
                <tr class="totals-row">
                    <td class="center">Grand Total</td>
                    <td class="number">{{ $fmtBlank($grandTotals['BJ']) }}</td>
                    <td class="number">{{ $fmtBlank($grandTotals['CCAkhir']) }}</td>
                    <td class="number">{{ $fmtBlank($grandTotals['Moulding']) }}</td>
                    <td class="number">{{ $fmtBlank($grandTotals['Reproses']) }}</td>
                    <td class="number">{{ $fmtBlank($grandTotals['Sanding']) }}</td>
                    <td class="number strong-number">{{ $fmtBlank($grandTotals['TotalInput']) }}</td>
                    <td class="number strong-number">{{ $fmtBlank($grandTotals['OutputLaminating']) }}</td>
                    <td class="center">{{ $fmtIntBlank((int) round($grandTotals['Jam'])) }}</td>
                    <td class="center">{{ $fmtIntBlank((int) round($grandTotals['Org'])) }}</td>
                    <td class="number">{{ $fmtRatioBlank($machineCount > 0 ? ($grandM3Jam / $machineCount) : 0.0) }}</td>
                    <td class="number">{{ $fmtRatioBlank($machineCount > 0 ? ($grandM3JamOrg / $machineCount) : 0.0) }}</td>
                    <td class="number strong-number">{{ $fmtPercentBlank($grandTotals['TotalInput'] > 0 ? ($grandTotals['OutputLaminating'] / $grandTotals['TotalInput']) * 100.0 : 0.0) }}</td>
                </tr>
            </tbody>
        </table>
    @endif

    @include('reports.partials.pdf-footer-table')
</body>

</html>
