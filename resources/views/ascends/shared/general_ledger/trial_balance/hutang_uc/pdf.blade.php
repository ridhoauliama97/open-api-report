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

        .grand-total-row td {
            font-weight: bold;
            font-size: 10px;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding: 3px 4px;
        }

        .empty-row td {
            text-align: center;
            font-style: italic;
            background: #c9d1df;
            font-weight: bold;
            color: #9c111d;
            font-size: 10px;
            padding: 8px 4px;
        }

        .col-no {
            width: 5%;
        }

        .col-account {
            width: 32%;
        }

        .col-beginning {
            width: 16%;
        }

        .col-debit {
            width: 16%;
        }

        .col-credit {
            width: 16%;
        }

        .col-ending {
            width: 15%;
        }

        .signature-section {
            width: 100%;
            margin-top: 40px;
            border-collapse: collapse;
        }

        .signature-section td {
            text-align: center;
            vertical-align: top;
            padding: 0 20px;
        }

        .sign-label {
            font-size: 10px;
            font-weight: bold;
            padding-bottom: 10px;
        }

        .sign-line {
            font-size: 10px;
        }
    </style>
</head>

<body>
    @php
        $items = $reportData['items'] ?? [];
        $grandDebit = (float) ($reportData['grand_debit'] ?? 0);
        $grandCredit = (float) ($reportData['grand_credit'] ?? 0);
        $grandBeginning = (float) ($reportData['grand_beginning'] ?? 0);
        $grandEnding = (float) ($reportData['grand_ending'] ?? 0);
        $headerCompany = trim((string) ($company ?? ($reportData['company'] ?? '')));
        $headerTitle = trim((string) ($title ?? ($reportData['title'] ?? ($fallbackTitle ?? ''))));
        $headerSubtitle = trim((string) ($reportData['period_label'] ?? ''));
        $generatedByName = trim((string) ($reportData['printed_by'] ?? ''));

        function fmtAmount($value)
        {
            $v = (float) $value;
            if ($v < 0) {
                return '(' . number_format(abs($v), 2, ',', '.') . ')';
            }
            if ($v == 0.0) {
                return '0,00';
            }
            return number_format($v, 2, ',', '.');
        }
    @endphp

    <h1 class="report-companyTitle">{{ $headerCompany }}</h1>
    <h1 class="report-title">{{ $headerTitle }}</h1>
    <p class="report-subtitle">{{ $headerSubtitle }}</p>

    <table class="data-table">
        <colgroup>
            <col class="col-no">
            <col class="col-account">
            <col class="col-beginning">
            <col class="col-debit">
            <col class="col-credit">
            <col class="col-ending">
        </colgroup>
        <thead>
            <tr>
                <th>No</th>
                <th>Kode & Nama Akun</th>
                <th>Saldo Awal</th>
                <th>Debit</th>
                <th>Kredit</th>
                <th>Saldo Akhir</th>
            </tr>
        </thead>
        <tbody>
            @if (count($items) > 0)
                @php $globalRow = 0; @endphp
                @foreach ($items as $item)
                    @php $globalRow++; @endphp
                    <tr class="{{ $globalRow % 2 === 0 ? 'row-even' : 'row-odd' }}">
                        <td class="center">{{ $globalRow }}</td>
                        <td>{{ (string) ($item['account_code'] ?? '') }} {{ (string) ($item['account_name'] ?? '') }}
                        </td>
                        <td class="number nowrap">{{ fmtAmount($item['beginning'] ?? 0) }}</td>
                        <td class="number nowrap">{{ fmtAmount($item['debit'] ?? 0) }}</td>
                        <td class="number nowrap">{{ fmtAmount($item['credit'] ?? 0) }}</td>
                        <td class="number nowrap">{{ fmtAmount($item['ending'] ?? 0) }}</td>
                    </tr>
                @endforeach

                <tr class="grand-total-row">
                    <td colspan="2" class="center">Total :</td>
                    <td class="number nowrap">{{ fmtAmount($grandBeginning) }}</td>
                    <td class="number nowrap">{{ fmtAmount($grandDebit) }}</td>
                    <td class="number nowrap">{{ fmtAmount($grandCredit) }}</td>
                    <td class="number nowrap">{{ fmtAmount($grandEnding) }}</td>
                </tr>
            @else
                <tr class="empty-row">
                    <td colspan="6">Tidak ada data.</td>
                </tr>
            @endif
        </tbody>
    </table>

    @if (count($items) > 0)
        <table class="signature-section">
            <tr>
                <td>
                    <div class="sign-label">Diverifikasi Oleh</div>
                    <br><br><br><br><br>
                    <div class="sign-line">( Kadiv F&A UC )</div>
                </td>
            </tr>
        </table>
    @endif

    @include('ascends.shared.partials.report-footer')
</body>

</html>
