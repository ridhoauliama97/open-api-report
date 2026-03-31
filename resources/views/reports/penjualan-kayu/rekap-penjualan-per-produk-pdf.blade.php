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

        .section-title {
            margin: 8px 0 4px 0;
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

        .product-table {
            width: 100%;
            margin-left: 18px;
            margin-bottom: 8px;
        }

        .product-total {
            margin: 4px 0 12px 0;
            font-weight: bold;
            font-size: 11px;
        }

        .summary-title {
            margin: 10px 0 4px 0;
            font-weight: bold;
            font-size: 11px;
        }

        .summary-table {
            width: 62%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .summary-table td {
            border: 0 !important;
            padding: 1px 4px;
        }

        .empty-state {
            text-align: center;
            padding: 12px;
            font-style: italic;
        }

        .total-row td {
            font-weight: bold;
            border-top: 1px solid #000 !important;
            background: #fff !important;
        }

        @include('reports.partials.pdf-footer-table-style')
    </style>
</head>

<body>
    @php
        $data = is_array($reportData ?? null) ? $reportData : [];
        $products = is_array($data['products'] ?? null) ? $data['products'] : [];
        $summary = is_array($data['summary'] ?? null) ? $data['summary'] : [];
        $grandTotalM3 = (float) ($summary['grand_total_m3'] ?? 0.0);
        $start = \Carbon\Carbon::parse($startDate)->locale('id')->translatedFormat('d-M-y');
        $end = \Carbon\Carbon::parse($endDate)->locale('id')->translatedFormat('d-M-y');

        $fmtInt = static fn($value): string => $value === null ? '' : number_format((float) $value, 0, '.', ',');
        $fmtM3 = static fn($value): string => $value === null ? '' : number_format((float) $value, 4, '.', ',');
        $fmtPct = static fn($value): string => $value === null ? '' : number_format((float) $value, 2, '.', ',') . ' %';
    @endphp

    <h1 class="report-title">Laporan Rekap Penjualan Per-Produk</h1>
    <div class="report-subtitle">Periode {{ $start }} s/d {{ $end }}</div>

    @forelse ($products as $product)
        <div class="section-title">{{ $product['roman'] ?? '' }} Produk : {{ $product['name'] ?? '-' }}</div>
        <table class="report-table product-table">
            <thead>
                <tr>
                    <th style="width: 40px;">No</th>
                    <th style="width: 54px;">Tebal</th>
                    <th style="width: 60px;">Lebar</th>
                    <th style="width: 80px;">Panjang</th>
                    <th style="width: 60px;">Pcs</th>
                    <th style="width: 90px;">M3</th>
                    <th style="width: 70px;">Rasio (%)</th>
                    <th style="width: 78px;"></th>
                </tr>
            </thead>
            <tfoot>
                <tr class="table-end-line">
                    <td colspan="8"></td>
                </tr>
            </tfoot>
            <tbody>
                @foreach ($product['rows'] as $index => $row)
                    <tr class="{{ ($index + 1) % 2 === 1 ? 'row-odd' : 'row-even' }}">
                        <td class="center">{{ $row['No'] ?? $index + 1 }}</td>
                        <td class="center">{{ $fmtInt($row['Tebal'] ?? null) }}</td>
                        <td class="center">{{ $fmtInt($row['Lebar'] ?? null) }}</td>
                        <td class="center">{{ $fmtInt($row['Panjang'] ?? null) }}</td>
                        <td class="number">{{ $fmtInt($row['JmlhBatang'] ?? null) }}</td>
                        <td class="number">{{ $fmtM3($row['M3'] ?? null) }}</td>
                        <td class="number">
                            {{ $row['Ratio'] !== null ? number_format((float) $row['Ratio'], 2, '.', ',') : '' }}</td>
                        <td class="number">
                            {{ !empty($row['DisplayCumulative']) ? $fmtPct($row['CumulativeRatio'] ?? null) : '' }}
                        </td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td class="center" colspan="5">Total</td>
                    <td class="number">{{ $fmtM3($product['total_m3'] ?? null) }}</td>
                    <td class="number">{{ $fmtPct($product['summary_ratio'] ?? null) }}</td>
                    <td></td>
                </tr>
            </tbody>
        </table>
    @empty
        <table class="report-table">
            <tbody>
                <tr>
                    <td class="empty-state">Tidak ada data untuk periode ini.</td>
                </tr>
            </tbody>
        </table>
    @endforelse

    @if ($products !== [])

        <div class="summary-title">Rangkuman</div>
        <table class="summary-table">
            <tbody>
                @foreach ($products as $product)
                    <tr>
                        <td>{{ $product['name'] ?? '-' }}</td>
                        <td class="number" style="width: 90px;">{{ $fmtM3($product['total_m3'] ?? null) }}</td>
                        <td class="number" style="width: 70px;">{{ $fmtPct($product['summary_ratio'] ?? null) }}</td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td style="padding-top: 6px;">Total (M3) Per-Produk :</td>
                    <td class="number" style="padding-top: 6px;">{{ $fmtM3($grandTotalM3) }}</td>
                    <td class="number" style="padding-top: 6px;">{{ $fmtPct(100.0) }}</td>
                </tr>
            </tbody>
        </table>
    @endif

    @include('reports.partials.pdf-footer-table')
</body>

</html>
