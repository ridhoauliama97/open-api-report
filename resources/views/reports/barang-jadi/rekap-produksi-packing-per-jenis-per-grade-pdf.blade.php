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

        .group-title {
            margin: 10px 0 4px 0;
            font-size: 11px;
            font-weight: bold;
            font-style: italic;
            color: #9c111d;
        }
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
        $grandTotals = [
            'InMoulding' => 0.0,
            'InSanding' => 0.0,
            'InWIP' => 0.0,
            'InBarangJadi' => 0.0,
            'Output' => 0.0,
            'OutReproses' => 0.0,
        ];
        foreach ($groups as $g) {
            $t = is_array($g['totals'] ?? null) ? $g['totals'] : [];
            foreach (array_keys($grandTotals) as $key) {
                $grandTotals[$key] += (float) ($t[$key] ?? 0.0);
            }
        }
    @endphp

    <h1 class="report-title">Laporan Rekap Produksi Packing Per-Jenis &amp; Per-Grade (m3)</h1>
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
                    <th style="width: 28px;">No</th>
                    <th style="width: 100px;">Jenis Kayu</th>
                    <th>Nama Grade</th>
                    <th style="width: 78px;">In Moulding</th>
                    <th style="width: 74px;">In Sanding</th>
                    <th style="width: 62px;">In WIP</th>
                    <th style="width: 86px;">In Barang Jadi</th>
                    <th style="width: 68px;">Output</th>
                    <th style="width: 84px;">Out Reproses</th>
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
                        <td class="number">{{ $fmt($row['InMoulding'] ?? null) }}</td>
                        <td class="number">{{ $fmt($row['InSanding'] ?? null) }}</td>
                        <td class="number">{{ $fmt($row['InWIP'] ?? null) }}</td>
                        <td class="number">{{ $fmt($row['InBarangJadi'] ?? null) }}</td>
                        <td class="number">{{ $fmt($row['Output'] ?? null) }}</td>
                        <td class="number">{{ $fmt($row['OutReproses'] ?? null) }}</td>
                    </tr>
                @endforeach

                <tr class="totals-row">
                    <td colspan="3" class="center">Total</td>
                    <td class="number">{{ $fmt($totals['InMoulding'] ?? null) }}</td>
                    <td class="number">{{ $fmt($totals['InSanding'] ?? null) }}</td>
                    <td class="number">{{ $fmt($totals['InWIP'] ?? null) }}</td>
                    <td class="number">{{ $fmt($totals['InBarangJadi'] ?? null) }}</td>
                    <td class="number">{{ $fmt($totals['Output'] ?? null) }}</td>
                    <td class="number">{{ $fmt($totals['OutReproses'] ?? null) }}</td>
                </tr>
            </tbody>
        </table>
    @endforeach
</body>

</html>
