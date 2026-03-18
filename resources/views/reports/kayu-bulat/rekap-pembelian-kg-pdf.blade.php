<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
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
            margin: 2px 0 20px 0;
            font-size: 12px;
            color: #636466;
        }

        .container-fluid {
            width: 100%;
            padding: 0;
            margin: 0;
        }

        .table-responsive {
            width: 100%;
            overflow-x: auto;
            margin-bottom: 6px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
            table-layout: fixed;
        }

        .report-table {
            border-collapse: separate;
            border-spacing: 0;
            border: 1px solid #000;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 2px 4px;
            vertical-align: middle;
        }

        th {
            text-align: center;
            font-weight: 700;
            background: #fff;
        }

        td.center {
            text-align: center;
        }

        td.number {
            text-align: right;
            font-family: "Calibri", "DejaVu Sans", sans-serif;
        }

        .headers-row th {
            font-weight: bold;
            font-size: 11px;
            border-top: 0;
            border-bottom: 1px solid #000;
        }

        .totals-row td {
            font-weight: bold;
            font-size: 11px;
        }

        .row-odd td {
            background: #c9d1df;
        }

        .row-even td {
            background: #eef2f8;
        }

        .report-table tbody tr.data-row td.data-cell {
            border-top: 0 !important;
            border-bottom: 0 !important;
            border-left: 1px solid #000 !important;
            border-right: 1px solid #000 !important;
        }

        /* Bold key columns (body): Tahun + Total */
        .report-table tbody tr.data-row td.data-cell:first-child,
        .report-table tbody tr.data-row td.data-cell:last-child {
            font-weight: bold;
        }

        .report-table tbody tr.data-row+tr.data-row td.data-cell {
            border-top: 0 !important;
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
@include('reports.partials.pdf-footer-table-style')
    </style>
</head>

<body>
    @php
        $data = is_array($reportData ?? null) ? $reportData : [];
        $yearRows = is_array($data['year_rows'] ?? null) ? $data['year_rows'] : [];
        $monthLabels = is_array($data['month_labels'] ?? null) ? $data['month_labels'] : [];
        $monthTotals = is_array($data['month_totals'] ?? null) ? $data['month_totals'] : [];
        $summary = is_array($data['summary'] ?? null) ? $data['summary'] : [];
        $fmt = static fn(float $value): string => abs($value) < 0.0000001 ? '' : number_format($value, 4, '.', ',');

        $startYear = (int) ($summary['start_year'] ?? 0);
        $endYear = (int) ($summary['end_year'] ?? 0);
        $dataCellStyle =
            'border-top:none;border-bottom:none;border-left:1px solid #000;border-right:1px solid #000;font-weight:bold;font-size:11px;';
        $dataCellMonthStyle =
            'border-top:none;border-bottom:none;border-left:1px solid #000;border-right:1px solid #000;';

        $generatedByName = $generatedBy?->name ?? 'sistem';
        $generatedAtText = $generatedAt->copy()->locale('id')->translatedFormat('d-M-y H:i');
    @endphp

    <h1 class="report-title">Laporan Rekap Pembelian Kayu Bulat (Ton) - Timbang KG</h1>
    <p class="report-subtitle">
        @if ($startYear > 0 && $endYear > 0)
            Periode {{ $startYear }} s/d {{ $endYear }}
        @endif
    </p>

    <div class="container-fluid">
        <div class="table-responsive">
            <table class="table table-striped report-table">
                <thead>
                    <tr class="headers-row">
                        <th style="width: 48px;">Tahun</th>
                        @foreach ($monthLabels as $label)
                            <th>{{ $label }}</th>
                        @endforeach
                        <th style="width: 74px;">Total</th>
                    </tr>
                </thead>
                <tfoot>
                    <tr class="table-end-line">
                        <td colspan="{{ 2 + count($monthLabels) }}"></td>
                    </tr>
                </tfoot>
                <tbody>
                    @forelse ($yearRows as $row)
                        <tr class="data-row {{ $loop->odd ? 'row-odd' : 'row-even' }}">
                            <td class="center data-cell" style="{{ $dataCellStyle }}">{{ $row['tahun'] ?? '' }}</td>
                            @foreach ($monthLabels as $month => $label)
                                <td class="number data-cell" style="{{ $dataCellMonthStyle }}">
                                    {{ $fmt((float) ($row['months'][$month] ?? 0.0)) }}</td>
                            @endforeach
                            <td class="number data-cell" style="{{ $dataCellStyle }}">
                                {{ $fmt((float) ($row['total'] ?? 0.0)) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ 2 + count($monthLabels) }}" class="center">Tidak ada data.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @include('reports.partials.pdf-footer-table')
</body>

</html>
