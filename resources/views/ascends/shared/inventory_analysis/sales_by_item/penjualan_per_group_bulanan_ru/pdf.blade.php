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
        $familySubtotals = $reportData['family_subtotals'] ?? [];
        $grandQty = (float) ($reportData['grand_qty'] ?? 0);
        $grandPenjualan = (float) ($reportData['grand_penjualan'] ?? 0);
        $grandHpp = (float) ($reportData['grand_hpp'] ?? 0);
        $grandLaba = (float) ($reportData['grand_laba'] ?? 0);
        $grandPersen = (float) ($reportData['grand_persen'] ?? 0);
        $generatedAtText = \Carbon\Carbon::parse($generatedAt ?? now())
            ->locale('id')->translatedFormat('d-M-y H:i');
        $generatedByName = trim((string) ($reportData['printed_by'] ?? ''));
        $headerCompany = trim((string) ($company ?? $reportData['company'] ?? ''));
        $headerSubtitle = trim((string) ($reportData['period_label'] ?? ''));

        $fmt = function ($v) {
            return $v != 0 ? number_format($v, 1, '.', ',') : '-';
        };

        $fmtQty = function ($v) {
            return $v != 0 ? number_format($v, 4, '.', ',') : '-';
        };

        $fmtPersen = function ($v) {
            return $v != 0 ? number_format($v, 1, '.', ',') : '-';
        };
    @endphp

    <h1 class="report-companyTitle">{{ $headerCompany }}</h1>
    <h1 class="report-title">{{ $title ?? $reportData['title'] }}</h1>
    <p class="report-subtitle">{{ $headerSubtitle }}</p>

    @if (count($familyGroups) > 0)
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 30%">Nama Item</th>
                    <th style="width: 12%">Qty</th>
                    <th style="width: 16%">Penjualan</th>
                    <th style="width: 16%">Hpp</th>
                    <th style="width: 16%">Laba Kotor</th>
                    <th style="width: 10%">Total (%)</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($familyGroups as $groupName => $items)
                    <tr class="section-header">
                        <td colspan="6">{{ $groupName }}</td>
                    </tr>

                    @php $itemIndex = 0; @endphp
                    @foreach ($items as $item)
                        @php
                            $rowClass = $itemIndex % 2 === 0 ? 'row-even' : 'row-odd';
                            $itemIndex++;
                        @endphp
                        <tr class="{{ $rowClass }}">
                            <td>{{ $item['item_name'] }}</td>
                            <td class="number nowrap">{{ $fmtQty($item['qty']) }} {{ $item['uom'] }}</td>
                            <td class="number nowrap">{{ $fmt($item['penjualan']) }}</td>
                            <td class="number nowrap">{{ $fmt($item['hpp']) }}</td>
                            <td class="number nowrap">{{ $fmt($item['laba']) }}</td>
                            <td class="number nowrap">{{ $fmtPersen($item['persen']) }} %</td>
                        </tr>
                    @endforeach

                    @php
                        $subtotal = $familySubtotals[$groupName] ?? [];
                        $stQty = $subtotal['qty'] ?? 0;
                        $stUom = $subtotal['uom'] ?? '';
                        $stPenjualan = $subtotal['penjualan'] ?? 0;
                        $stHpp = $subtotal['hpp'] ?? 0;
                        $stLaba = $subtotal['laba'] ?? 0;
                        $stPersen = $subtotal['persen'] ?? 0;
                    @endphp
                    <tr class="subtotal-row">
                        <td class="center">Subtotal {{ $groupName }}</td>
                        <td class="number nowrap">{{ $fmtQty($stQty) }} {{ $stUom }}</td>
                        <td class="number nowrap">{{ $fmt($stPenjualan) }}</td>
                        <td class="number nowrap">{{ $fmt($stHpp) }}</td>
                        <td class="number nowrap">{{ $fmt($stLaba) }}</td>
                        <td class="number nowrap">{{ $fmtPersen($stPersen) }} %</td>
                    </tr>
                @endforeach

                <tr class="grand-total-row">
                    <td class="center">Grand Total</td>
                    <td class="number nowrap">{{ $fmtQty($grandQty) }}</td>
                    <td class="number nowrap">{{ $fmt($grandPenjualan) }}</td>
                    <td class="number nowrap">{{ $fmt($grandHpp) }}</td>
                    <td class="number nowrap">{{ $fmt($grandLaba) }}</td>
                    <td class="number nowrap">{{ $fmtPersen($grandPersen) }} %</td>
                </tr>
            </tbody>
        </table>
    @else
        <table class="data-table">
            <tbody>
                <tr class="empty-row">
                    <td colspan="6">Tidak ada data penjualan.</td>
                </tr>
            </tbody>
        </table>
    @endif

    @include('ascends.shared.partials.report-footer')
</body>

</html>
