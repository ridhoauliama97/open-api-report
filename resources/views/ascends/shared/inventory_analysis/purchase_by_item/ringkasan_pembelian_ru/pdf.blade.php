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
        }

        .section-break {
            margin-top: 40px;
            padding-top: 20px;
            page-break-before: always;
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

        .category-header td {
            text-align: left;
            font-weight: bold;
            font-size: 10px;
            padding: 3px 4px;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
        }

        .subtotal-row td {
            font-weight: bold;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            font-size: 10px;
        }

        .grand-total td {
            font-weight: bold;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            font-size: 11px;
        }

        .number-left {
            text-align: left;
        }
    </style>
</head>

<body>
    @php
        $summary = $reportData['summary_rows'] ?? [];
        $detailGroups = $reportData['detail_groups'] ?? [];
        $grandTotal = (float) ($reportData['grand_total'] ?? 0);
        $generatedAtText = \Carbon\Carbon::parse($generatedAt ?? now())
            ->locale('id')->translatedFormat('d-M-y H:i');
        $generatedByName = trim((string) ($reportData['printed_by'] ?? ''));
        $headerCompany = trim((string) ($company ?? $reportData['company'] ?? ''));
        $headerSubtitle = trim((string) ($reportData['period_label'] ?? ''));
        $catKeys = ['BAHAN BAKU', 'BAHAN PEMBANTU', 'WIP'];

        $fmt = function ($v) {
            return $v != 0 ? number_format($v, 2, ',', '.') : '-'; };
    @endphp

    {{-- ===== SECTION 1: SUMMARY (REKAP) ===== --}}
    <h1 class="report-companyTitle">{{ $headerCompany }}</h1>
    <h1 class="report-title">{{ $title ?? $reportData['title_rekap'] }}</h1>
    <p class="report-subtitle">{{ $headerSubtitle }}</p>

    @if (count($summary['rows'] ?? []) > 0)
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 12%">Bulan</th>
                    <th style="width: 22%">BAHAN BAKU</th>
                    <th style="width: 22%">BAHAN PEMBANTU</th>
                    <th style="width: 22%">WIP</th>
                    <th style="width: 22%">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($summary['rows'] as $row)
                    <tr class="{{ $loop->index % 2 === 0 ? 'row-even' : 'row-odd' }}">
                        <td class="center">{{ $row['month_label'] }}</td>
                        @foreach ($catKeys as $ck)
                            <td class="number nowrap">{{ $fmt($row['totals'][$ck] ?? 0) }}</td>
                        @endforeach
                        <td class="number nowrap">{{ $fmt($row['row_total']) }}</td>
                    </tr>
                @endforeach

                <tr class="grand-total">
                    <td class="center">Total</td>
                    @foreach ($catKeys as $ck)
                        <td class="number nowrap">{{ $fmt($summary['grand_totals'][$ck] ?? 0) }}</td>
                    @endforeach
                    <td class="number nowrap">{{ $fmt($summary['grand_total_all']) }}</td>
                </tr>
            </tbody>
        </table>
    @else
        <table class="data-table">
            <tbody>
                <tr class="empty-row">
                    <td colspan="5">Tidak ada data rekap.</td>
                </tr>
            </tbody>
        </table>
    @endif

    {{-- ===== SECTION 2: DETAIL PER KATEGORI (RINGKASAN PEMBELIAN) ===== --}}
    @php $detailGroups = $reportData['detail_groups'] ?? []; @endphp

    @foreach ($detailGroups as $gi => $group)
        @if (count($group['rows']) > 0)
            <div class="{{ $gi > 0 ? 'section-break' : 'section-break' }}">
                <h1 class="report-companyTitle">{{ $headerCompany }}</h1>
                <h1 class="report-title">{{ $reportData['title_ringkasan'] }}</h1>
                <p class="report-subtitle">{{ $headerSubtitle }}</p>

                <p
                    style="text-align:left;font-weight:bold;font-size:12px;margin:12px 0 6px 0;">
                    {{ $group['category_name'] }}
                </p>

                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width: 12%">Tanggal</th>
                            <th style="width: 18%">Nama Supplier</th>
                            <th style="width: 28%">Nama Barang</th>
                            <th style="width: 16%">Qty</th>
                            <th style="width: 26%">Nilai</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($group['rows'] as $row)
                            <tr class="{{ $loop->index % 2 === 0 ? 'row-even' : 'row-odd' }}">
                                <td class="center nowrap">
                                    {{ $row['purchase_date'] ? $row['purchase_date']->locale('id')->isoFormat('DD-MMM-YY') : '-' }}
                                </td>
                                <td>{{ $row['supplier_name'] }}</td>
                                <td>{{ $row['item_name'] }}</td>
                                @php $isBb = $group['category_key'] === 'BAHAN BAKU'; @endphp
                                @if ($isBb && (float) ($row['ton'] ?? 0) != 0)
                                    <td class="number-left nowrap">{{ number_format((float) $row['ton'], 4, ',', '.') }} TON</td>
                                @elseif ($isBb && (float) ($row['qty'] ?? 0) != 0)
                                    <td class="number nowrap">{{ number_format((float) $row['qty'], 1, ',', '.') }} KG</td>
                                @elseif (!$isBb && (float) ($row['quantity'] ?? 0) != 0)
                                    <td class="number nowrap">{{ number_format((float) $row['quantity'], 1, ',', '.') }}
                                        {{ $row['uom'] }}</td>
                                @else
                                    <td class="number nowrap">-</td>
                                @endif
                                <td class="number nowrap">{{ $fmt($row['ru_total']) }}</td>
                            </tr>
                        @endforeach

                        <tr class="subtotal-row">
                            <td class="center" colspan="3">Total {{ $group['category_name'] }}</td>
                            <td class="number nowrap">
                                @php $isBb = $group['category_key'] === 'BAHAN BAKU'; @endphp
                                @if ($isBb)
                                    @php
                                        $qtyParts = [];
                                        if ($group['total_ton'] > 0) {
                                            $qtyParts[] = number_format($group['total_ton'], 4, ',', '.') . ' TON';
                                        }
                                        if ($group['total_qty'] > 0) {
                                            $qtyParts[] = number_format($group['total_qty'], 1, ',', '.') . ' KG';
                                        }
                                    @endphp
                                    {{ !empty($qtyParts) ? implode(' / ', $qtyParts) : '-' }}
                                @else
                                    {{ $group['total_qty'] > 0 ? number_format($group['total_qty'], 1, ',', '.') : '-' }}
                                @endif
                            </td>
                            <td class="number nowrap">{{ $fmt($group['total']) }}</td>
                        </tr>

                        @if ($loop->last)
                            <tr class="grand-total">
                                <td class="center" colspan="3">Grand Total</td>
                                <td class="number nowrap">
                                    @php
                                        $allTon = 0;
                                        $allKg = 0;
                                        foreach ($detailGroups as $g) {
                                            $allTon += $g['total_ton'];
                                            $allKg += $g['total_qty'];
                                        }
                                        $qtyParts = [];
                                        if ($allTon > 0) {
                                            $qtyParts[] = number_format($allTon, 1, ',', '.') . ' TON';
                                        }
                                        if ($allKg > 0) {
                                            $qtyParts[] = number_format($allKg, 1, ',', '.') . ' KG';
                                        }
                                    @endphp
                                    {{ !empty($qtyParts) ? implode(' / ', $qtyParts) : '-' }}
                                </td>
                                <td class="number nowrap">{{ $fmt($grandTotal) }}</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        @endif
    @endforeach

    @include('ascends.shared.partials.report-footer')
</body>

</html>
