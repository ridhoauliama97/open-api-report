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
        }

        @page {
            margin: 18mm 10mm 18mm 10mm;
            footer: html_reportFooter;
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
            margin: 2px 0;
            font-size: 12px;
            color: #636466;
        }

        .report-meta {
            text-align: center;
            margin: 0 0 14px;
            font-size: 10px;
            color: #444;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
            page-break-inside: auto;
        }

        thead {
            display: table-header-group;
        }

        tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }

        th,
        td {
            border: 1px solid #9ca3af;
            padding: 3px 4px;
            vertical-align: middle;
        }

        th {
            text-align: center;
            font-weight: 700;
            background: #fff;
            color: #000;
        }

        td.center {
            text-align: center;
        }

        td.number {
            text-align: right;
            font-family: "Calibri", "DejaVu Sans", sans-serif;
            white-space: nowrap;
        }

        .row-odd td {
            background: #c9d1df;
        }

        .row-even td {
            background: #eef2f8;
        }

        .headers-row th {
            font-weight: bold;
            font-size: 11px;
            border: 1.5px solid #000;
        }

        .totals-row td {
            font-weight: bold;
            border: 1.5px solid #000;
            background: #f8f9fc;
        }

        .footer-wrap {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }

        .footer-left {
            font-size: 8px;
            font-style: italic;
        }

        .footer-right {
            font-size: 8px;
            font-style: italic;
            text-align: right;
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
        $generatedAtText = $generatedAt->copy()->locale('id')->translatedFormat('d M Y H:i');

        $columns = ['Jenis', 'Tebal', 'Lebar', 'Panjang', 'Period1', 'Period2', 'Period3', 'Period4', 'Period5'];

        $toFloat = static function ($value): ?float {
            if (is_numeric($value)) {
                return (float) $value;
            }
            return null;
        };
        $formatDecimal = static function (?float $value, int $decimals): string {
            return $value === null ? '' : number_format($value, $decimals, '.', '');
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
            'Period1' => "<= {$umur1} hari",
            'Period2' => $umur1 + 1 . " - {$umur2} hari",
            'Period3' => $umur2 + 1 . " - {$umur3} hari",
            'Period4' => $umur3 + 1 . " - {$umur4} hari",
            'Period5' => "> {$umur4} hari",
        ];
    @endphp

    <h1 class="report-title">Laporan Umur Sawn Timber Detail (Ton)</h1>
    <p class="report-subtitle"></p>
    <p class="report-meta">

    </p>

    <table>
        <thead>
            <tr class="headers-row">
                <th style="width: 4%;">No</th>
                <th style="width: 13%;">Jenis</th>
                <th style="width: 7%;">Tebal</th>
                <th style="width: 7%;">Lebar</th>
                <th style="width: 8%;">Panjang</th>
                <th style="width: 12%;">Periode - 1<br>{{ $periodLabels['Period1'] }}</th>
                <th style="width: 12%;">Periode - 2<br>{{ $periodLabels['Period2'] }}</th>
                <th style="width: 12%;">Periode - 3<br>{{ $periodLabels['Period3'] }}</th>
                <th style="width: 12%;">Periode - 4<br>{{ $periodLabels['Period4'] }}</th>
                <th style="width: 13%;">Periode - 5<br>{{ $periodLabels['Period5'] }}</th>
                <th style="width: 10%;">Total</th>
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
                <tr class="{{ $loop->odd ? 'row-odd' : 'row-even' }}">
                    <td class="center">{{ $loop->iteration }}</td>
                    <td class="center">{{ (string) ($row['Jenis'] ?? '') }}</td>
                    <td class="number">{{ $formatDecimal($toFloat($row['Tebal'] ?? null), 0) }}</td>
                    <td class="number">{{ $formatDecimal($toFloat($row['Lebar'] ?? null), 0) }}</td>
                    <td class="number">{{ $formatDecimal($toFloat($row['Panjang'] ?? null), 0) }}</td>
                    <td class="number">{{ $formatDecimal($toFloat($row['Period1'] ?? null), 4) }}</td>
                    <td class="number">{{ $formatDecimal($toFloat($row['Period2'] ?? null), 4) }}</td>
                    <td class="number">{{ $formatDecimal($toFloat($row['Period3'] ?? null), 4) }}</td>
                    <td class="number">{{ $formatDecimal($toFloat($row['Period4'] ?? null), 4) }}</td>
                    <td class="number">{{ $formatDecimal($toFloat($row['Period5'] ?? null), 4) }}</td>
                    <td class="number">{{ number_format($rowTotal, 4, '.', '') }}</td>
                </tr>
            @empty
                <tr>
                    <td class="center" colspan="11">Tidak ada data.</td>
                </tr>
            @endforelse
            @if (count($rowsData) > 0)
                <tr class="totals-row">
                    <td colspan="5" class="number">Total</td>
                    <td class="number">{{ number_format($totals['Period1'], 4, '.', '') }}</td>
                    <td class="number">{{ number_format($totals['Period2'], 4, '.', '') }}</td>
                    <td class="number">{{ number_format($totals['Period3'], 4, '.', '') }}</td>
                    <td class="number">{{ number_format($totals['Period4'], 4, '.', '') }}</td>
                    <td class="number">{{ number_format($totals['Period5'], 4, '.', '') }}</td>
                    <td class="number">{{ number_format($totals['RowTotal'], 4, '.', '') }}</td>
                </tr>
            @endif
        </tbody>
    </table>

    <htmlpagefooter name="reportFooter">
        <div class="footer-wrap">
            <div class="footer-left">Dicetak oleh: {{ $generatedByName }} pada {{ $generatedAtText }}</div>
            <div class="footer-right">Halaman {PAGENO} dari {nbpg}</div>
        </div>
    </htmlpagefooter>
    <sethtmlpagefooter name="reportFooter" value="on" />
</body>

</html>
