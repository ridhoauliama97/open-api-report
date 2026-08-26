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
        $rowsData =
            isset($rows) && is_iterable($rows) ? (is_array($rows) ? $rows : collect($rows)->values()->all()) : [];
        $params = is_array($parameters ?? null) ? $parameters : [];

        $umur1 = (int) ($params['Umur1'] ?? 0);
        $umur2 = (int) ($params['Umur2'] ?? 0);
        $umur3 = (int) ($params['Umur3'] ?? 0);
        $umur4 = (int) ($params['Umur4'] ?? 0);

        $generatedByName = $generatedBy?->name ?? 'sistem';
        $generatedAtText = $generatedAt->copy()->locale('id')->translatedFormat('d-M-y H:i');

        $columns = ['Jenis', 'Tebal', 'Lebar', 'Panjang', 'Period1', 'Period2', 'Period3', 'Period4', 'Period5'];

        $toFloat = static function ($value): ?float {
            if (is_numeric($value)) {
                return (float) $value;
            }
            return null;
        };
        $formatDecimal = static function (?float $value, int $decimals): string {
            return $value === null ? '' : number_format($value, $decimals, '.', ',');
        };

        $totals = [
            'Period1' => 0.0,
            'Period2' => 0.0,
            'Period3' => 0.0,
            'Period4' => 0.0,
            'Period5' => 0.0,
            'RowTotal' => 0.0,
        ];

        foreach ($rowsData as $row) {
            $rowTotal = 0.0;
            foreach (array_keys($totals) as $periodColumn) {
                if ($periodColumn === 'RowTotal') {
                    continue;
                }
                $periodValue = $toFloat($row[$periodColumn] ?? null) ?? 0.0;
                $totals[$periodColumn] += $periodValue;
                $rowTotal += $periodValue;
            }
            $totals['RowTotal'] += $rowTotal;
        }

        $periodLabels = [
            'Period1' => "0 - {$umur1}",
            'Period2' => $umur1 + 1 . " - {$umur2}",
            'Period3' => $umur2 + 1 . " - {$umur3}",
            'Period4' => $umur3 + 1 . " - {$umur4}",
            'Period5' => "> {$umur4}",
        ];
    @endphp

    <h1 class="report-title">Laporan Umur Sawn Timber Detail (Ton)</h1>
    <p class="report-subtitle"></p>

    <table class="report-table">
        <thead>
            <tr class="headers-row">
                <th style="width: 5%;">No</th>
                <th>Jenis</th>
                <th>Tebal</th>
                <th>Lebar</th>
                <th>Panjang</th>
                <th>{{ $periodLabels['Period1'] }}</th>
                <th>{{ $periodLabels['Period2'] }}</th>
                <th>{{ $periodLabels['Period3'] }}</th>
                <th>{{ $periodLabels['Period4'] }}</th>
                <th>{{ $periodLabels['Period5'] }}</th>
                <th>Total</th>
            </tr>
        </thead>

        <tbody>
            @forelse ($rowsData as $row)
                @php
                    $rowPeriod1 = $toFloat($row['Period1'] ?? null) ?? 0.0;
                    $rowPeriod2 = $toFloat($row['Period2'] ?? null) ?? 0.0;
                    $rowPeriod3 = $toFloat($row['Period3'] ?? null) ?? 0.0;
                    $rowPeriod4 = $toFloat($row['Period4'] ?? null) ?? 0.0;
                    $rowPeriod5 = $toFloat($row['Period5'] ?? null) ?? 0.0;
                    $rowTotal = $rowPeriod1 + $rowPeriod2 + $rowPeriod3 + $rowPeriod4 + $rowPeriod5;
                @endphp
                <tr class="data-row {{ $loop->odd ? 'row-odd' : 'row-even' }}">
                    <td class="data-cell center">{{ $loop->iteration }}</td>
                    <td class="data-cell">{{ (string) ($row['Jenis'] ?? '') }}</td>
                    <td class="data-cell number">{{ $formatDecimal($toFloat($row['Tebal'] ?? null), 0) }}</td>
                    <td class="data-cell number">{{ $formatDecimal($toFloat($row['Lebar'] ?? null), 0) }}</td>
                    <td class="data-cell number">{{ $formatDecimal($toFloat($row['Panjang'] ?? null), 0) }}</td>
                    <td class="data-cell number">{{ $formatDecimal($toFloat($row['Period1'] ?? null), 4) }}</td>
                    <td class="data-cell number">{{ $formatDecimal($toFloat($row['Period2'] ?? null), 4) }}</td>
                    <td class="data-cell number">{{ $formatDecimal($toFloat($row['Period3'] ?? null), 4) }}</td>
                    <td class="data-cell number">{{ $formatDecimal($toFloat($row['Period4'] ?? null), 4) }}</td>
                    <td class="data-cell number">{{ $formatDecimal($toFloat($row['Period5'] ?? null), 4) }}</td>
                    <td class="data-cell number" style="font-weight: bold;">{{ number_format($rowTotal, 4, '.', ',') }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td class="center" colspan="11">Tidak ada data.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            @if (count($rowsData) > 0)
                <tr class="totals-row">
                    <td colspan="5" class="center">Total</td>
                    <td class="number">{{ number_format($totals['Period1'], 4, '.', ',') }}</td>
                    <td class="number">{{ number_format($totals['Period2'], 4, '.', ',') }}</td>
                    <td class="number">{{ number_format($totals['Period3'], 4, '.', ',') }}</td>
                    <td class="number">{{ number_format($totals['Period4'], 4, '.', ',') }}</td>
                    <td class="number">{{ number_format($totals['Period5'], 4, '.', ',') }}</td>
                    <td class="number">{{ number_format($totals['RowTotal'], 4, '.', ',') }}</td>
                </tr>
            @endif
        </tfoot>
    </table>
</body>

</html>
