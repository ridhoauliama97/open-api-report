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

        .category-header {
            font-size: 11px;
            font-weight: bold;
            font-style: italic;
            color: #9c111d;
            margin-top: 12px;
            margin-bottom: 3px;
        }

        .grp-header {
            font-size: 10px;
            font-weight: bold;
            color: #9c111d;
            margin-top: 6px;
            margin-bottom: 2px;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            page-break-inside: auto;
            border-spacing: 0;
            margin-bottom: 8px;
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

        .row-odd td {
            background: #c9d1df;
        }

        .row-even td {
            background: #eef2f8;
        }

        .subtotal-row td {
            font-size: 11px;
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
            font-size: 10px;
            padding: 4px;
        }

        .page-break {
            page-break-before: always;
        }
    </style>
</head>

<body>
    @php
        $categories = $reportData['categories'] ?? [];
        $dayNumbers = $reportData['day_numbers'] ?? [];
        $headerCompany = trim((string) ($company ?? ($reportData['company'] ?? '')));
        $headerTitle = trim((string) ($title ?? ($reportData['title'] ?? '')));
        $headerSubtitle = trim((string) ($reportData['period_label'] ?? ''));

        function fmtNum($value)
        {
            $v = (float) $value;
            if ($v == 0.0) {
                return '-';
            }
            return number_format($v, 0, '.', ',');
        }
    @endphp

    <h1 class="report-companyTitle">{{ $headerCompany }}</h1>
    <h1 class="report-title">{{ $headerTitle }}</h1>
    @if ($headerSubtitle !== '')
        <p class="report-subtitle">{{ $headerSubtitle }}</p>
    @endif

    @if (count($categories) > 0)
        @php $catIndex = 0; @endphp
        @foreach ($categories as $category)
            @php $catIndex++; @endphp

            <div class="category-header">{{ $category['label'] }}</div>

            @if ($category['grp_groups'] !== null)
                {{-- Kabinet 1 with Grp groups --}}
                @foreach ($category['grp_groups'] as $group)
                    @php $grpItems = $group['items'] ?? []; @endphp
                    @if (count($grpItems) > 0)
                        <div class="grp-header">{{ $group['grp'] }}</div>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th style="width: 22%;">Tanggal</th>
                                    @foreach ($dayNumbers as $day)
                                        <th style="width: {{ round(72 / count($dayNumbers), 1) }}%;">{{ sprintf('%02d', $day) }}</th>
                                    @endforeach
                                    <th style="width: 6%;" class="center">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $rowNum = 0; @endphp
                                @foreach ($grpItems as $item)
                                    @php $rowNum++; @endphp
                                    <tr class="{{ $rowNum % 2 === 0 ? 'row-even' : 'row-odd' }}">
                                        <td>{{ $item['item_name'] }}</td>
                                        @foreach ($dayNumbers as $day)
                                            <td class="number">{{ fmtNum($item['daily'][$day] ?? 0) }}</td>
                                        @endforeach
                                        <td class="number">{{ fmtNum($item['total_qty']) }}</td>
                                    </tr>
                                @endforeach
                                {{-- Group subtotal --}}
                                <tr class="subtotal-row">
                                    <td class="center">Total {{ $group['grp'] }}</td>
                                    @foreach ($dayNumbers as $day)
                                        <td class="number">{{ fmtNum($group['daily'][$day] ?? 0) }}</td>
                                    @endforeach
                                    <td class="number">{{ fmtNum($group['total_qty']) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    @endif
                @endforeach
                {{-- Category total for Kabinet 1 --}}
                <table class="data-table">
                    <tbody>
                        <tr class="subtotal-row">
                            <td style="width: 22%;" class="center">Total {{ $category['label'] }}</td>
                            @foreach ($dayNumbers as $day)
                                <td style="width: {{ round(72 / count($dayNumbers), 1) }}%;" class="number">
                                    {{ fmtNum($category['daily'][$day] ?? 0) }}
                                </td>
                            @endforeach
                            <td style="width: 6%;" class="number">{{ fmtNum($category['total_qty']) }}</td>
                        </tr>
                    </tbody>
                </table>
            @else
                {{-- Simple category --}}
                @php $catItems = $category['items'] ?? []; @endphp
                @if (count($catItems) > 0)
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th style="width: 22%;">Tanggal</th>
                                @foreach ($dayNumbers as $day)
                                    <th style="width: {{ round(72 / count($dayNumbers), 1) }}%;">{{ sprintf('%02d', $day) }}</th>
                                @endforeach
                                <th style="width: 6%;" class="center">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $rowNum = 0; @endphp
                            @foreach ($catItems as $item)
                                @php $rowNum++; @endphp
                                <tr class="{{ $rowNum % 2 === 0 ? 'row-even' : 'row-odd' }}">
                                    <td>{{ $item['item_name'] }}</td>
                                    @foreach ($dayNumbers as $day)
                                        <td class="number">{{ fmtNum($item['daily'][$day] ?? 0) }}</td>
                                    @endforeach
                                    <td class="number">{{ fmtNum($item['total_qty']) }}</td>
                                </tr>
                            @endforeach
                            {{-- Category total --}}
                            <tr class="subtotal-row">
                                <td class="center">Total</td>
                                @foreach ($dayNumbers as $day)
                                    <td class="number">{{ fmtNum($category['daily'][$day] ?? 0) }}</td>
                                @endforeach
                                <td class="number">{{ fmtNum($category['total_qty']) }}</td>
                            </tr>
                        </tbody>
                    </table>
                @endif
            @endif
        @endforeach
    @else
        <table class="data-table">
            <tbody>
                <tr class="empty-row">
                    <td>Tidak ada data pengiriman per kategori.</td>
                </tr>
            </tbody>
        </table>
    @endif

    @include('ascends.shared.partials.report-footer')
</body>

</html>
