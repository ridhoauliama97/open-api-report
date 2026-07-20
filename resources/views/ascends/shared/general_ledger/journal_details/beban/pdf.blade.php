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
            line-height: 1.1;
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
            padding: 1px 2px;
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

        .data-table td {
            font-size: 9px;
            border-top: none;
            border-bottom: none;
        }

        .category-header td {
            font-weight: bold;
            font-size: 9px;
            font-style: italic;
            padding: 3px 3px;
            color: #9c111d;
            border-bottom: 1px solid #000;
        }

        .category-subtotal td {
            font-weight: bold;
            font-size: 9px;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding: 2px 3px;
        }

        .grand-row td {
            font-weight: bold;
            font-size: 9px;
            border-bottom: 1px solid #000;
            padding: 2px 3px;
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
            background: #c9d1df;
            font-weight: bold;
            color: #9c111d;
            font-size: 11px;
            padding: 6px 4px;
        }
    </style>
</head>

<body>
    @php
        $categories = $reportData['categories'] ?? [];
        $months = $reportData['months'] ?? [];
        $grandTotalMonthly = $reportData['grand_total_monthly'] ?? [];
        $grandTotal = (float) ($reportData['grand_total'] ?? 0);
        $grandTotalTerendah = (float) ($reportData['grand_total_terendah'] ?? 0);
        $grandTotalTertinggi = (float) ($reportData['grand_total_tertinggi'] ?? 0);
        $grandTotalRataRata = (float) ($reportData['grand_total_rata_rata'] ?? 0);
        $numMonths = count($months);

        $namePct = 18;
        $statPct = 5;
        $monthPairPct = ($numMonths > 0) ? (100 - $namePct - 6 - 3 - ($statPct * 3)) / $numMonths : 0;
        $monthAmtPct = round($monthPairPct * 0.6, 1);
        $monthPctPct = round($monthPairPct * 0.4, 1);

        $headerCompany = trim((string) ($company ?? $reportData['company'] ?? ''));
        $headerTitle = trim((string) ($title ?? $reportData['title'] ?? $fallbackTitle ?? ''));
        $headerSubtitle = trim((string) ($reportData['period_label'] ?? ''));

        function formatAmount($value)
        {
            $value = (float) $value;
            if ($value < 0) {
                return '(' . number_format(abs($value), 0, ',', '.') . ')';
            }
            return number_format($value, 0, ',', '.');
        }

        function formatPct($value)
        {
            $v = (float) $value;
            if ($v < 0) {
                return '(' . number_format(abs($v), 1, ',', '.') . '%)';
            }
            return number_format($v, 1, ',', '.') . '%';
        }
    @endphp

    <h1 class="report-companyTitle">{{ $headerCompany }}</h1>
    <h1 class="report-title">{{ $headerTitle }}</h1>
    <p class="report-subtitle">{{ $headerSubtitle }}</p>

    @if (count($categories) > 0)
        <table class="data-table">
            <colgroup>
                <col style="width: {{ $namePct }}%;">
                @foreach ($months as $mk => $ml)
                    <col style="width: {{ $monthAmtPct }}%;">
                    <col style="width: {{ $monthPctPct }}%;">
                @endforeach
                <col style="width: 6%;">
                <col style="width: 3%;">
                <col style="width: {{ $statPct }}%;">
                <col style="width: {{ $statPct }}%;">
                <col style="width: {{ $statPct }}%;">
            </colgroup>
            <thead>
                <tr>
                    <th rowspan="2" style="width: {{ $namePct }}%;">Nama Perkiraan</th>
                    @foreach ($months as $mk => $ml)
                        <th colspan="2" style="width: {{ $monthAmtPct + $monthPctPct }}%;">{{ $ml }}</th>
                    @endforeach
                    <th colspan="2" style="width: 9%;">Total</th>
                    <th rowspan="2" style="width: {{ $statPct }}%;">Terendah</th>
                    <th rowspan="2" style="width: {{ $statPct }}%;">Tertinggi</th>
                    <th rowspan="2" style="width: {{ $statPct }}%;">Rata - Rata</th>
                </tr>
                <tr>
                    @foreach ($months as $mk => $ml)
                        <th>Jumlah</th>
                        <th>%</th>
                    @endforeach
                    <th>Jumlah</th>
                    <th>%</th>
                </tr>
            </thead>
            <tbody>
                @php $globalRow = 0; @endphp
                @foreach ($categories as $category)
                    <tr class="category-header">
                        <td colspan="{{ 1 + ($numMonths * 2) + 2 + 3 }}">{{ $category['name'] }}</td>
                    </tr>

                    @foreach ($category['accounts'] as $account)
                        @php $globalRow++; @endphp
                        <tr class="{{ $globalRow % 2 === 0 ? 'row-even' : 'row-odd' }}">
                            <td>{{ (string) ($account['account_name'] ?? '') }}</td>
                            @foreach ($months as $mk => $ml)
                                <td class="number nowrap">{{ formatAmount($account['monthly_amounts'][$mk] ?? 0) }}</td>
                                <td class="number nowrap">{{ formatPct($account['monthly_pcts'][$mk] ?? 0) }}</td>
                            @endforeach
                            <td class="number nowrap">{{ formatAmount($account['total_amount'] ?? 0) }}</td>
                            <td class="number nowrap">{{ formatPct($account['total_pct'] ?? 0) }}</td>
                            <td class="number nowrap">{{ formatAmount($account['terendah'] ?? 0) }}</td>
                            <td class="number nowrap">{{ formatAmount($account['tertinggi'] ?? 0) }}</td>
                            <td class="number nowrap">{{ formatAmount($account['rata_rata'] ?? 0) }}</td>
                        </tr>
                    @endforeach

                    <tr class="category-subtotal">
                        <td>{{ $category['name'] }}</td>
                        @foreach ($months as $mk => $ml)
                            <td class="number nowrap">{{ formatAmount($category['monthly_subtotals'][$mk] ?? 0) }}</td>
                            <td class="number nowrap">{{ formatPct($category['monthly_pcts'][$mk] ?? 0) }}</td>
                        @endforeach
                        <td class="number nowrap">{{ formatAmount($category['total_amount'] ?? 0) }}</td>
                        <td class="number nowrap">{{ formatPct($category['total_pct'] ?? 0) }}</td>
                        <td class="number nowrap">{{ formatAmount($category['terendah'] ?? 0) }}</td>
                        <td class="number nowrap">{{ formatAmount($category['tertinggi'] ?? 0) }}</td>
                        <td class="number nowrap">{{ formatAmount($category['rata_rata'] ?? 0) }}</td>
                    </tr>
                @endforeach

                <tr class="grand-row">
                    <td class="center">Grand Total</td>
                    @foreach ($months as $mk => $ml)
                        <td class="number nowrap">{{ formatAmount($grandTotalMonthly[$mk] ?? 0) }}</td>
                        <td></td>
                    @endforeach
                    <td class="number nowrap">{{ formatAmount($grandTotal) }}</td>
                    <td></td>
                    <td class="number nowrap">{{ formatAmount($grandTotalTerendah) }}</td>
                    <td class="number nowrap">{{ formatAmount($grandTotalTertinggi) }}</td>
                    <td class="number nowrap">{{ formatAmount($grandTotalRataRata) }}</td>
                </tr>
            </tbody>
        </table>
    @else
        <table class="data-table">
            <tbody>
                <tr class="empty-row">
                    <td colspan="{{ 1 + ($numMonths * 2) + 2 + 3 }}">Tidak ada data.</td>
                </tr>
            </tbody>
        </table>
    @endif

    @include('ascends.shared.partials.report-footer')
</body>

</html>