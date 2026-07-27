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

        .grand-total-row td {
            font-weight: bold;
            font-size: 11px;
            padding: 3px 2px;
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

        .center {
            text-align: center;
        }

        .salesman-header td {
            font-weight: bold;
            font-style: italic;
            font-size: 11px;
            padding: 4px 3px;
            border-bottom: 1px solid #000;
        }

        .salesman-subtotal td {
            font-weight: bold;
            font-size: 10px;
            padding: 3px 2px;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
        }
    </style>
</head>

<body>
    @php
        $salesmenGroups = $reportData['salesmen_groups'] ?? [];
        $grandTotalLine = (float) ($reportData['grand_total_line'] ?? 0);
        $grandTotalVoucher = (float) ($reportData['grand_total_voucher'] ?? 0);
        $headerCompany = trim((string) ($company ?? ($reportData['company'] ?? '')));
        $headerTitle = trim((string) ($title ?? ($reportData['title'] ?? ($fallbackTitle ?? ''))));
        $headerSubtitle = trim((string) ($reportData['period_label'] ?? ''));
        $generatedByName = trim((string) ($reportData['printed_by'] ?? ''));

        function fmtAmount($value)
        {
            $v = (float) $value;
            if ($v < 0) {
                return '(' . number_format(abs($v), 0, '.', ',') . ')';
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

    @forelse ($salesmenGroups as $salesmanIdx => $salesman)
        @if ($loop->first)
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width:14%">Pelanggan</th>
                        <th style="width:12%">No. Invoice</th>
                        <th style="width:12%">Tgl. Invoice</th>
                        <th style="width:12%">Tgl. Voucher</th>
                        <th style="width:13%">Nilai Invoice</th>
                        <th style="width:13%">Total Bayar</th>
                        <th style="width:5%">Umur</th>
                        <th style="width:10%">Ket. Hari</th>
                        <th style="width:12%">Status</th>
                    </tr>
                </thead>
                <tbody>
        @endif

                <tr class="salesman-header">
                    <td colspan="9">Salesman : {{ $salesman['salesman_name'] }}</td>
                </tr>

                @foreach ($salesman['records'] as $idx => $record)
                    <tr class="{{ ($salesmanIdx + $idx) % 2 === 0 ? 'row-odd' : 'row-even' }}">
                        <td>{{ $record['customer_name'] }}</td>
                        <td>{{ $record['item_ref'] }}</td>
                        <td class="center">{{ fmtDate($record['item_date']) }}</td>
                        <td class="center">{{ fmtDate($record['voucher_date']) }}</td>
                        <td class="number nowrap {{ ($record['line_total'] ?? 0) < 0 ? 'number-negative' : '' }}">
                            {{ fmtAmount($record['line_total']) }}
                        </td>
                        <td class="number nowrap {{ ($record['total_voucher'] ?? 0) < 0 ? 'number-negative' : '' }}">
                            {{ fmtAmount($record['total_voucher']) }}
                        </td>
                        <td class="number">{{ (int) ($record['age'] ?? 0) }}</td>
                        <td class="center">{{ $record['ket_hari'] ?: '' }}</td>
                        <td class="center"
                            style=" {{ $record['status'] === 'Belum Lunas' ? 'color: #9c111d; font-weight: bold;' : '' }}">
                            {{ $record['status'] }}
                        </td>
                    </tr>
                @endforeach

                <tr class="salesman-subtotal">
                    <td colspan="4" class="center">Sub Total {{ $salesman['salesman_name'] }}</td>
                    <td class="number nowrap {{ ($salesman['subtotal_line'] ?? 0) < 0 ? 'number-negative' : '' }}">
                        {{ fmtAmount($salesman['subtotal_line']) }}
                    </td>
                    <td class="number nowrap {{ ($salesman['subtotal_voucher'] ?? 0) < 0 ? 'number-negative' : '' }}">
                        {{ fmtAmount($salesman['subtotal_voucher']) }}
                    </td>
                    <td colspan="3"></td>
                </tr>

                @if ($loop->last)
                            <tr class="grand-total-row">
                                <td colspan="4" class="center">Grand Total</td>
                                <td class="number nowrap {{ $grandTotalLine < 0 ? 'number-negative' : '' }}">
                                    {{ fmtAmount($grandTotalLine) }}
                                </td>
                                <td class="number nowrap {{ $grandTotalVoucher < 0 ? 'number-negative' : '' }}">
                                    {{ fmtAmount($grandTotalVoucher) }}
                                </td>
                                <td colspan="3"></td>
                            </tr>
                        </tbody>
                    </table>
                @endif
    @empty
        <table class="data-table">
            <tbody>
                <tr class="empty-row">
                    <td colspan="9">Tidak ada data.</td>
                </tr>
            </tbody>
        </table>
    @endforelse

    @include('ascends.shared.partials.report-footer')
</body>

</html>