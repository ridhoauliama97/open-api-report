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
        }

        body {
            margin: 0;
            font-family: "Noto Serif", serif;
            font-size: 9px;
            line-height: 1.15;
            color: #000;
        }

        .page-title {
            width: 135px;
            text-align: left;
            font-size: 21px;
            font-weight: bold;
            background: rgb(83, 143, 231);
            border: 1.5px solid #000;
            border-radius: 6px;
            padding: 3px 14px;
            margin: 0 0 6px 0;
            line-height: 1.2;
        }

        .price-code {
            text-align: right;
            font-size: 9px;
            margin: 0 0 8px 0;
        }

        .group-separator {
            height: 4mm;
            background: #000;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            page-break-inside: auto;
            border-spacing: 0;
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

        .brand-logo {
            width: 110px;
            height: auto;
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

        if (!function_exists('fmtNum')) {
            if (!function_exists('fmtNum_daftar_harga_furniture_all')) {
                function fmtNum_daftar_harga_furniture_all($value)
                {
                    $v = (float) $value;
                    if ($v == 0.0) {
                        return '';
                    }
                    return number_format($v, 0, '.', ',');
                }
            }
        }

        $groupLogos = [
            'MERONA' => public_path('storage/images/Merona.png'),
            'MO.RE' => public_path('storage/images/More.png'),
            'MODELUX' => public_path('storage/images/Modelux.png'),
            'GRANDE' => public_path('storage/images/Grande.png'),
            '' => public_path('storage/images/Panen Hana.png'),
        ];
    @endphp

    <h1 class="page-title">FURNITURE</h1>
    <p class="price-code">04/PRICELIST-PANEN/IX/25</p>

    @if (count($items) > 0)
        @php
            $currentGroup = '';
            $rowNo = 0;
        @endphp

        @foreach ($items as $item)
            @if ($currentGroup !== $item['group'])
                @if ($currentGroup !== '')
                    </tbody>
                    </table>
                    <div class="group-separator"></div>
                @endif
                @php
                    $currentGroup = $item['group'];
                    $rowNo = 0;

                    if (preg_match('/^(.*?)\s*\(([^)]+)\)/', $item['ket'], $m)) {
                        $ketLabel = trim($m[1]);
                        $ketUnit = trim($m[2]);
                    } else {
                        $ketLabel = $item['ket'];
                        $ketUnit = '';
                    }
                    $logo = $groupLogos[$currentGroup] ?? null;
                @endphp
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width: 5%;">No</th>
                            <th style="width: 35%;">
                                @if ($logo)
                                    <img src="{{ $logo }}" class="brand-logo" alt="">
                                @endif
                            </th>
                            <th style="width: 10%;">{{ $ketLabel }} <br> ({{ $ketUnit }})</th>
                            <th style="width: 10%;">Harga Konsumen</th>
                            <th style="width: 10%;">Retail Diskon 5%</th>
                            <th style="width: 10%;">Semi Grosir Diskon 7%</th>
                            <th style="width: 10%;">Grosir Diskon 9%</th>
                            <th style="width: 10%;">Akun Spesial Diskon 11%</th>
                        </tr>
                    </thead>
                    <tbody>
            @endif
                    @php $rowNo++; @endphp
                    <tr class="{{ $rowNo % 2 === 0 ? 'row-even' : 'row-odd' }}">
                        <td class="center">{{ $rowNo }}</td>
                        <td>{{ $item['description'] }}</td>
                        <td class="center">{{ rtrim(rtrim(number_format($item['per_dus'], 2, '.', ''), '0'), '.') }}</td>
                        <td class="number">{{ fmtNum_daftar_harga_furniture_all($item['base_price']) }}</td>
                        <td class="number">{{ fmtNum_daftar_harga_furniture_all($item['retail_disc_5']) }}</td>
                        <td class="number">{{ fmtNum_daftar_harga_furniture_all($item['semi_grosir']) }}</td>
                        <td class="number">{{ fmtNum_daftar_harga_furniture_all($item['grosir']) }}</td>
                        <td class="number">{{ fmtNum_daftar_harga_furniture_all($item['akun_spesial']) }}</td>
                    </tr>
        @endforeach
            </tbody>
            <div class="group-separator"></div>
        </table>
    @else
        <table class="data-table">
            <tbody>
                <tr>
                    <td class="center" style="font-style: italic; padding: 10px;">
                        Tidak ada data daftar harga furniture.
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
            <li>Dengan berlakunya Price List ini maka pricelist sebelumnya dinyatakan
                <strong style="text-decoration: underline;">TIDAK BERLAKU</strong>
            </li>
            <li>Harga berdasarkan Level Toko</li>
        </ol>
    </div>
</body>

</html>