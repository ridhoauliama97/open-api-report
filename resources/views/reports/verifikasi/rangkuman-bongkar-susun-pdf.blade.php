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
            margin: 14mm 8mm 14mm 8mm;
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

        .section-title {
            margin: 10px 0 4px 0;
            font-size: 11px;
            font-weight: bold;
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

        .summary-table {
            width: 70%;
            margin-top: 0px;
            margin-bottom: 12px;
        }

        .total-row td {
            font-weight: bold;
            border-top: 1px solid #000 !important;
            background: #fff !important;
        }

        .empty-state {
            text-align: center;
            padding: 12px;
            font-style: italic;
        }

        @include('reports.partials.pdf-footer-table-style')
    </style>
</head>

<body>
    @php
        $data = is_array($reportData ?? null) ? $reportData : [];
        $categories = is_array($data['categories'] ?? null) ? $data['categories'] : [];
        $summaryRows = is_array($data['summary_rows'] ?? null) ? $data['summary_rows'] : [];
        $grandTotals = is_array($data['grand_totals'] ?? null) ? $data['grand_totals'] : [];
        $date = \Carbon\Carbon::parse($reportDate)->locale('id')->translatedFormat('d-M-y');
        $fmt = static fn($value): string => $value === null ? '' : number_format((float) $value, 4, '.', ',');
    @endphp

    <h1 class="report-title">Laporan Rangkuman Bongkar Susun</h1>
    <div class="report-subtitle">Tanggal {{ $date }}</div>

    @forelse ($categories as $category)
        <div class="section-title">{{ $category['no'] ?? '' }}. {{ $category['name'] ?? '-' }}</div>
        <table class="report-table">
            <thead>
                <tr>
                    <th style="width: 36px;">No</th>
                    <th style="width: 110px;">No Bongkar Susun</th>
                    <th style="width: 110px;">Jenis</th>
                    <th style="width: 84px;">In</th>
                    <th style="width: 84px;">Out</th>
                    <th>Keterangan</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($category['rows'] ?? [] as $index => $row)
                    <tr class="{{ ($index + 1) % 2 === 1 ? 'row-odd' : 'row-even' }}">
                        <td class="center">{{ $index + 1 }}</td>
                        <td class="center">{{ ($row['NoBongkarSusun'] ?? '') !== '' ? $row['NoBongkarSusun'] : '-' }}
                        </td>
                        <td>{{ ($row['Jenis'] ?? '') !== '' ? $row['Jenis'] : '-' }}</td>
                        <td class="number">{{ $fmt($row['InA'] ?? null) }}</td>
                        <td class="number">{{ $fmt($row['OutA'] ?? null) }}</td>
                        <td>{{ ($row['Keterangan'] ?? '') !== '' ? $row['Keterangan'] : '-' }}</td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="3" class="center">Total {{ $category['name'] ?? '-' }}</td>
                    <td class="number">{{ $fmt($category['total_in'] ?? null) }}</td>
                    <td class="number">{{ $fmt($category['total_out'] ?? null) }}</td>
                    <td></td>
                </tr>
            </tbody>
        </table>
    @empty
        <table class="report-table">
            <tbody>
                <tr>
                    <td class="empty-state">Tidak ada data untuk tanggal ini.</td>
                </tr>
            </tbody>
        </table>
    @endforelse

    @if ($summaryRows !== [])
        <div class="section-title">Rangkuman</div>
        <table class="report-table summary-table">
            <thead>
                <tr>
                    <th>Kategori</th>
                    <th style="width: 84px;">Jumlah</th>
                    <th style="width: 96px;">Total In</th>
                    <th style="width: 96px;">Total Out</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($summaryRows as $index => $row)
                    <tr class="{{ ($index + 1) % 2 === 1 ? 'row-odd' : 'row-even' }}">
                        <td>{{ $row['Kategori'] ?? '-' }}</td>
                        <td class="number">{{ number_format((float) ($row['Jumlah'] ?? 0), 0, '.', ',') }}</td>
                        <td class="number">{{ $fmt($row['TotalIn'] ?? null) }}</td>
                        <td class="number">{{ $fmt($row['TotalOut'] ?? null) }}</td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td>Total</td>
                    <td class="number">{{ number_format((float) ($grandTotals['row_count'] ?? 0), 0, '.', ',') }}</td>
                    <td class="number">{{ $fmt($grandTotals['total_in'] ?? null) }}</td>
                    <td class="number">{{ $fmt($grandTotals['total_out'] ?? null) }}</td>
                </tr>
            </tbody>
        </table>
    @endif

    @include('reports.partials.pdf-footer-table')
</body>

</html>
