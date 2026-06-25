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
            margin: 14mm 8mm 14mm 8mm;
            footer: html_reportFooter;
        }

        body {
            margin: 0;
            font-family: "Noto Serif", serif;
            font-size: 11px;
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
            padding: 1.5px 2px;
            vertical-align: middle;
            word-wrap: break-word;
        }

        .data-table th {
            font-weight: bold;
            font-size: 11px;
            text-align: center;
        }

        .header-group th {
            font-size: 11px;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
        }

        .header-sub th {
            border-bottom: 1px solid #000;
        }

        .header-final th {
            border-bottom: 1px solid #000;
            font-size: 11px;
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
            font-size: 11px;
        }

        .category-header td {
            text-align: left;
            font-weight: bold;
            font-size: 11px;
            padding: 3px 4px;
            background: #b0b8c9;
            border-top: 2px solid #000;
            border-bottom: 1px solid #000;
        }

        .family-header td {
            text-align: left;
            font-weight: bold;
            font-size: 11px;
            font-style: italic;
            padding: 2px 4px;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
        }

        .subtotal-row td {
            font-weight: bold;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            font-size: 11px;
            background: #dde3ec;
        }

        .category-subtotal td {
            font-weight: bold;
            border-top: 2px solid #000;
            border-bottom: 2px solid #000;
            font-size: 11px;
            background: #c6cedc;
        }

        .grand-total td {
            font-weight: bold;
            border-top: 2px solid #000;
            border-bottom: 2px solid #000;
            font-size: 11px;
            background: #a8b3c8;
        }
    </style>
</head>

