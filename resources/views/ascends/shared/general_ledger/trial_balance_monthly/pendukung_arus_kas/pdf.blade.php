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

        .section-header td {
            font-weight: bold;
            font-size: 9px;
            padding: 2px 3px;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
        }

        .item-row td {
            font-size: 9px;
            padding: 1px 2px;
        }

        .item-row td:first-child {
            padding-left: 4px;
        }

        .subtotal-row td {
            font-weight: bold;
            font-size: 9px;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding: 2px 3px;
        }

        .grand-total-row td {
            font-weight: bold;
            font-size: 10px;
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
            font-size: 10px;
            padding: 8px 4px;
        }

        .col-code {
            width: 15%;
        }

        .col-name {
            width: 27%;
        }

        .col-amount {
            width: 20%;
        }

        .col-selisih {
            width: 18%;
        }
    </style>
</head>

<body>
    @php
        $sections = $reportData['sections'] ?? [];
        $grandStart = (float) ($reportData['grand_start'] ?? 0);
        $grandEnd = (float) ($reportData['grand_end'] ?? 0);
        $grandSelisih = (float) ($reportData['grand_selisih'] ?? 0);
        $periodStartLabel = (string) ($reportData['period_start_label'] ?? '');
        $periodEndLabel = (string) ($reportData['period_end_label'] ?? '');
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
            if ($v == 0.0) {
                return '-';
            }
            return number_format($v, 2, ',', '.');
        }
    @endphp

    <h1 class="report-companyTitle">{{ $headerCompany }}</h1>
    <h1 class="report-title">{{ $headerTitle }}</h1>
    <p class="report-subtitle">{{ $headerSubtitle }}</p>

    @if (count($sections) > 0)
        <table class="data-table">
            <colgroup>
                <col style="width: 15%;">
                <col style="width: 27%;">
                <col style="width: 20%;">
                <col style="width: 20%;">
                <col style="width: 18%;">
            </colgroup>
            <thead>
                <tr>
                    <th class="col-code">Kode Akun</th>
                    <th class="col-name">Nama Account</th>
                    <th class="col-amount">{{ $periodStartLabel }}</th>
                    <th class="col-amount">{{ $periodEndLabel }}</th>
                    <th class="col-selisih">Selisih</th>
                </tr>
            </thead>
            <tbody>
                @php $globalRow = 0; @endphp
                @foreach ($sections as $section)
                    <tr class="section-header">
                        <td colspan="5">{{ $section['section_name'] }}</td>
                    </tr>

                    @foreach ($section['items'] as $item)
                        @php
                            $globalRow++;
                            $amtStart = (float) ($item['amount_start'] ?? 0);
                            $amtEnd = (float) ($item['amount_end'] ?? 0);
                            $selisih = $amtStart - $amtEnd;
                        @endphp
                        <tr class="{{ $globalRow % 2 === 0 ? 'row-even' : 'row-odd' }} item-row">
                            <td>{{ (string) ($item['account_code'] ?? '') }}</td>
                            <td>{{ (string) ($item['account_name'] ?? '') }}</td>
                            <td class="number nowrap {{ $amtStart < 0 ? 'number-negative' : '' }}">
                                {{ fmtAmount($amtStart) }}</td>
                            <td class="number nowrap {{ $amtEnd < 0 ? 'number-negative' : '' }}">
                                {{ fmtAmount($amtEnd) }}</td>
                            <td class="number nowrap {{ $selisih < 0 ? 'number-negative' : '' }}">
                                {{ fmtAmount($selisih) }}</td>
                        </tr>
                    @endforeach

                    @php
                        $subStart = (float) ($section['subtotal_start'] ?? 0);
                        $subEnd = (float) ($section['subtotal_end'] ?? 0);
                        $subSelisih = $subStart - $subEnd;
                    @endphp
                    <tr class="subtotal-row">
                        <td colspan="2">TOTAL {{ $section['section_name'] }}</td>
                        <td class="number nowrap {{ $subStart < 0 ? 'number-negative' : '' }}">
                            {{ fmtAmount($subStart) }}</td>
                        <td class="number nowrap {{ $subEnd < 0 ? 'number-negative' : '' }}">
                            {{ fmtAmount($subEnd) }}</td>
                        <td class="number nowrap {{ $subSelisih < 0 ? 'number-negative' : '' }}">
                            {{ fmtAmount($subSelisih) }}</td>
                    </tr>
                @endforeach

                <tr class="grand-total-row">
                    <td colspan="2">Grand Total</td>
                    <td class="number nowrap {{ $grandStart < 0 ? 'number-negative' : '' }}">
                        {{ fmtAmount($grandStart) }}</td>
                    <td class="number nowrap {{ $grandEnd < 0 ? 'number-negative' : '' }}">
                        {{ fmtAmount($grandEnd) }}</td>
                    <td class="number nowrap {{ $grandSelisih < 0 ? 'number-negative' : '' }}">
                        {{ fmtAmount($grandSelisih) }}</td>
                </tr>
            </tbody>
        </table>
    @else
        <table class="data-table">
            <tbody>
                <tr class="empty-row">
                    <td colspan="5">Tidak ada data.</td>
                </tr>
            </tbody>
        </table>
    @endif

    @include('ascends.shared.partials.report-footer')
</body>

</html>
