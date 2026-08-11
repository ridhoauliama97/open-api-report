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
            border-bottom: 1px solid #000;
            text-align: center;
        }

        .center {
            text-align: center;
        }

        .number {
            text-align: right;
        }

        .row-odd td {
            background: #c9d1df;
        }

        .row-even td {
            background: #eef2f8;
        }

        .totals-row td {
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
            padding: 4px 4px;
        }
    </style>
</head>

<body>
    @php
        $months = $reportData['months'] ?? [];
        $rows = $reportData['rows'] ?? [];
        $totals = $reportData['totals'] ?? [];
        $monthCount = count($months);

        $headerCompany = trim((string) ($company ?? ($reportData['company'] ?? '')));
        $headerTitle = trim((string) ($title ?? ($reportData['title'] ?? ($fallbackTitle ?? ''))));
        $headerSubtitle = trim((string) ($reportData['period_label'] ?? ''));
        $generatedByName = trim((string) ($reportData['printed_by'] ?? ''));

        $colNoWidth = 3;
        $colNameWidth = 15;
        $monthSubWidth = $monthCount > 0 ? round((100 - $colNoWidth - $colNameWidth) / ($monthCount * 3), 1) : 0;

        if (!function_exists('fmtNum_penjualan_per_item_analisa_sku')) {
            function fmtNum_penjualan_per_item_analisa_sku($value)
            {
                $v = (float) $value;
                if ($v == 0.0) {
                    return '-';
                }
                return number_format($v, 0, '.', ',');
            }
        }

        function fmtPersen($value, $hasil)
        {
            if ((float) $hasil == 0.0) {
                return '-';
            }
            return number_format((float) $value, 1, '.', ',') . '%';
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

    @if (count($rows) > 0)
        <table class="data-table">
            <thead>
                <tr>
                    <th rowspan="2" style="width: {{ $colNoWidth }}%;">No</th>
                    <th rowspan="2" style="width: {{ $colNameWidth }}%;">Nama Barang</th>
                    @foreach ($months as $month)
                        <th colspan="3">{{ $month }}</th>
                    @endforeach
                </tr>
                <tr>
                    @foreach ($months as $month)
                        <th style="width: {{ $monthSubWidth }}%;">SKU</th>
                        <th style="width: {{ $monthSubWidth }}%;">Capai</th>
                        <th style="width: {{ $monthSubWidth }}%;">%</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    @php $globalRow++; @endphp
                    @php
                        $rowClass = $globalRow % 2 === 0 ? 'row-even' : 'row-odd';
                    @endphp
                    <tr class="{{ $rowClass }}">
                        <td class="center">{{ $globalRow }}</td>
                        <td>{{ $row['family'] ?? '' }}</td>
                        @foreach ($row['cells'] ?? [] as $cell)
                            <td class="number">{{ fmtNum_penjualan_per_item_analisa_sku($cell['sku'] ?? 0) }}</td>
                            <td class="number">{{ fmtNum_penjualan_per_item_analisa_sku($cell['hasil'] ?? 0) }}</td>
                            <td class="number">{{ fmtPersen($cell['percent'] ?? 0, $cell['hasil'] ?? 0) }}</td>
                        @endforeach
                    </tr>
                @endforeach

                @if (count($rows) > 0)
                    <tr class="totals-row">
                        <td class="center" colspan="2">Total</td>
                        @foreach ($totals['cells'] ?? [] as $cell)
                            <td class="number">{{ fmtNum_penjualan_per_item_analisa_sku($cell['sku'] ?? 0) }}</td>
                            <td class="number">{{ fmtNum_penjualan_per_item_analisa_sku($cell['hasil'] ?? 0) }}</td>
                            <td class="number">{{ fmtPersen($cell['percent'] ?? 0, $cell['hasil'] ?? 0) }}</td>
                        @endforeach
                    </tr>
                @endif
            </tbody>
        </table>
    @else
        <table class="data-table">
            <tbody>
                <tr class="empty-row">
                    <td colspan="{{ 2 + $monthCount * 3 }}">Tidak ada data.</td>
                </tr>
            </tbody>
        </table>
    @endif

    @include('ascends.shared.partials.report-footer')
</body>

</html>