<body>
    @php
        $categories = $reportData['categories'] ?? [];
        $grandTotals = $reportData['grand_totals'] ?? [];
        $generatedAtText = \Carbon\Carbon::parse($generatedAt ?? now())
            ->locale('id')->translatedFormat('d-M-y H:i');
        $generatedByName = trim((string) ($reportData['printed_by'] ?? ''));
        $headerCompany = trim((string) ($company ?? $reportData['company'] ?? ''));
        $headerTitle = trim((string) ($title ?? $reportData['title'] ?? $fallbackTitle ?? ''));
        $headerSubtitle = trim((string) ($reportData['period_label'] ?? ''));
        $svc = \App\Services\Ascends\Shared\Analysis\AktifitasStockGsuPerGudangReportService::class;
    @endphp

    <h1 class="report-companyTitle">{{ $headerCompany }}</h1>
    <h1 class="report-title">{{ $headerTitle }}</h1>
    <p class="report-subtitle">{{ $headerSubtitle }}</p>

    @if (count($categories) > 0)
        <table class="data-table">
            <thead>
                <tr class="header-group">
                    <th rowspan="3" style="width: 3%">No</th>
                    <th rowspan="3" style="width: 20%">Nama Barang</th>
                    <th rowspan="2" style="width: 8%">Saldo Awal</th>
                    <th colspan="2" style="width: 14%">Masuk</th>
                    <th colspan="2" style="width: 14%">Keluar</th>
                    <th colspan="3" rowspan="2" style="width: 16%">Saldo Akhir</th>
                    <th rowspan="3" style="width: 12%">Est. Stock<br>Tersedia (Bln)</th>
                </tr>
                <tr class="header-sub">
                    <th style="width: 7%">Pembelian</th>
                    <th style="width: 7%">Produksi</th>
                    <th style="width: 7%">Penjualan</th>
                    <th style="width: 7%">Produksi</th>
                </tr>
                <tr class="header-final">
                    <th style="width: 8%">Qty</th>
                    <th style="width: 7%">Qty</th>
                    <th style="width: 7%">Qty</th>
                    <th style="width: 7%">Qty</th>
                    <th style="width: 7%">Qty</th>
                    <th style="width: 5%">Value</th>
                    <th style="width: 5%">Qty</th>
                    <th style="width: 6%">Satuan<br>Dus/Bal</th>
                </tr>
            </thead>
            <tbody>
                @php $globalRowNumber = 0; @endphp
                @foreach ($categories as $catIdx => $category)
                    <tr class="category-header">
                        <td colspan="11">{{ $category['category'] }}</td>
                    </tr>

                    @php $itemCounter = 0; @endphp
                    @foreach ($category['families'] as $famIdx => $family)
                        <tr class="family-header">
                            <td colspan="11">{{ $family['family_name'] }}</td>
                        </tr>

                        @forelse ($family['items'] as $item)
                            @php
                                $globalRowNumber++;
                                $itemCounter++;
                                $itemHpp = $svc::computeItemHpp(
                                    (float) ($item['ending'] ?? 0),
                                    $item['category'] ?? '',
                                    $item['uom_factors'] ?? '',
                                    (float) ($item['bdg'] ?? 0),
                                );
                                $itemSelisih = $svc::computeItemSelisih(
                                    (float) ($item['ending'] ?? 0),
                                    (float) ($item['sales'] ?? 0),
                                    (float) ($item['pro_out_qt'] ?? 0),
                                );
                            @endphp
                            <tr class="{{ $globalRowNumber % 2 === 0 ? 'row-even' : 'row-odd' }}">
                                <td class="center">{{ $itemCounter }}</td>
                                <td>{{ $item['item_name'] ?? '' }}</td>
                                <td class="number nowrap">{{ number_format((float) ($item['beginning'] ?? 0), 2, '.', ',') }}</td>
                                <td class="number nowrap">{{ number_format((float) ($item['qty_pembelian'] ?? 0), 2, '.', ',') }}</td>
                                <td class="number nowrap">{{ number_format((float) ($item['prd_qt'] ?? 0), 2, '.', ',') }}</td>
                                <td class="number nowrap">{{ number_format((float) ($item['qty_penjualan'] ?? 0), 2, '.', ',') }}</td>
                                <td class="number nowrap">{{ number_format((float) ($item['pro_out_qt'] ?? 0), 2, '.', ',') }}</td>
                                <td class="number nowrap">{{ number_format((float) ($item['ending_value'] ?? 0), 2, '.', ',') }}</td>
                                <td class="number nowrap">{{ number_format((float) ($item['ending'] ?? 0), 2, '.', ',') }}</td>
                                @php
                                    $itemUomRaw = $item['uom_factors'] ?? '';
                                    $itemUomNum = $itemUomRaw !== '' ? max((float) $itemUomRaw, 0) : 0;
                                    $itemEstStockDenom = (float) ($item['pro_out_qt'] ?? 0);
                                @endphp
                                <td class="number nowrap">{{ number_format((float) ($item['ending'] ?? 0) * ($itemUomNum > 0 ? $itemUomNum : 1), 1, '.', ',') }}</td>
                                <td class="number nowrap">{{ number_format((float) ($item['ending'] ?? 0) / ($itemEstStockDenom > 0 ? $itemEstStockDenom : 1), 1, '.', ',') }}</td>
                            </tr>
                        @empty
                            <tr class="empty-row">
                                <td colspan="11">Tidak ada item.</td>
                            </tr>
                        @endforelse

                        @php
                            $ft = $family['totals'];
                            $familySelisih = $svc::computeSelisih2($ft);
                            $familyHpp = $ft['ending'] / ($ft['pro_out_qt'] > 0 ? $ft['pro_out_qt'] : 1);
                        @endphp
                        <tr class="subtotal-row">
                            <td colspan="2" class="center">Total {{ $family['family_name'] }}</td>
                            <td class="number nowrap">{{ number_format($ft['beginning'], 2, '.', ',') }}</td>
                            <td class="number nowrap">{{ number_format($ft['qty_pembelian'], 2, '.', ',') }}</td>
                            <td class="number nowrap">{{ number_format($ft['prd_qt'], 2, '.', ',') }}</td>
                            <td class="number nowrap">{{ number_format($ft['qty_penjualan'], 2, '.', ',') }}</td>
                            <td class="number nowrap">{{ number_format($ft['pro_out_qt'], 2, '.', ',') }}</td>
                            <td class="number nowrap">{{ number_format($ft['ending_value'], 2, '.', ',') }}</td>
                            <td class="number nowrap">{{ number_format($ft['ending'], 2, '.', ',') }}</td>
                            <td class="number nowrap">{{ number_format($ft['ending'], 1, '.', ',') }}</td>
                            <td class="number nowrap">{{ number_format($familyHpp, 1, '.', ',') }}</td>
                        </tr>
                    @endforeach

                    @php
                        $ct = $category['totals'];
                        $catSelisih = $svc::computeSelisih2($ct);
                        $categoryHpp = $ct['ending'] / ($ct['pro_out_qt'] > 0 ? $ct['pro_out_qt'] : 1);
                    @endphp
                    <tr class="category-subtotal">
                        <td colspan="2" class="center">{{ $category['category'] }}</td>
                        <td class="number nowrap">{{ number_format($ct['beginning'], 2, '.', ',') }}</td>
                        <td class="number nowrap">{{ number_format($ct['qty_pembelian'], 2, '.', ',') }}</td>
                        <td class="number nowrap">{{ number_format($ct['prd_qt'], 2, '.', ',') }}</td>
                        <td class="number nowrap">{{ number_format($ct['qty_penjualan'], 2, '.', ',') }}</td>
                        <td class="number nowrap">{{ number_format($ct['pro_out_qt'], 2, '.', ',') }}</td>
                        <td class="number nowrap">{{ number_format($ct['ending_value'], 2, '.', ',') }}</td>
                        <td class="number nowrap">{{ number_format($ct['ending'], 2, '.', ',') }}</td>
                        <td class="number nowrap">{{ number_format($ct['ending'], 1, '.', ',') }}</td>
                        <td class="number nowrap">{{ number_format($categoryHpp, 1, '.', ',') }}</td>
                    </tr>
                @endforeach

                @php
                    $gt = $grandTotals;
                    $grandSelisih = $svc::computeSelisih2($gt);
                    $grandHpp = $gt['ending'] / ($gt['pro_out_qt'] > 0 ? $gt['pro_out_qt'] : 1);
                @endphp
                <tr class="grand-total">
                    <td colspan="2" class="center">Grand Total</td>
                    <td class="number nowrap">{{ number_format($gt['beginning'], 2, '.', ',') }}</td>
                    <td class="number nowrap">{{ number_format($gt['qty_pembelian'], 2, '.', ',') }}</td>
                    <td class="number nowrap">{{ number_format($gt['prd_qt'], 2, '.', ',') }}</td>
                    <td class="number nowrap">{{ number_format($gt['qty_penjualan'], 2, '.', ',') }}</td>
                    <td class="number nowrap">{{ number_format($gt['pro_out_qt'], 2, '.', ',') }}</td>
                    <td class="number nowrap">{{ number_format($gt['ending_value'], 2, '.', ',') }}</td>
                    <td class="number nowrap">{{ number_format($gt['ending'], 2, '.', ',') }}</td>
                    <td class="number nowrap">{{ number_format($gt['ending'], 1, '.', ',') }}</td>
                    <td class="number nowrap">{{ number_format($grandHpp, 1, '.', ',') }}</td>
                </tr>
            </tbody>
        </table>
    @else
        <table class="data-table">
            <tbody>
                <tr class="empty-row">
                    <td colspan="11">Tidak ada data.</td>
                </tr>
            </tbody>
        </table>
    @endif

    @include('ascends.shared.partials.report-footer')
</body>

</html>
