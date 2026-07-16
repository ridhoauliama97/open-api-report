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

        .section-header td {
            font-weight: bold;
            font-size: 10px;
            font-style: italic;
            padding: 4px 4px;
            color: #9c111d;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
        }

        .akm-subtotal td {
            font-weight: bold;
            font-size: 10px;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
        }

        .section-subtotal td {
            font-weight: bold;
            font-size: 10px;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
        }

        .calculation-row td {
            font-weight: bold;
            font-size: 10px;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
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

        .indent-item td:first-child {
            padding-left: 12px;
        }

        .indent-akm td:first-child {
            padding-left: 6px;
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

        .col-desc {
            width: 26%;
        }

        .col-amount {
            width: 14%;
        }

        .col-rasio {
            width: 11%;
        }

        .col-selisih {
            width: 12%;
        }
    </style>
</head>

<body>
    @php
        $sections = $reportData['sections'] ?? [];
        $calculations = $reportData['calculations'] ?? [];
        $bulanB = $reportData['bulan_b_label'] ?? 'Jun-26';
        $bulanA = $reportData['bulan_a_label'] ?? 'Mei-26';
        $generatedAtText = \Carbon\Carbon::parse($generatedAt ?? now())
            ->locale('id')
            ->translatedFormat('d-M-y H:i');
        $generatedByName = trim((string) ($reportData['printed_by'] ?? ''));
        $headerCompany = trim((string) ($company ?? ($reportData['company'] ?? '')));
        $headerTitle = trim((string) ($title ?? ($reportData['title'] ?? ($fallbackTitle ?? ''))));
        $headerSubtitle = trim((string) ($reportData['period_label'] ?? ''));
    @endphp

    <h1 class="report-companyTitle">{{ $headerCompany }}</h1>
    <h1 class="report-title">{{ $headerTitle }}</h1>
    <p class="report-subtitle">{{ $headerSubtitle }}</p>

    @if (count($sections) > 0)
        <table class="data-table">
            <thead>
                <tr>
                    <th class="col-desc" rowspan="2">Keterangan</th>
                    <th class="col-amount" colspan="2">{{ $bulanB }}</th>
                    <th class="col-amount" colspan="2">{{ $bulanA }}</th>
                    <th class="col-selisih" rowspan="2">Selisih (%)</th>
                </tr>
                <tr>
                    <th class="col-amount">Jumlah</th>
                    <th class="col-rasio">Rasio (%)</th>
                    <th class="col-amount">Jumlah</th>
                    <th class="col-rasio">Rasio (%)</th>
                </tr>
            </thead>
            <tbody>
                @php $globalRow = 0; @endphp
                @foreach ($sections as $section)
                    <tr class="section-header">
                        <td colspan="6">{{ $section['akl'] }}</td>
                    </tr>

                    @foreach ($section['akm_groups'] as $akmGroup)
                        @foreach ($akmGroup['items'] as $item)
                            @php $globalRow++; @endphp
                            <tr class="indent-item {{ $globalRow % 2 === 0 ? 'row-even' : 'row-odd' }}">
                                <td>{{ (string) ($item['account_name'] ?? '') }}</td>
                                <td class="number nowrap">
                                    {{ number_format(abs((float) ($item['amount_b'] ?? 0)), 2, ',', '.') }}</td>
                                <td class="number nowrap">
                                    {{ $item['rasio_b'] != 0 ? number_format((float) ($item['rasio_b'] ?? 0), 2, ',', '.') : '0,00' }}
                                    %</td>
                                <td class="number nowrap">
                                    {{ number_format(abs((float) ($item['amount_a'] ?? 0)), 2, ',', '.') }}</td>
                                <td class="number nowrap">
                                    {{ $item['rasio_a'] != 0 ? number_format((float) ($item['rasio_a'] ?? 0), 2, ',', '.') : '0,00' }}
                                    %</td>
                                <td class="number nowrap">
                                    {{ number_format((float) ($item['selisih'] ?? 0), 2, ',', '.') }} %</td>
                            </tr>
                        @endforeach

                        <tr class="akm-subtotal indent-akm">
                            <td>TOTAL {{ $akmGroup['akm'] }}</td>
                            <td class="number nowrap">
                                {{ number_format(abs((float) ($akmGroup['subtotal_b'] ?? 0)), 2, ',', '.') }}</td>
                            <td class="number nowrap">
                                {{ $akmGroup['rasio_b'] != 0 ? number_format((float) ($akmGroup['rasio_b'] ?? 0), 2, ',', '.') : '0,00' }}
                                %</td>
                            <td class="number nowrap">
                                {{ number_format(abs((float) ($akmGroup['subtotal_a'] ?? 0)), 2, ',', '.') }}</td>
                            <td class="number nowrap">
                                {{ $akmGroup['rasio_a'] != 0 ? number_format((float) ($akmGroup['rasio_a'] ?? 0), 2, ',', '.') : '0,00' }}
                                %</td>
                            <td class="number nowrap">
                                {{ number_format((float) ($akmGroup['selisih'] ?? 0), 2, ',', '.') }} %</td>
                        </tr>
                    @endforeach

                    <tr class="section-subtotal">
                        <td>TOTAL {{ $section['akl'] }}</td>
                        <td class="number nowrap">
                            {{ number_format(abs((float) ($section['subtotal_b'] ?? 0)), 2, ',', '.') }}</td>
                        <td class="number nowrap">
                            {{ $section['rasio_b'] != 0 ? number_format((float) ($section['rasio_b'] ?? 0), 2, ',', '.') : '0,00' }}
                            %</td>
                        <td class="number nowrap">
                            {{ number_format(abs((float) ($section['subtotal_a'] ?? 0)), 2, ',', '.') }}</td>
                        <td class="number nowrap">
                            {{ $section['rasio_a'] != 0 ? number_format((float) ($section['rasio_a'] ?? 0), 2, ',', '.') : '0,00' }}
                            %</td>
                        <td class="number nowrap">{{ number_format((float) ($section['selisih'] ?? 0), 2, ',', '.') }}
                            %</td>
                    </tr>
                @endforeach

                @foreach ($calculations as $calc)
                    <tr class="calculation-row">
                        <td>{{ $calc['label'] }}</td>
                        <td class="number nowrap">
                            {{ number_format(abs((float) ($calc['amount_b'] ?? 0)), 2, ',', '.') }}</td>
                        <td class="number nowrap">{{ number_format((float) ($calc['rasio_b'] ?? 0), 2, ',', '.') }} %
                        </td>
                        <td class="number nowrap">
                            {{ number_format(abs((float) ($calc['amount_a'] ?? 0)), 2, ',', '.') }}</td>
                        <td class="number nowrap">{{ number_format((float) ($calc['rasio_a'] ?? 0), 2, ',', '.') }} %
                        </td>
                        <td class="number nowrap">{{ number_format((float) ($calc['selisih'] ?? 0), 2, ',', '.') }} %
                        </td>
                    </tr>
                @endforeach
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

    @include('ascends.shared.partials.report-footer')
</body>

</html>
