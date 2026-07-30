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

        .section-header td {
            font-size: 11px;
            font-weight: bold;
            font-style: italic;
            color: #9c111d;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
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
        $sections = $reportData['sections'] ?? [];
        $grandTotals = $reportData['grand_totals'] ?? [];
        $months = $reportData['months'] ?? [];
        $monthCount = count($months);

        $totalCols = 1 + $monthCount + 4;

        $colNameWidth = 22;
        $colMonthWidth = $monthCount > 0 ? round((100 - $colNameWidth - 10 - 7 - 5 - 6) / $monthCount, 1) : 0;
        $colTotalWidth = 10;
        $colRata2Width = 7;
        $colMinWidth = 5;
        $colMaxWidth = 6;

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
            return number_format((float) $value, 2, '.', ',') . '%';
        }

        $startDate = trim((string) ($reportData['start_date'] ?? ''));
        $endDate = trim((string) ($reportData['end_date'] ?? ''));
        if ($startDate !== '' && $endDate !== '') {
            $headerSubtitle = 'Dari ' . $startDate . ' s/d ' . $endDate;
        }

        $globalRow = 0;
    @endphp

    <h1 class="report-companyTitle">{{ $headerCompany }}</h1>
    <h1 class="report-title">{{ $headerTitle }}</h1>
    <p class="report-subtitle">{{ $headerSubtitle }}</p>

    @if (count($sections) > 0)
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: {{ $colNameWidth }}%;">Penjualan</th>
                    @foreach ($months as $month)
                        <th style="width: {{ $colMonthWidth }}%;">{{ $month }}</th>
                    @endforeach
                    <th style="width: {{ $colTotalWidth }}%;">Total</th>
                    <th style="width: {{ $colRata2Width }}%;">Rata2</th>
                    <th style="width: {{ $colMinWidth }}%;">Terendah</th>
                    <th style="width: {{ $colMaxWidth }}%;">Tertinggi</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($sections as $section)
                    <tr class="section-header">
                        <td colspan="{{ $totalCols }}">{{ $section['salesperson'] ?? '' }}</td>
                    </tr>

                    @if ($section['has_penjualan'])
                        @foreach ($section['penjualan_rows'] as $row)
                            @php $globalRow++; @endphp
                            <tr class="{{ $globalRow % 2 === 0 ? 'row-even' : 'row-odd' }}">
                                <td>{{ $row['family'] ?? '' }}</td>
                                @foreach ($row['values'] as $val)
                                    <td class="number">{{ fmtNum($val) }}</td>
                                @endforeach
                                <td class="number">{{ fmtNum($row['total']) }}</td>
                                <td class="number">{{ fmtNum($row['rata2']) }}</td>
                                <td class="number">{{ fmtNum($row['terendah']) }}</td>
                                <td class="number">{{ fmtNum($row['tertinggi']) }}</td>
                            </tr>
                        @endforeach

                        <tr class="grand-total-row">
                            <td>TOTAL PENJUALAN</td>
                            @foreach ($section['penjualan_total']['values'] as $val)
                                <td class="number">{{ fmtNum($val) }}</td>
                            @endforeach
                            <td class="number">{{ fmtNum($section['penjualan_total']['total']) }}</td>
                            <td class="number">{{ fmtNum($section['penjualan_total']['rata2']) }}</td>
                            <td class="number">{{ fmtNum($section['penjualan_total']['terendah']) }}</td>
                            <td class="number">{{ fmtNum($section['penjualan_total']['tertinggi']) }}</td>
                        </tr>
                    @endif

                    @if ($section['has_biaya'])
                        <tr class="grand-total-row">
                            <td>BIAYA PERJALANAN DINAS</td>
                            @foreach ($section['biaya']['values'] as $val)
                                <td class="number">{{ fmtNum($val) }}</td>
                            @endforeach
                            <td class="number">{{ fmtNum($section['biaya']['total']) }}</td>
                            <td class="number">{{ fmtNum($section['biaya']['rata2']) }}</td>
                            <td class="number">{{ fmtNum($section['biaya']['terendah']) }}</td>
                            <td class="number">{{ fmtNum($section['biaya']['tertinggi']) }}</td>
                        </tr>

                        <tr class="grand-total-row">
                            <td>TOTAL BIAYA</td>
                            @foreach ($section['biaya']['values'] as $val)
                                <td class="number">{{ fmtNum($val) }}</td>
                            @endforeach
                            <td class="number">{{ fmtNum($section['biaya']['total']) }}</td>
                            <td class="number">{{ fmtNum($section['biaya']['rata2']) }}</td>
                            <td class="number">{{ fmtNum($section['biaya']['terendah']) }}</td>
                            <td class="number">{{ fmtNum($section['biaya']['tertinggi']) }}</td>
                        </tr>
                    @endif

                    <tr class="grand-total-row">
                        <td>
                            Persentase Biaya Perjalanan<br>
                            Dinas VS Penjualan
                        </td>
                        @foreach ($section['percentage'] as $val)
                            <td class="number">{{ fmtPersen($val) }}</td>
                        @endforeach
                        <td colspan="4"></td>
                    </tr>
                @endforeach

                @if ($grandTotals['has_penjualan'] || $grandTotals['has_biaya'])
                    <tr class="section-header">
                        <td colspan="{{ $totalCols }}">Grand Total</td>
                    </tr>

                    @if ($grandTotals['has_penjualan'])
                        @foreach ($grandTotals['penjualan_rows'] as $row)
                            @php $globalRow++; @endphp
                            <tr class="{{ $globalRow % 2 === 0 ? 'row-even' : 'row-odd' }}">
                                <td>{{ $row['family'] ?? '' }}</td>
                                @foreach ($row['values'] as $val)
                                    <td class="number">{{ fmtNum($val) }}</td>
                                @endforeach
                                <td class="number">{{ fmtNum($row['total']) }}</td>
                                <td class="number">{{ fmtNum($row['rata2']) }}</td>
                                <td class="number">{{ fmtNum($row['terendah']) }}</td>
                                <td class="number">{{ fmtNum($row['tertinggi']) }}</td>
                            </tr>
                        @endforeach

                        <tr class="grand-total-row">
                            <td>Total Penjualan</td>
                            @foreach ($grandTotals['penjualan_total']['values'] as $val)
                                <td class="number">{{ fmtNum($val) }}</td>
                            @endforeach
                            <td class="number">{{ fmtNum($grandTotals['penjualan_total']['total']) }}</td>
                            <td class="number">{{ fmtNum($grandTotals['penjualan_total']['rata2']) }}</td>
                            <td class="number">{{ fmtNum($grandTotals['penjualan_total']['terendah']) }}</td>
                            <td class="number">{{ fmtNum($grandTotals['penjualan_total']['tertinggi']) }}</td>
                        </tr>
                    @endif

                    @if ($grandTotals['has_biaya'])
                        <tr class="grand-total-row">
                            <td>BIAYA PERJALANAN DINAS</td>
                            @foreach ($grandTotals['biaya']['values'] as $val)
                                <td class="number">{{ fmtNum($val) }}</td>
                            @endforeach
                            <td class="number">{{ fmtNum($grandTotals['biaya']['total']) }}</td>
                            <td class="number">{{ fmtNum($grandTotals['biaya']['rata2']) }}</td>
                            <td class="number">{{ fmtNum($grandTotals['biaya']['terendah']) }}</td>
                            <td class="number">{{ fmtNum($grandTotals['biaya']['tertinggi']) }}</td>
                        </tr>

                        <tr class="grand-total-row">
                            <td>Total Biaya</td>
                            @foreach ($grandTotals['biaya']['values'] as $val)
                                <td class="number">{{ fmtNum($val) }}</td>
                            @endforeach
                            <td class="number">{{ fmtNum($grandTotals['biaya']['total']) }}</td>
                            <td class="number">{{ fmtNum($grandTotals['biaya']['rata2']) }}</td>
                            <td class="number">{{ fmtNum($grandTotals['biaya']['terendah']) }}</td>
                            <td class="number">{{ fmtNum($grandTotals['biaya']['tertinggi']) }}</td>
                        </tr>
                    @endif

                    <tr class="grand-total-row">
                        <td>
                            Persentase Biaya Perjalanan<br>
                            Dinas VS Penjualan
                        </td>
                        @foreach ($grandTotals['percentage'] as $val)
                            <td class="number">{{ fmtPersen($val) }}</td>
                        @endforeach
                        <td colspan="4"></td>
                    </tr>
                @endif
            </tbody>
        </table>
    @else
        <table class="data-table">
            <tbody>
                <tr class="empty-row">
                    <td colspan="{{ $totalCols }}">Tidak ada data.</td>
                </tr>
            </tbody>
        </table>
    @endif

    @include('ascends.shared.partials.report-footer')
</body>

</html>