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
            font-size: 12px;
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

        $generatedByName = $generatedBy?->name ?? 'sistem';
        $generatedAtText = $generatedAt->copy()->locale('id')->translatedFormat('d-M-y H:i');

        $start = \Carbon\Carbon::parse((string) ($data['start_date'] ?? ''))->locale('id')->translatedFormat('d-M-y');
        $end = \Carbon\Carbon::parse((string) ($data['end_date'] ?? ''))->locale('id')->translatedFormat('d-M-y');

        $eps = 0.0000001;
        $fmt = static fn(?float $v): string => $v === null ? '' : number_format($v, 4, '.', '');
        $fmtTotal = static fn(?float $v) => $v === null || abs($v) < $eps ? '' : number_format($v, 4, '.', '');
        $fmtPercent = static fn(?float $v): string => $v === null || !is_finite($v)
            ? ''
            : number_format($v, 2, '.', '') . '%';

        $grandTotals = [
            'ST' => 0.0,
            'S4S' => 0.0,
            'WIP' => 0.0,
            'Reproses' => 0.0,
            'Output' => 0.0,
        ];
        foreach ($groups as $g) {
            $t = is_array($g['totals'] ?? null) ? $g['totals'] : [];
            $grandTotals['ST'] += (float) ($t['ST'] ?? 0.0);
            $grandTotals['S4S'] += (float) ($t['S4S'] ?? 0.0);
            $grandTotals['WIP'] += (float) ($t['WIP'] ?? 0.0);
            $grandTotals['Reproses'] += (float) ($t['Reproses'] ?? 0.0);
            $grandTotals['Output'] += (float) ($t['Output'] ?? 0.0);
        }
    @endphp

    <h1 class="report-title">Laporan Rekap Produksi S4S Per-Jenis & Per-Grade (m3)</h1>
    <p class="report-subtitle">Periode {{ $start }} s/d {{ $end }}</p>

    @foreach ($groups as $group)
        @php
            $jenis = (string) ($group['jenis'] ?? '');
            $rows = is_array($group['rows'] ?? null) ? $group['rows'] : [];
            $totals = is_array($group['totals'] ?? null) ? $group['totals'] : [];
            $outputTotal = (float) ($totals['Output'] ?? 0.0);
        @endphp

        <div class="group-title">{{ $jenis }}</div>

        <table style="margin-bottom: 12px;">
            <thead>
                <tr>
                    <th style="width: 4%;">No</th>
                    <th style="width: 15%;">Jenis Kayu</th>
                    <th style="width: 15%;">Nama Grade</th>
                    <th style="width: 12%;">In ST</th>
                    <th style="width: 12%;">In S4S</th>
                    <th style="width: 12%;">In WIP</th>
                    <th style="width: 12%;">In Reproses</th>
                    <th style="width: 12%;">Output</th>
                    <th style="width: 12%;">Ratio %</th>
                </tr>
            </thead>
            <tbody>
                @php $i = 0; @endphp
                @foreach ($rows as $row)
                    @php
                        $i++;
                        $row = is_array($row) ? $row : (array) $row;
                        $cls = $i % 2 === 1 ? 'row-odd' : 'row-even';

                        $st = $row['ST'] ?? null;
                        $s4s = $row['S4S'] ?? null;
                        $wip = $row['WIP'] ?? null;
                        $repr = $row['Reproses'] ?? null;
                        $out = $row['Output'] ?? null;

                        $ratio =
                            $out !== null && abs($outputTotal) > $eps ? ((float) $out / $outputTotal) * 100.0 : null;
                    @endphp
                    <tr class="{{ $cls }}">
                        <td class="center">{{ $i }}</td>
                        <td class="center">{{ (string) ($row['Jenis'] ?? '') }}</td>
                        <td>{{ (string) ($row['NamaGrade'] ?? '') }}</td>
                        <td class="number">{{ $fmt($st) }}</td>
                        <td class="number">{{ $fmt($s4s) }}</td>
                        <td class="number">{{ $fmt($wip) }}</td>
                        <td class="number">{{ $fmt($repr) }}</td>
                        <td class="number" style="font-weight: bold;">{{ $fmt($out) }}</td>
                        <td class="number" style="font-weight: bold;">{{ $fmtPercent($ratio) }}</td>
                    </tr>
                @endforeach

                <tr class="totals-row">
                    <td colspan="3" class="center">Total </td>
                    <td class="number">{{ $fmtTotal($totals['ST'] ?? null) }}</td>
                    <td class="number">{{ $fmtTotal($totals['S4S'] ?? null) }}</td>
                    <td class="number">{{ $fmtTotal($totals['WIP'] ?? null) }}</td>
                    <td class="number">{{ $fmtTotal($totals['Reproses'] ?? null) }}</td>
                    <td class="number">{{ $fmtTotal($totals['Output'] ?? null) }}</td>
                    <td></td>
                </tr>
            </tbody>
        </table>
    @endforeach

    @if ($groups !== [])
        <div class="group-title">Grand Total</div>
        <ul style="margin: 4px 0 14px 0; padding-left: 14px; font-size: 11px; list-style: none;">
            <li style="display: flex; justify-content: space-between; gap: 10px; padding: 2px 0;">
                <strong>In ST :
                    <span
                        style="min-width: 120px; text-align: right; white-space: nowrap; font-family: Calibri, 'DejaVu Sans', sans-serif;">
                        {{ $fmtTotal($grandTotals['ST']) }}
                </strong>
                </span>
            </li>
            <li style="display: flex; justify-content: space-between; gap: 10px; padding: 2px 0;">
                <strong>In S4S :
                    <span
                        style="min-width: 120px; text-align: right; white-space: nowrap; font-family: Calibri, 'DejaVu Sans', sans-serif;">
                        {{ $fmtTotal($grandTotals['S4S']) }}
                    </span>
                </strong>
            </li>
            <li style="display: flex; justify-content: space-between; gap: 10px; padding: 2px 0;">
                <strong>In WIP :
                    <span
                        style="min-width: 120px; text-align: right; white-space: nowrap; font-family: Calibri, 'DejaVu Sans', sans-serif;">
                        {{ $fmtTotal($grandTotals['WIP']) }}
                    </span>
                </strong>
            </li>
            <li style="display: flex; justify-content: space-between; gap: 10px; padding: 2px 0;">
                <strong>In Reproses :
                    <span
                        style="min-width: 120px; text-align: right; white-space: nowrap; font-family: Calibri, 'DejaVu Sans', sans-serif;">
                        {{ $fmtTotal($grandTotals['Reproses']) }}
                    </span>
                </strong>
            </li>
            <li style="display: flex; justify-content: space-between; gap: 10px; padding: 2px 0;">
                <strong>Output :
                    <span
                        style="min-width: 120px; text-align: right; white-space: nowrap; font-family: Calibri, 'DejaVu Sans', sans-serif;">
                        {{ $fmtTotal($grandTotals['Output']) }}
                    </span>
                </strong>
            </li>
        </ul>
    @endif
</body>

</html>
