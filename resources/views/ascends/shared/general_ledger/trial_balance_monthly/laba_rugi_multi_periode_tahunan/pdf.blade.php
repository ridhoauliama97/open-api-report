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
            font-size: 11px;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            text-align: center;
        }

        .data-table td {
            font-size: 10px;
            border-top: none;
            border-bottom: none;
        }

        .section-header td {
            font-weight: bold;
            font-size: 10px;
            padding: 3px 4px;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
        }

        .item-row td {
            font-size: 10px;
            padding: 1px 3px;
        }

        .item-row td:first-child {
            padding-left: 10px;
        }

        .subtotal-row td {
            font-weight: bold;
            font-size: 10px;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding: 2px 3px;
        }

        .grand-total-row td {
            font-weight: bold;
            font-size: 11px;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding: 3px 4px;
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

        .empty-row td {
            text-align: center;
            font-style: italic;
            background: #c9d1df;
            font-weight: bold;
            color: #9c111d;
            font-size: 11px;
            padding: 8px 4px;
        }

        .col-code {
            width: 15%;
        }

        .col-name {
            width: 25%;
        }

        .col-detail {
            width: 35%;
        }

        .col-total {
            width: 25%;
        }
    </style>
</head>

<body>
    @php
        $sections = $reportData['sections'] ?? [];
        $grandTotal = (float) ($reportData['grand_total'] ?? 0);
        $generatedAtText = \Carbon\Carbon::parse($generatedAt ?? now())
            ->locale('id')
            ->translatedFormat('d-M-y H:i');
        $generatedByName = trim((string) ($reportData['printed_by'] ?? ''));
        $headerCompany = trim((string) ($company ?? ($reportData['company'] ?? '')));
        $headerTitle = trim((string) ($title ?? ($reportData['title'] ?? ($fallbackTitle ?? ''))));
        $headerSubtitle = trim((string) ($reportData['period_label'] ?? ''));

        function fmtAmount($value)
        {
            $v = (float) $value;
            if ($v < 0) {
                return '(' . number_format(abs($v), 2, ',', '.') . ')';
            }
            return number_format($v, 2, ',', '.');
        }
    @endphp

    <h1 class="report-companyTitle">{{ $headerCompany }}</h1>
    <h1 class="report-title">{{ $headerTitle }}</h1>
    <p class="report-subtitle">{{ $headerSubtitle }}</p>

    @if (count($sections) > 0)
        <table class="data-table">
            <thead>
                <tr>
                    <th class="col-code">Kode Akun</th>
                    <th class="col-name">Nama Perkiraan</th>
                    <th class="col-detail">Detail</th>
                    <th class="col-total">Total</th>
                </tr>
            </thead>
            <tbody>
                @php $globalRow = 0; @endphp
                @foreach ($sections as $section)
                    @php
                        $sectionCode = (string) ($section['section_code'] ?? '');
                        $sectionName = (string) ($section['section_name'] ?? '');
                        $items = $section['items'] ?? [];
                        $subtotal = (float) ($section['subtotal'] ?? 0);
                        $showDetail = ! in_array($sectionCode, ['800.000.000', '900.000.000']);
                    @endphp

                    @if ($showDetail && count($items) > 0)
                        <tr class="section-header">
                            <td colspan="4">{{ $sectionCode }} {{ $sectionName }}</td>
                        </tr>

                        @foreach ($items as $item)
                            @php $globalRow++; @endphp
                            @php
                                $itemAmount = (float) ($item['amount'] ?? 0);
                            @endphp
                            <tr class="{{ $globalRow % 2 === 0 ? 'row-even' : 'row-odd' }} item-row">
                                <td></td>
                                <td></td>
                                <td>{{ (string) ($item['account_name'] ?? '') }}</td>
                                <td class="number nowrap {{ $itemAmount < 0 ? 'number-negative' : '' }}">
                                    {{ fmtAmount($itemAmount) }}</td>
                            </tr>
                        @endforeach

                        <tr class="subtotal-row">
                            <td colspan="2">Total</td>
                            <td></td>
                            <td class="number nowrap {{ $subtotal < 0 ? 'number-negative' : '' }}">
                                {{ fmtAmount($subtotal) }}</td>
                        </tr>
                    @else
                        <tr class="section-header">
                            <td>{{ $sectionCode }}</td>
                            <td>{{ $sectionName }}</td>
                            <td></td>
                            <td class="number nowrap {{ $subtotal < 0 ? 'number-negative' : '' }}">
                                {{ fmtAmount($subtotal) }}</td>
                        </tr>
                        <tr class="subtotal-row">
                            <td colspan="2">Total</td>
                            <td></td>
                            <td class="number nowrap {{ $subtotal < 0 ? 'number-negative' : '' }}">
                                {{ fmtAmount($subtotal) }}</td>
                        </tr>
                    @endif
                @endforeach

                <tr class="grand-total-row">
                    <td colspan="2">Total</td>
                    <td></td>
                    <td class="number nowrap {{ $grandTotal < 0 ? 'number-negative' : '' }}">
                        {{ fmtAmount($grandTotal) }}</td>
                </tr>
            </tbody>
        </table>
    @else
        <table class="data-table">
            <tbody>
                <tr class="empty-row">
                    <td colspan="4">Tidak ada data.</td>
                </tr>
            </tbody>
        </table>
    @endif

    @include('ascends.shared.partials.report-footer')
</body>

</html>
