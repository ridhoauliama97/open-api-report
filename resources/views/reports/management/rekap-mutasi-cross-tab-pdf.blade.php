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
            margin: 12mm 8mm 14mm 8mm;
            footer: html_reportFooter;
        }

        body {
            margin: 0;
            font-family: "Noto Serif", serif;
            font-size: 10px;
            line-height: 1.15;
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
        }

        .report-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #000;
            page-break-inside: auto;
            margin-bottom: 8px;
        }

        thead {
            display: table-header-group;
        }

        tfoot {
            display: table-footer-group;
        }

        tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }

        th,
        td {
            border: 0;
            border-left: 1px solid #000;
            padding: 2px 4px;
            vertical-align: middle;
        }

        th:first-child,
        td:first-child {
            border-left: 0;
        }

        th {
            text-align: center;
            font-weight: bold;
            border-bottom: 1px solid #000;
        }

        tbody td {
            border-top: 0;
            border-bottom: 0;
        }

        .row-odd td {
            background: #c9d1df;
        }

        .row-even td {
            background: #eef2f8;
        }

        .center {
            text-align: center;
        }

        .number {
            text-align: right;
            white-space: nowrap;
            font-family: "Calibri", "DejaVu Sans", sans-serif;
        }

        .table-end-line td {
            border-top: 1px solid #000 !important;
            border-right: 0 !important;
            border-bottom: 0 !important;
            border-left: 0 !important;
            padding: 0 !important;
            height: 0 !important;
            line-height: 0 !important;
            background: #fff !important;
        }

        .empty-state {
            text-align: center;
            padding: 12px;
            font-style: italic;
        }

        .stats-table {
            margin-top: 12px;
        }

        @include('reports.partials.pdf-footer-table-style')
    </style>
</head>

<body>
    @php
        $data = is_array($reportData ?? null) ? $reportData : [];
        $rows = is_array($data['rows'] ?? null) ? $data['rows'] : [];
        $statsRows = is_array($data['stats_rows'] ?? null) ? $data['stats_rows'] : [];
        $displayColumns = is_array($data['summary']['display_columns'] ?? null)
            ? $data['summary']['display_columns']
            : [];
        $start = \Carbon\Carbon::parse($startDate)->locale('id')->translatedFormat('d-M-y');
        $end = \Carbon\Carbon::parse($endDate)->locale('id')->translatedFormat('d-M-y');
        $fmt = static fn($value): string => $value === null ? '' : number_format((float) $value, 4, '.', ',');
        $fmtKg = static fn($value): string => $value === null ? '' : number_format((float) $value, 0, '.', ',');
        $isKgColumn = static fn(string $key): bool => $key === 'KBKG';
    @endphp

    <h1 class="report-title">Laporan Rekap Mutasi (Cross Tab)</h1>
    <div class="report-subtitle">Periode {{ $start }} s/d {{ $end }}</div>

    <table class="report-table">
        <thead>
            <tr>
                <th style="width: 42px;">Tanggal</th>
                @foreach ($displayColumns as $label)
                    <th>{{ $label }}</th>
                @endforeach
            </tr>
        </thead>
        <tfoot>
            <tr class="table-end-line">
                <td colspan="{{ count($displayColumns) + 1 }}"></td>
            </tr>
        </tfoot>
        <tbody>
            @forelse ($rows as $index => $row)
                <tr class="{{ ($index + 1) % 2 === 1 ? 'row-odd' : 'row-even' }}">
                    <td class="center">{{ $row['day'] ?? '' }}</td>
                    @foreach ($displayColumns as $key => $label)
                        <td class="number">
                            {{ $isKgColumn($key) ? $fmtKg($row['metrics'][$key] ?? null) : $fmt($row['metrics'][$key] ?? null) }}
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($displayColumns) + 1 }}" class="empty-state">Tidak ada data untuk periode
                        ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if ($statsRows !== [])
        <table class="report-table stats-table">
            <thead>
                <tr>
                    <th style="width: 42px;"></th>
                    @foreach ($displayColumns as $label)
                        <th>{{ $label }}</th>
                    @endforeach
                </tr>
            </thead>
            <tfoot>
                <tr class="table-end-line">
                    <td colspan="{{ count($displayColumns) + 1 }}"></td>
                </tr>
            </tfoot>
            <tbody>
                @foreach ($statsRows as $index => $row)
                    <tr class="{{ ($index + 1) % 2 === 1 ? 'row-odd' : 'row-even' }}">
                        <td class="center"><strong>{{ $row['label'] ?? '' }}</strong></td>
                        @foreach ($displayColumns as $key => $label)
                            <td class="number">
                                {{ $isKgColumn($key) ? $fmtKg($row['metrics'][$key] ?? null) : $fmt($row['metrics'][$key] ?? null) }}
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @include('reports.partials.pdf-footer-table')
</body>

</html>
