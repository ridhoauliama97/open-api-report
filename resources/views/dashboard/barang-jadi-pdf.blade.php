<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif:ital,wght@0,100..900;1,100..900&display=swap"
        rel="stylesheet">
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
    </style>
</head>

<body>
    @php
        $reportData = is_array($reportData ?? null) ? $reportData : [];
        $columns = is_array($reportData['columns'] ?? null) ? $reportData['columns'] : [];
        $rows = is_array($reportData['rows'] ?? null) ? $reportData['rows'] : [];
        $sAkhirByColumn = is_array($reportData['s_akhir_by_column'] ?? null) ? $reportData['s_akhir_by_column'] : [];
        $percentByColumn = is_array($reportData['percent_by_column'] ?? null) ? $reportData['percent_by_column'] : [];
        $ctrByColumn = is_array($reportData['ctr_by_column'] ?? null) ? $reportData['ctr_by_column'] : [];
        $totals = is_array($reportData['totals'] ?? null) ? $reportData['totals'] : ['s_akhir' => 0, 'ctr' => 0];

        $startText = \Carbon\Carbon::parse($startDate)->locale('id')->translatedFormat('d-M-y');
        $endText = \Carbon\Carbon::parse($endDate)->locale('id')->translatedFormat('d-M-y');
        $generatedByName = $generatedBy?->name ?? 'sistem';
        $generatedAtText = $generatedAt->copy()->locale('id')->translatedFormat('d-M-y H:i');

        $fmt1 = static fn($v): string => number_format((float) ($v ?? 0), 1, '.', ',');
        $fmt2 = static fn($v): string => number_format((float) ($v ?? 0), 2, '.', ',');
        $fmtPct = static fn($v): string => number_format((float) ($v ?? 0), 2, '.', ',') . '%';
    @endphp

    <h1 class="report-title">Laporan Dashboard Barang Jadi</h1>
    <p class="report-subtitle">Dari {{ $startText }} s/d {{ $endText }}</p>

    <table class="report-table">
        <thead>
            <tr class="headers-row">
                <th rowspan="2" style="width: 58px;">Tanggal</th>
                @foreach ($columns as $column)
                    <th colspan="2">{{ $column }}</th>
                @endforeach
            </tr>
            <tr class="headers-row">
                @foreach ($columns as $column)
                    <th>Masuk</th>
                    <th>Keluar</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr class="data-row {{ $loop->odd ? 'row-odd' : 'row-even' }}">
                    <td class="data-cell label" style="text-align: center;">
                        {{ \Carbon\Carbon::parse((string) ($row['date'] ?? now()))->locale('id')->translatedFormat('d-M-y') }}
                    </td>
                    @foreach ($columns as $column)
                        @php
                            $inflow = (float) ($row['cells'][$column]['in'] ?? 0 ?: 0);
                            $outflow = (float) ($row['cells'][$column]['out'] ?? 0 ?: 0);
                        @endphp
                        <td class="data-cell number">{{ abs($inflow) < 0.000001 ? '' : $fmt1($inflow) }}</td>
                        <td class="data-cell number">{{ abs($outflow) < 0.000001 ? '' : $fmt1($outflow) }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ 1 + count($columns) * 2 }}" style="text-align: center;">Data tidak tersedia.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="totals-row">
                <td class="label">S Akhir</td>
                @foreach ($columns as $column)
                    <td class="number">{{ $fmt2($sAkhirByColumn[$column] ?? 0) }}</td>
                    <td class="number">{{ $fmtPct($percentByColumn[$column] ?? 0) }}</td>
                @endforeach
            </tr>
            <tr class="totals-row">
                <td class="label"># Ctr</td>
                @foreach ($columns as $column)
                    <td class="number" colspan="2" style="text-align: center;">
                        {{ $fmt2($ctrByColumn[$column] ?? 0) }}</td>
                @endforeach
            </tr>
        </tfoot>
    </table>

    <p class="section-title">Total</p>
    <table class="summary-table" style="width: 230px;">
        <tr class="totals-row">
            <td class="label" style="width: 90px;">S Akhir</td>
            <td class="number" style="width: 70px;">{{ $fmt2($totals['s_akhir'] ?? 0) }}</td>
        </tr>
        <tr class="totals-row">
            <td class="label"># Ctr</td>
            <td class="number">{{ $fmt2($totals['ctr'] ?? 0) }}</td>
        </tr>
    </table>
</body>

</html>
