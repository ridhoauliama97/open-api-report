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
            line-height: 1.2;
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
            border-spacing: 0;
            border: 1px solid #000;
        }

        .data-table th,
        .data-table td {
            border-left: 1px solid #000;
            border-right: 1px solid #000;
            padding: 1px 2px;
            vertical-align: middle;
        }

        .data-table thead th {
            font-weight: bold;
            font-size: 10px;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            text-align: center;
        }

        .data-table tbody td {
            font-size: 10px;
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
        }
    </style>
</head>

<body>
    @php
        $sections = $reportData['sections'] ?? [];
        $months = $reportData['months'] ?? [];
        $grandTotals = $reportData['grand_totals'] ?? [];
        $monthCount = count($months);

        $grandTotalQty = 0.0;
        $grandTotalPenjualan = 0.0;
        foreach ($grandTotals as $gt) {
            $grandTotalQty += (float) ($gt['qty'] ?? 0);
            $grandTotalPenjualan += (float) ($gt['penjualan'] ?? 0);
        }

        $colspanHeader = 1 + $monthCount * 2 + 2;
        $colNoWidth = 3;
        $colNameWidth = $monthCount > 6 ? 16 : 18;
        $colTotalQtyWidth = 4;
        $colTotalPenjualanWidth = 5;
        $remainingPerMonth = $monthCount > 0
            ? round((100 - $colNoWidth - $colNameWidth - $colTotalQtyWidth - $colTotalPenjualanWidth) / ($monthCount * 2), 1)
            : 0;

        $fmtNum = static function ($value) {
            $v = (float) $value;
            if ($v == 0.0) {
                return '-';
            }
            return number_format($v, 0, ',', '.');
        };

        $company = $reportData['headerCompany'] ?? '';
        $headerTitle = $reportData['headerTitle'] ?? 'Laporan Penjualan Per Item Barang (Detail)';
        $startDate = trim((string) ($reportData['start_date'] ?? ''));
        $endDate = trim((string) ($reportData['end_date'] ?? ''));
        $subtitle = '';
        if ($startDate !== '' && $endDate !== '') {
            $subtitle = 'Dari ' . $startDate . ' s/d ' . $endDate;
        }

        $itemCounter = 0;
        $generatedByName = $reportData['printed_by'] ?? 'sistem';
    @endphp

    @include('ascends.shared.partials.report-header', [
        'company' => $company,
        'title' => $headerTitle,
        'subtitle' => $subtitle,
    ])

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
            @forelse ($sections as $sectionIdx => $section)
                <tr class="section-header">
                    <td colspan="{{ $colspanHeader }}">{{ $section['family'] ?? '' }}</td>
                </tr>
                @foreach ($section['rows'] ?? [] as $rowIdx => $row)
                    @php
                        $itemCounter++;
                        $rowClass = $itemCounter % 2 === 0 ? 'row-even' : 'row-odd';
                        $itemTotalQty = 0.0;
                        $itemTotalPenjualan = 0.0;
                        foreach ($row['cells'] ?? [] as $cell) {
                            $itemTotalQty += (float) ($cell['qty'] ?? 0);
                            $itemTotalPenjualan += (float) ($cell['penjualan'] ?? 0);
                        }
                    @endphp
                    <tr class="{{ $rowClass }}">
                        <td class="center">{{ $itemCounter }}</td>
                        <td>{{ $row['item'] ?? '' }}</td>
                        @foreach ($row['cells'] ?? [] as $cell)
                            <td class="number">{{ $fmtNum($cell['qty'] ?? 0) }}</td>
                            <td class="number">{{ $fmtNum($cell['penjualan'] ?? 0) }}</td>
                        @endforeach
                        <td class="number">{{ $fmtNum($itemTotalQty) }}</td>
                        <td class="number">{{ $fmtNum($itemTotalPenjualan) }}</td>
                    </tr>
                @endforeach
            @empty
                <tr class="empty-row">
                    <td colspan="{{ $colspanHeader }}">Tidak ada data.</td>
                </tr>
            @endforelse

            @if (count($sections) > 0 && count($grandTotals) > 0)
                <tr class="grand-total-row">
                    <td class="center" colspan="2">Total</td>
                    @foreach ($grandTotals as $gt)
                        <td class="number">{{ $fmtNum($gt['qty'] ?? 0) }}</td>
                        <td class="number">{{ $fmtNum($gt['penjualan'] ?? 0) }}</td>
                    @endforeach
                    <td class="number">{{ $fmtNum($grandTotalQty) }}</td>
                    <td class="number">{{ $fmtNum($grandTotalPenjualan) }}</td>
                </tr>
            @endif
        </tbody>
    </table>

    @include('ascends.shared.partials.report-footer')
</body>

</html>
