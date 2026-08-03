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
            border-bottom: 1px solid #000;
        }

        .data-table th,
        .data-table td {
            border-left: 1px solid #000;
            border-right: 1px solid #000;
            padding: 1px 2px;
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

        .subtotal-row td {
            font-size: 9px;
            font-weight: bold;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
        }

        .empty-row td {
            text-align: center;
            font-style: italic;
            background: #c9d1df;
            color: #9c111d;
            font-weight: bold;
            font-size: 9px;
            padding: 4px;
        }

        .subkolom th {
            border-top: none;
        }

        .total-row td {
            font-size: 9.5px;
            font-weight: bold;
            border-top: none;
        }
    </style>
</head>

<body>
    @php
        $categories = $reportData['categories'] ?? [];
        $months = $reportData['months'] ?? [];
        $headerCompany = trim((string) ($company ?? ($reportData['company'] ?? '')));
        $headerTitle = trim((string) ($title ?? ($reportData['title'] ?? '')));
        $headerSubtitle = trim((string) ($reportData['period_label'] ?? ''));
        $headerYear = trim((string) ($reportData['year'] ?? date('Y')));

        if (!function_exists('fmtNum_pengiriman_lemari_tahunan')) {
            function fmtNum_pengiriman_lemari_tahunan($value)
            {
                $v = (float) $value;
                if ($v == 0.0) {
                    return '-';
                }
                return number_format($v, 0, '.', ',');
            }
        }

        if (!function_exists('fmtMonthLabel_pengiriman_lemari_tahunan')) {
            function fmtMonthLabel_pengiriman_lemari_tahunan($monthNumber, $year)
            {
                try {
                    return \Carbon\Carbon::createFromDate((int) $year, (int) $monthNumber, 1)
                        ->locale('id')
                        ->isoFormat('MMM-YY');
                } catch (\Throwable $e) {
                    return (string) $monthNumber;
                }
            }
        }
    @endphp

    <h1 class="report-companyTitle">{{ $headerCompany !== '' ? $headerCompany : 'GSU' }}</h1>
    <h1 class="report-title">{{ $headerTitle }}</h1>
    @if ($headerSubtitle !== '')
        <p class="report-subtitle">{{ $headerSubtitle }}</p>
    @endif

    @if (count($categories) > 0)
        @php $rowNum = 0; @endphp
        @foreach ($categories as $category)
            <div @if(!$loop->first) style="page-break-before: always;" @endif> </div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th colspan="2" rowspan="2" class="center">{{ $category['label'] }}</th>
                        <th colspan="{{ max(1, count($months)) }}" class="center">Bulan</th>
                        <th rowspan="2" class="center" style="width: 5%;">Total</th>
                    </tr>
                    <tr class="subkolom">
                        @foreach ($months as $mNum => $mLabel)
                            <th style="width: {{ round(60 / 12, 1) }}%;">
                                {{ fmtMonthLabel_pengiriman_lemari_tahunan($mNum, $headerYear) }}
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($category['grp_groups'] as $group)
                        @php $showGroup = true; @endphp
                        @foreach ($group['items'] as $item)
                            @php $rowNum++; @endphp
                            <tr class="{{ $rowNum % 2 === 0 ? 'row-even' : 'row-odd' }}">
                                <td class="center nowrap">
                                    @if ($showGroup)
                                        {{ $group['grp'] }}
                                        @php $showGroup = false; @endphp
                                    @endif
                                </td>
                                <td>{{ $item['item_name'] }}</td>
                                @foreach ($months as $mNum => $mLabel)
                                    <td class="number nowrap">{{ fmtNum_pengiriman_lemari_tahunan($item['monthly'][$mNum] ?? 0) }}</td>
                                @endforeach
                                <td class="number nowrap">{{ fmtNum_pengiriman_lemari_tahunan($item['total_qty']) }}</td>
                            </tr>
                        @endforeach
                        <tr class="subtotal-row">
                            <td colspan="2" class="center">SUBTOTAL </td>
                            @foreach ($months as $mNum => $mLabel)
                                <td class="number">{{ fmtNum_pengiriman_lemari_tahunan($group['monthly'][$mNum] ?? 0) }}</td>
                            @endforeach
                            <td class="number">{{ fmtNum_pengiriman_lemari_tahunan($group['total_qty']) }}</td>
                        </tr>
                    @endforeach

                    {{-- Category Subtotal Row --}}
                    <tr class="total-row">
                        <td colspan="2" class="center">TOTAL</td>
                        @foreach ($months as $mNum => $mLabel)
                            <td class="number">{{ fmtNum_pengiriman_lemari_tahunan($category['monthly'][$mNum] ?? 0) }}</td>
                        @endforeach
                        <td class="number">{{ fmtNum_pengiriman_lemari_tahunan($category['total_qty']) }}</td>
                    </tr>
                </tbody>
            </table>
        @endforeach
    @else
        <table class="data-table">
            <tbody>
                <tr class="empty-row">
                    <td>Tidak ada data pengiriman lemari.</td>
                </tr>
            </tbody>
        </table>
    @endif

    @include('ascends.shared.partials.report-footer')
</body>

</html>