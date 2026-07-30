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
            font-size: 9px;
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
            margin: 2px 0 15px 0;
            font-size: 11px;
            color: #636466;
        }

        .category-header {
            font-size: 11px;
            font-weight: bold;
            font-style: italic;
            color: #9c111d;
            margin-top: 10px;
            margin-bottom: 2px;
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
            font-size: 8px;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            text-align: center;
            background: transparent;
        }

        .data-table td {
            padding: 1px 4px;
            font-size: 9px;
        }

        .center {
            text-align: center;
        }

        .number {
            text-align: right;
        }

        .row-odd td {
            background: #c9d1df;
        }

        .row-even td {
            background: #eef2f8;
        }

        .notes-section {
            margin-top: 12px;
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
        $groups = $reportData['groups'] ?? [];
        $headerCompany = trim((string) ($company ?? ($reportData['company'] ?? '')));
        $headerTitle = trim((string) ($reportData['title'] ?? 'DAFTAR HARGA FURNITURE'));

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
    <h1 class="report-title">FURNITURE</h1>
    <p class="report-subtitle"></p>

    @if (count($groups) > 0)
        @foreach ($groups as $group)
            @php
                $qty = $group['qty_labels'] ?? ['', '', '', ''];
                $disc = $group['disc_labels'] ?? ['', '', '', ''];
                $has4 = $group['has_4_tiers'] ?? false;
            @endphp

            <div class="category-header">{{ $group['name'] }}</div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 5%;">No</th>
                        <th style="width: @if($has4) 35% @else 40% @endif;">Nama Barang</th>
                        <th style="width: 15%;">{{ $qty[0] !== '' ? $qty[0] : 'Qty 1' }}<br>{{ $disc[0] }}</th>
                        <th style="width: 15%;">{{ $qty[1] !== '' ? $qty[1] : 'Qty 2' }}<br>{{ $disc[1] }}</th>
                        <th style="width: 15%;">{{ $qty[2] !== '' ? $qty[2] : 'Qty 3' }}<br>{{ $disc[2] }}</th>
                        @if ($has4)
                            <th style="width: 15%;">{{ $qty[3] !== '' ? $qty[3] : 'Qty 4' }}<br>{{ $disc[3] }}</th>
                        @endif
                        <th style="width: @if($has4) 10% @else 15% @endif;">Harga Konsumen</th>
                    </tr>
                </thead>
                <tbody>
                    @php $rowNo = 0; @endphp
                    @foreach ($group['items'] as $item)
                        @php $rowNo++; @endphp
                        <tr class="{{ $rowNo % 2 === 0 ? 'row-even' : 'row-odd' }}">
                            <td class="center">{{ $rowNo }}</td>
                            <td>{{ $item['description'] }}</td>
                            <td class="number">{{ fmtNum($item['semi_grosir_1']) }}</td>
                            <td class="number">{{ fmtNum($item['semi_grosir_2']) }}</td>
                            <td class="number">{{ fmtNum($item['semi_grosir_3']) }}</td>
                            @if ($has4)
                                <td class="number">{{ fmtNum($item['semi_grosir_4']) }}</td>
                            @endif
                            <td class="number">{{ fmtNum($item['harga_konsumen']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endforeach
    @else
        <table class="data-table">
            <tbody>
                <tr>
                    <td class="center" style="font-style: italic; padding: 10px;">
                        Tidak ada data daftar harga furniture sales project.
                    </td>
                </tr>
            </tbody>
        </table>
    @endif

    <div class="notes-section">
        <ol>
            <li>Daftar harga berlaku per Tgl 1 Mei 2026</li>
            <li>Franco Medan</li>
            <li>Harga sewaktu waktu bisa berubah / tanpa pemberitahuan terlebih dahulu</li>
            <li>Dengan berlakunya Price List ini maka pricelist sebelumnya dinyatakan TIDAK BERLAKU</li>
            <li>harga berdasarkan Level Toko</li>
        </ol>
    </div>

    @include('ascends.shared.partials.report-footer')
</body>

</html>