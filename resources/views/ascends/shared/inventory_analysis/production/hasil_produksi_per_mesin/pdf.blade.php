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

        .machine-header td {
            padding: 4px;
            font-weight: bold;
            font-size: 11px;
            background: #eef2f8;
            border: 1px solid #000;
        }

        .production-number-header td {
            padding: 4px;
            font-weight: bold;
            font-size: 10px;
            background: #f5f5f5;
            border: 1px solid #000;
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

        .summary-row td {
            font-weight: bold;
            font-size: 11px;
            border: none;
        }

        .summary-row-first td {
            border-top: 1px solid #000;
        }

        .summary-row-last td {
            border-bottom: 1px solid #000;
        }
    </style>
</head>

<body>
    @php
        $machines = $reportData['machines'] ?? [];
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
                <th rowspan="2" style="width: 10%">Tanggal</th>
                <th rowspan="2" style="width: 8%">Time</th>
                <th colspan="2" style="width: 30%">Input</th>
                <th colspan="2" style="width: 30%">Output</th>
                <th rowspan="2" style="width: 12%">Reprogres<br>(Kg)</th>
            </tr>
            <tr>
                <th style="width: 20%">Nama Barang</th>
                <th style="width: 10%">Qty</th>
                <th style="width: 20%">Nama Barang</th>
                <th style="width: 10%">Qty</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($machines as $machineIndex => $machine)
                @php
                    $productionNumbers = $machine['production_numbers'];
                    $totals = $machine['totals'];
                    $pcts = $machine['percentages'] ?? [];
                @endphp

                <tr class="machine-header">
                    <td colspan="7">
                        {{ $machine['line_name'] }}
                    </td>
                </tr>

                @foreach ($productionNumbers as $pnIndex => $pn)
                    @php
                        $dates = $pn['dates'];
                    @endphp

                    <tr class="production-number-header">
                        <td colspan="7">
                            {{ $pn['production_number'] }}
                        </td>
                    </tr>

                    @foreach ($dates as $dateIndex => $date)
                        @php
                            $materialItems = $date['material_items'];
                            $outputItems = $date['output_items'];
                            $extraOutputItems = $date['extra_output_items'];
                            $maxItems = max(count($materialItems), count($outputItems) + count($extraOutputItems));
                            if ($maxItems < 1) {
                                $maxItems = 1;
                            }
                        @endphp

                        @for ($i = 0; $i < $maxItems; $i++)
                            @php
                                $matItem = $materialItems[$i] ?? null;
                                $outItem = $outputItems[$i] ?? null;
                                $extItem = $extraOutputItems[$i] ?? null;
                            @endphp
                            <tr class="{{ ($dateIndex + $i) % 2 === 0 ? 'row-even' : 'row-odd' }}">
                                @if ($i === 0)
                                    <td class="center nowrap" rowspan="{{ $maxItems }}">
                                        {{ $date['date_display'] }}
                                    </td>
                                    <td class="center nowrap" rowspan="{{ $maxItems }}">
                                        {{ $date['time_display'] !== '00:00:00' ? $date['time_display'] : '' }}
                                    </td>
                                @endif
                                <td>{{ $matItem['name'] ?? '' }}</td>
                                <td class="number nowrap">
                                    {{ $matItem ? number_format($matItem['qty'], 2, '.', ',') : '' }}
                                </td>
                                <td>
                                    @if ($outItem)
                                        {{ $outItem['name'] }}
                                    @elseif ($extItem)
                                        {{ $extItem['name'] }}
                                    @endif
                                </td>
                                <td class="number nowrap">
                                    @if ($outItem)
                                        {{ number_format($outItem['qty'], 2, '.', ',') }}
                                    @elseif ($extItem)
                                        {{ number_format($extItem['qty'], 2, '.', ',') }}
                                    @endif
                                </td>
                                @if ($i === 0)
                                    <td class="number nowrap" rowspan="{{ $maxItems }}">
                                        0
                                    </td>
                                @endif
                            </tr>
                        @endfor
                    @endforeach
                @endforeach

                <tr class="summary-row summary-row-first">
                    <td colspan="7">
                        Sub Total = {{ number_format($machine['sub_total'], 0, '.', ',') }}
                    </td>
                </tr>
                <tr class="summary-row">
                    <td colspan="7">
                        Akumulasi Input (Kg) = {{ number_format($totals['total_material'], 0, '.', ',') }}
                        ({{ $pcts['input_pct'] ?? 0 }}%)
                    </td>
                </tr>
                <tr class="summary-row">
                    <td colspan="7">
                        Akumulasi Output (Kg) = {{ number_format($totals['total_output'], 0, '.', ',') }}
                        ({{ $pcts['output_pct'] ?? 0 }}%)
                    </td>
                </tr>
                <tr class="summary-row summary-row-last">
                    <td colspan="7">
                        Akumulasi Downtime Machine (Kg) = 0 (0%)
                    </td>
                </tr>
            @empty
                <tr class="empty-row">
                    <td colspan="7">Tidak ada data.</td>
                </tr>
            @endforelse

            @if (!empty($machines))
                <tr class="summary-header">
                    <td colspan="7">
                        Akumulasi Total Input (Kg) = {{ number_format($grandTotals['total_material'], 0, '.', ',') }}
                        (100%)
                    </td>
                </tr>
                <tr>
                    <td colspan="7">
                        Akumulasi Total Output (Kg) = {{ number_format($grandTotals['total_output'], 0, '.', ',') }}
                        (100%)
                    </td>
                </tr>
                <tr>
                    <td colspan="7">
                        Akumulasi Grand Total Input (Kg) = {{ number_format($grandTotals['total_material'], 0, '.', ',') }}
                        (100%)
                    </td>
                </tr>
                <tr>
                    <td colspan="7">
                        Akumulasi Grand Total Output (Kg) = {{ number_format($grandTotals['total_output'], 0, '.', ',') }}
                        (100%)
                    </td>
                </tr>
            @endif
        </tbody>
    </table>

    @include('ascends.shared.partials.report-footer')
</body>

</html>
