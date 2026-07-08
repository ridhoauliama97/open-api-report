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
            font-size: 10px;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            text-align: center;
        }

        .data-table td {
            font-size: 10px;
            border-top: none;
            border-bottom: none;
        }

        .account-header td {
            font-weight: bold;
            font-size: 10px;
            font-style: italic;
            padding: 4px 4px;
            color: #9c111d;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
        }

        .account-subtotal td {
            font-weight: bold;
            font-size: 10px;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding: 3px 4px;
        }

        .grand-row td {
            font-weight: bold;
            font-size: 10px;
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

        .empty-row td {
            text-align: center;
            font-style: italic;
            background: #c9d1df;
            font-weight: bold;
            color: #9c111d;
            font-size: 11px;
            padding: 8px 4px;
        }

        .col-no {
            width: 5%;
        }

        .col-nama {
            width: 22%;
        }

        .col-keterangan {
            width: 28%;
        }

        .col-debet {
            width: 15%;
        }

        .col-kredit {
            width: 15%;
        }

        .col-jumlah {
            width: 15%;
        }

        .summary-table {
            width: 60%;
            margin: 0 auto;
            border-collapse: collapse;
            table-layout: fixed;
            border: 1px solid #000;
        }

        .summary-table th {
            border: 1px solid #000;
            padding: 3px 4px;
            vertical-align: middle;
            word-wrap: break-word;
        }

        .summary-table td {
            border-left: 1px solid #000;
            border-right: 1px solid #000;
            padding: 3px 4px;
            vertical-align: middle;
            word-wrap: break-word;
        }

        .summary-table th {
            font-weight: bold;
            font-size: 10px;
            text-align: center;
        }

        .summary-table td {
            font-size: 10px;
        }

        .summary-title {
            text-align: center;
            margin: 0 0 15px 0;
            font-size: 14px;
            font-weight: bold;
        }

        .summary-table .grand-row td {
            font-weight: bold;
            font-size: 10px;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding: 3px 4px;
        }
    </style>
</head>

<body>
    @php
        $groups = $reportData['groups'] ?? [];
        $grandTotalDb = (float) ($reportData['grand_total_db'] ?? 0);
        $grandTotalCr = (float) ($reportData['grand_total_cr'] ?? 0);
        $grandTotal = (float) ($reportData['grand_total'] ?? 0);
        $summary = $reportData['summary'] ?? [];
        $generatedAtText = \Carbon\Carbon::parse($generatedAt ?? now())
            ->locale('id')
            ->translatedFormat('d-M-y H:i');
        $generatedByName = trim((string) ($reportData['printed_by'] ?? ''));
        $headerCompany = trim((string) ($company ?? ($reportData['company'] ?? '')));
        $headerTitle = trim((string) ($title ?? ($reportData['title'] ?? ($fallbackTitle ?? ''))));
        $headerSubtitle = trim((string) ($reportData['period_label'] ?? ''));

        function formatAmount($value)
        {
            return number_format((float) $value, 0, ',', '.');
        }
    @endphp

    <h1 class="report-companyTitle">{{ $headerCompany }}</h1>
    <h1 class="report-title">{{ $headerTitle }}</h1>
    <p class="report-subtitle">{{ $headerSubtitle }}</p>

    @if (count($groups) > 0)
        <table class="data-table">
            <thead>
                <tr>
                    <th class="col-no">No</th>
                    <th class="col-nama">Kode & Nama Perkiraan</th>
                    <th class="col-keterangan">Keterangan</th>
                    <th class="col-debet">Debet</th>
                    <th class="col-kredit">Kredit</th>
                    <th class="col-jumlah">Jumlah</th>
                </tr>
            </thead>
            <tbody>
                @php $globalRow = 0; @endphp
                @foreach ($groups as $groupIndex => $group)
                    @php $itemIndex = 0; @endphp
                    @foreach ($group['items'] as $item)
                        @php
                            $globalRow++;
                            $itemIndex++;
                        @endphp
                        <tr class="{{ $globalRow % 2 === 0 ? 'row-even' : 'row-odd' }}">
                            <td class="center">{{ $itemIndex }}</td>
                            <td>{{ $itemIndex === 1 ? (string) ($group['account_code'] ?? '') . ' ' . (string) ($group['account_name'] ?? '') : '' }}
                            </td>
                            <td>{{ (string) ($item['description'] ?? '') }}</td>
                            <td class="number nowrap">{{ formatAmount($item['amount_db'] ?? 0) }}</td>
                            <td class="number nowrap">{{ formatAmount($item['amount_cr'] ?? 0) }}</td>
                            <td class="number nowrap">{{ formatAmount($item['amount'] ?? 0) }}</td>
                        </tr>
                    @endforeach
                    <tr class="account-subtotal">
                        <td colspan="3" class="center">Total {{ (string) ($group['account_name'] ?? '') }} :</td>
                        <td class="number nowrap">{{ formatAmount($group['subtotal_db'] ?? 0) }}</td>
                        <td class="number nowrap">{{ formatAmount($group['subtotal_cr'] ?? 0) }}</td>
                        <td class="number nowrap">{{ formatAmount($group['subtotal'] ?? 0) }}</td>
                    </tr>
                @endforeach

                <tr class="grand-row">
                    <td colspan="3" class="center" style="font-size: 11px;">Grand Total</td>
                    <td class="number nowrap">{{ formatAmount($grandTotalDb) }}</td>
                    <td class="number nowrap">{{ formatAmount($grandTotalCr) }}</td>
                    <td class="number nowrap">{{ formatAmount($grandTotal) }}</td>
                </tr>
            </tbody>
        </table>

        @if (count($summary) > 0)
            <div style="page-break-before: always;"></div>
            <h2 class="summary-title">Rekapitulasi Perkiraan</h2>
            <table class="summary-table">
                <thead>
                    <tr>
                        <th style="width: 25%;">Kode Perkiraan</th>
                        <th style="width: 45%;">Nama Perkiraan</th>
                        <th style="width: 30%;">Jumlah</th>
                    </tr>
                </thead>
                <tbody>
                    @php $summaryRow = 0; @endphp
                    @foreach ($summary as $s)
                        @php $summaryRow++; @endphp
                        <tr class="{{ $summaryRow % 2 === 0 ? 'row-even' : 'row-odd' }}">
                            <td>{{ (string) ($s['account_code'] ?? '') }}</td>
                            <td>{{ (string) ($s['account_name'] ?? '') }}</td>
                            <td class="number nowrap">{{ formatAmount($s['subtotal'] ?? 0) }}</td>
                        </tr>
                    @endforeach
                    <tr class="grand-row">
                        <td colspan="2" class="center" style="font-size: 11px;">Grand Total</td>
                        <td class="number nowrap">{{ formatAmount($grandTotal) }}</td>
                    </tr>
                </tbody>
            </table>
        @endif
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
