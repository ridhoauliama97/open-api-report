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
            border-spacing: 0;
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
            font-size: 10px;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            text-align: center;
        }

        .sub-header th {
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
            font-size: 11px;
            padding: 3px 4px;
        }
    </style>
</head>

<body>
    @php
        $rows = $reportData['rows'] ?? [];
        $total = $reportData['total'] ?? [];
        $monthLabelsFull = $reportData['month_labels_full'] ?? [];

        $headerCompany = trim((string) ($company ?? ($reportData['company'] ?? '')));
        $headerTitle = trim((string) ($title ?? ($reportData['title'] ?? ($fallbackTitle ?? ''))));
        $headerSubtitle = trim((string) ($reportData['period_label'] ?? ''));
        $generatedByName = trim((string) ($reportData['printed_by'] ?? ''));

        function fmtNum($value)
        {
            $v = (float) $value;
            if ($v == 0.0) {
                return '-';
            }
            return number_format($v, 0, '.', ',');
        }

        function fmtPersen($value)
        {
            return number_format((float) $value, 1, '.', ',') . '%';
        }

        $reportYear = trim((string) ($reportData['start_date'] ?? ''));
        if ($reportYear !== '') {
            $headerSubtitle = 'Periode : ' . $reportYear;
        }

        $globalRow = 0;
    @endphp

    <h1 class="report-companyTitle">{{ $headerCompany }}</h1>
    <h1 class="report-title">{{ $headerTitle }}</h1>
    <p class="report-subtitle">{{ $headerSubtitle }}</p>

    @if (count($rows) > 0)
        <table class="data-table">
            <thead>
                <tr>
                    <th rowspan="2" style="width: 15%;">Biaya Promosi Penjualan</th>
                    <th rowspan="2" style="width: 5%;">Budget</th>
                    <th colspan="12" style="width: 75%;">Realisasi Biaya Promosi Penjualan</th>
                    <th rowspan="2" style="width: 8%;">Total</th>
                    <th rowspan="2" style="width: 7%;">% Realisasi<br>Terhadap<br>Budget</th>
                </tr>
                <tr class="sub-header">
                    @foreach ($monthLabelsFull as $month)
                        <th style="width: 6.25%;">{{ $month }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    @php $globalRow++; @endphp
                    <tr class="{{ $globalRow % 2 === 0 ? 'row-even' : 'row-odd' }}">
                        <td>{{ $row['name'] ?? '' }}</td>
                        <td class="number">-</td>
                        @foreach ($row['values'] as $val)
                            <td class="number">{{ fmtNum($val) }}</td>
                        @endforeach
                        <td class="number">{{ fmtNum($row['total']) }}</td>
                        <td class="number">0.0%</td>
                    </tr>
                @endforeach

                @if (count($total) > 0)
                    <tr class="grand-total-row">
                        <td>Total Biaya Promosi Penjualan</td>
                        <td class="number">-</td>
                        @foreach ($total['values'] as $val)
                            <td class="number">{{ fmtNum($val) }}</td>
                        @endforeach
                        <td class="number">{{ fmtNum($total['total']) }}</td>
                        <td class="number">0.0%</td>
                    </tr>
                @endif
            </tbody>
        </table>
    @else
        <table class="data-table">
            <tbody>
                <tr class="empty-row">
                    <td colspan="16">Tidak ada data.</td>
                </tr>
            </tbody>
        </table>
    @endif

    @include('ascends.shared.partials.report-footer')
</body>

</html>