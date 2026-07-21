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
        }

        .center {
            text-align: center;
        }

        .row-odd td {
            background: #c9d1df;
        }

        .row-even td {
            background: #eef2f8;
        }

        .number {
            text-align: right;
        }

        .nowrap {
            white-space: nowrap;
        }

        .empty-row td {
            text-align: center;
            font-style: italic;
            background: #c9d1df;
            font-weight: bold;
            color: #9c111d;
            font-size: 10px;
        }

        .grand-total td {
            font-weight: bold;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            font-size: 11px;
        }
    </style>
</head>

<body>
    @php
        $rows = $reportData['rows'] ?? [];
        $grandTotals = $reportData['grand_totals'] ?? [];
        $generatedAtText = \Carbon\Carbon::parse($generatedAt ?? now())
            ->locale('id')->translatedFormat('d-M-y H:i');
        $generatedByName = trim((string) ($reportData['printed_by'] ?? ''));
        $headerCompany = trim((string) ($company ?? $reportData['company'] ?? ''));
        $headerTitle = trim((string) ($title ?? $reportData['title'] ?? $fallbackTitle ?? ''));
        $headerSubtitle = trim((string) ($reportData['period_label'] ?? ''));
    @endphp

    <h1 class="report-companyTitle">{{ $headerCompany }}</h1>
    <h1 class="report-title">{{ $headerTitle }}</h1>
    <p class="report-subtitle">{{ $headerSubtitle }}</p>

    @if (count($rows) > 0)
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 30%">Supplier</th>
                    <th style="width: 14%">Saldo Awal</th>
                    <th style="width: 14%">Faktur Pembelian</th>
                    <th style="width: 14%">AP Notes</th>
                    <th style="width: 14%">Pembayaran Supplier</th>
                    <th style="width: 14%">Saldo Akhir</th>
                </tr>
            </thead>
            <tbody>
                @php $rowNum = 0; @endphp
                @foreach ($rows as $row)
                    @php $rowNum++; @endphp
                    <tr class="{{ $rowNum % 2 === 0 ? 'row-even' : 'row-odd' }}">
                        <td>{{ $row['supplier_name'] }}</td>
                        <td class="number nowrap">
                            @php $v = (float) ($row['opening'] ?? 0); @endphp
                            {{ $v != 0 ? number_format($v, 0, '.', ',') : '-' }}
                        </td>
                        <td class="number nowrap">
                            @php $v = (float) ($row['ap_purchase'] ?? 0); @endphp
                            {{ $v != 0 ? number_format($v, 0, '.', ',') : '-' }}
                        </td>
                        <td class="number nowrap">
                            @php $v = (float) ($row['ap_note'] ?? 0); @endphp
                            {{ $v != 0 ? number_format($v, 0, '.', ',') : '-' }}
                        </td>
                        <td class="number nowrap">
                            @php $v = (float) ($row['payment'] ?? 0); @endphp
                            {{ $v != 0 ? number_format($v, 0, '.', ',') : '-' }}
                        </td>
                        <td class="number nowrap">
                            @php $v = (float) ($row['ending'] ?? 0); @endphp
                            {{ $v != 0 ? number_format($v, 0, '.', ',') : '-' }}
                        </td>
                    </tr>
                @endforeach

                <tr class="grand-total">
                    <td class="center">Total</td>
                    <td class="number nowrap">
                        @php $v = (float) ($grandTotals['opening'] ?? 0); @endphp
                        {{ $v != 0 ? number_format($v, 0, '.', ',') : '-' }}
                    </td>
                    <td class="number nowrap">
                        @php $v = (float) ($grandTotals['ap_purchase'] ?? 0); @endphp
                        {{ $v != 0 ? number_format($v, 0, '.', ',') : '-' }}
                    </td>
                    <td class="number nowrap">
                        @php $v = (float) ($grandTotals['ap_note'] ?? 0); @endphp
                        {{ $v != 0 ? number_format($v, 0, '.', ',') : '-' }}
                    </td>
                    <td class="number nowrap">
                        @php $v = (float) ($grandTotals['payment'] ?? 0); @endphp
                        {{ $v != 0 ? number_format($v, 0, '.', ',') : '-' }}
                    </td>
                    <td class="number nowrap">
                        @php $v = (float) ($grandTotals['ending'] ?? 0); @endphp
                        {{ $v != 0 ? number_format($v, 0, '.', ',') : '-' }}
                    </td>
                </tr>
            </tbody>
        </table>
    @else
        <table class="data-table">
            <tbody>
                <tr class="empty-row">
                    <td colspan="6">Tidak ada data.</td>
                </tr>
            </tbody>
        </table>
    @endif

    @include('ascends.shared.partials.report-footer')
</body>

</html>