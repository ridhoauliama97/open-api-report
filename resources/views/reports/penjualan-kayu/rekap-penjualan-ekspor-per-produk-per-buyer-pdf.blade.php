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
            margin: 14mm 10mm 14mm 10mm;
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
            margin: 10px 0 5px 0;
            font-size: 11px;
            font-weight: bold;
        }

        .buyer-title {
            margin: 8px 0 5px 14px;
            font-size: 10px;
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

        .product-table {
            width: 100%;
            margin-bottom: 8px;
            margin-left: 14px;
        }

        .summary-title {
            margin: 12px 0 4px 0;
            font-weight: bold;
            font-size: 11px;
        }

        .summary-table {
            width: 70%;
        }

        .summary-table td,
        .summary-table th {
            padding: 2px 4px;
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
        $products = is_array($data['products'] ?? null) ? $data['products'] : [];
        $summary = is_array($data['summary'] ?? null) ? $data['summary'] : [];
        $grandTotalM3 = (float) ($summary['grand_total_m3'] ?? 0.0);
        $sortedProducts = $products;
        usort($sortedProducts, static function (array $left, array $right): int {
            return ((float) ($right['summary_ratio'] ?? 0)) <=> ((float) ($left['summary_ratio'] ?? 0));
        });
        $start = \Carbon\Carbon::parse($startDate)->locale('id')->translatedFormat('d-M-y');
        $end = \Carbon\Carbon::parse($endDate)->locale('id')->translatedFormat('d-M-y');
        $fmtInt = static fn($value): string => $value === null ? '' : number_format((float) $value, 0, '.', ',');
        $fmtM3 = static fn($value): string => $value === null ? '' : number_format((float) $value, 4, '.', ',');
        $fmtPct = static fn($value): string => $value === null ? '' : number_format((float) $value, 2, '.', ',') . ' %';
    @endphp

    <h1 class="report-title">Laporan Rekap Penjualan Ekspor Per-Produk dan Per-Buyer</h1>
    <div class="report-subtitle">Periode {{ $start }} s/d {{ $end }}</div>

    @forelse ($sortedProducts as $product)
        @php
            $productBuyers = is_array($product['buyers'] ?? null) ? $product['buyers'] : [];
            usort($productBuyers, static function (array $left, array $right): int {
                return ((float) ($right['total_m3'] ?? 0)) <=> ((float) ($left['total_m3'] ?? 0));
            });
        @endphp
        <div class="section-title">{{ $loop->iteration }}. Produk : {{ $product['name'] ?? '-' }}</div>
        @foreach ($productBuyers as $buyer)
            <div class="buyer-title">Buyer : {{ $buyer['name'] ?? '-' }}</div>
            <table class="report-table product-table">
                <thead>
                    <tr>
                        <th style="width: 5%;">No</th>
                        <th>Tebal</th>
                        <th>Lebar</th>
                        <th>Panjang</th>
                        <th>Pcs</th>
                        <th style="width: 15%">M3</th>
                        <th style="width: 15%;">Rasio (%)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($buyer['rows'] ?? [] as $index => $row)
                        <tr class="{{ ($index + 1) % 2 === 1 ? 'row-odd' : 'row-even' }}">
                            <td class="center">{{ $row['No'] ?? $index + 1 }}</td>
                            <td class="center">{{ $fmtInt($row['Tebal'] ?? null) }}</td>
                            <td class="center">{{ $fmtInt($row['Lebar'] ?? null) }}</td>
                            <td class="center">{{ $fmtInt($row['Panjang'] ?? null) }}</td>
                            <td class="number">{{ $fmtInt($row['JmlhBatang'] ?? null) }}</td>
                            <td class="number">{{ $fmtM3($row['M3'] ?? null) }}</td>
                            <td class="number">
                                {{ $row['Ratio'] !== null ? number_format((float) $row['Ratio'], 2, '.', ',') : '' }}
                            </td>
                        </tr>
                    @endforeach
                    <tr class="total-row">
                        <td class="center" colspan="5">Total </td>
                        <td class="number">{{ $fmtM3($buyer['total_m3'] ?? null) }}</td>
                        <td class="number">{{ $fmtPct($buyer['summary_ratio'] ?? null) }}</td>
                    </tr>
                </tbody>
            </table>
        @endforeach
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
        <div class="summary-title">Rangkuman Hasil :</div>
        <table class="report-table summary-table">
            <thead>
                <tr>
                    <th style="width: 8%;">No</th>
                    <th>Produk</th>
                    <th>Jumlah Buyer</th>
                    <th>Total M3</th>
                    <th>Rasio (%)</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($sortedProducts as $index => $product)
                    <tr class="{{ ($index + 1) % 2 === 1 ? 'row-odd' : 'row-even' }}">
                        <td class="center">{{ $index + 1 }}</td>
                        <td>{{ $product['name'] ?? '-' }}</td>
                        <td class="center">{{ count($product['buyers'] ?? []) }}</td>
                        <td class="number">{{ $fmtM3($product['total_m3'] ?? null) }}</td>
                        <td class="number">{{ $fmtPct($product['summary_ratio'] ?? null) }}</td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="3" class="center">Grand Total</td>
                    <td class="number">{{ $fmtM3($grandTotalM3) }}</td>
                    <td class="number">{{ $fmtPct(100.0) }}</td>
                </tr>
            </tbody>
        </table>
    @endif

    @include('reports.partials.pdf-footer-table')
</body>

</html>
