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
            page-break-inside: auto;
            border-spacing: 0;
            border: 1px solid #000;
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
            font-size: 11px;
            border-top: 1px solid #000;
            text-align: center;
        }

        .group-row td {
            font-weight: bold;
            text-align: center;
            font-size: 11px;
            font-style: italic;
            padding: 3px 4px;
            color: #9c111d;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
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

        .subtotal-row td {
            font-weight: bold;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            font-size: 11px;
        }

        .day-warning {
            color: #cc8800;
            font-weight: bold;
            font-style: italic;
        }

        .day-danger {
            color: #cc0000;
            font-weight: bold;
            font-style: italic;
        }

        .grand-total-row td {
            font-weight: bold;
            font-size: 12px;
        }

        .empty-row td {
            text-align: center;
            font-style: italic;
            background: #c9d1df;
            font-weight: bold;
            color: #9c111d;
            font-size: 11px;
        }

        .number {
            text-align: right;
        }

        .nowrap {
            white-space: nowrap;
        }
    </style>
</head>

<body>
    @php
        $rows = $reportData['rows'] ?? [];
        $grandTotals = $reportData['grand_totals'] ?? [];
        $printedAt = $reportData['printed_at'] ?? '';
        $generatedAtText = \Carbon\Carbon::parse($generatedAt ?? now())
            ->locale('id')->translatedFormat('d-M-y H:i');
        $generatedByName = trim((string) ($reportData['printed_by'] ?? ''));
        $headerCompany = trim((string) ($company ?? $reportData['company'] ?? ''));
        $headerTitle = trim((string) ($title ?? $reportData['title'] ?? $fallbackTitle ?? ''));
        $headerSubtitle = trim((string) ($reportData['period_label'] ?? ''));
    @endphp

    <h1 class="report-companyTitle">{{ $headerCompany }}</h1>
    <h1 class="report-title">{{ $headerTitle }}</h1>
    <p class="report-subtitle">{{ $headerSubtitle }}</p>

    @php $globalRowNumber = 0; @endphp

    @if (count($rows) > 0)
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 5%">No</th>
                    <th style="width: 18%">Customer</th>
                    <th style="width: 10%">Sales</th>
                    <th style="width: 7%">Bln</th>
                    <th style="width: 9%">Thn</th>
                    <th style="width: 8%">Hari</th>
                    <th style="width: 14%">Total Pembelian</th>
                    <th style="width: 14%">Belum Terkirim</th>
                    <th style="width: 15%">Terkirim</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $group)
                    <tr class="group-row">
                        <td colspan="9">Nama Barang : {{ $group['item_name'] ?: '(tanpa nama)' }}</td>
                    </tr>

                    @foreach ($group['rows'] as $detail)
                        @php $globalRowNumber++; @endphp
                        <tr class="{{ $globalRowNumber % 2 === 0 ? 'row-even' : 'row-odd' }}">
                            <td class="center">{{ $globalRowNumber }}</td>
                            <td>{{ $detail['customer'] ?? '' }}</td>
                            <td>{{ $detail['sales_person'] ?? '' }}</td>
                            <td class="center">{{ $detail['invoice_date'] ? $detail['invoice_date']->format('m') : '' }}</td>
                            <td class="center">{{ $detail['invoice_date'] ? $detail['invoice_date']->format('Y') : '' }}</td>
                            @php $daysVal = (int) ($detail['days'] ?? 0); $dayClass = $daysVal > 90 ? 'day-danger' : ($daysVal > 60 ? 'day-warning' : ''); @endphp
                            <td class="center {{ $dayClass }}">{{ $daysVal ?: '' }}</td>
                            <td class="number nowrap">{{ number_format((float) ($detail['qty_purchased'] ?? 0), 0, '.', ',') }} {{ $detail['uom'] ?? '' }}</td>
                            <td class="number nowrap">{{ number_format((float) ($detail['qty_outstanding'] ?? 0), 0, '.', ',') }} {{ $detail['uom'] ?? '' }}</td>
                            <td class="number nowrap">{{ number_format((float) ($detail['qty_delivered'] ?? 0), 0, '.', ',') }} {{ $detail['uom'] ?? '' }}</td>
                        </tr>
                    @endforeach

                    <tr class="subtotal-row">
                        <td colspan="6" class="center">Sub Total</td>
                        <td class="number nowrap">{{ number_format((float) ($group['total_purchased'] ?? 0), 0, '.', ',') }} {{ $detail['uom'] ?? '' }}</td>
                        <td class="number nowrap">{{ number_format((float) ($group['total_outstanding'] ?? 0), 0, '.', ',') }} {{ $detail['uom'] ?? '' }}</td>
                        <td class="number nowrap">{{ number_format((float) ($group['total_delivered'] ?? 0), 0, '.', ',') }} {{ $detail['uom'] ?? '' }}</td>
                    </tr>
                @endforeach

                @if (!empty($grandTotals))
                    <tr class="grand-total-row">
                        <td colspan="6" class="center">Grand Total</td>
                        <td class="number nowrap">{{ number_format((float) ($grandTotals['qty_purchased'] ?? 0), 0, '.', ',') }}</td>
                        <td class="number nowrap">{{ number_format((float) ($grandTotals['qty_outstanding'] ?? 0), 0, '.', ',') }}</td>
                        <td class="number nowrap">{{ number_format((float) ($grandTotals['qty_delivered'] ?? 0), 0, '.', ',') }}</td>
                    </tr>
                @endif
            </tbody>
        </table>
    @else
        <table class="data-table">
            <tbody>
                <tr class="empty-row">
                    <td colspan="9">Tidak ada data.</td>
                </tr>
            </tbody>
        </table>
    @endif

    @include('ascends.shared.partials.report-footer')
</body>

</html>
