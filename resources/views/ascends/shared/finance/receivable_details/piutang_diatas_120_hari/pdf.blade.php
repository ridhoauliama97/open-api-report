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
            padding: 2px 2px;
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

        .data-table td {
            font-size: 10px;
            border-top: none;
            border-bottom: none;
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

        .number-negative {
            color: #9c111d;
        }

        .row-odd td {
            background: #c9d1df;
        }

        .row-even td {
            background: #eef2f8;
        }

        .customer-total td {
            font-weight: bold;
            font-size: 10px;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding: 3px 4px;
        }

        .grand-total td {
            font-weight: bold;
            font-size: 10px;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding: 3px 4px;
        }

        .rasio-row td {
            font-size: 10px;
            border-top: none;
            border-bottom: 1px solid #000;
            padding: 2px 4px;
            font-style: italic;
        }

        .section-title {
            font-weight: bold;
            font-size: 12px;
            text-align: center;
            margin: 20px 0 8px 0;
        }

        .salesman-total td {
            font-weight: bold;
            font-size: 10px;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding: 3px 4px;
        }

        .empty-row td {
            text-align: center;
            font-style: italic;
            background: #c9d1df;
            font-weight: bold;
            color: #9c111d;
            font-size: 11px;
            padding: 8px 4px;
        }

        .col-customer {
            width: 17%;
        }

        .col-invoice {
            width: 13%;
        }

        .col-umur {
            width: 6%;
        }

        .col-amount {
            width: 10%;
        }

        .col-saldo {
            width: 14%;
        }

        .col-salesman {
            width: 20%;
        }

        .col-totalcust {
            width: 7%;
        }

        .col-samount {
            width: 11%;
        }

        .col-stotal {
            width: 18%;
        }
    </style>
</head>

<body>
    @php
        $detailItems = $reportData['detail_items'] ?? [];
        $customerTotals = $reportData['customer_totals'] ?? [];
        $grand120_240 = (float) ($reportData['grand_total_120_240'] ?? 0);
        $grand241_360 = (float) ($reportData['grand_total_241_360'] ?? 0);
        $grand361_480 = (float) ($reportData['grand_total_361_480'] ?? 0);
        $grand481_600 = (float) ($reportData['grand_total_481_600'] ?? 0);
        $grandOver600 = (float) ($reportData['grand_total_over_600'] ?? 0);
        $grandSaldo = (float) ($reportData['grand_total_saldo'] ?? 0);
        $rasio = $reportData['rasio'] ?? [];
        $salesmanSummary = $reportData['salesman_summary'] ?? [];
        $grandSalesman120_240 = (float) ($reportData['grand_salesman_120_240'] ?? 0);
        $grandSalesman241_360 = (float) ($reportData['grand_salesman_241_360'] ?? 0);
        $grandSalesman361_480 = (float) ($reportData['grand_salesman_361_480'] ?? 0);
        $grandSalesman481_600 = (float) ($reportData['grand_salesman_481_600'] ?? 0);
        $grandSalesmanOver600 = (float) ($reportData['grand_salesman_over_600'] ?? 0);
        $grandSalesmanTotal = (float) ($reportData['grand_salesman_total'] ?? 0);
        $headerCompany = trim((string) ($company ?? ($reportData['company'] ?? '')));
        $headerTitle = trim((string) ($title ?? ($reportData['title'] ?? ($fallbackTitle ?? ''))));
        $headerSubtitle = trim((string) ($reportData['period_label'] ?? ''));
        $generatedByName = trim((string) ($reportData['printed_by'] ?? ''));

        function fmtAmount($value)
        {
            $v = (float) $value;
            if ($v < 0) {
                return '- ' . number_format(abs($v), 0, ',', '.');
            }
            if ($v == 0.0) {
                return '-';
            }
            return number_format($v, 0, ',', '.');
        }

        function fmtUmur($value)
        {
            $v = (int) $value;
            return number_format($v, 0, ',', '.');
        }

        function fmtRasio($value)
        {
            if ($value === null) {
                return '-';
            }
            return '(' . number_format($value, 1, ',', '.') . '%)';
        }

        $globalRow = 0;
    @endphp

    <h1 class="report-companyTitle">{{ $headerCompany }}</h1>
    <h1 class="report-title">{{ $headerTitle }}</h1>
    <p class="report-subtitle">{{ $headerSubtitle }}</p>

    @if (count($detailItems) > 0)
        {{-- Table 1: Detail per Customer --}}
        <table class="data-table">
            <colgroup>
                <col class="col-customer">
                <col class="col-invoice">
                <col class="col-umur">
                <col class="col-amount">
                <col class="col-amount">
                <col class="col-amount">
                <col class="col-amount">
                <col class="col-amount">
                <col class="col-saldo">
            </colgroup>
            <thead>
                <tr>
                    <th>Nama Pelanggan</th>
                    <th>No. Invoice</th>
                    <th>Umur</th>
                    <th>120-240 Hari</th>
                    <th>241-360 Hari</th>
                    <th>361-480 Hari</th>
                    <th>481-600 Hari</th>
                    <th>&gt; 600 Hari</th>
                    <th>Saldo Piutang</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($customerTotals as $customer)
                    @php
                        $custItems = array_values(
                            array_filter($detailItems, function ($i) use ($customer) {
                                return $i['customer_name'] === $customer['customer_name'];
                            }),
                        );
                    @endphp

                    @foreach ($custItems as $itemIndex => $item)
                        @php $globalRow++; @endphp
                        <tr class="{{ $globalRow % 2 === 0 ? 'row-even' : 'row-odd' }}">
                            <td>{{ $itemIndex === 0 ? $item['customer_name'] ?? '' : '' }}</td>
                            <td>{{ $item['item_ref'] ?? '' }}</td>
                            <td class="center">{{ fmtUmur($item['umur'] ?? 0) }}</td>
                            <td class="number nowrap {{ ($item['bucket_120_240'] ?? 0) < 0 ? 'number-negative' : '' }}">
                                {{ fmtAmount($item['bucket_120_240'] ?? 0) }}</td>
                            <td class="number nowrap {{ ($item['bucket_241_360'] ?? 0) < 0 ? 'number-negative' : '' }}">
                                {{ fmtAmount($item['bucket_241_360'] ?? 0) }}</td>
                            <td
                                class="number nowrap {{ ($item['bucket_361_480'] ?? 0) < 0 ? 'number-negative' : '' }}">
                                {{ fmtAmount($item['bucket_361_480'] ?? 0) }}</td>
                            <td
                                class="number nowrap {{ ($item['bucket_481_600'] ?? 0) < 0 ? 'number-negative' : '' }}">
                                {{ fmtAmount($item['bucket_481_600'] ?? 0) }}</td>
                            <td
                                class="number nowrap {{ ($item['bucket_over_600'] ?? 0) < 0 ? 'number-negative' : '' }}">
                                {{ fmtAmount($item['bucket_over_600'] ?? 0) }}</td>
                            <td class="number nowrap {{ ($item['saldo'] ?? 0) < 0 ? 'number-negative' : '' }}">
                                {{ fmtAmount($item['saldo'] ?? 0) }}</td>
                        </tr>
                    @endforeach

                    <tr class="customer-total">
                        <td colspan="3">TOTAL</td>
                        <td class="number nowrap {{ ($customer['total_120_240'] ?? 0) < 0 ? 'number-negative' : '' }}">
                            {{ fmtAmount($customer['total_120_240'] ?? 0) }}</td>
                        <td class="number nowrap {{ ($customer['total_241_360'] ?? 0) < 0 ? 'number-negative' : '' }}">
                            {{ fmtAmount($customer['total_241_360'] ?? 0) }}</td>
                        <td class="number nowrap {{ ($customer['total_361_480'] ?? 0) < 0 ? 'number-negative' : '' }}">
                            {{ fmtAmount($customer['total_361_480'] ?? 0) }}</td>
                        <td class="number nowrap {{ ($customer['total_481_600'] ?? 0) < 0 ? 'number-negative' : '' }}">
                            {{ fmtAmount($customer['total_481_600'] ?? 0) }}</td>
                        <td
                            class="number nowrap {{ ($customer['total_over_600'] ?? 0) < 0 ? 'number-negative' : '' }}">
                            {{ fmtAmount($customer['total_over_600'] ?? 0) }}</td>
                        <td class="number nowrap {{ ($customer['total_saldo'] ?? 0) < 0 ? 'number-negative' : '' }}">
                            {{ fmtAmount($customer['total_saldo'] ?? 0) }}</td>
                    </tr>
                @endforeach

                {{-- Grand Total --}}
                <tr class="grand-total">
                    <td colspan="3" style="text-align: center;">GRAND TOTAL </td>
                    <td class="number nowrap {{ $grand120_240 < 0 ? 'number-negative' : '' }}">
                        {{ fmtAmount($grand120_240) }}</td>
                    <td class="number nowrap {{ $grand241_360 < 0 ? 'number-negative' : '' }}">
                        {{ fmtAmount($grand241_360) }}</td>
                    <td class="number nowrap {{ $grand361_480 < 0 ? 'number-negative' : '' }}">
                        {{ fmtAmount($grand361_480) }}</td>
                    <td class="number nowrap {{ $grand481_600 < 0 ? 'number-negative' : '' }}">
                        {{ fmtAmount($grand481_600) }}</td>
                    <td class="number nowrap {{ $grandOver600 < 0 ? 'number-negative' : '' }}">
                        {{ fmtAmount($grandOver600) }}</td>
                    <td class="number nowrap {{ $grandSaldo < 0 ? 'number-negative' : '' }}">
                        {{ fmtAmount($grandSaldo) }}</td>
                </tr>

                {{-- Rasio --}}
                <tr class="rasio-row">
                    <td colspan="3" style="text-align: center;">Rasio :</td>
                    <td class="number nowrap">{{ fmtRasio($rasio[0] ?? null) }}</td>
                    <td class="number nowrap">{{ fmtRasio($rasio[1] ?? null) }}</td>
                    <td class="number nowrap">{{ fmtRasio($rasio[2] ?? null) }}</td>
                    <td class="number nowrap">{{ fmtRasio($rasio[3] ?? null) }}</td>
                    <td class="number nowrap">{{ fmtRasio($rasio[4] ?? null) }}</td>
                    <td></td>
                </tr>
            </tbody>
        </table>

        {{-- Table 2: Rincian Per Salesman --}}
        <div class="section-title">Rincian Umur Piutang Per Salesman</div>

        <table class="data-table salesman-table">
            <colgroup>
                <col class="col-salesman">
                <col class="col-totalcust">
                <col class="col-samount">
                <col class="col-samount">
                <col class="col-samount">
                <col class="col-samount">
                <col class="col-samount">
                <col class="col-stotal">
            </colgroup>
            <thead>
                <tr>
                    <th>Nama Salesman</th>
                    <th>Total Customer</th>
                    <th>120-240 Hari</th>
                    <th>241-360 Hari</th>
                    <th>361-480 Hari</th>
                    <th>481-600 Hari</th>
                    <th>&gt; 600 Hari</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($salesmanSummary as $sm)
                    <tr class="{{ $loop->odd ? 'row-odd' : 'row-even' }}">
                        <td>{{ $sm['salesman_name'] ?? '' }}</td>
                        <td class="center">{{ $sm['total_cust'] ?? 0 }}</td>
                        <td class="number nowrap {{ ($sm['total_120_240'] ?? 0) < 0 ? 'number-negative' : '' }}">
                            {{ fmtAmount($sm['total_120_240'] ?? 0) }}</td>
                        <td class="number nowrap {{ ($sm['total_241_360'] ?? 0) < 0 ? 'number-negative' : '' }}">
                            {{ fmtAmount($sm['total_241_360'] ?? 0) }}</td>
                        <td class="number nowrap {{ ($sm['total_361_480'] ?? 0) < 0 ? 'number-negative' : '' }}">
                            {{ fmtAmount($sm['total_361_480'] ?? 0) }}</td>
                        <td class="number nowrap {{ ($sm['total_481_600'] ?? 0) < 0 ? 'number-negative' : '' }}">
                            {{ fmtAmount($sm['total_481_600'] ?? 0) }}</td>
                        <td class="number nowrap {{ ($sm['total_over_600'] ?? 0) < 0 ? 'number-negative' : '' }}">
                            {{ fmtAmount($sm['total_over_600'] ?? 0) }}</td>
                        <td class="number nowrap {{ ($sm['total_all'] ?? 0) < 0 ? 'number-negative' : '' }}">
                            {{ fmtAmount($sm['total_all'] ?? 0) }}</td>
                    </tr>
                @empty
                    <tr class="empty-row">
                        <td colspan="8">Tidak ada data.</td>
                    </tr>
                @endforelse

                <tr class="salesman-total">
                    <td colspan="2" class="center">TOTAL</td>
                    <td class="number nowrap {{ $grandSalesman120_240 < 0 ? 'number-negative' : '' }}">
                        {{ fmtAmount($grandSalesman120_240) }}</td>
                    <td class="number nowrap {{ $grandSalesman241_360 < 0 ? 'number-negative' : '' }}">
                        {{ fmtAmount($grandSalesman241_360) }}</td>
                    <td class="number nowrap {{ $grandSalesman361_480 < 0 ? 'number-negative' : '' }}">
                        {{ fmtAmount($grandSalesman361_480) }}</td>
                    <td class="number nowrap {{ $grandSalesman481_600 < 0 ? 'number-negative' : '' }}">
                        {{ fmtAmount($grandSalesman481_600) }}</td>
                    <td class="number nowrap {{ $grandSalesmanOver600 < 0 ? 'number-negative' : '' }}">
                        {{ fmtAmount($grandSalesmanOver600) }}</td>
                    <td class="number nowrap {{ $grandSalesmanTotal < 0 ? 'number-negative' : '' }}">
                        {{ fmtAmount($grandSalesmanTotal) }}</td>
                </tr>
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
