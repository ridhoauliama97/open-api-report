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
            border-bottom: 1px solid #000;
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
            font-size: 9px;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            text-align: center;
        }

        .sub-kolom {
            font-size: 9px;
            font-weight: bold;
        }

        .sub-kolom th {
            border-top: none;
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

        .grand-total-row td {
            font-weight: bold;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
        }

        .empty-row td {
            text-align: center;
            font-style: italic;
            background: #c9d1df;
            color: #9c111d;
            font-weight: bold;
            font-size: 10px;
            padding: 5px 4px;
        }
    </style>
</head>

<body>
    @php
        $customers = $reportData['customers'] ?? [];
        $periods = $reportData['periods'] ?? [];
        $periodTotals = $reportData['period_totals'] ?? [];
        $grandMin = $reportData['grand_min'] ?? 0;
        $grandMax = $reportData['grand_max'] ?? 0;
        $grandAvg = $reportData['grand_avg'] ?? 0;
        $grandTotalSum = $reportData['grand_total_sum'] ?? 0;

        $headerCompany = trim((string) ($company ?? ($reportData['company'] ?? '')));
        $headerTitle = trim((string) ($title ?? ($reportData['title'] ?? ($fallbackTitle ?? ''))));
        $periodLabel = trim((string) ($reportData['period_label'] ?? ''));

        function fmtAmt($value)
        {
            $v = (float) $value;
            if ($v == 0.0) {
                return '0';
            }
            return number_format($v, 0, '.', ',');
        }
    @endphp

    <h1 class="report-companyTitle">{{ $headerCompany !== '' ? $headerCompany : 'GSU' }}</h1>
    <h1 class="report-title">{{ $headerTitle }}</h1>
    @if ($periodLabel !== '')
        <p class="report-subtitle">{{ $periodLabel }}</p>
    @endif

    <table class="data-table">
        <thead>
            <tr>
                <th rowspan="2" style="width: 18%;">Nama Customer</th>
                @foreach ($periods as $idx => $p)
                    <th colspan="2" style="width: 12%;">{{ $p }}</th>
                @endforeach
                <th colspan="5" style="width: 30%;">Total</th>
            </tr>
            <tr class="sub-kolom">
                @foreach ($periods as $idx => $p)
                    <th class="number">RP</th>
                    <th class="number">SKU</th>
                @endforeach
                <th class="number">Min</th>
                <th class="number">Max</th>
                <th class="number">Avg</th>
                <th class="number">Max <br> SKU</th>
                <th class="number">Avg <br> SKU</th>
            </tr>
        </thead>
        <tbody>
            @php $globalRow = 0; @endphp
            @foreach ($customers as $cust)
                @php $globalRow++; @endphp
                <tr class="{{ $globalRow % 2 === 0 ? 'row-even' : 'row-odd' }}">
                    <td>{{ $cust['customer_name'] ?? '' }}</td>
                    @foreach ($periods as $idx => $p)
                        <td class="number nowrap">{{ fmtAmt($cust['months'][$idx] ?? 0) }}</td>
                        <td class="number nowrap">{{ $cust['sku_months'][$idx] ?? 0 }}</td>
                    @endforeach
                    <td class="number nowrap">{{ fmtAmt($cust['min'] ?? 0) }}</td>
                    <td class="number nowrap">{{ fmtAmt($cust['max'] ?? 0) }}</td>
                    <td class="number nowrap">{{ fmtAmt($cust['avg'] ?? 0) }}</td>
                    <td class="number nowrap">{{ $cust['max_sku'] ?? 0 }}</td>
                    <td class="number nowrap">{{ number_format($cust['avg_sku'] ?? 0, 0, '.', ',') }}</td>
                </tr>
            @endforeach

            @if (count($customers) <= 0)
                <tr class="empty-row">
                    <td colspan="{{ (count($periods) * 2) + 7 }}">Tidak ada data sales summary.</td>
                </tr>
            @endif
        </tbody>
    </table>

    @include('ascends.shared.partials.report-footer')
</body>

</html>