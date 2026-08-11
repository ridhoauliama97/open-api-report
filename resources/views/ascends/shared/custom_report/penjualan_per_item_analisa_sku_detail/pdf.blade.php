<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif:ital,wght@0,100..900;1,100..900&display=swap"
        rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
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

        .report-companyTitle {
            text-align: center;
            margin: 0 0 4px 0;
            font-size: 18px;
            font-weight: bold;
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

        .data-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            page-break-inside: auto;
            border-spacing: 0;
        }

        .data-table th,
        .data-table td {
            border-left: 1px solid #000;
            border-right: 1px solid #000;
            padding: 3px 4px;
            vertical-align: middle;
            word-wrap: break-word;
        }

        .data-table th {
            font-weight: bold;
            font-size: 10px;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            text-align: center;
        }

        .center {
            text-align: center;
        }

        .number {
            text-align: right;
        }

        .nowrap {
            white-space: nowrap;
        }

        .row-odd td {
            background: #c9d1df;
        }

        .row-even td {
            background: #eef2f8;
        }

        .grand-total-row td {
            font-weight: bold;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
        }

        .section-header td {
            font-size: 11px;
            font-weight: bold;
            font-style: italic;
            color: #9c111d;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
        }

        .empty-row td {
            text-align: center;
            font-style: italic;
            background: #c9d1df;
            color: #9c111d;
            font-weight: bold;
            font-size: 11px;
            padding: 3px 4px;
        }
    </style>
</head>

<body>
    @php
        $sections = $reportData['sections'] ?? [];
        $months = $reportData['months'] ?? [];
        $grandTotals = $reportData['grand_totals'] ?? [];
        $monthCount = count($months);

        $headerCompany = trim((string) ($company ?? ($reportData['company'] ?? '')));
        $headerTitle = trim((string) ($title ?? ($reportData['title'] ?? ($fallbackTitle ?? ''))));
        $headerSubtitle = trim((string) ($reportData['period_label'] ?? ''));
        $generatedByName = trim((string) ($reportData['printed_by'] ?? ''));

        $colspanHeader = 2 + $monthCount * 2 + 2;

        $colNoWidth = 3;
        $colNameWidth = $monthCount > 6 ? 16 : 18;
        $colTotalQtyWidth = 4;
        $colTotalPenjualanWidth = 5;
        $remainingPerMonth = $monthCount > 0
            ? round((100 - $colNoWidth - $colNameWidth - $colTotalQtyWidth - $colTotalPenjualanWidth) / ($monthCount * 2), 1)
            : 0;

        if (!function_exists('fmtNum_penjualan_per_item_analisa_sku_detail')) {
            function fmtNum_penjualan_per_item_analisa_sku_detail($value)
            {
                $v = (float) $value;
                if ($v == 0.0) {
                    return '-';
                }
                return number_format($v, 0, '.', ',');
            }
        }

        $startDate = trim((string) ($reportData['start_date'] ?? ''));
        $endDate = trim((string) ($reportData['end_date'] ?? ''));
        if ($startDate !== '' && $endDate !== '') {
            $headerSubtitle = 'Dari ' . $startDate . ' s/d ' . $endDate;
        }

        $globalRow = 0;
    @endphp

    <h1 class="report-companyTitle">{{ $headerCompany }}</h1>
    <h1 class="report-title">{{ $headerTitle }}</h1>
    <p class="report-subtitle">{{ $headerSubtitle }}</p>

    @if (count($sections) > 0)
        <table class="data-table">
            <thead>
                <tr>
                    <th rowspan="2" style="width: {{ $colNoWidth }}%;">No</th>
                    <th rowspan="2" style="width: {{ $colNameWidth }}%;">Nama Barang</th>
                    @foreach ($months as $month)
                        <th colspan="2" style="width: {{ $remainingPerMonth * 2 }}%;">{{ $month }}</th>
                    @endforeach
                    <th colspan="2" style="width: {{ $colTotalQtyWidth + $colTotalPenjualanWidth }}%;">Total</th>
                </tr>
                <tr>
                    @foreach ($months as $month)
                        <th style="width: {{ $remainingPerMonth }}%;">Qty</th>
                        <th style="width: {{ $remainingPerMonth }}%;">Penjualan</th>
                    @endforeach
                    <th style="width: {{ $colTotalQtyWidth }}%;">Qty</th>
                    <th style="width: {{ $colTotalPenjualanWidth }}%;">Penjualan</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($sections as $section)
                    <tr class="section-header">
                        <td colspan="{{ $colspanHeader }}">{{ $section['family'] ?? '' }}</td>
                    </tr>
                    @foreach ($section['rows'] ?? [] as $row)
                        @php $globalRow++; @endphp
                        <tr class="{{ $globalRow % 2 === 0 ? 'row-even' : 'row-odd' }}">
                            <td class="center">{{ $globalRow }}</td>
                            <td>{{ $row['item'] ?? '' }}</td>
                            @foreach ($row['cells'] ?? [] as $cell)
                                <td class="number">{{ fmtNum_penjualan_per_item_analisa_sku_detail($cell['qty'] ?? 0) }}</td>
                                <td class="number">{{ fmtNum_penjualan_per_item_analisa_sku_detail($cell['penjualan'] ?? 0) }}</td>
                            @endforeach
                            <td class="number">{{ fmtNum_penjualan_per_item_analisa_sku_detail($row['row_total_qty'] ?? 0) }}</td>
                            <td class="number">{{ fmtNum_penjualan_per_item_analisa_sku_detail($row['row_total_penjualan'] ?? 0) }}</td>
                        </tr>
                    @endforeach
                @endforeach

                @if (count($grandTotals) > 0)
                    <tr class="grand-total-row">
                        <td class="center" colspan="2">TOTAL</td>
                        @foreach ($grandTotals as $gt)
                            <td class="number">{{ fmtNum_penjualan_per_item_analisa_sku_detail($gt['qty'] ?? 0) }}</td>
                            <td class="number">{{ fmtNum_penjualan_per_item_analisa_sku_detail($gt['penjualan'] ?? 0) }}</td>
                        @endforeach
                        <td class="number">{{ fmtNum_penjualan_per_item_analisa_sku_detail($reportData['grand_total_qty'] ?? 0) }}</td>
                        <td class="number">{{ fmtNum_penjualan_per_item_analisa_sku_detail($reportData['grand_total_penjualan'] ?? 0) }}</td>
                    </tr>
                @endif
            </tbody>
        </table>
    @else
        <table class="data-table">
            <tbody>
                <tr class="empty-row">
                    <td colspan="{{ $colspanHeader }}">Tidak ada data.</td>
                </tr>
            </tbody>
        </table>
    @endif

    @include('ascends.shared.partials.report-footer')
</body>

</html>