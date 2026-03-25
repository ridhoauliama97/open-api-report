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
            margin: 20mm 10mm 16mm 10mm;
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
            padding: 3px 4px;
            vertical-align: middle;
            word-break: break-word;
        }

        th {
            text-align: center;
            font-weight: bold;
            font-size: 11px;
        }

        td.center {
            text-align: center;
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
@include('reports.partials.pdf-footer-table-style')

        .totals-row td {
            font-weight: bold;
            font-size: 11px;
            border: 1px solid #000;
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
        <tfoot>
            <tr class="table-end-line">
                <td colspan="{{ $visibleColumnCount + 1 }}"></td>
            </tr>
        </tfoot>
        <tbody>
            @forelse ($rowsData as $row)
                <tr class="data-row {{ $loop->odd ? 'row-odd' : 'row-even' }}">
                    <td class="data-cell center">{{ $loop->iteration }}</td>
                    @foreach ($columns as $column)
                        <td class="data-cell">{{ (string) ($row[$column] ?? '') }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $visibleColumnCount + 1 }}" class="center">Tidak ada data.</td>
                </tr>
            @endforelse
        </tbody>
        @if (count($rowsData) > 0)
            <tfoot>
                <tr class="table-end-line">
                    <td colspan="{{ $visibleColumnCount + 1 }}"></td>
                </tr>
            </tfoot>
        @endif
    </table>

    @include('reports.partials.pdf-footer-table')
</body>

</html>
