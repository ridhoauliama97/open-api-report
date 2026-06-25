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
            line-height: 1.2;
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
            border: 1px solid #000;
        }

        .data-table th,
        .data-table td {
            border-left: 1px solid #000;
            border-right: 1px solid #000;
            padding: 3px 4px;
            vertical-align: middle;
            word-wrap: break-word;
        }

        .data-table th {
            font-weight: bold;
            font-size: 11px;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            text-align: center;
        }

        .center {
            text-align: center;
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
            font-size: 11px;
        }

        .number {
            text-align: right;
        }

        .nowrap {
            white-space: nowrap;
        }

        .summary-header td {
            border-top: 1px solid #000;
        }
    </style>
</head>

<body>
    @php
        $dates = $reportData['dates'] ?? [];
        $grandTotals = $reportData['grand_totals'] ?? [];
        $printedAt = $reportData['printed_at'] ?? '';
        $generatedAtText = \Carbon\Carbon::parse($generatedAt ?? now())
            ->locale('id')->translatedFormat('d-M-y H:i');
        $generatedByName = trim((string) ($reportData['printed_by'] ?? ''));
        $headerCompany = trim((string) ($company ?? $reportData['company'] ?? ''));
        $headerTitle = trim((string) ($title ?? $reportData['title'] ?? $fallbackTitle ?? ''));
        $headerSubtitle = trim((string) ($reportData['period_label'] ?? ''));
    @endphp

    <h1 class="report-companyTitle">{{ $headerCompany }}</h1>
    <h1 class="report-title">{{ $headerTitle }}</h1>
    <p class="report-subtitle">{{ $headerSubtitle }}</p>

    <table class="data-table">
        <thead>
            <tr>
                <th rowspan="2" style="width: 9%">Tanggal</th>
                <th rowspan="2" style="width: 7%">Time</th>
                <th rowspan="2" style="width: 16%">Supplier</th>
                <th colspan="2" style="width: 26%">Input</th>
                <th colspan="2" style="width: 26%">Output</th>
                <th rowspan="2" style="width: 12%">Limbah<br>(Kg)</th>
            </tr>
            <tr>
                <th style="width: 16%">Nama Barang</th>
                <th style="width: 10%">(Kg)</th>
                <th style="width: 16%">Nama Barang</th>
                <th style="width: 10%">(Kg)</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($dates as $index => $date)
                @php
                    $materialItems = $date['material_items'];
                    $outputItems = $date['output_items'];
                    $maxItems = max(count($materialItems), count($outputItems));
                    if ($maxItems < 1) {
                        $maxItems = 1;
                    }
                @endphp

                @for ($i = 0; $i < $maxItems; $i++)
                    @php
                        $matItem = $materialItems[$i] ?? null;
                        $outItem = $outputItems[$i] ?? null;
                    @endphp
                    <tr class="{{ ($index + $i) % 2 === 0 ? 'row-even' : 'row-odd' }}">
                        @if ($i === 0)
                            <td class="center nowrap" rowspan="{{ $maxItems }}">
                                {{ $date['date_display'] }}
                            </td>
                            <td class="center nowrap" rowspan="{{ $maxItems }}">
                                {{ $date['time_display'] !== '00:00:00' ? $date['time_display'] : '' }}
                            </td>
                        @endif
                        <td>{{ $outItem['code'] ?? '' }}</td>
                        <td>{{ $matItem['name'] ?? '' }}</td>
                        <td class="number nowrap">
                            {{ $matItem ? number_format($matItem['qty'], 2, '.', ',') : '' }}
                        </td>
                        <td>
                            {{ $outItem['name'] ?? '' }}
                        </td>
                        <td class="number nowrap">
                            {{ $outItem ? number_format($outItem['qty'], 2, '.', ',') : '' }}
                        </td>
                        @if ($i === 0)
                            <td class="number nowrap" rowspan="{{ $maxItems }}">
                                {{ number_format($date['limbah'], 2, '.', ',') }}
                            </td>
                        @endif
                    </tr>
                @endfor
            @empty
                <tr class="empty-row">
                    <td colspan="8">Tidak ada data.</td>
                </tr>
            @endforelse

            @if (!empty($dates))
                <tr class="summary-header">
                    <td colspan="8">Sub Total = {{ number_format($reportData['sub_total'] ?? 0, 0, '.', ',') }}</td>
                </tr>
                <tr>
                    <td colspan="8">Akumulasi Grand Total Input (Kg) = {{ number_format($grandTotals['total_material'], 0, '.', ',') }} (100%)</td>
                </tr>
                <tr>
                    <td colspan="8">Akumulasi Grand Total Output (Kg) = {{ number_format($grandTotals['total_output'], 0, '.', ',') }} (100%)</td>
                </tr>
                <tr>
                    <td colspan="8">Akumulasi Grand Total Limbah (Kg) = {{ number_format($grandTotals['total_limbah'], 0, '.', ',') }} (100%)</td>
                </tr>
            @endif
        </tbody>
    </table>

    @include('ascends.shared.partials.report-footer')
</body>

</html>
