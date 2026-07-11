<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
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
            margin: 2px 0 6px 0;
            font-size: 12px;
            color: #636466;
        }

        .column-header {
            font-weight: bold;
            font-size: 11px;
            text-align: center;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding: 3px;
        }

        .wrapper-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .wrapper-table td {
            vertical-align: top;
            padding: 0;
        }

        .wrapper-table td.left-cell {
            width: 48%;
            padding-right: 3px;
        }

        .wrapper-table td.gap-cell {
            width: 4%;
        }

        .wrapper-table td.right-cell {
            width: 48%;
            padding-left: 3px;
        }

        .side-table {
            width: 100%;
            border-collapse: collapse;
            page-break-inside: auto;
            border: 1px solid #000;
        }

        .side-table th,
        .side-table td {
            border-left: 1px solid #000;
            border-right: 1px solid #000;
            padding: 1px 2px;
            vertical-align: middle;
            word-wrap: break-word;
        }

        .side-table td {
            border-top: none;
            border-bottom: none;
            font-size: 8px;
        }

        .section-header td {
            font-weight: bold;
            font-size: 9px;
            padding: 2px 3px;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
        }

        .sub-section-header td {
            font-weight: bold;
            font-size: 8px;
            padding: 1px 2px;
        }

        .subtotal-row td {
            font-weight: bold;
            font-size: 8px;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding: 2px 3px;
        }

        .grand-total-row td {
            font-weight: bold;
            font-size: 10px;
            border-top: 2px solid #000;
            border-bottom: 2px solid #000;
            padding: 3px 4px;
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

        .col-desc {
            width: 70%;
        }

        .col-amount {
            width: 30%;
        }
    </style>
</head>

<body>
    @php
        $leftSections = $reportData['left_sections'] ?? [];
        $rightSections = $reportData['right_sections'] ?? [];
        $leftGrandTotal = (float) ($reportData['left_grand_total'] ?? 0);
        $rightGrandTotal = (float) ($reportData['right_grand_total'] ?? 0);
        $headerCompany = trim((string) ($company ?? ($reportData['company'] ?? '')));
        $headerTitle = trim((string) ($title ?? ($reportData['title'] ?? ($fallbackTitle ?? ''))));
        $headerSubtitle = trim((string) ($reportData['period_label'] ?? ''));
        $generatedByName = trim((string) ($reportData['printed_by'] ?? ''));

        function fmtAmount($value)
        {
            $v = (float) $value;
            if ($v < 0) {
                return '(' . number_format(abs($v), 0, ',', '.') . ')';
            }
            if ($v == 0.0) {
                return '0';
            }
            return number_format($v, 0, ',', '.');
        }
    @endphp

    <h1 class="report-companyTitle">{{ $headerCompany }}</h1>
    <h1 class="report-title">{{ $headerTitle }}</h1>
    <p class="report-subtitle">{{ $headerSubtitle }}</p>

    <table class="wrapper-table">
        <colgroup>
            <col style="width: 48%;">
            <col style="width: 4%;">
            <col style="width: 48%;">
        </colgroup>
        <tr>
            <td class="column-header">AKTIVA</td>
            <td class="column-header"></td>
            <td class="column-header">KEWAJIBAN &amp; EKUITAS</td>
        </tr>
    </table>

    <table class="wrapper-table">
        <colgroup>
            <col style="width: 48%;">
            <col style="width: 4%;">
            <col style="width: 48%;">
        </colgroup>
        <tr>
            <td class="left-cell">
                @if (count($leftSections) > 0)
                    <table class="side-table">
                        <colgroup>
                            <col class="col-desc">
                            <col class="col-amount">
                        </colgroup>
                        @php $leftGlobalRow = 0; @endphp
                        @foreach ($leftSections as $section)
                            @php
                                $hasItems = false;
                                foreach ($section['sub_sections'] as $sub) {
                                    if (count($sub['items']) > 0) {
                                        $hasItems = true;
                                        break;
                                    }
                                }
                            @endphp

                            @if (!$hasItems)
                                @continue
                            @endif

                            <tr class="section-header">
                                <td colspan="2">{{ $section['name'] }}</td>
                            </tr>

                            @foreach ($section['sub_sections'] as $sub)
                                @if (count($sub['items']) === 0)
                                    @continue
                                @endif

                                <tr class="sub-section-header">
                                    <td colspan="2">{{ $sub['name'] }}</td>
                                </tr>

                                @foreach ($sub['items'] as $item)
                                    @php $leftGlobalRow++; @endphp
                                    @php $bal = (float) ($item['balance'] ?? 0); @endphp
                                    <tr class="{{ $leftGlobalRow % 2 === 0 ? 'row-even' : 'row-odd' }}">
                                        <td>{{ (string) ($item['account_name'] ?? '') }}</td>
                                        <td class="number nowrap">{{ fmtAmount($bal) }}</td>
                                    </tr>
                                @endforeach

                                @php $subTotal = (float) ($sub['total'] ?? 0); @endphp
                                <tr class="subtotal-row">
                                    <td>TOTAL {{ $sub['name'] }}</td>
                                    <td class="number nowrap">{{ fmtAmount($subTotal) }}</td>
                                </tr>
                            @endforeach

                            @php $secTotal = (float) ($section['total'] ?? 0); @endphp
                            <tr class="subtotal-row">
                                <td></td>
                                <td class="number nowrap">{{ fmtAmount($secTotal) }}</td>
                            </tr>
                        @endforeach
                    </table>
                @endif
            </td>

            <td class="gap-cell"></td>

            <td class="right-cell">
                @if (count($rightSections) > 0)
                    <table class="side-table">
                        <colgroup>
                            <col class="col-desc">
                            <col class="col-amount">
                        </colgroup>
                        @php $rightGlobalRow = 0; @endphp
                        @foreach ($rightSections as $section)
                            @php
                                $hasItems = false;
                                foreach ($section['sub_sections'] as $sub) {
                                    if (count($sub['items']) > 0) {
                                        $hasItems = true;
                                        break;
                                    }
                                }
                            @endphp

                            @if (!$hasItems)
                                @continue
                            @endif

                            <tr class="section-header">
                                <td colspan="2">{{ $section['name'] }}</td>
                            </tr>

                            @foreach ($section['sub_sections'] as $sub)
                                @if (count($sub['items']) === 0)
                                    @continue
                                @endif

                                <tr class="sub-section-header">
                                    <td colspan="2">{{ $sub['name'] }}</td>
                                </tr>

                                @foreach ($sub['items'] as $item)
                                    @php $rightGlobalRow++; @endphp
                                    @php $bal = (float) ($item['balance'] ?? 0); @endphp
                                    <tr class="{{ $rightGlobalRow % 2 === 0 ? 'row-even' : 'row-odd' }}">
                                        <td>{{ (string) ($item['account_name'] ?? '') }}</td>
                                        <td class="number nowrap">{{ fmtAmount($bal) }}</td>
                                    </tr>
                                @endforeach

                                @php $subTotal = (float) ($sub['total'] ?? 0); @endphp
                                <tr class="subtotal-row">
                                    <td>TOTAL {{ $sub['name'] }}</td>
                                    <td class="number nowrap">{{ fmtAmount($subTotal) }}</td>
                                </tr>
                            @endforeach

                            @php $secTotal = (float) ($section['total'] ?? 0); @endphp
                            <tr class="subtotal-row">
                                <td></td>
                                <td class="number nowrap">{{ fmtAmount($secTotal) }}</td>
                            </tr>
                        @endforeach
                    </table>
                @endif
            </td>
        </tr>
    </table>

    <table class="wrapper-table" style="margin-top: 4px;">
        <colgroup>
            <col style="width: 48%;">
            <col style="width: 4%;">
            <col style="width: 48%;">
        </colgroup>
        <tr class="grand-total-row">
            <td class="number nowrap" style="padding-right: 10px;">TOTAL AKTIVA {{ fmtAmount($leftGrandTotal) }}</td>
            <td></td>
            <td class="number nowrap" style="padding-right: 10px;">TOTAL KEWAJIBAN &amp; EKUITAS {{ fmtAmount($rightGrandTotal) }}</td>
        </tr>
    </table>

    @include('ascends.shared.partials.report-footer')
</body>

</html>
