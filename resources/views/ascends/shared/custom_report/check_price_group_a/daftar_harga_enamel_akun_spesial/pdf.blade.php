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
            border-spacing: 0;
            margin-bottom: 8px;
            border-bottom: 1px solid #000;
        }

        .data-table th,
        .data-table td {
            border-left: 1px solid #000;
            border-right: 1px solid #000;
            padding: 2px 4px;
            vertical-align: middle;
            word-wrap: break-word;
        }

        .data-table th {
            font-weight: bold;
            font-size: 10px;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            text-align: center;
            background: transparent;
        }

        .data-table td {
            padding: 1px 4px;
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

        .notes-section {
            margin-top: 15px;
            font-size: 10px;
            line-height: 1.3;
        }

        .notes-section ol {
            margin-left: 15px;
        }
    </style>
</head>

<body>
    @php
        $items = $reportData['items'] ?? [];
        $headerCompany = trim((string) ($company ?? ($reportData['company'] ?? '')));

        function fmtNum($value)
        {
            $v = (float) $value;
            if ($v == 0.0) {
                return '-';
            }
            return number_format($v, 0, '.', ',');
        }
    @endphp

    @if ($headerCompany !== '')
        <h1 class="report-companyTitle">{{ $headerCompany }}</h1>
    @endif
    <h1 class="report-title">ENAMEL</h1>
    <p class="report-subtitle">Harga Akun Spesial</p>

    @if (count($items) > 0)
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 5%;">No</th>
                    <th style="width: 50%;">&nbsp;</th>
                    <th style="width: 12%;">Isi/Dus</th>
                    <th style="width: 17%;">Harga<br>Konsumen</th>
                    <th style="width: 16%;">AK Spesial<br>9 %</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($items as $idx => $item)
                    @php $rowNo = $idx + 1; @endphp
                    <tr class="{{ $rowNo % 2 === 0 ? 'row-even' : 'row-odd' }}">
                        <td class="center nowrap">{{ $rowNo }}{{ ($item['bnt'] ?? 0) === 1 ? '*' : '' }}</td>
                        <td>{{ $item['description'] }}</td>
                        <td class="center">{{ number_format((float) ($item['per_dus'] ?? 0), 0, '.', ',') }}</td>
                        <td class="number">{{ fmtNum($item['harga_konsumen'] ?? 0) }}</td>
                        <td class="number">{{ fmtNum($item['akun_spesial'] ?? 0) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <table class="data-table">
            <tbody>
                <tr>
                    <td class="center" style="font-style: italic; padding: 10px;">
                        Tidak ada data daftar harga enamel akun spesial.
                    </td>
                </tr>
            </tbody>
        </table>
    @endif

    <div class="notes-section">
        <ol>
            <li>Daftar harga berlaku per Tgl 6 April 2026</li>
            <li>Franco Medan</li>
            <li>Harga sewaktu waktu bisa berubah / tanpa pemberitahuan terlebih dahulu</li>
            <li>Dengan berlaku nya Price List ini maka Price List sebelumnya tidak berlaku lagi</li>
            <li>Harga berdasarkan Level Toko</li>
            <li>Produk GSU tidak bisa diretur (tanda * di nomor)</li>
        </ol>
    </div>

    @include('ascends.shared.partials.report-footer')
</body>

</html>