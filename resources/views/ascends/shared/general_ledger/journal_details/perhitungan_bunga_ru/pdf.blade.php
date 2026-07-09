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

        .grand-row td {
            font-weight: bold;
            font-size: 10px;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding: 3px 4px;
        }

        .saldo-awal-row td {
            font-weight: bold;
            font-size: 10px;
            font-style: italic;
            background: #fff;
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

        .col-date {
            width: 11%;
        }

        .col-voucher {
            width: 20%;
        }

        .col-remark {
            width: 36%;
        }

        .col-debet {
            width: 12%;
        }

        .col-kredit {
            width: 12%;
        }

        .col-saldo {
            width: 9%;
        }

        .col-b-date {
            width: 18%;
        }

        .col-b-hari {
            width: 10%;
        }

        .col-b-saldo {
            width: 36%;
        }

        .col-b-bunga {
            width: 36%;
        }

        .signature-section {
            width: 100%;
            margin-top: 60px;
            border-collapse: collapse;
        }

        .signature-section td {
            text-align: center;
            vertical-align: top;
            padding: 0 10px;
            width: 33.33%;
        }

        .signature-section .sign-label {
            font-weight: bold;
            font-size: 10px;
            margin-bottom: 4px;
        }

        .signature-section .sign-line {
            margin-top: 60px;
            border-top: 1px solid #000;
            padding-top: 4px;
            font-size: 10px;
        }

        .grand-label {
            font-weight: bold;
            font-size: 10px;
        }
    </style>
</head>

<body>
    @php
        $piutang = $reportData['piutang'] ?? [];
        $bunga = $reportData['bunga'] ?? [];
        $saldoAwal = (float) ($reportData['saldo_awal'] ?? 0);
        $totalDebet = (float) ($reportData['total_debet'] ?? 0);
        $totalKredit = (float) ($reportData['total_kredit'] ?? 0);
        $totalBunga = (float) ($reportData['total_bunga'] ?? 0);
        $totalHari = (int) ($reportData['total_hari'] ?? 0);
        $generatedAtText = \Carbon\Carbon::parse($generatedAt ?? now())
            ->locale('id')->translatedFormat('d-M-y H:i');
        $generatedByName = trim((string) ($reportData['printed_by'] ?? ''));
        $headerCompany = trim((string) ($company ?? $reportData['company'] ?? ''));
        $headerTitle = trim((string) ($title ?? $reportData['title'] ?? $fallbackTitle ?? ''));
        $periodLabel = trim((string) ($reportData['period_label'] ?? ''));
        $periodRange = trim((string) ($reportData['period_range'] ?? ''));
        $bungaLabel = trim((string) ($reportData['bunga_label'] ?? ''));
        $monthName = trim((string) ($reportData['month_name'] ?? ''));
        $yearName = trim((string) ($reportData['year_name'] ?? ''));

        function formatAmount($value)
        {
            $value = (float) $value;
            if ($value < 0) {
                return '-' . number_format(abs($value), 2, ',', '.');
            }
            return number_format($value, 2, ',', '.');
        }
    @endphp

    {{-- PAGE 1: PIUTANG RU --}}
    <h1 class="report-companyTitle">{{ $headerCompany }}</h1>
    <h1 class="report-title">{{ $periodLabel }}</h1>
    <p class="report-subtitle">{{ $periodRange }}</p>

    @if (count($piutang) > 0)
        <table class="data-table">
            <thead>
                <tr>
                    <th class="col-date">Tanggal</th>
                    <th class="col-voucher">Nomor Voucher</th>
                    <th class="col-remark">Keterangan</th>
                    <th class="col-debet">Debit</th>
                    <th class="col-kredit">Kredit</th>
                    <th class="col-saldo">Sisa Saldo</th>
                </tr>
            </thead>
            <tbody>
                <tr class="saldo-awal-row">
                    <td colspan="3">Saldo Awal = {{ formatAmount($saldoAwal) }}</td>
                    <td></td>
                    <td></td>
                    <td class="number nowrap">{{ formatAmount($saldoAwal) }}</td>
                </tr>

                @php $globalRow = 0; @endphp
                @foreach ($piutang as $row)
                    @php $globalRow++; @endphp
                    <tr class="{{ $globalRow % 2 === 0 ? 'row-even' : 'row-odd' }}">
                        <td>{{ (string) ($row['date'] ?? '') }}</td>
                        <td>{{ (string) ($row['voucher_number'] ?? '') }}</td>
                        <td>{{ (string) ($row['remark'] ?? '') }}</td>
                        <td class="number nowrap">{{ $row['debet'] > 0 ? formatAmount($row['debet']) : '0,00' }}</td>
                        <td class="number nowrap">{{ $row['kredit'] > 0 ? formatAmount($row['kredit']) : '0,00' }}</td>
                        <td class="number nowrap">{{ formatAmount($row['saldo'] ?? 0) }}</td>
                    </tr>
                @endforeach

                <tr class="grand-row">
                    <td colspan="3" class="center">TOTAL Pinjaman RU Bulan {{ $monthName }} - {{ $yearName }}
                    </td>
                    <td class="number nowrap">{{ formatAmount($totalDebet) }}</td>
                    <td class="number nowrap">{{ formatAmount($totalKredit) }}</td>
                    <td class="number nowrap">{{ formatAmount($saldoAwal) }}</td>
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

    <pagebreak />

    {{-- PAGE 2: PERHITUNGAN BUNGA --}}
    <h1 class="report-companyTitle">{{ $headerCompany }}</h1>
    <h1 class="report-title">{{ $bungaLabel }}</h1>

    @if (count($bunga) > 0)
        <table class="data-table" style="margin-top: 20px;">
            <thead>
                <tr>
                    <th class="col-b-date">Tanggal</th>
                    <th class="col-b-hari">Hari</th>
                    <th class="col-b-saldo">Saldo</th>
                    <th class="col-b-bunga">Bunga</th>
                </tr>
            </thead>
            <tbody>
                @php $globalRow = 0; @endphp
                @foreach ($bunga as $row)
                    @php $globalRow++; @endphp
                    <tr class="{{ $globalRow % 2 === 0 ? 'row-even' : 'row-odd' }}">
                        <td>{{ (string) ($row['date'] ?? '') }}</td>
                        <td class="center">{{ (int) ($row['hari'] ?? 0) }}</td>
                        <td class="number nowrap">{{ formatAmount($row['saldo'] ?? 0) }}</td>
                        <td class="number nowrap">{{ formatAmount($row['bunga'] ?? 0) }}</td>
                    </tr>
                @endforeach

                <tr class="grand-row">

                    <td colspan="3" class="center">Total Bunga RU Bulan {{ $monthName }} {{ $yearName }} </td>
                    <td class="number nowrap">{{ formatAmount($totalBunga) }}</td>
                </tr>
            </tbody>
        </table>

        <table class="signature-section">
            <tr>
                <td>
                    <div class="sign-label">DIBUAT OLEH,</div>
                    <div class="sign-line">({{ $generatedByName !== '' ? $generatedByName : ' ' }})</div>
                </td>
                <td>
                    <div class="sign-label">DITERIMA OLEH,</div>
                    <div class="sign-line">( )</div>
                </td>
                <td>
                    <div class="sign-label">DIPERIKSA OLEH,</div>
                    <div class="sign-line">( )</div>
                </td>
            </tr>
        </table>
    @else
        <table class="data-table" style="margin-top: 20px;">
            <tbody>
                <tr class="empty-row">
                    <td colspan="4">Tidak ada data.</td>
                </tr>
            </tbody>
        </table>
    @endif

    @include('ascends.shared.partials.report-footer')
</body>

</html>