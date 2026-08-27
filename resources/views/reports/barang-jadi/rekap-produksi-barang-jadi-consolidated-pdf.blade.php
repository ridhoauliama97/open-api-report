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
            margin: 0;
            padding: 0;
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
            width: calc(100% - 2px);
            line-height: inherit;
            border-collapse: collapse;
            border-spacing: 0;
            border: 1px solid #000;
        }

        th,
        td {
            border: 1px solid #000;
            word-wrap: break-word;
            padding: 2px 2px;
        }

        td.center {
            text-align: center;
        }

        td.label {
            white-space: nowrap;
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

        .totals-row td {
            font-weight: bold;
        }

        .headers-row th {
            font-weight: bold;
        }

        .total-row td,
        .grand-total-row td {
            font-weight: bold;
        }
    </style>
</head>

<body>
    @php
        $data = is_array($reportData ?? null) ? $reportData : [];
        $machines = is_array($data['machines'] ?? null) ? $data['machines'] : [];
        $start = \Carbon\Carbon::parse((string) ($data['start_date'] ?? ''))->locale('id')->translatedFormat('d-M-y');
        $end = \Carbon\Carbon::parse((string) ($data['end_date'] ?? ''))->locale('id')->translatedFormat('d-M-y');
        $eps = 0.0000001;
        $fmtDate = static fn(string $v): string => $v === ''
            ? ''
            : \Carbon\Carbon::parse($v)->locale('id')->translatedFormat('d-M-y');
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
            'Moulding' => 0.0,
            'Sanding' => 0.0,
            'TotalInput' => 0.0,
            'OutputPacking' => 0.0,
            'OutputReproses' => 0.0,
            'TotalOutput' => 0.0,
            'Jam' => 0.0,
            'Org' => 0.0,
            'M3Jam' => 0.0,
            'M3JamOrg' => 0.0,
        ];
        foreach ($machines as $machineItem) {
            $machineTotals = is_array($machineItem['totals'] ?? null) ? $machineItem['totals'] : [];
            foreach (array_keys($grandTotals) as $key) {
                $grandTotals[$key] += (float) ($machineTotals[$key] ?? 0.0);
            }
        }
        $grandTotals['Rend'] =
            abs($grandTotals['TotalInput']) > $eps
                ? ($grandTotals['TotalOutput'] / $grandTotals['TotalInput']) * 100.0
                : 0.0;
    @endphp
    <h1 class="report-title">Laporan Rekap Produksi Packing Consolidated</h1>
    <p class="report-subtitle">Periode {{ $start }} s/d {{ $end }}</p>
    @foreach ($machines as $machine)
        @php
            $namaMesin = (string) ($machine['nama_mesin'] ?? '');
            $rows = is_array($machine['rows'] ?? null) ? $machine['rows'] : [];
            $totals = is_array($machine['totals'] ?? null) ? $machine['totals'] : [];
            $hk = (int) ($machine['hk'] ?? 0);
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
        @endphp
        <div class="section-title">Nama Mesin : {{ $namaMesin }}</div>
        <table>
            <thead>
                <tr>
                    <th rowspan="2" style="width: 38.4px;">Tanggal</th>
                    <th rowspan="2" style="width: 38.4px;">Shift</th>
                    <th colspan="4">Input</th>
                    <th colspan="3">Output</th>
                    <th rowspan="2" style="width: 38.4px;">Jam</th>
                    <th rowspan="2" style="width: 38.4px;">Org</th>
                    <th rowspan="2" style="width: 38.4px;">M3/Jam</th>
                    <th rowspan="2" style="width: 38.4px;">M3/jam/<br>Org</th>
                    <th rowspan="2" style="width: 38.4px;">Rend <br>(%)</th>
                </tr>
                <tr>
                    <th style="width: 38.4px;">BJ</th>
                    <th style="width: 38.4px;">Moulding</th>
                    <th style="width: 38.4px;">Sanding</th>
                    <th style="width: 38.4px;">TOTAL</th>
                    <th style="width: 38.4px;">Packing</th>
                    <th style="width: 38.4px;">Reproses</th>
                    <th style="width: 38.4px;">TOTAL </th>
                </tr>
            </thead>
            <tbody>
                @php $rowIndex = 0; @endphp
                @foreach ($rows as $row)
                    @php
                        $rowIndex++;
                        $row = is_array($row) ? $row : (array) $row;
                    @endphp
                    <tr class="bounded-row {{ $rowIndex % 2 === 1 ? 'row-odd' : 'row-even' }}">
                        <td class="center">{{ $fmtDate((string) ($row['Tanggal'] ?? '')) }}</td>
                        <td class="center">{{ (int) ($row['Shift'] ?? 0) }}</td>
                        <td class="number">{{ $fmtBlank($row['BJ'] ?? null) }}</td>
                        <td class="number">{{ $fmtBlank($row['Moulding'] ?? null) }}</td>
                        <td class="number">{{ $fmtBlank($row['Sanding'] ?? null) }}</td>
                        <td class="number" style="font-weight: bold;">{{ $fmtBlank($row['TotalInput'] ?? null) }}</td>
                        <td class="number">{{ $fmtBlank($row['OutputPacking'] ?? null) }}</td>
                        <td class="number">{{ $fmtBlank($row['OutputReproses'] ?? null) }}</td>
                        <td class="number" style="font-weight: bold;">{{ $fmtBlank($row['TotalOutput'] ?? null) }}</td>
                        <td class="number">{{ $fmtIntBlank($row['Jam'] ?? null) }}</td>
                        <td class="number">{{ $fmtIntBlank($row['Org'] ?? null) }}</td>
                        <td class="number">{{ $fmtRatioBlank($row['M3Jam'] ?? null) }}</td>
                        <td class="number">{{ $fmtRatioBlank($row['M3JamOrg'] ?? null) }}</td>
                        <td class="number" style="font-weight: bold;">{{ $fmtPercentBlank($row['Rend'] ?? null) }}</td>
                    </tr>
                @endforeach
                @if ($rows !== [] && $totals !== [])
                    <tr class="bounded-row totals-row">
                        <td colspan="2" class="center">{{ $hk > 0 ? 'HK : ' . $hk : 'HK : -' }}</td>
                        <td class="number">{{ $fmtBlank($totals['BJ'] ?? null) }}</td>
                        <td class="number">{{ $fmtBlank($totals['Moulding'] ?? null) }}</td>
                        <td class="number">{{ $fmtBlank($totals['Sanding'] ?? null) }}</td>
                        <td class="number" style="font-weight: bold;">{{ $fmtBlank($totals['TotalInput'] ?? null) }}
                        </td>
                        <td class="number">{{ $fmtBlank($totals['OutputPacking'] ?? null) }}</td>
                        <td class="number">{{ $fmtBlank($totals['OutputReproses'] ?? null) }}</td>
                        <td class="number" style="font-weight: bold;">{{ $fmtBlank($totals['TotalOutput'] ?? null) }}
                        </td>
                        <td class="number">
                            {{ $fmtIntBlank(isset($totals['Jam']) ? (int) round((float) $totals['Jam']) : null) }}</td>
                        <td class="number">
                            {{ $fmtIntBlank(isset($totals['Org']) ? (int) round((float) $totals['Org']) : null) }}</td>
                        <td class="number">{{ $fmtRatioBlank($totals['M3Jam'] ?? null) }}</td>
                        <td class="number">{{ $fmtRatioBlank($totals['M3JamOrg'] ?? null) }}</td>
                        <td class="number" style="font-weight: bold;">{{ $fmtPercentBlank($totals['Rend'] ?? null) }}
                        </td>
                    </tr>
                    <tr class="bounded-row totals-row">
                        <td colspan="2" class="center">Jmlh/HK</td>
                        <td class="number">
                            {{ $fmtBlank($perColumnAverage((float) ($totals['BJ'] ?? 0.0), $countNonZero($rows, 'BJ'))) }}
                        </td>
                        <td class="number">
                            {{ $fmtBlank($perColumnAverage((float) ($totals['Moulding'] ?? 0.0), $countNonZero($rows, 'Moulding'))) }}
                        </td>
                        <td class="number">
                            {{ $fmtBlank($perColumnAverage((float) ($totals['Sanding'] ?? 0.0), $countNonZero($rows, 'Sanding'))) }}
                        </td>
                        <td class="number">
                            {{ $fmtBlank($hk > 0 ? (float) ($totals['TotalInput'] ?? 0.0) / $hk : 0.0) }}</td>
                        <td class="number">
                            {{ $fmtBlank($perColumnAverage((float) ($totals['OutputPacking'] ?? 0.0), $countNonZero($rows, 'OutputPacking'))) }}
                        </td>
                        <td class="number">
                            {{ $fmtBlank($perColumnAverage((float) ($totals['OutputReproses'] ?? 0.0), $countNonZero($rows, 'OutputReproses'))) }}
                        </td>
                        <td class="number">
                            {{ $fmtBlank($hk > 0 ? (float) ($totals['TotalOutput'] ?? 0.0) / $hk : 0.0) }}</td>
                        <td class="number"></td>
                        <td class="number"></td>
                        <td class="number"></td>
                        <td class="number"></td>
                        <td class="number"></td>
                    </tr>
                @endif
                @if ($loop->last && $machines !== [])
                    <tr class="grand-total-row">
                        <td colspan="2" class="center">Grand Total</td>
                        <td class="number">{{ $fmtBlank($grandTotals['BJ']) }}</td>
                        <td class="number">{{ $fmtBlank($grandTotals['Moulding']) }}</td>
                        <td class="number">{{ $fmtBlank($grandTotals['Sanding']) }}</td>
                        <td class="number">{{ $fmtBlank($grandTotals['TotalInput']) }}</td>
                        <td class="number">{{ $fmtBlank($grandTotals['OutputPacking']) }}</td>
                        <td class="number">{{ $fmtBlank($grandTotals['OutputReproses']) }}</td>
                        <td class="number">{{ $fmtBlank($grandTotals['TotalOutput']) }}</td>
                        <td class="number">{{ $fmtIntBlank((int) round($grandTotals['Jam'])) }}</td>
                        <td class="number">{{ $fmtIntBlank((int) round($grandTotals['Org'])) }}</td>
                        <td class="number">{{ $fmtRatioBlank($grandTotals['M3Jam']) }}</td>
                        <td class="number">{{ $fmtRatioBlank($grandTotals['M3JamOrg']) }}</td>
                        <td class="number">{{ $fmtPercentBlank($grandTotals['Rend']) }}</td>
                    </tr>
                @endif
            </tbody>
        </table>
    @endforeach

</body>

</html>
