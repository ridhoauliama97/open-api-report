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
            margin: 24mm 10mm 20mm 10mm;
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

        .section-title {
            margin: 10px 0 4px 0;
            font-size: 12px;
            font-weight: bold;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
            page-break-inside: auto;
            table-layout: fixed;
        }

        .report-table {
            border-collapse: collapse;
            border-spacing: 0;
            border: 1px solid #000;
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
            border: 1px solid #000;
            padding: 2px 4px;
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

        .headers-row th {
            font-weight: bold;
            font-size: 11px;
            border-top: 0;
            border-bottom: 1px solid #000;
        }

        .report-table tbody tr.data-row td.data-cell {
            border-top: 0 !important;
            border-bottom: 0 !important;
            border-left: 1px solid #000 !important;
            border-right: 1px solid #000 !important;
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

        .totals-row td {
            font-weight: bold;
            font-size: 11px;
            border: 1px solid #000;
        }

        .totals-row td.blank {
            text-align: center;
        }

        .summary-block {
            margin-top: 8px;
        }

        .summary-list {
            margin: 4px 0 0 18px;
            padding: 0;
        }

        .summary-list li {
            margin: 0 0 2px 0;
        }

        @include('reports.partials.pdf-footer-table-style')
    </style>
</head>

<body>
    @php
        $reportData = is_array($reportData ?? null) ? $reportData : [];
        $groups = is_array($reportData['groups'] ?? null) ? $reportData['groups'] : [];
        $summary = is_array($reportData['summary'] ?? null) ? $reportData['summary'] : [];
        $fmtPcs = static fn($v): string => is_numeric($v) && (int) round((float) $v) !== 0
            ? number_format((int) round((float) $v), 0, '.', ',')
            : '';
        $fmtM3 = static fn($v): string => is_numeric($v) && abs((float) $v) >= 0.0000001
            ? number_format((float) $v, 4, '.', ',')
            : '';
    @endphp

    <h1 class="report-title">Laporan Saldo Barang Jadi Hidup Per-Jenis Per-Produk</h1>
    <p class="report-subtitle"></p>

    @forelse ($groups as $jenisIndex => $jenisGroup)
        <div class="section-title">{{ $jenisGroup['name'] ?? 'LAINNYA' }}</div>
        @foreach ($jenisGroup['products'] ?? [] as $productGroup)
            <div style="font-weight:bold; margin: 4px 0 2px 0;">Produk : {{ $productGroup['name'] ?? '-' }}</div>
            <table class="report-table">
                <thead>
                    <tr class="headers-row">
                        <th style="width:32px;">No</th>
                        <th style="width:56px;">Tebal</th>
                        <th style="width:56px;">Lebar</th>
                        <th style="width:72px;">Panjang</th>
                        <th style="width:80px;">Pcs</th>
                        <th style="width:84px;">M3</th>
                    </tr>
                </thead>
                <tfoot>
                    <tr class="table-end-line">
                        <td colspan="6"></td>
                    </tr>
                </tfoot>
                <tbody>
                    @foreach ($productGroup['rows'] ?? [] as $row)
                        <tr class="data-row {{ $loop->odd ? 'row-odd' : 'row-even' }}">
                            <td class="center data-cell">{{ $loop->iteration }}</td>
                            <td class="number data-cell">{{ $fmtPcs($row['Tebal'] ?? null) }}</td>
                            <td class="number data-cell">{{ $fmtPcs($row['Lebar'] ?? null) }}</td>
                            <td class="number data-cell">{{ $fmtPcs($row['Panjang'] ?? null) }}</td>
                            <td class="number data-cell">{{ $fmtPcs($row['Pcs'] ?? null) }}</td>
                            <td class="number data-cell">{{ $fmtM3($row['M3'] ?? null) }}</td>
                        </tr>
                    @endforeach
                    <tr class="totals-row">
                        <td colspan="4" class="blank">Total {{ $productGroup['name'] ?? '-' }}</td>
                        <td class="number">{{ $fmtPcs($productGroup['total_pcs'] ?? null) }}</td>
                        <td class="number">{{ $fmtM3($productGroup['total_m3'] ?? null) }}</td>
                    </tr>
                </tbody>
            </table>
        @endforeach
        <table class="report-table">
            <tfoot>
                <tr class="table-end-line">
                    <td colspan="2"></td>
                </tr>
            </tfoot>
            <tbody>
                <tr class="totals-row">
                    <td class="blank">Total (M3) Per-Jenis {{ $jenisGroup['name'] ?? 'LAINNYA' }}</td>
                    <td class="number"> {{ $fmtM3($jenisGroup['total_m3'] ?? null) }}</td>
                </tr>
            </tbody>
        </table>
    @empty
        <table class="report-table">
            <tbody>
                <tr>
                    <td class="center">Tidak ada data.</td>
                </tr>
            </tbody>
        </table>
    @endforelse

    @if (($summary['total_rows'] ?? 0) > 0)
        <div class="summary-block">
            <div class="section-title">Grand Total</div>
            <ul class="summary-list">
                <li>Total Jenis:
                    <strong> {{ number_format((int) ($summary['total_jenis'] ?? 0), 0, '.', ',') }} Jenis </strong>
                </li>
                <li>Total Produk:
                    <strong>{{ number_format((int) ($summary['total_produk'] ?? 0), 0, '.', ',') }} Produk </strong>
                </li>
                <li>Total Pcs:
                    <strong>{{ number_format((int) ($summary['total_pcs'] ?? 0), 0, '.', ',') }} Pcs </strong>
                </li>
                <li>Total M3:
                    <strong>{{ number_format((float) ($summary['total_m3'] ?? 0), 4, '.', ',') }} M3 </strong>
                </li>
            </ul>
        </div>
    @endif

    @include('reports.partials.pdf-footer-table')
</body>

</html>
