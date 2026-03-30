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
        $grandTotals['Rend'] = abs($grandTotals['TotalInput']) > $eps
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
                    <th rowspan="2" style="width: 70px;">Tanggal</th>
                    <th rowspan="2" style="width: 34px;">Shift</th>
                    <th colspan="3">Input</th>
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
                    <th style="width: 40px;">BJ</th>
                    <th style="width: 56px;">Moulding</th>
                    <th style="width: 48px;">Sanding</th>
                    <th style="width: 58px;">Packing</th>
                    <th style="width: 58px;">Reproses</th>
                </tr>
            </thead>
            <tfoot>
                <tr class="table-end-line">
                    <td colspan="15"></td>
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
                        <td class="number">{{ $fmtBlank($row['BJ'] ?? null) }}</td>
                        <td class="number">{{ $fmtBlank($row['Moulding'] ?? null) }}</td>
                        <td class="number">{{ $fmtBlank($row['Sanding'] ?? null) }}</td>
                        <td class="number">{{ $fmtBlank($row['TotalInput'] ?? null) }}</td>
                        <td class="number">{{ $fmtBlank($row['OutputPacking'] ?? null) }}</td>
                        <td class="number">{{ $fmtBlank($row['OutputReproses'] ?? null) }}</td>
                        <td class="number">{{ $fmtBlank($row['TotalOutput'] ?? null) }}</td>
                        <td class="center">{{ $fmtIntBlank($row['Jam'] ?? null) }}</td>
                        <td class="center">{{ $fmtIntBlank($row['Org'] ?? null) }}</td>
                        <td class="number">{{ $fmtRatioBlank($row['M3Jam'] ?? null) }}</td>
                        <td class="number">{{ $fmtRatioBlank($row['M3JamOrg'] ?? null) }}</td>
                        <td class="number">{{ $fmtPercentBlank($row['Rend'] ?? null) }}</td>
                    </tr>
                @endforeach
                @if ($rows !== [] && $totals !== [])
                    <tr class="totals-row">
                        <td colspan="2" class="center">{{ $hk > 0 ? 'HK : ' . $hk : 'HK : -' }}</td>
                        <td class="number">{{ $fmtBlank($totals['BJ'] ?? null) }}</td>
                        <td class="number">{{ $fmtBlank($totals['Moulding'] ?? null) }}</td>
                        <td class="number">{{ $fmtBlank($totals['Sanding'] ?? null) }}</td>
                        <td class="number">{{ $fmtBlank($totals['TotalInput'] ?? null) }}</td>
                        <td class="number">{{ $fmtBlank($totals['OutputPacking'] ?? null) }}</td>
                        <td class="number">{{ $fmtBlank($totals['OutputReproses'] ?? null) }}</td>
                        <td class="number">{{ $fmtBlank($totals['TotalOutput'] ?? null) }}</td>
                        <td class="center">
                            {{ $fmtIntBlank(isset($totals['Jam']) ? (int) round((float) $totals['Jam']) : null) }}</td>
                        <td class="center">
                            {{ $fmtIntBlank(isset($totals['Org']) ? (int) round((float) $totals['Org']) : null) }}</td>
                        <td class="number">{{ $fmtRatioBlank($totals['M3Jam'] ?? null) }}</td>
                        <td class="number">{{ $fmtRatioBlank($totals['M3JamOrg'] ?? null) }}</td>
                        <td class="number">{{ $fmtPercentBlank($totals['Rend'] ?? null) }}</td>
                    </tr>
                    <tr class="totals-row">
                        <td colspan="2" class="center"><strong>Jmlh/HK</strong></td>
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
        <table style="margin-top: 10px;">
            <tfoot>
                <tr class="table-end-line">
                    <td colspan="13"></td>
                </tr>
            </tfoot>
            <tbody>
                <tr class="totals-row">
                    <td style="width: 130px;" class="center"><strong>Grand Total :</strong></td>
                    <td class="number">{{ $fmtBlank($grandTotals['BJ']) }}</td>
                    <td class="number">{{ $fmtBlank($grandTotals['Moulding']) }}</td>
                    <td class="number">{{ $fmtBlank($grandTotals['Sanding']) }}</td>
                    <td class="number">{{ $fmtBlank($grandTotals['TotalInput']) }}</td>
                    <td class="number">{{ $fmtBlank($grandTotals['OutputPacking']) }}</td>
                    <td class="number">{{ $fmtBlank($grandTotals['OutputReproses']) }}</td>
                    <td class="number">{{ $fmtBlank($grandTotals['TotalOutput']) }}</td>
                    <td class="center">{{ $fmtIntBlank((int) round($grandTotals['Jam'])) }}</td>
                    <td class="center">{{ $fmtIntBlank((int) round($grandTotals['Org'])) }}</td>
                    <td class="number">{{ $fmtRatioBlank($grandTotals['M3Jam']) }}</td>
                    <td class="number">{{ $fmtRatioBlank($grandTotals['M3JamOrg']) }}</td>
                    <td class="number">{{ $fmtPercentBlank($grandTotals['Rend']) }}</td>
                </tr>
            </tbody>
        </table>
    @endif

    @include('reports.partials.pdf-footer-table')
</body>

</html>
