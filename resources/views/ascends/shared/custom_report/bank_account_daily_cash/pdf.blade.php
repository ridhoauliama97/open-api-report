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
            line-height: 1.2;
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

        .bank-label {
            text-align: left;
            margin: 10px 0 4px 0;
            font-size: 12px;
            font-weight: bold;
            color: #9c111d;
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
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            text-align: center;
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

        .subtotal-row td {
            font-weight: bold;
            font-size: 11px;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
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
            color: #9c111d;
            font-weight: bold;
            font-size: 10px;
            padding: 5px 4px;
        }
    </style>
</head>

<body>
    @php
        $sections = $reportData['rows'] ?? [];
        $periodLabel = trim((string) ($reportData['period_label'] ?? ''));

        $headerCompany = trim((string) ($company ?? ($reportData['company'] ?? '')));
        $headerTitle = trim((string) ($title ?? ($reportData['title'] ?? '')));

        if (!function_exists('fmtAmount_bank_account_daily_cash')) {
            function fmtAmount_bank_account_daily_cash($value)
            {
                $v = (float) $value;
                if ($v == 0.0) {
                    return '';
                }
                return number_format($v, 2, '.', ',');
            }
        }

        if (!function_exists('fmtDate_bank_account_daily_cash')) {
            function fmtDate_bank_account_daily_cash($value)
            {
                if (empty($value)) {
                    return '';
                }
                return $value->locale('id')->isoFormat('DD-MMM-YY');
            }
        }
    @endphp

    <h1 class="report-companyTitle">{{ $headerCompany }}</h1>
    <h1 class="report-title">{{ $headerTitle }}</h1>
    @if ($periodLabel !== '')
        <p class="report-subtitle">{{ $periodLabel }}</p>
    @endif

    @forelse ($sections as $section)
        <?php    $rowIdx = 0; ?>

        <p class="bank-label">{{ $section['bank_name'] }}</p>

        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 10%">Tanggal</th>
                    <th style="width: 26%">Keterangan</th>
                    <th style="width: 14%">Pemasukan</th>
                    <th style="width: 14%">Pengeluaran</th>
                    <th style="width: 26%">Keterangan</th>
                    <th style="width: 10%">Tanggal</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($section['rows'] as $detail)
                    @php $rowIdx++; @endphp
                    <tr class="{{ $rowIdx % 2 === 0 ? 'row-even' : 'row-odd' }}">
                        <td class="center nowrap">{{ fmtDate_bank_account_daily_cash($detail['receive_date'] ?? null) }}</td>
                        <td>{{ $detail['receive_remark'] ?? '' }}</td>
                        <td class="number nowrap">{{ fmtAmount_bank_account_daily_cash($detail['receive_amount'] ?? 0) }}</td>
                        <td class="number nowrap">{{ fmtAmount_bank_account_daily_cash($detail['payment_amount'] ?? 0) }}</td>
                        <td>{{ $detail['payment_remark'] ?? '' }}</td>
                        <td class="center nowrap">{{ fmtDate_bank_account_daily_cash($detail['payment_date'] ?? null) }}</td>
                    </tr>
                @endforeach

                <tr class="subtotal-row">
                    <td></td>
                    <td>Total Pemasukan</td>
                    <td class="number nowrap">{{ fmtAmount_bank_account_daily_cash($section['total_receive_amount']) }}</td>
                    <td class="number nowrap">{{ fmtAmount_bank_account_daily_cash($section['total_payment_amount']) }}</td>
                    <td>Total Pengeluaran</td>
                    <td></td>
                </tr>
            </tbody>
        </table>
    @empty
        <table class="data-table">
            <tbody>
                <tr class="empty-row">
                    <td colspan="6">Tidak ada data.</td>
                </tr>
            </tbody>
        </table>
    @endforelse

    @include('ascends.shared.partials.report-footer')
</body>

</html>