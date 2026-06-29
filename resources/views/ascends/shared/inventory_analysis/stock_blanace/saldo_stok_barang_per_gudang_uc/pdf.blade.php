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

        .wh-header {
            text-align: left;
            font-size: 12px;
            font-weight: bold;
            color: #000;
            margin: 16px 0 2px 0;
            padding: 4px 0;
        }

        .wh-header:first-of-type {
            margin-top: 0;
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
            padding: 2px 4px;
            vertical-align: middle;
            word-wrap: break-word;
        }

        .data-table th {
            font-weight: bold;
            font-size: 10px;
            text-align: center;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
        }

        .data-table td {
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

        .empty-row td {
            text-align: center;
            font-style: italic;
            font-weight: bold;
            color: #9c111d;
            font-size: 11px;
            padding: 8px 4px;
        }

        .category-header td {
            text-align: center;
            font-weight: bold;
            font-size: 11px;
            padding: 3px 4px;
            color: #9c111d;
            font-style: italic;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
        }

        .warehouse-total td {
            font-weight: bold;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            font-size: 11px;
        }

        .col-no {
            width: 6%;
        }

        .col-nama {
            width: 55%;
        }

        .col-uom {
            width: 16%;
        }

        .col-stock {
            width: 23%;
        }

        .signature-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin-top: 8px;
        }

        .signature-table td {
            border: 0;
            text-align: center;
            vertical-align: top;
            padding: 0 4px;
            line-height: 1.15;
        }

        .signature-space td {
            height: 60px;
            line-height: 60px;
            font-size: 1px;
        }

        .signature-line {
            width: 60%;
            margin: 0 auto;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .signature-line td {
            height: 1px;
            padding: 0;
            border-top: 1px solid #000;
            font-size: 1px;
            line-height: 1px;
        }
    </style>
</head>

<body>
    @php
        $warehouses = $reportData['warehouses'] ?? [];
        $warehouseTotals = $reportData['warehouse_totals'] ?? [];
        $perDate = trim((string) ($reportData['per_date'] ?? ''));
        $printedAtText = \Carbon\Carbon::parse($generatedAt ?? now())
            ->locale('id')->translatedFormat('d-M-y H:i');
        $printedByName = trim((string) ($reportData['printed_by'] ?? ''));
        $headerCompany = trim((string) ($company ?? $reportData['company'] ?? ''));
        $headerTitle = trim((string) ($title ?? $reportData['title'] ?? $fallbackTitle ?? ''));
    @endphp

    <h1 class="report-companyTitle">{{ $headerCompany }}</h1>
    <h1 class="report-title">{{ $headerTitle }}</h1>
    <p class="report-subtitle">Per Tanggal : {{ $perDate }}</p>

    @forelse ($warehouses as $whName => $categories)
        <div class="wh-header">Gudang : {{ $whName }}</div>

        <table class="data-table">
            <thead>
                <tr>
                    <th class="col-no">No</th>
                    <th class="col-nama">Nama Barang</th>
                    <th class="col-uom">UOM</th>
                    <th class="col-stock">Stock</th>
                </tr>
            </thead>
            <tbody>
                @php $globalRowNumber = 0; @endphp
                @foreach ($categories as $catName => $items)
                    <tr class="category-header">
                        <td colspan="4">Kategori : {{ $catName }}</td>
                    </tr>

                    @foreach ($items as $item)
                        @php $globalRowNumber++; @endphp
                        <tr class="{{ $globalRowNumber % 2 === 0 ? 'row-even' : 'row-odd' }}">
                            <td class="center">{{ $globalRowNumber }}</td>
                            <td>{{ $item['item_name'] ?? '' }}</td>
                            <td class="center">{{ $item['uom'] ?? '' }}</td>
                            <td class="number">{{ number_format((float) ($item['on_hand'] ?? 0), 2, '.', ',') }}</td>
                        </tr>
                    @endforeach
                @endforeach

                @php $whTotal = $warehouseTotals[$whName]['on_hand'] ?? 0; @endphp
                <tr class="warehouse-total">
                    <td colspan="3" class="center">Total {{ $whName }}</td>
                    <td class="number">{{ number_format((float) $whTotal, 2, '.', ',') }}</td>
                </tr>
            </tbody>
        </table>

        @if (!$loop->last)
            <div style="page-break-before: always;"></div>
        @endif
    @empty
        <table class="data-table">
            <tbody>
                <tr class="empty-row">
                    <td colspan="4">Tidak ada data.</td>
                </tr>
            </tbody>
        </table>
    @endforelse

    @if (count($warehouses) > 0)
        <div style="page-break-before: always;"></div>
        <div class="wh-header">Rangkuman</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th class="col-no">No</th>
                    <th class="col-nama">Nama Gudang</th>
                    <th class="col-stock">Total Stock</th>
                </tr>
            </thead>
            <tbody>
                @php $summaryNo = 0;
                $grandTotal = 0; @endphp
                @foreach ($warehouseTotals as $whName => $totals)
                    @php $summaryNo++;
                    $grandTotal += $totals['on_hand']; @endphp
                    <tr class="{{ $summaryNo % 2 === 0 ? 'row-even' : 'row-odd' }}">
                        <td class="center">{{ $summaryNo }}</td>
                        <td>{{ $whName }}</td>
                        <td class="number">{{ number_format((float) ($totals['on_hand'] ?? 0), 2, '.', ',') }}</td>
                    </tr>
                @endforeach
                <tr class="warehouse-total">
                    <td colspan="2" class="center">Grand Total</td>
                    <td class="number">{{ number_format((float) $grandTotal, 2, '.', ',') }}</td>
                </tr>
            </tbody>
        </table>
    @endif

    @php
        $medanDate = \Carbon\Carbon::parse($generatedAt ?? now())
            ->locale('id')->translatedFormat('d-M-y');
    @endphp

    <div style="text-align: right; margin-top: 24px; font-size: 11px;">
        Medan, {{ $medanDate }}
    </div>
            <table class="signature-table">
                <tr>
                    <td style="width: 33%;">Petugas Gudang</td>
                    <td style="width: 33%;">Diantar oleh</td>
                    <td style="width: 33%;">Diterima Oleh</td>
                </tr>
                <tr class="signature-space">
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
                <tr>
                    <td>
                        <table class="signature-line">
                            <tr>
                                <td>&nbsp;</td>
                            </tr>
                        </table>
                    </td>
                    <td>
                        <table class="signature-line">
                            <tr>
                                <td>&nbsp;</td>
                            </tr>
                        </table>
                    </td>
                    <td>
                        <table class="signature-line">
                            <tr>
                                <td>&nbsp;</td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>

    @include('ascends.shared.partials.report-footer')
</body>

</html>
