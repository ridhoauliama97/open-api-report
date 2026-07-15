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
            padding: 3px 5px;
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
            font-size: 10px;
            padding: 3px 2px;
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

        .signature-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin-top: 24px;
        }

        .signature-table td {
            border: 0;
            text-align: center;
            vertical-align: top;
            padding: 0 4px;
            line-height: 1.15;
        }

        .signature-space td {
            height: 46px;
            line-height: 46px;
            font-size: 1px;
        }

        .signature-line {
            width: 60%;
            margin: 0 auto;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .signature-line td {
            height: 1px;
            padding: 0;
            border-top: 1px solid #000;
            font-size: 1px;
            line-height: 1px;
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

        $grandTotalInvoice = 0;
        $grandTotalBayar = 0;
        $grandTotalNilaiBayar = 0;
        foreach ($records as $r) {
            $grandTotalInvoice += (float) ($r['item_amount'] ?? 0);
            $grandTotalBayar += (float) ($r['nilai_bayar'] ?? 0);
            $grandTotalNilaiBayar += (float) ($r['total_nilai_bayar'] ?? 0);
        }
    @endphp

    <h1 class="report-companyTitle">{{ $headerCompany }}</h1>
    <h1 class="report-title">{{ $headerTitle }}</h1>
    <p class="report-subtitle">{{ $headerSubtitle }}</p>

    @forelse ($records as $index => $record)
        @if ($index === 0)
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width:8%">Nama Sales</th>
                        <th style="width:16%">Nama Customer</th>
                        <th style="width:12%">No. Invoice</th>
                        <th style="width:9%">Tgl Invoice</th>
                        <th style="width:9%">Tgl Bayar</th>
                        <th style="width:4%">Lama</th>
                        <th style="width:9%">Nilai Invoice</th>
                        <th style="width:9%">Nilai Bayar</th>
                        <th style="width:9%">Total Nilai Bayar</th>
                        <th style="width:7%">Cara Bayar</th>
                        <th style="width:8%">Nama Akun Bank Penerima</th>
                    </tr>
                </thead>
                <tbody>
        @endif

        <tr class="{{ $index % 2 === 0 ? 'row-odd' : 'row-even' }}">
            <td>{{ $record['sales_person'] !== '-' ? $record['sales_person'] : '' }}</td>
            <td>{{ $record['customer_name'] }}</td>
            <td>{{ $record['item_ref'] }}</td>
            <td>{{ fmtDate($record['item_date']) }}</td>
            <td>{{ fmtDate($record['voucher_date']) }}</td>
            <td class="number">{{ (int) ($record['lama_piutang'] ?? 0) }}</td>
            <td class="number nowrap {{ ($record['item_amount'] ?? 0) < 0 ? 'number-negative' : '' }}">
                {{ fmtAmount($record['item_amount']) }}
            </td>
            <td class="number nowrap {{ ($record['nilai_bayar'] ?? 0) < 0 ? 'number-negative' : '' }}">
                {{ fmtAmount($record['nilai_bayar']) }}
            </td>
            <td class="number nowrap {{ ($record['total_nilai_bayar'] ?? 0) < 0 ? 'number-negative' : '' }}">
                {{ fmtAmount($record['total_nilai_bayar']) }}
            </td>
            <td>{{ $record['payment_method'] ?: '' }}</td>
            <td class="nowrap">{{ $record['gab_ket'] ?: '' }}</td>
        </tr>
    @empty
        <table class="data-table">
            <tbody>
                <tr class="empty-row">
                    <td colspan="11">Tidak ada data.</td>
                </tr>
            </tbody>
        </table>
    @endforelse

    @if (count($records) > 0)
        <tr class="grand-total-row">
            <td colspan="5" style="text-align: center;">Total</td>
            <td class="number"></td>
            <td class="number nowrap {{ $grandTotalInvoice < 0 ? 'number-negative' : '' }}">
                {{ fmtAmount($grandTotalInvoice) }}
            </td>
            <td class="number nowrap {{ $grandTotalBayar < 0 ? 'number-negative' : '' }}">
                {{ fmtAmount($grandTotalBayar) }}
            </td>
            <td class="number nowrap {{ $grandTotalNilaiBayar < 0 ? 'number-negative' : '' }}">
                {{ fmtAmount($grandTotalNilaiBayar) }}
            </td>
            <td colspan="2"></td>
        </tr>
        </tbody>
        </table>
    @endif

    <table class="signature-table">
        <tr>
            <td style="width: 33%; font-weight: bold;">Dibuat oleh</td>
            <td style="width: 33%; font-weight: bold;">Diperiksa oleh</td>
            <td style="width: 33%; font-weight: bold;">Diketahui oleh</td>
        </tr>
        <tr class="signature-space">
            <td></td>
            <td></td>
            <td></td>
        </tr>
        <tr>
            <td>
                <table class="signature-line">
                    <tr><td>&nbsp;</td></tr>
                </table>
            </td>
            <td>
                <table class="signature-line">
                    <tr><td>&nbsp;</td></tr>
                </table>
            </td>
            <td>
                <table class="signature-line">
                    <tr><td>&nbsp;</td></tr>
                </table>
            </td>
        </tr>
    </table>

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
