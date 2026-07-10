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
            padding: 1px 2px;
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

        .sub-section-header td {
            font-weight: bold;
            font-size: 10px;
            padding: 2px 4px;
            color: #9c111d;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
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

        .row-odd td {
            background: #c9d1df;
        }

        .row-even td {
            background: #eef2f8;
        }

        .indent-item td:first-child {
            padding-left: 8px;
        }

        .indent-subsection td:first-child {
            padding-left: 4px;
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
    </style>
</head>

<body>
    @php
        $sections = $reportData['sections'] ?? [];
        $periods = $reportData['periods'] ?? [];
        $periodCount = count($periods);
        $grandTotals = $reportData['grand_totals'] ?? [];
        $grandTotal = $reportData['grand_total'] ?? 0;
        $generatedAtText = \Carbon\Carbon::parse($generatedAt ?? now())
            ->locale('id')
            ->translatedFormat('d-M-y H:i');
        $generatedByName = trim((string) ($reportData['printed_by'] ?? ''));
        $headerCompany = trim((string) ($company ?? ($reportData['company'] ?? '')));
        $headerTitle = trim((string) ($title ?? ($reportData['title'] ?? ($fallbackTitle ?? ''))));
        $headerSubtitle = trim((string) ($reportData['period_label'] ?? ''));

        $totalCols = 2 + $periodCount; // Keterangan + N periods + Total

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
            <colgroup>
                <col style="width: 28%;">
                @foreach ($periods as $period)
                    <col style="width: {{ round(64 / ($periodCount + 1), 1) }}%;">
                @endforeach
                <col style="width: {{ round(64 / ($periodCount + 1), 1) }}%;">
            </colgroup>
            <thead>
                <tr>
                    <th style="width: 28%;">Keterangan</th>
                    @foreach ($periods as $period)
                        <th>{{ $period }}</th>
                    @endforeach
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @php $globalRow = 0; @endphp
                @foreach ($sections as $section)
                    <tr class="section-header">
                        <td colspan="{{ $totalCols }}">{{ $section['section_code'] }} {{ $section['section_name'] }}
                        </td>
                    </tr>

                    @foreach ($section['sub_sections'] as $subIndex => $subSection)
                        @if ($section['section_code'] === '700.000.000' && count($section['sub_sections']) > 1 && $subIndex > 0)
                            <tr class="sub-section-header indent-subsection">
                                <td colspan="{{ $totalCols }}">{{ $section['section_code'] }}
                                    {{ $subSection['sub_name'] }}</td>
                            </tr>
                        @endif

                        @foreach ($subSection['items'] as $item)
                            @php $globalRow++; @endphp
                            <tr class="{{ $globalRow % 2 === 0 ? 'row-even' : 'row-odd' }} indent-item">
                                <td>{{ (string) ($item['account_name'] ?? '') }}</td>
                                @foreach ($item['amounts'] as $amt)
                                    <td class="number nowrap">{{ $amt != 0 ? fmtAmount($amt) : '-' }}</td>
                                @endforeach
                                <td class="number nowrap">{{ $item['total'] != 0 ? fmtAmount($item['total']) : '-' }}
                                </td>
                            </tr>
                        @endforeach

                        @if ($section['section_code'] === '700.000.000' && count($section['sub_sections']) > 1)
                            <tr class="subtotal-row indent-subsection">
                                <td>Total {{ $subSection['sub_name'] }}</td>
                                @foreach ($subSection['subtotals'] as $st)
                                    <td class="number nowrap">{{ $st != 0 ? fmtAmount($st) : '-' }}</td>
                                @endforeach
                                <td class="number nowrap">
                                    {{ $subSection['subtotal_total'] != 0 ? fmtAmount($subSection['subtotal_total']) : '-' }}
                                </td>
                            </tr>
                        @endif
                    @endforeach

                    @if ($section['section_code'] === '700.000.000' && count($section['sub_sections']) > 1)
                        <tr class="subtotal-row">
                            <td>Total {{ $section['section_name'] }}</td>
                            @foreach ($section['subtotals'] as $st)
                                <td class="number nowrap">{{ $st != 0 ? fmtAmount($st) : '-' }}</td>
                            @endforeach
                            <td class="number nowrap">
                                {{ $section['subtotal_total'] != 0 ? fmtAmount($section['subtotal_total']) : '-' }}
                            </td>
                        </tr>
                    @else
                        <tr class="subtotal-row">
                            <td>Total</td>
                            @foreach ($section['subtotals'] as $st)
                                <td class="number nowrap">{{ $st != 0 ? fmtAmount($st) : '-' }}</td>
                            @endforeach
                            <td class="number nowrap">
                                {{ $section['subtotal_total'] != 0 ? fmtAmount($section['subtotal_total']) : '-' }}
                            </td>
                        </tr>
                    @endif
                @endforeach

                <tr class="grand-total-row">
                    <td>Grand Total</td>
                    @foreach ($grandTotals as $gt)
                        <td class="number nowrap">{{ $gt != 0 ? fmtAmount($gt) : '-' }}</td>
                    @endforeach
                    <td class="number nowrap">{{ $grandTotal != 0 ? fmtAmount($grandTotal) : '-' }}</td>
                </tr>
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
