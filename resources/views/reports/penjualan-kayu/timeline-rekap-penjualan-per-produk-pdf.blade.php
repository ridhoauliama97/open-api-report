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
            line-height: 1.12;
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
            table-layout: fixed;
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
            white-space: nowrap;
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

        .left {
            text-align: left;
        }

        .number {
            text-align: right;
            white-space: nowrap;
            font-family: "Calibri", "DejaVu Sans", sans-serif;
        }

        .detail-table,
        .summary-table {
            margin-bottom: 14px;
        }

        .detail-table .total-row td,
        .summary-table .total-row td {
            font-weight: bold;
            border-top: 1px solid #000 !important;
            background: #fff !important;
        }

        .detail-table .product-divider td {
            border-top: 1px solid #000 !important;
        }

        .detail-table td.product-name-cell {
            background: #fff !important;
            vertical-align: middle !important;
            text-align: center !important;
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

        @include('reports.partials.pdf-footer-table-style')
    </style>
</head>

<body>
    @php
        use Carbon\Carbon;

        $data = is_array($reportData ?? null) ? $reportData : [];
        $products = is_array($data['products'] ?? null) ? $data['products'] : [];
        $monthColumns = is_array($data['month_keys'] ?? null) ? $data['month_keys'] : [];
        $summary = is_array($data['summary'] ?? null) ? $data['summary'] : [];
        $monthTotals = is_array($summary['month_totals'] ?? null) ? $summary['month_totals'] : [];
        $grandTotal = (float) ($summary['grand_total'] ?? 0.0);

        $start = Carbon::parse($startDate)->locale('id')->translatedFormat('d-M-y');
        $end = Carbon::parse($endDate)->locale('id')->translatedFormat('d-M-y');

        $fmtInt = static fn($value): string => $value === null ? '' : number_format((float) $value, 0, '.', ',');
        $fmtM3 = static fn($value): string => $value === null ? '' : number_format((float) $value, 4, '.', ',');
        $fmtRatio = static fn($value): string => $value === null ? '' : number_format((float) $value, 2, '.', ',');
        $fmtSummaryRatio = static fn($value): string => $value === null
            ? ''
            : number_format((float) $value, 1, '.', ',') . '%';

        $monthCount = max(1, count($monthColumns));
        $detailProductWidth = 16.0;
        $detailTebalWidth = 6.0;
        $detailLebarWidth = 6.0;
        $detailPanjangWidth = 7.0;
        $detailTotalWidth = 8.0;
        $detailRatioWidth = 5.0;
        $detailMonthWidth = max(
            3.5,
            (100 -
                $detailProductWidth -
                $detailTebalWidth -
                $detailLebarWidth -
                $detailPanjangWidth -
                $detailTotalWidth -
                $detailRatioWidth) /
                $monthCount,
        );

        $summaryProductWidth = 22.0;
        $summaryTotalWidth = 8.0;
        $summaryRatioWidth = 6.0;
        $summaryMonthWidth = max(
            3.5,
            (100 - $summaryProductWidth - $summaryTotalWidth - $summaryRatioWidth) / $monthCount,
        );
    @endphp

    <h1 class="report-title">Laporan Timeline Rekap Penjualan Per-Produk</h1>
    <div class="report-subtitle">Periode {{ $start }} s/d {{ $end }}</div>

    @if ($products !== [])
        <table class="report-table detail-table">
            <colgroup>
                <col style="width: {{ $detailProductWidth }}%;">
                <col style="width: {{ $detailTebalWidth }}%;">
                <col style="width: {{ $detailLebarWidth }}%;">
                <col style="width: {{ $detailPanjangWidth }}%;">
                @foreach ($monthColumns as $month)
                    <col style="width: {{ $detailMonthWidth }}%;">
                @endforeach
                <col style="width: {{ $detailTotalWidth }}%;">
                <col style="width: {{ $detailRatioWidth }}%;">
            </colgroup>
            <thead>
                <tr>
                    <th>Produk</th>
                    <th>Tebal</th>
                    <th>Lebar</th>
                    <th>Panjang</th>
                    @foreach ($monthColumns as $month)
                        <th>{{ $month['short'] ?? '-' }}</th>
                    @endforeach
                    <th colspan="2">Total</th>
                </tr>
            </thead>
            <tbody>
                @php $globalRowIndex = 0; @endphp
                @foreach ($products as $productIndex => $product)
                    @php
                        $tebalGroups = is_array($product['tebal_groups'] ?? null) ? $product['tebal_groups'] : [];
                        $productRows = [];
                        foreach ($tebalGroups as $tebalGroup) {
                            $tebalRows = is_array($tebalGroup['rows'] ?? null) ? $tebalGroup['rows'] : [];
                            foreach ($tebalRows as $row) {
                                $productRows[] = [
                                    'tebal' => $tebalGroup['tebal'] ?? null,
                                    'row' => $row,
                                ];
                            }
                        }
                        $productRowCount = count($productRows);
                        $middleProductRow = $productRowCount > 0 ? (int) ceil($productRowCount / 2) : 1;
                        $currentProductRow = 0;
                    @endphp
                    @foreach ($tebalGroups as $tebalGroup)
                        @php
                            $tebalRows = is_array($tebalGroup['rows'] ?? null) ? $tebalGroup['rows'] : [];
                            $tebalShown = false;
                        @endphp
                        @foreach ($tebalRows as $detailIndex => $row)
                            @php
                                $globalRowIndex++;
                                $currentProductRow++;
                                $rowClass = $globalRowIndex % 2 === 1 ? 'row-odd' : 'row-even';
                                $months = is_array($row['months'] ?? null) ? $row['months'] : [];
                                $isFirstRowOfProduct = $currentProductRow === 1;
                            @endphp
                            <tr
                                class="{{ $rowClass }} {{ $isFirstRowOfProduct && $productIndex > 0 ? 'product-divider' : '' }}">
                                <td class="product-name-cell">
                                    {{ $currentProductRow === $middleProductRow ? $product['name'] ?? '-' : '' }}
                                </td>

                                <td class="center">{{ !$tebalShown ? $fmtInt($tebalGroup['tebal'] ?? null) : '' }}</td>
                                @php $tebalShown = true; @endphp

                                <td class="center">{{ $fmtInt($row['Lebar'] ?? null) }}</td>
                                <td class="center">{{ $fmtInt($row['Panjang'] ?? null) }}</td>
                                @foreach ($monthColumns as $month)
                                    @php $monthValue = (float) ($months[$month['key']] ?? 0); @endphp
                                    <td class="number">{{ $monthValue > 0 ? $fmtM3($monthValue) : '' }}</td>
                                @endforeach
                                <td class="number">
                                    {{ (float) ($row['Total'] ?? 0) > 0 ? $fmtM3($row['Total'] ?? 0) : '' }}</td>
                                <td class="number">
                                    {{ (float) ($row['Ratio'] ?? 0) > 0 ? $fmtRatio($row['Ratio'] ?? 0) : '' }}</td>
                            </tr>
                        @endforeach
                    @endforeach
                    <tr class="total-row">
                        <td> &nbsp;</td>
                        <td class="center" colspan="3">Total</td>
                        @foreach ($monthColumns as $month)
                            @php $productMonthTotal = (float) ($product['month_totals'][$month['key']] ?? 0); @endphp
                            <td class="number">{{ $productMonthTotal > 0 ? $fmtM3($productMonthTotal) : '' }}</td>
                        @endforeach
                        <td class="number">
                            {{ (float) ($product['total'] ?? 0) > 0 ? $fmtM3($product['total'] ?? 0) : '' }}</td>
                        <td class="number">100.00</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <table class="report-table">
            <tbody>
                <tr>
                    <td class="empty-state">Tidak ada data untuk periode ini.</td>
                </tr>
            </tbody>
        </table>
    @endif

    @if ($products !== [])
        <h3>Rangkuman Hasil :</h3>
        <table class="report-table summary-table">
            <colgroup>
                <col style="width: {{ $summaryProductWidth }}%;">
                @foreach ($monthColumns as $month)
                    <col style="width: {{ $summaryMonthWidth }}%;">
                @endforeach
                <col style="width: {{ $summaryTotalWidth }}%;">
                <col style="width: {{ $summaryRatioWidth }}%;">
            </colgroup>
            <thead>
                <tr>
                    <th>Produk</th>
                    @foreach ($monthColumns as $month)
                        <th>{{ $month['short'] ?? ($month['short'] ?? '-') }}</th>
                    @endforeach
                    <th colspan="2">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($products as $index => $product)
                    <tr class="{{ ($index + 1) % 2 === 1 ? 'row-odd' : 'row-even' }}">
                        <td class="left">{{ $product['name'] ?? '-' }}</td>
                        @foreach ($monthColumns as $month)
                            @php $productMonthTotal = (float) ($product['month_totals'][$month['key']] ?? 0); @endphp
                            <td class="number">{{ $productMonthTotal > 0 ? $fmtM3($productMonthTotal) : '' }}</td>
                        @endforeach
                        <td class="number">
                            {{ (float) ($product['total'] ?? 0) > 0 ? $fmtM3($product['total'] ?? 0) : '' }}</td>
                        <td class="number">{{ $fmtSummaryRatio($product['ratio'] ?? null) }}</td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td class="center">Grand Total</td>
                    @foreach ($monthColumns as $month)
                        @php $monthGrandTotal = (float) ($monthTotals[$month['key']] ?? 0); @endphp
                        <td class="number">{{ $monthGrandTotal > 0 ? $fmtM3($monthGrandTotal) : '' }}</td>
                    @endforeach
                    <td class="number">{{ $grandTotal > 0 ? $fmtM3($grandTotal) : '' }}</td>
                    <td class="number">100.0%</td>
                </tr>
            </tbody>
        </table>
    @endif

    @include('reports.partials.pdf-footer-table')
</body>

</html>
