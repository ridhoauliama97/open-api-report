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

        .total-row td,
        .grand-total-row td {
            font-weight: bold;
        }

        .empty-state,
        .empty-row td {
            text-align: center;
            font-style: italic;
            font-weight: bold;
            color: #9c111d;
            background: #c9d1df;
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

        $normalizeColumnName = static function (string $column): string {
            return strtolower(str_replace([' ', '_', '(', ')'], '', trim($column)));
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
                        @php
                            $normalizedColumn = $normalizeColumnName($column);
                            $isBoldColumn =
                                $isMasukColumn($column) ||
                                in_array($normalizedColumn, ['jlhbtg', 'jumlahbatang', 'jumlahbatangpcs'], true);
                            $cellStyle = $isBoldColumn ? 'font-weight: bold;' : '';
                        @endphp
                        @if (($numericColumns[$column] ?? false) === true)
                            <td class="data-cell number" style="{{ $cellStyle }}">
                                {{ $formatNumber($row[$column] ?? null, $column) }}
                            </td>
                        @else
                            <td class="data-cell" style="{{ $cellStyle }}">{{ (string) ($row[$column] ?? '') }}</td>
                        @endif
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $visibleColumnCount + 1 }}" class="empty-state">Tidak ada data.</td>
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
            </tfoot>
        @endif
    </table>
</body>

</html>
