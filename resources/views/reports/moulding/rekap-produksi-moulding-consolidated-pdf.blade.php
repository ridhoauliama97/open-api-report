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
        $fmtRatio2Blank = static fn(?float $v): string => $v === null || !is_finite($v) || abs($v) < $eps
            ? ''
            : number_format($v, 2, '.', '');
        $fmtPercentBlank = static fn(?float $v): string => $v === null || !is_finite($v) || abs($v) < $eps
            ? ''
            : number_format($v, 1, '.', '');

        $countNonZero = static function (array $sourceRows, string $key) use ($eps): int {
            $count = 0;
            foreach ($sourceRows as $sourceRow) {
                $sourceRow = is_array($sourceRow) ? $sourceRow : (array) $sourceRow;
                if (abs((float) ($sourceRow[$key] ?? 0.0)) > $eps) {
                    $count++;
                }
            }
            return $count;
        };

        $perColumnAverage = static function (float $value, int $count) use ($eps): float {
            return $count > 0 && abs($value) > $eps ? $value / $count : 0.0;
        };

        $grandTotals = [
            'BJ' => 0.0,
            'CCAkhir' => 0.0,
            'FJ' => 0.0,
            'Laminating' => 0.0,
            'Moulding' => 0.0,
            'Reproses' => 0.0,
            'S4S' => 0.0,
            'TotalInput' => 0.0,
            'OutputMoulding' => 0.0,
            'OutputReproses' => 0.0,
            'TotalOutput' => 0.0,
            'Jam' => 0.0,
            'Org' => 0.0,
            'M3Jam' => 0.0,
            'M3JamOrg' => 0.0,
            'Rend' => 0.0,
        ];
        $grandHk = 0;
        $grandRows = [];

        foreach ($machines as $machine) {
            $machineRows = is_array($machine['rows'] ?? null) ? $machine['rows'] : [];
            $machineTotals = is_array($machine['totals'] ?? null) ? $machine['totals'] : [];
            $grandHk += (int) ($machine['hk'] ?? 0);
            $grandRows = array_merge($grandRows, $machineRows);

            foreach (array_keys($grandTotals) as $key) {
                $grandTotals[$key] += (float) ($machineTotals[$key] ?? 0.0);
            }
        }

        $summaryMachines = [];
        foreach ($machines as $machine) {
            $machineTotals = is_array($machine['totals'] ?? null) ? $machine['totals'] : [];
            $machineHk = (int) ($machine['hk'] ?? 0);
            $machineJam = (int) round((float) ($machineTotals['Jam'] ?? 0.0));
            $machineOrg = (int) round((float) ($machineTotals['Org'] ?? 0.0));

            $summaryMachines[] = [
                'nama_mesin' => (string) ($machine['nama_mesin'] ?? ''),
                'hk' => $machineHk,
                'cc_akhir' => (float) ($machineTotals['CCAkhir'] ?? 0.0),
                'fj' => (float) ($machineTotals['FJ'] ?? 0.0),
                'reproses' => (float) ($machineTotals['Reproses'] ?? 0.0),
                's4s' => (float) ($machineTotals['S4S'] ?? 0.0),
                'st' => (float) ($machineTotals['Moulding'] ?? 0.0),
                'total_input' => (float) ($machineTotals['TotalInput'] ?? 0.0),
                'output_s4s' => (float) ($machineTotals['OutputMoulding'] ?? 0.0),
                'jam' => $machineJam,
                'org' => $machineOrg,
                'm3_jam' => (float) ($machineTotals['M3Jam'] ?? 0.0),
                'm3_jam_org' => (float) ($machineTotals['M3JamOrg'] ?? 0.0),
                'rend' => (float) ($machineTotals['Rend'] ?? 0.0),
            ];
        }

        $grandRowCount = count($grandRows);
        $grandM3Jam =
            $grandRowCount > 0
                ? array_sum(
                        array_map(
                            static fn($row): float => (float) (is_array($row) ? $row['M3Jam'] ?? 0.0 : 0.0),
                            $grandRows,
                        ),
                    ) / $grandRowCount
                : 0.0;
        $grandM3JamOrg =
            $grandRowCount > 0
                ? array_sum(
                        array_map(
                            static fn($row): float => (float) (is_array($row) ? $row['M3JamOrg'] ?? 0.0 : 0.0),
                            $grandRows,
                        ),
                    ) / $grandRowCount
                : 0.0;
        $grandRend =
            (float) ($grandTotals['TotalInput'] ?? 0.0) > 0
                ? ((float) ($grandTotals['TotalOutput'] ?? 0.0) / (float) ($grandTotals['TotalInput'] ?? 0.0)) * 100.0
                : 0.0;
    @endphp

    <h1 class="report-title">Laporan Rekap Produksi Moulding Consolidated</h1>
    <p class="report-subtitle">Periode {{ $start }} s/d {{ $end }}</p>

    @foreach ($machines as $machine)
        @php
            $namaMesin = (string) ($machine['nama_mesin'] ?? '');
            $rows = is_array($machine['rows'] ?? null) ? $machine['rows'] : [];
            $totals = is_array($machine['totals'] ?? null) ? $machine['totals'] : [];
            $hk = (int) ($machine['hk'] ?? 0);
        @endphp

        <div class="section-title">Nama Mesin : {{ $namaMesin }}</div>

        <table>
            <thead>
                <tr>
                    <th rowspan="2" style="width: 70px;">Tanggal</th>
                    <th rowspan="2" style="width: 34px;">Shift</th>
                    <th colspan="7">Input</th>
                    <th rowspan="2" style="width: 54px;">Total Input</th>
                    <th colspan="2">Output</th>
                    <th rowspan="2" style="width: 54px;">Total Output</th>
                    <th rowspan="2" style="width: 34px;">Jam</th>
                    <th rowspan="2" style="width: 34px;">Org</th>
                    <th rowspan="2" style="width: 52px;">M3/Jam</th>
                    <th rowspan="2" style="width: 58px;">M3/jam/Org</th>
                    <th rowspan="2" style="width: 48px;">Rend (%)</th>
                </tr>
                <tr>
                    <th style="width: 36px;">BJ</th>
                    <th style="width: 52px;">CCAkhir</th>
                    <th style="width: 40px;">FJ</th>
                    <th style="width: 58px;">Lmnatng</th>
                    <th style="width: 56px;">Moulding</th>
                    <th style="width: 56px;">Reproses</th>
                    <th style="width: 42px;">S4S</th>
                    <th style="width: 58px;">Moulding</th>
                    <th style="width: 58px;">Reproses</th>
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
                        <td class="number">{{ $fmtBlank($row['FJ'] ?? null) }}</td>
                        <td class="number">{{ $fmtBlank($row['Laminating'] ?? null) }}</td>
                        <td class="number">{{ $fmtBlank($row['Moulding'] ?? null) }}</td>
                        <td class="number">{{ $fmtBlank($row['Reproses'] ?? null) }}</td>
                        <td class="number">{{ $fmtBlank($row['S4S'] ?? null) }}</td>
                        <td class="number strong-number">{{ $fmtBlank($row['TotalInput'] ?? null) }}</td>
                        <td class="number strong-number">{{ $fmtBlank($row['OutputMoulding'] ?? null) }}</td>
                        <td class="number strong-number">{{ $fmtBlank($row['OutputReproses'] ?? null) }}</td>
                        <td class="number strong-number">{{ $fmtBlank($row['TotalOutput'] ?? null) }}</td>
                        <td class="center">{{ $fmtIntBlank($row['Jam'] ?? null) }}</td>
                        <td class="center">{{ $fmtIntBlank($row['Org'] ?? null) }}</td>
                        <td class="number">{{ $fmtRatio2Blank($row['M3Jam'] ?? null) }}</td>
                        <td class="number">{{ $fmtRatio2Blank($row['M3JamOrg'] ?? null) }}</td>
                        <td class="number strong-number">{{ $fmtPercentBlank($row['Rend'] ?? null) }}</td>
                    </tr>
                @endforeach

                @if ($rows !== [] && $totals !== [])
                    @php
                        $hkText = $hk > 0 ? 'HK : ' . $hk : 'HK : -';
                    @endphp
                    <tr class="totals-row">
                        <td colspan="2" class="center">{{ $hkText }}</td>
                        <td class="number">{{ $fmtBlank($totals['BJ'] ?? null) }}</td>
                        <td class="number">{{ $fmtBlank($totals['CCAkhir'] ?? null) }}</td>
                        <td class="number">{{ $fmtBlank($totals['FJ'] ?? null) }}</td>
                        <td class="number">{{ $fmtBlank($totals['Laminating'] ?? null) }}</td>
                        <td class="number">{{ $fmtBlank($totals['Moulding'] ?? null) }}</td>
                        <td class="number">{{ $fmtBlank($totals['Reproses'] ?? null) }}</td>
                        <td class="number">{{ $fmtBlank($totals['S4S'] ?? null) }}</td>
                        <td class="number">{{ $fmtBlank($totals['TotalInput'] ?? null) }}</td>
                        <td class="number">{{ $fmtBlank($totals['OutputMoulding'] ?? null) }}</td>
                        <td class="number">{{ $fmtBlank($totals['OutputReproses'] ?? null) }}</td>
                        <td class="number">{{ $fmtBlank($totals['TotalOutput'] ?? null) }}</td>
                        <td class="center">{{ $fmtIntBlank((int) round((float) ($totals['Jam'] ?? 0.0))) }}</td>
                        <td class="center">{{ $fmtIntBlank((int) round((float) ($totals['Org'] ?? 0.0))) }}</td>
                        <td class="number">{{ $fmtRatio2Blank($totals['M3Jam'] ?? null) }}</td>
                        <td class="number">{{ $fmtRatio2Blank($totals['M3JamOrg'] ?? null) }}</td>
                        <td class="number">{{ $fmtPercentBlank($totals['Rend'] ?? null) }}</td>
                    </tr>

                    <tr class="totals-row">
                        <td colspan="2" class="center"><strong>Jmlh/HK</strong></td>
                        <td class="number">
                            {{ $fmtBlank($perColumnAverage((float) ($totals['BJ'] ?? 0.0), $countNonZero($rows, 'BJ'))) }}
                        </td>
                        <td class="number">
                            {{ $fmtBlank($perColumnAverage((float) ($totals['CCAkhir'] ?? 0.0), $countNonZero($rows, 'CCAkhir'))) }}
                        </td>
                        <td class="number">
                            {{ $fmtBlank($perColumnAverage((float) ($totals['FJ'] ?? 0.0), $countNonZero($rows, 'FJ'))) }}
                        </td>
                        <td class="number">
                            {{ $fmtBlank($perColumnAverage((float) ($totals['Laminating'] ?? 0.0), $countNonZero($rows, 'Laminating'))) }}
                        </td>
                        <td class="number">
                            {{ $fmtBlank($perColumnAverage((float) ($totals['Moulding'] ?? 0.0), $countNonZero($rows, 'Moulding'))) }}
                        </td>
                        <td class="number">
                            {{ $fmtBlank($perColumnAverage((float) ($totals['Reproses'] ?? 0.0), $countNonZero($rows, 'Reproses'))) }}
                        </td>
                        <td class="number">
                            {{ $fmtBlank($perColumnAverage((float) ($totals['S4S'] ?? 0.0), $countNonZero($rows, 'S4S'))) }}
                        </td>
                        <td class="number">
                            {{ $fmtBlank($hk > 0 ? (float) ($totals['TotalInput'] ?? 0.0) / $hk : 0.0) }}</td>
                        <td class="number">
                            {{ $fmtBlank($perColumnAverage((float) ($totals['OutputMoulding'] ?? 0.0), $countNonZero($rows, 'OutputMoulding'))) }}
                        </td>
                        <td class="number">
                            {{ $fmtBlank($perColumnAverage((float) ($totals['OutputReproses'] ?? 0.0), $countNonZero($rows, 'OutputReproses'))) }}
                        </td>
                        <td class="number">
                            {{ $fmtBlank($hk > 0 ? (float) ($totals['TotalOutput'] ?? 0.0) / $hk : 0.0) }}</td>
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
                    <th style="width: 34px;">HK</th>
                    <th style="width: 36px;">BJ</th>
                    <th style="width: 64px;">CCAkhir</th>
                    <th style="width: 42px;">FJ</th>
                    <th style="width: 58px;">Lmnatng</th>
                    <th style="width: 56px;">Moulding</th>
                    <th style="width: 64px;">Reproses</th>
                    <th style="width: 48px;">S4S</th>
                    <th style="width: 68px;">Total Input</th>
                    <th style="width: 58px;">Output Moulding</th>
                    <th style="width: 58px;">Output Reproses</th>
                    <th style="width: 68px;">Total Output</th>
                    <th style="width: 36px;">Jam</th>
                    <th style="width: 36px;">Org</th>
                    <th style="width: 56px;">M3/Jam</th>
                    <th style="width: 68px;">M3/jam/Org</th>
                    <th style="width: 54px;">Rend (%)</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($summaryMachines as $summaryMachine)
                    <tr class="{{ $loop->odd ? 'row-odd' : 'row-even' }}">
                        <td>{{ $summaryMachine['nama_mesin'] }}</td>
                        <td class="center">{{ $fmtIntBlank($summaryMachine['hk']) }}</td>
                        <td class="number"></td>
                        <td class="number">{{ $fmtBlank($summaryMachine['cc_akhir']) }}</td>
                        <td class="number">{{ $fmtBlank($summaryMachine['fj']) }}</td>
                        <td class="number"></td>
                        <td class="number">{{ $fmtBlank($summaryMachine['st']) }}</td>
                        <td class="number">{{ $fmtBlank($summaryMachine['reproses']) }}</td>
                        <td class="number">{{ $fmtBlank($summaryMachine['s4s']) }}</td>
                        <td class="number strong-number">{{ $fmtBlank($summaryMachine['total_input']) }}</td>
                        <td class="number strong-number">{{ $fmtBlank($summaryMachine['output_s4s']) }}</td>
                        <td class="number"></td>
                        <td class="number strong-number">{{ $fmtBlank($summaryMachine['output_s4s']) }}</td>
                        <td class="center">{{ $fmtIntBlank($summaryMachine['jam']) }}</td>
                        <td class="center">{{ $fmtIntBlank($summaryMachine['org']) }}</td>
                        <td class="number">{{ $fmtRatio2Blank($summaryMachine['m3_jam']) }}</td>
                        <td class="number">{{ $fmtRatio2Blank($summaryMachine['m3_jam_org']) }}</td>
                        <td class="number strong-number">{{ $fmtPercentBlank($summaryMachine['rend']) }}</td>
                    </tr>
                @endforeach
                <tr class="totals-row">
                    <td class="center">Grand Total</td>
                    <td class="center">{{ $fmtIntBlank($grandHk) }}</td>
                    <td class="number">{{ $fmtBlank($grandTotals['BJ']) }}</td>
                    <td class="number">{{ $fmtBlank($grandTotals['CCAkhir']) }}</td>
                    <td class="number">{{ $fmtBlank($grandTotals['FJ']) }}</td>
                    <td class="number">{{ $fmtBlank($grandTotals['Laminating']) }}</td>
                    <td class="number">{{ $fmtBlank($grandTotals['Moulding']) }}</td>
                    <td class="number">{{ $fmtBlank($grandTotals['Reproses']) }}</td>
                    <td class="number">{{ $fmtBlank($grandTotals['S4S']) }}</td>
                    <td class="number strong-number">{{ $fmtBlank($grandTotals['TotalInput']) }}</td>
                    <td class="number strong-number">{{ $fmtBlank($grandTotals['OutputMoulding']) }}</td>
                    <td class="number strong-number">{{ $fmtBlank($grandTotals['OutputReproses']) }}</td>
                    <td class="number strong-number">{{ $fmtBlank($grandTotals['TotalOutput']) }}</td>
                    <td class="number" style="text-align: center;">
                        {{ $fmtIntBlank((int) round($grandTotals['Jam'])) }}</td>
                    <td class="number" style="text-align: center;">
                        {{ $fmtIntBlank((int) round($grandTotals['Org'])) }}</td>
                    <td class="number">{{ $fmtRatio2Blank($grandM3Jam) }}</td>
                    <td class="number">{{ $fmtRatio2Blank($grandM3JamOrg) }}</td>
                    <td class="number strong-number">
                        {{ $fmtPercentBlank($grandRend) }}
                    </td>
                </tr>
            </tbody>
        </table>
    @endif

    @include('reports.partials.pdf-footer-table')
</body>

</html>
