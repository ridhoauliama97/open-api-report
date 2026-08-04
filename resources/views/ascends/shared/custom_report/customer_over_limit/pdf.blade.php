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
            border-bottom: 1px solid #000;
        }

        .data-table th,
        .data-table td {
            border-left: 1px solid #000;
            border-right: 1px solid #000;
            padding: 1px 2px;
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

        .center {
            text-align: center;
        }

        .left {
            text-align: left;
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

        .grand-total-row td {
            font-size: 11px;
            font-weight: bold;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
        }

        .number-negative {
            color: #9c111d;
        }

        .empty-row td {
            text-align: center;
            font-style: italic;
            background: #c9d1df;
            color: #9c111d;
            font-weight: bold;
            font-size: 10px;
            padding: 4px;
        }
    </style>
</head>

<body>
    @php
        $rows = $reportData['rows'] ?? [];
        $totals = $reportData['totals'] ?? [];
        $totalRows = (int) ($reportData['total_rows'] ?? 0);

        $headerCompany = trim((string) ($company ?? ($reportData['company'] ?? '')));
        $headerTitle = trim((string) ($title ?? ($reportData['title'] ?? ($fallbackTitle ?? ''))));
        $headerSubtitle = trim((string) ($reportData['period_label'] ?? ''));

        function fmtAmount($value)
        {
            $v = (float) $value;
            if ($v == 0.0) {
                return '0';
            }
            $rounded = round($v);
            return number_format($rounded, 0, '.', ',');
        }

        function fmtAmountNegative($value)
        {
            return (float) $value < 0 ? 'number-negative' : '';
        }

        $globalRow = 0;
    @endphp

    <h1 class="report-companyTitle">{{ $headerCompany }}</h1>
    <h1 class="report-title">{{ $headerTitle }}</h1>
    @if ($headerSubtitle !== '')
        <p class="report-subtitle">{{ $headerSubtitle }}</p>
    @endif

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 22%;">Nama Customer</th>
                <th style="width: 12%;">Credit Limit</th>
                <th style="width: 13%;">1 - 30 Hari</th>
                <th style="width: 13%;">31 - 60 Hari</th>
                <th style="width: 13%;">61 - 90 Hari</th>
                <th style="width: 13%;"> > 90 Hari </th>
                <th style="width: 14%;">Tagihan</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                @php $globalRow++; @endphp
                <tr class="{{ $globalRow % 2 === 0 ? 'row-even' : 'row-odd' }}">
                    <td class="left">{{ $row['customer_name'] ?? '' }}</td>
                    <td class="number nowrap {{ fmtAmountNegative($row['credit_limit'] ?? 0) }}">
                        {{ fmtAmount($row['credit_limit'] ?? 0) }}</td>
                    <td class="number {{ fmtAmountNegative($row['b1_30'] ?? 0) }}">{{ fmtAmount($row['b1_30'] ?? 0) }}</td>
                    <td class="number {{ fmtAmountNegative($row['b31_60'] ?? 0) }}">{{ fmtAmount($row['b31_60'] ?? 0) }}
                    </td>
                    <td class="number {{ fmtAmountNegative($row['b61_90'] ?? 0) }}">{{ fmtAmount($row['b61_90'] ?? 0) }}
                    </td>
                    <td class="number {{ fmtAmountNegative($row['over90'] ?? 0) }}">{{ fmtAmount($row['over90'] ?? 0) }}
                    </td>
                    <td class="number {{ fmtAmountNegative($row['tagihan'] ?? 0) }}">{{ fmtAmount($row['tagihan'] ?? 0) }}
                    </td>
                </tr>
            @endforeach

            @if ($totalRows <= 0)
                <tr class="empty-row">
                    <td colspan="7">Tidak ada data customer over limit.</td>
                </tr>
            @endif
        </tbody>
        @if ($totalRows > 0)
            <tfoot>
                <tr class="grand-total-row">
                    <td class="center" colspan="2">TOTAL</td>
                    {{-- <td class="number nowrap {{ fmtAmountNegative($totals['credit_limit'] ?? 0) }}">
                        {{ fmtAmount($totals['credit_limit'] ?? 0) }}</td> --}}
                    <td class="number {{ fmtAmountNegative($totals['b1_30'] ?? 0) }}">{{ fmtAmount($totals['b1_30'] ?? 0) }}
                    </td>
                    <td class="number {{ fmtAmountNegative($totals['b31_60'] ?? 0) }}">
                        {{ fmtAmount($totals['b31_60'] ?? 0) }}</td>
                    <td class="number {{ fmtAmountNegative($totals['b61_90'] ?? 0) }}">
                        {{ fmtAmount($totals['b61_90'] ?? 0) }}</td>
                    <td class="number {{ fmtAmountNegative($totals['over90'] ?? 0) }}">
                        {{ fmtAmount($totals['over90'] ?? 0) }}</td>
                    <td class="number {{ fmtAmountNegative($totals['tagihan'] ?? 0) }}">
                        {{ fmtAmount($totals['tagihan'] ?? 0) }}</td>
                </tr>
            </tfoot>
        @endif
    </table>

    @include('ascends.shared.partials.report-footer')
</body>

</html>
