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
        .detail-table, .report-table, .summary-table {
            border: 1px solid #000;
            border-collapse: collapse;
            width: calc(100% - 2px);
            line-height: inherit;
        }
.detail-table th, .detail-table td, .report-table th, .report-table td, .summary-table th, .summary-table td {
            border: 1px solid #000;
            word-wrap: break-word;
            padding: 2px;
            vertical-align: top;
        }

        .detail-table th { font-weight: bold; background-color: #eef2f8; }

        .report-table th { font-weight: bold; background-color: #eef2f8; }

        .summary-table th { font-weight: bold; background-color: #eef2f8; }
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

    </body>

</html>
