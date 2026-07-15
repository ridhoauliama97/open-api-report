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
            padding: 2px 2px;
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

        .section-header {
            font-weight: bold;
            font-size: 12px;
            padding: 4px 2px;
        }

        .sub-section-header td {
            font-weight: bold;
            font-size: 11px;
            padding: 3px 2px;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
        }

        .item-row td {
            padding: 3px 2px;
            border-bottom: 1px solid #000;
        }

        .subtotal-row td {
            font-weight: bold;
            font-size: 10px;
            padding: 3px 4px;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
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

        .number {
            text-align: right;
        }

        .nowrap {
            white-space: nowrap;
        }

        .number-negative {
            color: #9c111d;
        }

        .col-voucher {
            width: 28%;
        }

        .col-date {
            width: 22%;
        }

        .col-days {
            width: 18%;
        }

        .col-payment {
            width: 32%;
        }
    </style>
</head>

<body>
    @php
        $collectors = $reportData['collectors'] ?? [];
        $headerCompany = trim((string) ($company ?? ($reportData['company'] ?? '')));
        $headerTitle = trim((string) ($title ?? ($reportData['title'] ?? ($fallbackTitle ?? ''))));
        $headerSubtitle = trim((string) ($reportData['period_label'] ?? ''));
        $generatedByName = trim((string) ($reportData['printed_by'] ?? ''));

        function fmtAmount($value)
        {
            $v = (float) $value;
            if ($v < 0) {
                return '- ' . number_format(abs($v), 0, '.', ',');
            }
            if ($v == 0.0) {
                return '0.00';
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

    @forelse ($collectors as $collector)
        <div class="section-header">Nama Collector : {{ $collector['collector_name'] ?? '' }}</div>
        <table class="data-table">
            <colgroup>
                <col class="col-voucher">
                <col class="col-date">
                <col class="col-days">
                <col class="col-payment">
            </colgroup>
            <thead>
                <tr>
                    <th>Nomor Penagihan</th>
                    <th>Tanggal Tagih</th>
                    <th>Beda Hari</th>
                    <th>Pembayaran</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($collector['customer_groups'] ?? [] as $customer)
                    <tr class="sub-section-header">
                        <td colspan="4">Customer : {{ $customer['customer_name'] ?? '' }}</td>
                    </tr>

                    @foreach ($customer['invoice_groups'] ?? [] as $invoice)
                        <tr class="item-row">
                            <td colspan="4">
                                {{ $invoice['item_ref'] ?? '' }}
                                Tanggal Invoice : {{ fmtDate($invoice['item_date'] ?? '') }}
                                Nilai Invoice : <strong>{{ fmtAmount($invoice['item_amount'] ?? 0) }}</strong>
                            </td>
                        </tr>

                        @forelse ($invoice['vouchers'] ?? [] as $vIndex => $voucher)
                            <tr class="{{ $vIndex % 2 === 0 ? 'row-odd' : 'row-even' }}">
                                <td>{{ $voucher['voucher_no'] ?? '' }}</td>
                                <td>{{ fmtDate($voucher['voucher_date'] ?? '') }}</td>
                                <td>{{ abs($voucher['beda_hari'] ?? 0) }} Hari</td>
                                <td
                                    class="number nowrap {{ ($voucher['payment'] ?? 0) < 0 ? 'number-negative' : '' }}">
                                    {{ fmtAmount($voucher['payment'] ?? 0) }}
                                </td>
                            </tr>
                        @empty
                            <tr class="empty-row">
                                <td colspan="4">Tidak ada data.</td>
                            </tr>
                        @endforelse

                        <tr class="subtotal-row">
                            <td colspan="4" class="number">
                                Total Pembayaran : {{ fmtAmount($invoice['total_payment'] ?? 0) }},
                                Sisa : {{ fmtAmount($invoice['sisa'] ?? 0) }}
                            </td>
                        </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>
        @empty
            <table class="data-table">
                <tbody>
                    <tr class="empty-row">
                        <td colspan="4">Tidak ada data.</td>
                    </tr>
                </tbody>
            </table>
        @endforelse

        @include('ascends.shared.partials.report-footer')
    </body>

    </html>
