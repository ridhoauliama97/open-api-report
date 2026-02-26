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
            margin: 16mm 8mm 18mm 8mm;
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
            margin: 2px 0 20px 0;
            font-size: 12px;
            color: #636466;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
            page-break-inside: auto;
            table-layout: fixed;
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
            border: 1px solid #5f5f5f;
            padding: 2px 3px;
            vertical-align: middle;
        }

        th {
            text-align: center;
            font-weight: bold;
            font-size: 11px;
        }

        td.center {
            text-align: center;
        }

        td.number {
            text-align: right;
            white-space: nowrap;
            font-family: "Calibri", "DejaVu Sans", sans-serif;
        }

        .headers-row th {
            font-weight: bold;
            font-size: 11px;
            border: 1.5px solid #000;
        }

        .row-odd td {
            background: #c9d1df;
        }

        .row-even td {
            background: #eef2f8;
        }

        .totals-row td {
            font-weight: bold;
            font-size: 11px;
            border: 1.5px solid #000;
            background: #fff;
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
        $rows = is_array($reportData['rows'] ?? null) ? $reportData['rows'] : [];
        $columns = is_array($reportData['columns'] ?? null) ? $reportData['columns'] : [];
        $numericColumns = is_array($reportData['numeric_columns'] ?? null) ? $reportData['numeric_columns'] : [];
        $totals = is_array($reportData['totals'] ?? null) ? $reportData['totals'] : [];

        $end = \Carbon\Carbon::parse($endDate)->locale('id')->translatedFormat('d M Y');
        $generatedByName = $generatedBy?->name ?? 'sistem';
        $generatedAtText = $generatedAt->copy()->locale('id')->translatedFormat('d M Y H:i');
        $headerLabelMap = [
            'JlhBtg' => 'Jumlah Batang',
        ];

        $isMasukColumn = static function (string $column): bool {
            $normalized = strtolower(trim($column));

            return $normalized === 'masuk' || str_contains($normalized, 'masuk');
        };

        $masukColumns = [];
        $nonMasukColumns = [];
        foreach ($columns as $column) {
            if ($isMasukColumn($column)) {
                $masukColumns[] = $column;
                continue;
            }
            $nonMasukColumns[] = $column;
        }
        $columns = array_values(array_merge($nonMasukColumns, $masukColumns));
        $lastMasukColumn = $masukColumns !== [] ? end($masukColumns) : null;

        $formatNumber = static function (mixed $value, string $column) use ($isMasukColumn): string {
            $num = (float) ($value ?? 0);
            if (abs($num) < 0.0000001) {
                return '';
            }

            return $isMasukColumn($column) ? number_format($num, 4, '.', ',') : number_format($num, 0, '.', ',');
        };
    @endphp

    <h1 class="report-title">Laporan Hasil Output Racip Harian</h1>
    <p class="report-subtitle">Per Tanggal {{ $end }}</p>

    <table>
        <thead>
            <tr class="headers-row">
                <th style="width: 40px;">No</th>
                @foreach ($columns as $column)
                    <th>{{ $headerLabelMap[$column] ?? $column }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr class="{{ $loop->odd ? 'row-odd' : 'row-even' }}">
                    <td class="center">{{ $loop->iteration }}</td>
                    @foreach ($columns as $column)
                        @if (($numericColumns[$column] ?? false) === true)
                            <td class="number">{{ $formatNumber($row[$column] ?? null, $column) }}</td>
                        @else
                            <td>{{ (string) ($row[$column] ?? '') }}</td>
                        @endif
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($columns) + 1 }}" class="center">Tidak ada data.</td>
                </tr>
            @endforelse

            @if ($rows !== [] && $totals !== [])
                <tr class="totals-row">
                    <td colspan="{{ count($columns) }}" class="center">Total</td>
                    <td class="number">
                        {{ $lastMasukColumn !== null ? $formatNumber($totals[$lastMasukColumn] ?? null, $lastMasukColumn) : '' }}
                    </td>
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
