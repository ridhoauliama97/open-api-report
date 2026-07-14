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

        .salesman-total td {
            font-weight: bold;
            font-size: 10px;
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
            width: 18%;
        }

        .col-date {
            width: 12%;
        }

        .col-ref {
            width: 14%;
        }

        .col-amount {
            width: 14%;
        }
    </style>
</head>

<body>
    @php
        $salesmenGroups = $reportData['salesmen_groups'] ?? [];
        $grandTotal60_75 = (float) ($reportData['grand_total_60_75'] ?? 0);
        $grandTotal76_90 = (float) ($reportData['grand_total_76_90'] ?? 0);
        $grandTotal91_120 = (float) ($reportData['grand_total_91_120'] ?? 0);
        $grandTotal120 = (float) ($reportData['grand_total_120'] ?? 0);
        $headerCompany = trim((string) ($company ?? ($reportData['company'] ?? '')));
        $headerTitle = trim((string) ($title ?? ($reportData['title'] ?? ($fallbackTitle ?? ''))));
        $headerSubtitle = trim((string) ($reportData['period_label'] ?? ''));
        $generatedByName = trim((string) ($reportData['printed_by'] ?? ''));

        function fmtAmount($value)
        {
            $v = (float) $value;
            if ($v < 0) {
                return '(' . number_format(abs($v), 0, '.', ',') . ')';
            }
            if ($v == 0.0) {
                return '-';
            }
            return number_format($v, 0, '.', ',');
        }

        function fmtDate($value)
        {
            if ($value === '' || $value === null) {
                return '';
            }
            try {
                return \Carbon\Carbon::parse($value)->locale('id')->isoFormat('DD-MMM-YY');
            } catch (\Throwable) {
                return $value;
            }
        }

        $globalRow = 0;
    @endphp

    <h1 class="report-companyTitle">{{ $headerCompany }}</h1>
    <h1 class="report-title">{{ $headerTitle }}</h1>
    <p class="report-subtitle">{{ $headerSubtitle }}</p>

    @if (count($salesmenGroups) > 0)
        <table class="data-table">
            <colgroup>
                <col class="col-customer">
                <col class="col-date">
                <col class="col-ref">
                <col class="col-amount">
                <col class="col-amount">
                <col class="col-amount">
                <col class="col-amount">
            </colgroup>
            <thead>
                <tr>
                    <th>Nama Pelanggan</th>
                    <th>Tgl Invoice</th>
                    <th>No. Ref</th>
                    <th>60 - 75 Hari</th>
                    <th>76 - 90 Hari</th>
                    <th>91 - 120 Hari</th>
                    <th>&gt; 120 Hari</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($salesmenGroups as $salesman)
                    @php $customerGroups = $salesman['customer_groups'] ?? []; @endphp

                    @foreach ($customerGroups as $customer)
                        @php $items = $customer['items'] ?? []; @endphp
                        @if (count($items) > 0)
                            @foreach ($items as $itemIndex => $item)
                                @php $globalRow++; @endphp
                                <tr class="{{ $globalRow % 2 === 0 ? 'row-even' : 'row-odd' }}">
                                    <td>{{ $itemIndex === 0 ? $customer['customer_name'] ?? '' : '' }}</td>
                                    <td class="center">{{ fmtDate($item['Item Date'] ?? '') }}</td>
                                    <td>{{ $item['Item Ref'] ?? '' }}</td>
                                    <td
                                        class="number nowrap {{ (float) ($item['bucket_60_75'] ?? 0) < 0 ? 'number-negative' : '' }}">
                                        {{ fmtAmount($item['bucket_60_75'] ?? 0) }}
                                    </td>
                                    <td
                                        class="number nowrap {{ (float) ($item['bucket_76_90'] ?? 0) < 0 ? 'number-negative' : '' }}">
                                        {{ fmtAmount($item['bucket_76_90'] ?? 0) }}
                                    </td>
                                    <td
                                        class="number nowrap {{ (float) ($item['bucket_91_120'] ?? 0) < 0 ? 'number-negative' : '' }}">
                                        {{ fmtAmount($item['bucket_91_120'] ?? 0) }}
                                    </td>
                                    <td
                                        class="number nowrap {{ (float) ($item['bucket_120'] ?? 0) < 0 ? 'number-negative' : '' }}">
                                        {{ fmtAmount($item['bucket_120'] ?? 0) }}
                                    </td>
                                </tr>
                            @endforeach

                            <tr class="customer-total">
                                <td colspan="3">TOTAL</td>
                                <td
                                    class="number nowrap {{ ($customer['total_60_75'] ?? 0) < 0 ? 'number-negative' : '' }}">
                                    {{ fmtAmount($customer['total_60_75'] ?? 0) }}
                                </td>
                                <td
                                    class="number nowrap {{ ($customer['total_76_90'] ?? 0) < 0 ? 'number-negative' : '' }}">
                                    {{ fmtAmount($customer['total_76_90'] ?? 0) }}
                                </td>
                                <td
                                    class="number nowrap {{ ($customer['total_91_120'] ?? 0) < 0 ? 'number-negative' : '' }}">
                                    {{ fmtAmount($customer['total_91_120'] ?? 0) }}
                                </td>
                                <td
                                    class="number nowrap {{ ($customer['total_120'] ?? 0) < 0 ? 'number-negative' : '' }}">
                                    {{ fmtAmount($customer['total_120'] ?? 0) }}
                                </td>
                            </tr>
                        @endif
                    @endforeach

                    <tr class="salesman-total">
                        <td colspan="3" class="center">TOTAL SALESMAN {{ $salesman['salesman_name'] ?? '' }}</td>
                        <td class="number nowrap {{ ($salesman['total_60_75'] ?? 0) < 0 ? 'number-negative' : '' }}">
                            {{ fmtAmount($salesman['total_60_75'] ?? 0) }}
                        </td>
                        <td class="number nowrap {{ ($salesman['total_76_90'] ?? 0) < 0 ? 'number-negative' : '' }}">
                            {{ fmtAmount($salesman['total_76_90'] ?? 0) }}
                        </td>
                        <td class="number nowrap {{ ($salesman['total_91_120'] ?? 0) < 0 ? 'number-negative' : '' }}">
                            {{ fmtAmount($salesman['total_91_120'] ?? 0) }}
                        </td>
                        <td class="number nowrap {{ ($salesman['total_120'] ?? 0) < 0 ? 'number-negative' : '' }}">
                            {{ fmtAmount($salesman['total_120'] ?? 0) }}
                        </td>
                    </tr>
                @endforeach

                <tr class="grand-total">
                    <td colspan="3" style="text-align: center;">GRAND TOTAL</td>
                    <td class="number nowrap {{ $grandTotal60_75 < 0 ? 'number-negative' : '' }}">
                        {{ fmtAmount($grandTotal60_75) }}
                    </td>
                    <td class="number nowrap {{ $grandTotal76_90 < 0 ? 'number-negative' : '' }}">
                        {{ fmtAmount($grandTotal76_90) }}
                    </td>
                    <td class="number nowrap {{ $grandTotal91_120 < 0 ? 'number-negative' : '' }}">
                        {{ fmtAmount($grandTotal91_120) }}
                    </td>
                    <td class="number nowrap {{ $grandTotal120 < 0 ? 'number-negative' : '' }}">
                        {{ fmtAmount($grandTotal120) }}
                    </td>
                </tr>
            </tbody>
        </table>
    @else
        <table class="data-table">
            <tbody>
                <tr class="empty-row">
                    <td colspan="7">Tidak ada data.</td>
                </tr>
            </tbody>
        </table>
    @endif

    @include('ascends.shared.partials.report-footer')
</body>

</html>
