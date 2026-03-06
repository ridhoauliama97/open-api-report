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

        .report-table {
            border-collapse: separate;
            border-spacing: 0;
            border: 1px solid #000;
        }

        thead {
            display: table-header-group;
        }

        tfoot {
            display: table-row-group;
        }

        tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }

        th,
        td {
            border: 1px solid #000;
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
            border-top: 0;
            border-bottom: 1px solid #000;
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
            border: 1px solid #000;
            background: #fff;
        }

        .report-table tbody tr.data-row td.data-cell {
            border-top: 0 !important;
            border-bottom: 0 !important;
            border-left: 1px solid #000 !important;
            border-right: 1px solid #000 !important;
        }

        .table-end-line td {
            border: 0 !important;
            border-top: 1px solid #000 !important;
            padding: 0 !important;
            height: 0 !important;
            line-height: 0 !important;
            background: transparent !important;
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
        if ($columns === []) {
            $expectedColumns = config('reports.hasil_output_racip_harian.expected_columns', []);
            $columns = is_array($expectedColumns) ? array_values(array_filter($expectedColumns, 'is_string')) : [];
            if ($columns === []) {
                $columns = ['Jenis', 'Masuk', 'Tebal', 'Lebar', 'Panjang', 'JlhBtg'];
            }
        }
        $numericColumns = is_array($reportData['numeric_columns'] ?? null) ? $reportData['numeric_columns'] : [];
        $totals = is_array($reportData['totals'] ?? null) ? $reportData['totals'] : [];

        $end = \Carbon\Carbon::parse($endDate)->locale('id')->translatedFormat('d-M-y');
        $generatedByName = $generatedBy?->name ?? 'sistem';
        $generatedAtText = $generatedAt->copy()->locale('id')->translatedFormat('d-M-y H:i');
        $headerLabelMap = [
            'JlhBtg' => 'Jumlah Batang (pcs)',
            'Tebal' => 'Tebal (mm)',
            'Lebar' => 'Lebar (mm)',
            'Panjang' => 'Panjang (ft)',
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
        $visibleColumnCount = max(count($columns), 1);

        $formatNumber = static function (mixed $value, string $column) use ($isMasukColumn): string {
            $num = (float) ($value ?? 0);
            if (abs($num) < 0.0000001) {
                return '';
            }

            return $isMasukColumn($column) ? number_format($num, 4, '.', ',') : number_format($num, 0, '.', ',');
        };
    @endphp

    <h1 class="report-title">Laporan Hasil Output Racip Harian</h1>
    <p class="report-subtitle">Per Tanggal : {{ $end }}</p>

    <table class="report-table">
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
                <tr class="data-row {{ $loop->odd ? 'row-odd' : 'row-even' }}">
                    <td class="data-cell center">{{ $loop->iteration }}</td>
                    @foreach ($columns as $column)
                        @if (($numericColumns[$column] ?? false) === true)
                            <td class="data-cell number">{{ $formatNumber($row[$column] ?? null, $column) }}</td>
                        @else
                            <td class="data-cell">{{ (string) ($row[$column] ?? '') }}</td>
                        @endif
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $visibleColumnCount + 1 }}" class="center">Tidak ada data.</td>
                </tr>
            @endforelse
        </tbody>
        @if ($rows !== [] && $totals !== [])
            <tfoot>
                <tr class="totals-row">
                    <td colspan="{{ count($columns) }}" class="center">Total</td>
                    <td class="number">
                        {{ $lastMasukColumn !== null ? $formatNumber($totals[$lastMasukColumn] ?? null, $lastMasukColumn) : '' }}
                    </td>
                </tr>
                <tr class="table-end-line">
                    <td colspan="{{ $visibleColumnCount + 1 }}"></td>
                </tr>
            </tfoot>
        @endif
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
