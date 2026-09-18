<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif:ital,wght@0,100..900;1,100..900&display=swap"
        rel="stylesheet">
    @include('reports.partials.pdf-reference-style')
    <style>
        .data-table th, .data-table td {
            border: 1px solid #000;
        }
        .report-table th, .report-table td {
            border: 1px solid #000;
            word-wrap: break-word;
            padding: 2px;
            vertical-align: top;
        }
        .report-table th {
            font-weight: bold;
            background-color: #eef2f8;
        }
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
        $start = $hasRange ? \Carbon\Carbon::parse($startDate)->locale('id')->isoFormat('DD-MMM-YY') : '';
        $end = $hasRange ? \Carbon\Carbon::parse($endDate)->locale('id')->isoFormat('DD-MMM-YY') : '';
        $formatDate = static function (mixed $value): string {
            $raw = trim((string) $value);

            if ($raw === '') {
                return '';
            }

            try {
                return \Illuminate\Support\Carbon::parse($raw)->locale('id')->isoFormat('DD-MMM-YY');
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
        $generatedAtText = $generatedAt->copy()->locale('id')->isoFormat('DD-MMM-YY HH:mm');
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
                                    'text-align:center;';
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