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
            font-size: 9px;
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

        .section-header {
            margin: 16px 0 6px 0;
            font-weight: bold;
            font-style: italic;
            font-size: 11px;
            color: #9c111d;
        }

        .sub-section-header {
            margin: 10px 0 4px 0;
            font-weight: bold;
            font-size: 10px;
            color: #9c111d;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            page-break-inside: auto;
            border-spacing: 0;
            border: 1px solid #000;
            margin-bottom: 12px;
        }

        .data-table th,
        .data-table td {
            border-left: 1px solid #000;
            border-right: 1px solid #000;
            padding: 2px 3px;
            vertical-align: middle;
            word-wrap: break-word;
        }

        .data-table th {
            font-weight: bold;
            font-size: 8px;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            text-align: center;
        }

        .center {
            text-align: center;
        }

        .number {
            text-align: right;
            white-space: nowrap;
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

        .subtotal-row td {
            font-weight: bold;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
        }

        .empty-row td {
            text-align: center;
            font-style: italic;
            background: #c9d1df;
            font-weight: bold;
            color: #9c111d;
            font-size: 11px;
        }
    </style>
</head>

<body>
    @php
        $groups = $reportData['groups'] ?? [];
        $headerCompany = trim((string) ($company ?? ($reportData['company'] ?? '')));
        $headerTitle = trim((string) ($title ?? ($reportData['title'] ?? ($fallbackTitle ?? ''))));
        $headerSubtitle = trim((string) ($reportData['period_label'] ?? ''));
    @endphp

    <h1 class="report-companyTitle">{{ $headerCompany }}</h1>
    <h1 class="report-title">{{ $headerTitle }}</h1>
    <p class="report-subtitle">{{ $headerSubtitle }}</p>

    @forelse ($groups as $group)
        <div class="section-header">{{ $group['category'] }}</div>

        @foreach ($group['statuses'] as $statusGroup)
            <div class="sub-section-header">{{ $statusGroup['status'] }}</div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 3%">No</th>
                        <th style="width: 6%">Tgl PO</th>
                        <th style="width: 10%">No PO</th>
                        <th style="width: 20%">Item Name</th>
                        <th style="width: 9%">Supplier</th>
                        <th style="width: 4%">Qty</th>
                        <th style="width: 8%">Harga PO</th>
                        <th style="width: 8%">Last Price 1</th>
                        <th style="width: 6%">Tgl 1</th>
                        <th style="width: 8%">Last Price 2</th>
                        <th style="width: 6%">Tgl 2</th>
                        <th style="width: 8%">Last Price 3</th>
                        <th style="width: 4%">Tgl 3</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($statusGroup['rows'] as $row)
                        <tr class="{{ $loop->odd ? 'row-odd' : 'row-even' }}">
                            <td class="center">{{ $row['no'] }}</td>
                            <td class="center nowrap">{{ $row['order_date'] }}</td>
                            <td class="center nowrap">{{ $row['order_number'] }}</td>
                            <td>{{ $row['item_name'] }}</td>
                            <td>{{ $row['supplier_name'] }}</td>
                            <td class="number">{{ $row['qty_ordered'] }}</td>
                            <td class="number">{{ $row['unit_cost'] }}</td>
                            <td class="number">{{ $row['last_price_1'] }}</td>
                            <td class="center nowrap">{{ $row['last_price_1_date'] }}</td>
                            <td class="number">{{ $row['last_price_2'] }}</td>
                            <td class="center nowrap">{{ $row['last_price_2_date'] }}</td>
                            <td class="number">{{ $row['last_price_3'] }}</td>
                            <td class="center nowrap">{{ $row['last_price_3_date'] }}</td>
                        </tr>
                    @endforeach
                    <tr class="subtotal-row">
                        <td colspan="13">Total {{ $statusGroup['status'] }}: {{ count($statusGroup['rows']) }}</td>
                    </tr>
                </tbody>
            </table>
        @endforeach
    @empty
        <table class="data-table">
            <tbody>
                <tr class="empty-row">
                    <td>Tidak ada data.</td>
                </tr>
            </tbody>
        </table>
    @endforelse

    @include('ascends.shared.partials.report-footer')
</body>

</html>
