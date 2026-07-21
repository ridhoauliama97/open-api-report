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
            border: 1px solid #000;
            page-break-inside: auto;
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

        .data-table td {
            font-size: 10px;
            border-top: none;
            border-bottom: none;
        }

        .row-odd td {
            background: #c9d1df;
        }

        .row-even td {
            background: #eef2f8;
        }

        .group-header td {
            font-weight: bold;
            font-size: 10px;
            padding: 4px 3px 2px 3px;
            border-bottom: 1px solid #000;
        }

        .subtotal-row td {
            font-weight: bold;
            font-size: 10px;
            padding: 2px 3px 2px 3px;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            background: transparent;
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

        .empty-row td {
            text-align: center;
            font-style: italic;
            background: #c9d1df;
            font-weight: bold;
            color: #9c111d;
            font-size: 11px;
            padding: 4px 4px;
        }

        .center {
            text-align: center;
        }
    </style>
</head>

<body>
    @php
        $records = $reportData['records'] ?? [];
        $headerCompany = trim((string) ($company ?? ($reportData['company'] ?? '')));
        $headerTitle = trim((string) ($title ?? ($reportData['title'] ?? ($fallbackTitle ?? ''))));
        $headerSubtitle = trim((string) ($reportData['period_label'] ?? ''));
        $generatedByName = trim((string) ($reportData['printed_by'] ?? ''));

        function fmtAmount($value)
        {
            $v = (float) $value;
            if ($v < 0) {
                return '-' . number_format(abs($v), 0, '.', ',');
            }
            return number_format($v, 0, '.', ',');
        }

        function fmtDate($value)
        {
            if ($value === '' || $value === null) {
                return '';
            }
            try {
                return \Carbon\Carbon::parse($value)->locale('id')->isoFormat('DD-MMM-YY');
            } catch (\Throwable) {
                return $value;
            }
        }
    @endphp

    <h1 class="report-companyTitle">{{ $headerCompany }}</h1>
    <h1 class="report-title">{{ $headerTitle }}</h1>
    <p class="report-subtitle">{{ $headerSubtitle }}</p>

    @forelse ($records as $index => $record)
        @if ($index === 0)
            <table class="data-table">
                <colgroup>
                    <col style="width:17%">
                    <col style="width:24%">
                    <col style="width:13%">
                    <col style="width:13%">
                    <col style="width:7%">
                    <col style="width:26%">
                </colgroup>
                <thead>
                    <tr>
                        <th>No Voucher</th>
                        <th>Nama Customer</th>
                        <th>Tgl. Invoice</th>
                        <th>Tgl. Voucher</th>
                        <th>Hari</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
        @endif

                @php
                    $prev = $records[$index - 1] ?? null;
                    $showGroup = $prev === null || $prev['group_key'] !== $record['group_key'];
                @endphp

                @if ($showGroup)
                    @php
                        $subtotal = 0;
                        foreach ($records as $r) {
                            if ($r['group_key'] === $record['group_key']) {
                                $subtotal += (float) ($r['total'] ?? 0);
                            }
                        }
                        $dataRowIndex = -1;
                    @endphp
                    <tr class="group-header">
                        <td colspan="6">
                            {{ $record['sales_person'] !== '-' ? $record['sales_person'] : $record['group_key'] }}
                        </td>
                    </tr>
                @endif

                @php $dataRowIndex++; @endphp

                <tr class="{{ $dataRowIndex % 2 === 0 ? 'row-odd' : 'row-even' }}">
                    <td>{{ $record['voucher_no'] }}</td>
                    <td>{{ $record['customer_name'] }}</td>
                    <td style="text-align: center;">{{ fmtDate($record['item_date']) }}</td>
                    <td style="text-align: center;">{{ fmtDate($record['voucher_date']) }}</td>
                    <td class="number">{{ (int) ($record['hari'] ?? 0) }}</td>
                    <td class="number nowrap {{ ($record['total'] ?? 0) < 0 ? 'number-negative' : '' }}">
                        {{ fmtAmount($record['total']) }}
                    </td>
                </tr>

                @php
                    $next = $records[$index + 1] ?? null;
                    $isLastInGroup = $next === null || $next['group_key'] !== $record['group_key'];
                @endphp

                @if ($isLastInGroup)
                    <tr class="subtotal-row">
                        <td colspan="5" class="center">Total</td>
                        <td class="number nowrap">{{ fmtAmount($subtotal) }}</td>
                    </tr>
                @endif
    @empty
                <table class="data-table">
                    <colgroup>
                        <col style="width:17%">
                        <col style="width:24%">
                        <col style="width:13%">
                        <col style="width:13%">
                        <col style="width:7%">
                        <col style="width:26%">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>No Voucher</th>
                            <th>Nama Customer</th>
                            <th>Tgl. Invoice</th>
                            <th>Tgl. Voucher</th>
                            <th>Hari</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="empty-row">
                            <td colspan="6">Tidak ada data.</td>
                        </tr>
                    </tbody>
                </table>
            @endforelse

            @if (count($records) > 0)
                    </tbody>
                </table>
            @endif

    <htmlpagefooter name="reportFooter">
        <table style="width: 100%; border-collapse: collapse; border: 0; margin: 0; padding: 0;">
            <tr>
                <td
                    style="border: 0; padding: 0; text-align: left; font-family: 'Noto Serif', serif; font-size: 8px; font-style: italic;">
                    Print by {{ $generatedByName ?: 'sistem' }} on {{ now()->format('d/m/Y H:i:s') }}
                </td>
                <td
                    style="border: 0; padding: 0; text-align: right; font-family: 'Noto Serif', serif; font-size: 8px; font-style: italic;">
                    Page {PAGENO} of {nbpg}
                </td>
            </tr>
        </table>
    </htmlpagefooter>
    <sethtmlpagefooter name="reportFooter" value="on" />
</body>

</html>