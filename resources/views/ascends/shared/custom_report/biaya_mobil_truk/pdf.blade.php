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

        .subtotal-row td {
            font-weight: bold;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
        }

        .grand-total-row td {
            font-size: 11px;
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

        $headerCompany = trim((string) ($company ?? ($reportData['company'] ?? '')));
        $headerTitle = trim((string) ($title ?? ($reportData['title'] ?? ($fallbackTitle ?? ''))));
        $startDate = trim((string) ($reportData['start_date'] ?? ''));
        $endDate = trim((string) ($reportData['end_date'] ?? ''));
        $headerSubtitle = '';
        if ($startDate !== '' && $endDate !== '') {
            $headerSubtitle = 'Dari ' . $startDate . ' s/d ' . $endDate;
        }

        if (!function_exists('fmtNum_biaya_mobil_truk')) {
            function fmtNum_biaya_mobil_truk($value)
            {
                $v = (float) $value;
                if ($v == 0.0) {
                    return '0';
                }
                return number_format($v, 0, '.', ',');
            }
        }

        $globalRow = 0;
    @endphp

    <h1 class="report-companyTitle">{{ $headerCompany }}</h1>
    <h1 class="report-title">{{ $headerTitle }}</h1>
    @if ($headerSubtitle !== '')
        <p class="report-subtitle">{{ $headerSubtitle }}</p>
    @endif

    @if (count($sections) > 0)
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 22%;">Akun</th>
                    @foreach ($months as $month)
                        <th style="width: 7.5%;">{{ $month }}</th>
                    @endforeach
                    <th style="width: 9.5%;">Total</th>
                    <th style="width: 8.5%;">Rata - Rata</th>
                    <th style="width: 8.5%;">Terendah</th>
                    <th style="width: 8.5%;">Tertinggi</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($sections as $section)
                    <tr class="section-header">
                        <td colspan="{{ 2 + $monthCount + 3 }}">{{ $section['lowest_description'] ?? '' }}</td>
                    </tr>

                    @foreach ($section['rows'] as $row)
                        @php $globalRow++; @endphp
                        <tr class="{{ $globalRow % 2 === 0 ? 'row-even' : 'row-odd' }}">
                            <td>{{ $row['account_name'] ?? '' }}</td>
                            @foreach ($row['values'] as $val)
                                <td class="number">{{ fmtNum_biaya_mobil_truk($val) }}</td>
                            @endforeach
                            <td class="number">{{ fmtNum_biaya_mobil_truk($row['total']) }}</td>
                            <td class="number">{{ fmtNum_biaya_mobil_truk($row['rata2']) }}</td>
                            <td class="number">{{ fmtNum_biaya_mobil_truk($row['terendah']) }}</td>
                            <td class="number">{{ fmtNum_biaya_mobil_truk($row['tertinggi']) }}</td>
                        </tr>
                    @endforeach

                    <tr class="subtotal-row">
                        <td>SUBTOTAL</td>
                        @foreach ($section['subtotal']['values'] as $val)
                            <td class="number">{{ fmtNum_biaya_mobil_truk($val) }}</td>
                        @endforeach
                        <td class="number">{{ fmtNum_biaya_mobil_truk($section['subtotal']['total']) }}</td>
                        <td class="number">{{ fmtNum_biaya_mobil_truk($section['subtotal']['rata2']) }}</td>
                        <td class="number">{{ fmtNum_biaya_mobil_truk($section['subtotal']['terendah']) }}</td>
                        <td class="number">{{ fmtNum_biaya_mobil_truk($section['subtotal']['tertinggi']) }}</td>
                    </tr>
                @endforeach

                @if (count($grandTotals) > 0)
                    <tr class="grand-total-row">
                        <td class="center">GRAND TOTAL</td>
                        @foreach ($grandTotals['values'] as $val)
                            <td class="number">{{ fmtNum_biaya_mobil_truk($val) }}</td>
                        @endforeach
                        <td class="number">{{ fmtNum_biaya_mobil_truk($grandTotals['total']) }}</td>
                        <td class="number">{{ fmtNum_biaya_mobil_truk($grandTotals['rata2']) }}</td>
                        <td class="number">{{ fmtNum_biaya_mobil_truk($grandTotals['terendah']) }}</td>
                        <td class="number">{{ fmtNum_biaya_mobil_truk($grandTotals['tertinggi']) }}</td>
                    </tr>
                @endif
            </tbody>
        </table>
    @else
        <table class="data-table">
            <tbody>
                <tr class="empty-row">
                    <td>Tidak ada data laporan biaya mobil / truk.</td>
                </tr>
            </tbody>
        </table>
    @endif

    @include('ascends.shared.partials.report-footer')
</body>

</html>