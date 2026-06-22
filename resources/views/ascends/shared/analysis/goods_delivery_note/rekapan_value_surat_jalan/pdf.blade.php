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
            border-bottom: 1px solid #000;
            text-align: center;
        }

        .data-table th::first {
            border-bottom: none !important;
        }

        .group-row td {
            font-weight: bold;
            text-align: center;
            font-size: 10px;
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

        .empty-row td {
            text-align: center;
            font-style: italic;
            background: #c9d1df;
            font-weight: bold;
            font-size: 11px;
            color: #9c111d;
        }

        .number {
            text-align: right;
        }

        .nowrap {
            white-space: nowrap;
        }

        .subtotal-row td {
            font-weight: bold;
            border-top: 1px solid #000;
            font-size: 11px;
        }

        .grand-total-row td {
            font-weight: bold;
            border-top: 1px solid #000;
            font-size: 12px;
        }
    </style>
</head>

<body>
    @php
        $customerGroups = $reportData['customer_groups'] ?? [];
        $grandTotalQty = (float) ($reportData['grand_total_qty'] ?? 0);
        $grandTotalTotal = (float) ($reportData['grand_total_total'] ?? 0);
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

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 7%">No</th>
                <th style="width: 26%">Nama Customer</th>
                <th style="width: 22%">No Surat Jalan</th>
                <th style="width: 18%">Jumlah</th>
                <th style="width: 27%">Line Total</th>
            </tr>
        </thead>
        <tbody>
            @php $rowNumber = 0; @endphp
            @forelse ($customerGroups as $customerIdx => $group)
                <tr class="group-row">
                    <td colspan="5">{{ $group['customer_name'] }}</td>
                </tr>
                @foreach ($group['items'] as $item)
                    @php $rowNumber++; @endphp
                    <tr class="{{ $rowNumber % 2 === 0 ? 'row-even' : 'row-odd' }}">
                        <td class="center">{{ $rowNumber }}</td>
                        <td>{{ $group['customer_name'] }}</td>
                        <td class="center nowrap">{{ $item['gdn_number'] ?? '' }}</td>
                        <td class="number nowrap">{{ number_format((float) ($item['qty'] ?? 0), 0, '.', ',') }}
                            {{ $item['uom'] ?? '' }}</td>
                        <td class="number nowrap">{{ number_format((float) ($item['total'] ?? 0), 2, '.', ',') }}</td>
                    </tr>
                @endforeach
                <tr class="subtotal-row">
                    <td colspan="3" class="center">Sub Total </td>
                    <td class="number nowrap">{{ number_format((float) ($group['subtotal_qty'] ?? 0), 0, '.', ',') }}</td>
                    <td class="number nowrap">{{ number_format((float) ($group['subtotal_total'] ?? 0), 2, '.', ',') }}</td>
                </tr>
            @empty
                <tr class="empty-row">
                    <td colspan="5">Tidak ada data.</td>
                </tr>
            @endforelse

            @if (!empty($customerGroups))
                <tr class="grand-total-row">
                    <td colspan="3" class="center">Grand Total</td>
                    <td class="number nowrap">{{ number_format($grandTotalQty, 0, '.', ',') }}</td>
                    <td class="number nowrap">{{ number_format($grandTotalTotal, 2, '.', ',') }}</td>
                </tr>
            @endif
        </tbody>
    </table>

    @include('ascends.shared.partials.report-footer')
</body>

</html>
