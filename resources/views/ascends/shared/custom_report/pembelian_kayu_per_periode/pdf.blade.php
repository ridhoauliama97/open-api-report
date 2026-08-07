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

        .section-heading {
            font-size: 11px;
            font-weight: bold;
            margin: 12px 0 2px 0;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            page-break-inside: auto;
            border-spacing: 0;
            border-bottom: 1px solid #000;
            margin-bottom: 10px;
        }

        .data-table th,
        .data-table td {
            border-left: 1px solid #000;
            border-right: 1px solid #000;
            padding: 1px 3px;
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

        .subtotal-row td {
            font-weight: bold;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
        }

        .grand-total-row td {
            font-weight: bold;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
        }

        .summary-table tr {
            border-top: 1px solid #000;
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

        .page-break {
            page-break-before: always;
        }
    </style>
</head>

<body>
    @php
        $sections = $reportData['sections'] ?? [];
        $periodLabel = trim((string) ($reportData['period_label'] ?? ''));
        $periodMonth = trim((string) ($reportData['period_month'] ?? ''));

        $headerCompany = trim((string) ($company ?? ($reportData['company'] ?? '')));
        $headerTitle = trim((string) ($title ?? ($reportData['title'] ?? '')));
        if ($headerCompany === '') {
            $headerCompany = 'RU';
        }

        if (!function_exists('fmtAmount_pembelian_kayu_per_periode')) {
            function fmtAmount_pembelian_kayu_per_periode($value)
            {
                $v = (float) $value;
                if ($v == 0.0) {
                    return '-';
                }
                $sign = $v < 0 ? '- ' : '';
                return $sign . number_format(abs($v), 2, '.', ',');
            }
        }
        if (!function_exists('fmtPercent1_pembelian_kayu_per_periode')) {
            function fmtPercent1_pembelian_kayu_per_periode($value)
            {
                $v = (float) $value;
                return number_format($v, 1, '.', ',') . '%';
            }
        }
        if (!function_exists('fmtPercent2_pembelian_kayu_per_periode')) {
            function fmtPercent2_pembelian_kayu_per_periode($value)
            {
                $v = (float) $value;
                return number_format($v, 2, '.', ',') . '%';
            }
        }
    @endphp

    <h1 class="report-companyTitle">{{ $headerCompany }}</h1>
    <h1 class="report-title">{{ $headerTitle }}</h1>
    @if ($periodLabel !== '')
        <p class="report-subtitle">{{ $periodLabel }}</p>
    @endif

    @if (count($sections) > 0)
        @foreach ($sections as $section)
            <div @if (!$loop->first) class="page-break" @endif></div>

            <p class="section-heading">Satuan : {{ $section['uom'] }}</p>
            @if ($periodMonth !== '')
                <p class="section-heading">Periode : {{ $periodMonth }}</p>
            @endif

            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 27%;">Supplier</th>
                        <th style="width: 33%;">Item Name</th>
                        <th style="width: 13%;">Quantity</th>
                        <th style="width: 8%;">UOM</th>
                        <th style="width: 19%;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @php $rowNum = 0; @endphp
                    @foreach ($section['suppliers'] as $supplier)
                        @foreach ($supplier['items'] as $idx => $item)
                            @php $rowNum++; @endphp
                            <tr class="{{ $rowNum % 2 === 0 ? 'row-even' : 'row-odd' }}">
                                <td>{{ $idx === 0 ? $supplier['supplier'] : '' }}</td>
                                <td>{{ $item['item'] }}</td>
                                <td class="number nowrap">{{ fmtAmount_pembelian_kayu_per_periode($item['qty']) }}</td>
                                <td class="center">{{ $section['uom'] }}</td>
                                <td class="number nowrap">{{ fmtAmount_pembelian_kayu_per_periode($item['total']) }}</td>
                            </tr>
                        @endforeach
                        <tr class="subtotal-row">
                            <td colspan="2">Total {{ $supplier['supplier'] }}
                                {{ fmtPercent1_pembelian_kayu_per_periode($supplier['percent']) }}</td>
                            <td class="number nowrap">{{ fmtAmount_pembelian_kayu_per_periode($supplier['qty']) }}</td>
                            <td class="center">{{ $section['uom'] }}</td>
                            <td class="number nowrap">{{ fmtAmount_pembelian_kayu_per_periode($supplier['total']) }}</td>
                        </tr>
                    @endforeach

                    <tr class="grand-total-row">
                        <td colspan="2">Total</td>
                        <td class="number nowrap">{{ fmtAmount_pembelian_kayu_per_periode($section['qty']) }}</td>
                        <td class="center">{{ $section['uom'] }}</td>
                        <td class="number nowrap">{{ fmtAmount_pembelian_kayu_per_periode($section['total']) }}</td>
                    </tr>
                    <tr class="grand-total-row">
                        <td colspan="2">Total Satuan {{ $section['uom'] }}</td>
                        <td class="number nowrap">{{ fmtAmount_pembelian_kayu_per_periode($section['qty']) }}</td>
                        <td class="center">{{ $section['uom'] }}</td>
                        <td class="number nowrap">{{ fmtAmount_pembelian_kayu_per_periode($section['total']) }}</td>
                    </tr>
                </tbody>
            </table>

            <p class="section-heading">Rangkuman : {{ $section['uom'] }}</p>

            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 42%;">Supplier Name</th>
                        <th style="width: 18%;">%</th>
                        <th style="width: 20%;">Quantity</th>
                        <th style="width: 20%;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($section['suppliers'] as $supplier)
                        <tr>
                            <td>{{ $supplier['supplier'] }}</td>
                            <td class="number nowrap">{{ fmtPercent2_pembelian_kayu_per_periode($supplier['percent']) }}</td>
                            <td class="number nowrap">{{ fmtAmount_pembelian_kayu_per_periode($supplier['qty']) }}</td>
                            <td class="number nowrap">{{ fmtAmount_pembelian_kayu_per_periode($supplier['total']) }}</td>
                        </tr>
                    @endforeach
                    <tr class="grand-total-row">
                        <td colspan="2" class="center">Total</td>
                        <td class="number nowrap">{{ fmtAmount_pembelian_kayu_per_periode($section['qty']) }}</td>
                        <td class="number nowrap">{{ fmtAmount_pembelian_kayu_per_periode($section['total']) }}</td>
                    </tr>
                </tbody>
            </table>
        @endforeach
    @else
        <table class="data-table">
            <tbody>
                <tr class="empty-row">
                    <td>Tidak ada data pembelian kayu per periode.</td>
                </tr>
            </tbody>
        </table>
    @endif

    @include('ascends.shared.partials.report-footer')
</body>

</html>
