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
            border: 1px solid #000;
        }

        .data-table th,
        .data-table td {
            border-left: 1px solid #000;
            border-right: 1px solid #000;
            padding: 2px 3px;
            vertical-align: middle;
        }

        .data-table th {
            font-weight: bold;
            font-size: 10px;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            text-align: center;
        }

        .sub-header th {
            border-top: none;
            border-bottom: none;
        }

        .data-table td {
            font-size: 10px;
        }

        .center {
            text-align: center;
        }

        .row-odd td {
            background: #c9d1df;
        }

        .row-even td {
            background: #eef2f8;
        }

        .number {
            text-align: right;
        }

        .nowrap {
            white-space: nowrap;
        }

        .empty-row td {
            text-align: center;
            font-style: italic;
            background: #c9d1df;
            font-weight: bold;
            color: #9c111d;
            font-size: 10px;
        }

        .section-header td {
            text-align: left;
            font-weight: bold;
            font-style: italic;
            color: #9c111d;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            font-size: 10px;
            padding: 3px 4px;
        }

        .subtotal-row td {
            font-weight: bold;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            font-size: 10px;
        }

        .grand-total-row td {
            font-weight: bold;
            border-bottom: 1px solid #000;
            font-size: 11px;
        }
    </style>
</head>

<body>
    @php
        $familyGroups = $reportData['family_groups'] ?? [];
        $familyTotals = $reportData['family_totals'] ?? [];
        $grandTotal = $reportData['grand_total'] ?? [];
        $month2Label = $reportData['month2_label'] ?? '';
        $month3Label = $reportData['month3_label'] ?? '';
        $month4Label = $reportData['month4_label'] ?? '';
        $generatedAtText = \Carbon\Carbon::parse($generatedAt ?? now())
            ->locale('id')->translatedFormat('d-M-y H:i');
        $generatedByName = trim((string) ($reportData['printed_by'] ?? ''));
        $headerCompany = trim((string) ($company ?? $reportData['company'] ?? ''));
        $headerSubtitle = trim((string) ($reportData['period_label'] ?? $subtitle ?? ''));

        $fmt = function ($v) {
            return $v != 0 ? number_format($v, 2, ',', '.') : '-';
        };

        $fmtQty = function ($v) {
            return $v != 0 ? number_format($v, 4, ',', '.') : '-';
        };

        $fmtPersen = function ($v) {
            return $v != 0 ? number_format($v, 2, ',', '.') : '-';
        };
    @endphp

    <h1 class="report-companyTitle">{{ $headerCompany }}</h1>
    <h1 class="report-title">{{ $title ?? $reportData['title'] }}</h1>
    <p class="report-subtitle">{{ $headerSubtitle }}</p>

    @if (count($familyGroups) > 0)
        <table class="data-table">
            <thead>
                <tr>
                    <th rowspan="2" style="width: 22%">Nama Barang</th>
                    <th colspan="2" style="width: 16%">{{ $month2Label }}</th>
                    <th colspan="2" style="width: 16%">{{ $month3Label }}</th>
                    <th colspan="2" style="width: 16%">{{ $month4Label }}</th>
                    <th rowspan="2" style="width: 10%">Rata-rata</th>
                    <th rowspan="2" style="width: 10%">Min</th>
                    <th rowspan="2" style="width: 10%">Max</th>
                </tr>
                <tr class="sub-header">
                    <th style="width: 8%">Qty</th>
                    <th style="width: 8%">(%)</th>
                    <th style="width: 8%">Qty</th>
                    <th style="width: 8%">(%)</th>
                    <th style="width: 8%">Qty</th>
                    <th style="width: 8%">(%)</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($familyGroups as $groupName => $items)
                    <tr class="section-header">
                        <td colspan="10">{{ $groupName }}</td>
                    </tr>

                    @php $itemIndex = 0; @endphp
                    @foreach ($items as $item)
                        @php
                            $rowClass = $itemIndex % 2 === 0 ? 'row-even' : 'row-odd';
                            $itemIndex++;
                        @endphp
                        <tr class="{{ $rowClass }}">
                            <td>{{ $item['name_item'] }}</td>
                            <td class="number nowrap">{{ $fmtQty($item['month2']['qty']) }}</td>
                            <td class="number nowrap">{{ $fmtPersen($item['month2']['persen']) }} %</td>
                            <td class="number nowrap">{{ $fmtQty($item['month3']['qty']) }}</td>
                            <td class="number nowrap">{{ $fmtPersen($item['month3']['persen']) }} %</td>
                            <td class="number nowrap">{{ $fmtQty($item['month4']['qty']) }}</td>
                            <td class="number nowrap">{{ $fmtPersen($item['month4']['persen']) }} %</td>
                            <td class="number nowrap">{{ $fmtPersen($item['fr_avg']) }} %</td>
                            <td class="number nowrap">{{ $fmtPersen($item['fr_min']) }} %</td>
                            <td class="number nowrap">{{ $fmtPersen($item['fr_max']) }} %</td>
                        </tr>
                    @endforeach

                    @php
                        $ft = $familyTotals[$groupName] ?? [];
                    @endphp
                    <tr class="subtotal-row">
                        <td class="center">Subtotal {{ $groupName }}</td>
                        <td class="number nowrap">{{ $fmtQty($ft['month2']['qty'] ?? 0) }}</td>
                        <td class="number nowrap">{{ $fmtPersen($ft['month2']['persen'] ?? 0) }} %</td>
                        <td class="number nowrap">{{ $fmtQty($ft['month3']['qty'] ?? 0) }}</td>
                        <td class="number nowrap">{{ $fmtPersen($ft['month3']['persen'] ?? 0) }} %</td>
                        <td class="number nowrap">{{ $fmtQty($ft['month4']['qty'] ?? 0) }}</td>
                        <td class="number nowrap">{{ $fmtPersen($ft['month4']['persen'] ?? 0) }} %</td>
                        <td class="number nowrap">{{ $fmtPersen($ft['fr_avg'] ?? 0) }} %</td>
                        <td class="number nowrap">{{ $fmtPersen($ft['fr_min'] ?? 0) }} %</td>
                        <td class="number nowrap">{{ $fmtPersen($ft['fr_max'] ?? 0) }} %</td>
                    </tr>
                @endforeach

                <tr class="grand-total-row">
                    <td class="center">Grand Total</td>
                    <td class="number nowrap">{{ $fmtQty($grandTotal['month2']['qty'] ?? 0) }}</td>
                    <td class="number nowrap">{{ $fmtPersen($grandTotal['month2']['persen'] ?? 0) }} %</td>
                    <td class="number nowrap">{{ $fmtQty($grandTotal['month3']['qty'] ?? 0) }}</td>
                    <td class="number nowrap">{{ $fmtPersen($grandTotal['month3']['persen'] ?? 0) }} %</td>
                    <td class="number nowrap">{{ $fmtQty($grandTotal['month4']['qty'] ?? 0) }}</td>
                    <td class="number nowrap">{{ $fmtPersen($grandTotal['month4']['persen'] ?? 0) }} %</td>
                    <td class="number nowrap">{{ $fmtPersen($grandTotal['fr_avg'] ?? 0) }} %</td>
                    <td class="number nowrap">{{ $fmtPersen($grandTotal['fr_min'] ?? 0) }} %</td>
                    <td class="number nowrap">{{ $fmtPersen($grandTotal['fr_max'] ?? 0) }} %</td>
                </tr>
            </tbody>
        </table>
    @else
        <table class="data-table">
            <tbody>
                <tr class="empty-row">
                    <td colspan="10">Tidak ada data penjualan.</td>
                </tr>
            </tbody>
        </table>
    @endif

    @include('ascends.shared.partials.report-footer')
</body>

</html>
