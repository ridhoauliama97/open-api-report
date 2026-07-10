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

        .group-row td {
            font-weight: bold;
            font-size: 10px;
            font-style: italic;
            color: #9c111d;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding: 3px 4px;
        }

        .grand-row td {
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
        $rows = $reportData['rows'] ?? [];
        $groupName = (string) ($reportData['group_name'] ?? 'Beban Penjualan');
        $totalDebit = (float) ($reportData['total_debit'] ?? 0);
        $totalKredit = (float) ($reportData['total_kredit'] ?? 0);
        $totalSaldo = (float) ($reportData['total_saldo'] ?? 0);
        $generatedAtText = \Carbon\Carbon::parse($generatedAt ?? now())
            ->locale('id')
            ->translatedFormat('d-M-y H:i');
        $generatedByName = trim((string) ($reportData['printed_by'] ?? ''));
        $headerCompany = trim((string) ($company ?? ($reportData['company'] ?? '')));
        $headerTitle = trim((string) ($title ?? ($reportData['title'] ?? ($fallbackTitle ?? ''))));
        $headerSubtitle = trim((string) ($reportData['period_label'] ?? ''));

        $formatAmount = static function ($value): string {
            $value = (float) $value;

            if ($value == 0.0) {
                return '-';
            }

            if ($value < 0) {
                return '(' . number_format(abs($value), 0, '.', ',') . ')';
            }

            return number_format($value, 0, '.', ',');
        };
    @endphp

    <h1 class="report-companyTitle">{{ $headerCompany }}</h1>
    <h1 class="report-title">{{ $headerTitle }}</h1>
    <p class="report-subtitle">{{ $headerSubtitle }}</p>

    @if (count($rows) > 0)
        <table class="data-table">
            <colgroup>
                <col style="width: 16%;">
                <col style="width: 36%;">
                <col style="width: 16%;">
                <col style="width: 16%;">
                <col style="width: 16%;">
            </colgroup>
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Account Name</th>
                    <th>Debit</th>
                    <th>Kredit</th>
                    <th>Saldo</th>
                </tr>
            </thead>
            <tbody>
                <tr class="group-row">
                    <td colspan="5">{{ $groupName }}</td>
                </tr>
                @foreach ($rows as $index => $row)
                    <tr class="{{ ($index + 1) % 2 === 0 ? 'row-even' : 'row-odd' }}">
                        <td class="center nowrap">{{ (string) ($row['code'] ?? '') }}</td>
                        <td>{{ (string) ($row['account_name'] ?? '') }}</td>
                        <td class="number nowrap">{{ $formatAmount($row['debit'] ?? 0) }}</td>
                        <td class="number nowrap">{{ $formatAmount($row['kredit'] ?? 0) }}</td>
                        <td class="number nowrap">{{ $formatAmount($row['saldo'] ?? 0) }}</td>
                    </tr>
                @endforeach
                <tr class="grand-row">
                    <td colspan="2">Grand Total</td>
                    <td class="number nowrap">{{ $formatAmount($totalDebit) }}</td>
                    <td class="number nowrap">{{ $formatAmount($totalKredit) }}</td>
                    <td class="number nowrap">{{ $formatAmount($totalSaldo) }}</td>
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
