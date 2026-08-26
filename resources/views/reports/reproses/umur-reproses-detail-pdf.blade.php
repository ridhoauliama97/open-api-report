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

        .empty-row td {
            font-style: italic;
            font-weight: bold;
            color: #9c111d;
            background: #c9d1df;
        }
    </style>
</head>

<body>
    @php
        $data = is_array($reportData ?? null) ? $reportData : [];
        $rows = is_array($data['rows'] ?? null) ? $data['rows'] : [];
        $totals = is_array($data['totals'] ?? null) ? $data['totals'] : null;
        $eps = 0.0000001;
        $fmtDim = static fn(?float $v): string => $v === null || abs($v) < $eps ? '' : number_format($v, 0, '.', ',');
        $fmt = static fn(?float $v): string => $v === null || abs($v) < $eps ? '' : number_format($v, 4, '.', ',');
        $fmtTotalZeroBlank = static fn(float $v): string => abs($v) < $eps ? '' : number_format($v, 4, '.', ',');
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

    <h1 class="report-title">Laporan Umur Reproses Detail</h1>
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
                    <th>{{ $label }}</th>
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
                    <td class="number">{{ $fmt($row['Total'] ?? null) }}</td>
                </tr>
            @empty
                <tr class="empty-row">
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
</body>

</html>
