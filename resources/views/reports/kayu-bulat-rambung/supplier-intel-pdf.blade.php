<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif:ital,wght@0,100..900;1,100..900&display=swap"
        rel="stylesheet">
    <style>
        body {
            font-family: "Noto Serif", serif;
            font-size: 10px;
            margin: 0;
            padding: 0;
        }
        .report-companyTitle {
            font-size: 18px;
            font-weight: bold;
            text-align: center;
            margin: 0 0 4px;
        }
        .report-title {
            font-size: 16px;
            font-weight: bold;
            text-align: center;
            margin: 0;
        }
        .report-subtitle {
            font-size: 12px;
            color: #636466;
            text-align: center;
            margin: 2px 0 20px;
        }
        .data-table {
            border: 1px solid #000;
            border-collapse: collapse;
            width: calc(100% - 2px);
            table-layout: fixed;
        }
        .data-table th,
        .data-table td {
            border: 1px solid #000;            padding: 1px 2px;
            vertical-align: top;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
        .data-table th {
            background-color: #eef2f8;
            font-weight: bold;
        }
        .section-header td {
            font-weight: bold;
            font-style: italic;
            color: #9c111d;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding-left: 4px;
        }
        .sub-section-header td {
            font-weight: bold;
            color: #9c111d;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding-left: 4px;
        }
        .item-row td {
            padding-left: 4px;
        }
        .row-odd td {
            background-color: #c9d1df;
        }
        .row-even td {
            background-color: #eef2f8;
        }
        .subtotal-row td {
            font-weight: bold;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
        }
        .grand-total-row td {
            font-weight: bold;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
        }
        .empty-row td {
            font-style: italic;
            font-weight: bold;
            color: #9c111d;
            background-color: #c9d1df;
        }
        .number {
            text-align: right;
        }
        .nowrap {
            white-space: nowrap;
        }
        .number-negative {
            color: #9c111d;
        }
    
        /* standardized table borders */
        .report-table {
            border: 1px solid #000;
            border-collapse: collapse;
            width: calc(100% - 2px);
            line-height: inherit;
        }
.report-table th, .report-table td {
            border: 1px solid #000;
            word-wrap: break-word;
            padding: 2px;
            vertical-align: top;
        }

        .report-table th { font-weight: bold; background-color: #eef2f8; }
    </style>
</head>

<body>
    @php
        $rowsData =
            isset($rows) && is_iterable($rows) ? (is_array($rows) ? $rows : collect($rows)->values()->all()) : [];
        $columns = array_keys($rowsData[0] ?? []);
        if ($columns === []) {
            $expectedColumns = config('reports.supplier_intel.expected_columns', []);
            $columns = is_array($expectedColumns) ? array_values(array_filter($expectedColumns, 'is_string')) : [];
        }
        if ($columns === []) {
            // Keep table headers visible even when SP returns no rows and expected_columns is empty.
            $columns = ['Data'];
        }
        $columnLabels = [
            'NamaSupplier' => 'Nama Supplier',
            'DateIn' => 'Tanggal Masuk',
            'JlhTruk' => 'Jumlah Truk',
            'TonKB' => 'Ton (KB)',
            'M3ST' => 'M3 (ST)',
        ];
        $visibleColumnCount = max(count($columns), 1);
        $hasRange = !empty($startDate) && !empty($endDate);
        $start = $hasRange ? \Carbon\Carbon::parse($startDate)->locale('id')->translatedFormat('d-M-y') : '';
        $end = $hasRange ? \Carbon\Carbon::parse($endDate)->locale('id')->translatedFormat('d-M-y') : '';
        $formatDate = static function (mixed $value): string {
            $raw = trim((string) $value);

            if ($raw === '') {
                return '';
            }

            try {
                return \Illuminate\Support\Carbon::parse($raw)->format('d-M-y');
            } catch (\Throwable $e) {
                return $raw;
            }
        };
        $formatFourDecimals = static function (mixed $value): string {
            if ($value === null || $value === '') {
                return '';
            }

            return number_format((float) $value, 4, '.', ',');
        };
        $generatedByName = $generatedBy?->name ?? 'sistem';
        $generatedAtText = $generatedAt->copy()->locale('id')->translatedFormat('d-M-y H:i');
    @endphp

    <h1 class="report-title">Laporan Supplier Intel</h1>
    <p class="report-subtitle">
        @if ($hasRange)
            Periode {{ $start }} s/d {{ $end }}
        @else
            Data Supplier Intel
        @endif
    </p>

    <table class="report-table">
        <thead>
            <tr class="headers-row">
                <th style="width: 34px;">No</th>
                @foreach ($columns as $column)
                    <th>{{ $columnLabels[$column] ?? $column }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($rowsData as $row)
                <tr class="data-row {{ $loop->odd ? 'row-odd' : 'row-even' }}">
                    <td class="data-cell center">{{ $loop->iteration }}</td>
                    @foreach ($columns as $column)
                        @php
                            $cellValue = $row[$column] ?? '';
                            $cellStyle = '';
                            $cellClass = 'data-cell';

                            if ($column === 'DateIn') {
                                $cellValue = $formatDate($cellValue);
                                $cellStyle = 'text-align:center;';
                            } elseif ($column === 'JlhTruk') {
                                $cellStyle =
                                    'text-align:center;font-family:\"Calibry\", \"Calibri\", \"DejaVu Sans\", sans-serif;';
                            } elseif (in_array($column, ['TonKB', 'M3ST'], true)) {
                                $cellValue = $formatFourDecimals($cellValue);
                                $cellStyle = 'font-weight:bold;';
                                $cellClass .= ' number';
                            }
                        @endphp
                        <td class="{{ $cellClass }}" @if ($cellStyle !== '') style="{{ $cellStyle }}" @endif>{{ $cellValue }}
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr class="empty-row">
                    <td colspan="{{ $visibleColumnCount + 1 }}" class="center">Tidak ada data.</td>
                </tr>
            @endforelse
        </tbody>
        @if (count($rowsData) > 0)
        @endif
    </table>

    </body>

</html>
