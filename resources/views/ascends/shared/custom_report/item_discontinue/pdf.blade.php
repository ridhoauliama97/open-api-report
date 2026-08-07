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

        .section-header {
            font-weight: bold;
            font-style: italic;
            font-size: 12px;
            color: #9c111d;
            background: transparent;
            margin-bottom: 2px;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            page-break-inside: auto;
            border-spacing: 0;
            border-bottom: 1px solid #000;
            margin-bottom: 8px;
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
            color: #9c111d;
            font-weight: bold;
            font-size: 10px;
            padding: 5px 4px;
        }

        .page-break {
            page-break-before: always;
        }
    </style>
</head>

<body>
    @php
        $sections = $reportData['sections'] ?? [];
        $periodLabel = trim((string) ($reportData['period_label'] ?? ''));

        $headerCompany = trim((string) ($company ?? ($reportData['company'] ?? '')));
        $headerTitle = trim((string) ($title ?? ($reportData['title'] ?? '')));

        if (!function_exists('fmtQty_item_discontinue')) {
            function fmtQty_item_discontinue($value)
            {
                $v = (float) $value;
                return number_format($v, 1, '.', ',');
            }
        }
    @endphp

    <h1 class="report-companyTitle">{{ $headerCompany }}</h1>
    <h1 class="report-title">{{ $headerTitle }}</h1>
    @if ($periodLabel !== '')
        <p class="report-subtitle">{{ $periodLabel }}</p>
    @endif

    @if (count($sections) > 0)
        @foreach ($sections as $section)
            @if (!$loop->first)
                <div class="page-break"></div>
            @endif

            <p class="section-header">{{ $section['category'] }} </p>
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 12%;">Kode Item</th>
                        <th style="width: 40%;">Nama Item</th>
                        {{-- <th style="width: 14%;">Kategori</th> --}}
                        <th style="width: 18%;">Family</th>
                        <th style="width: 7.5%;">Awal</th>
                        <th style="width: 7.5%;">Masuk</th>
                        <th style="width: 7.5%;">Keluar</th>
                        <th style="width: 7.5%;">Akhir</th>
                    </tr>
                </thead>
                <tbody>
                    @php $rowNum = 0; @endphp
                    @foreach ($section['items'] as $item)
                        @php $rowNum++; @endphp
                        <tr class="{{ $rowNum % 2 === 0 ? 'row-even' : 'row-odd' }}">
                            <td class="center nowrap">{{ $item['item_code'] }}</td>
                            <td>{{ $item['item_name'] }}</td>
                            {{-- <td class="center">{{ $item['category_name'] }}</td> --}}
                            <td class="center">{{ $item['family_name'] }}</td>
                            <td class="number nowrap">{{ fmtQty_item_discontinue($item['saldo_awal']) }}</td>
                            <td class="number nowrap">{{ fmtQty_item_discontinue($item['masuk']) }}</td>
                            <td class="number nowrap">{{ fmtQty_item_discontinue($item['keluar']) }}</td>
                            <td class="number nowrap" style="font-weight: bold;">{{ fmtQty_item_discontinue($item['akhir']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endforeach
    @else
        <table class="data-table">
            <tbody>
                <tr class="empty-row">
                    <td>Tidak ada data item discontinue.</td>
                </tr>
            </tbody>
        </table>
    @endif

    @include('ascends.shared.partials.report-footer')
</body>

</html>