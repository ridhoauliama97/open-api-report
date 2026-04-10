<!DOCTYPE html>
<html lang="id">

<head>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif:ital,wght@0,100..900;1,100..900&display=swap"
        rel="stylesheet">
    <meta charset="utf-8">
    <style>
        * {
            box-sizing: border-box;
        }

        @page {
            margin: 20mm 10mm 20mm 10mm;
            footer: html_reportFooter;
        }

        body {
            margin: 0;
            font-family: "Noto Serif", serif;
            font-size: 10px;
            line-height: 1.15;
            color: #000;
        }

        .report-title {
            text-align: center;
            margin: 0;
            font-size: 16px;
            font-weight: bold;
        }

        .report-subtitle {
            text-align: center;
            margin: 2px 0 12px 0;
            font-size: 12px;
            color: #636466;
        }

        .group-title {
            margin: 0 0 6px 0;
            font-size: 10px;
            font-weight: bold;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            page-break-inside: auto;
            border: 1px solid #000;
        }

        thead {
            display: table-header-group;
        }

        tfoot {
            display: table-footer-group;
        }

        tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }

        th,
        td {
            /* Default: hanya garis vertikal antar kolom (tanpa garis horizontal antar baris data). */
            border: 0;
            border-left: 1px solid #000;
            padding: 2px 3px;
            vertical-align: middle;
        }

        th:first-child,
        td:first-child {
            border-left: 0;
        }

        th {
            text-align: center;
            font-weight: bold;
            border-bottom: 1px solid #000;
        }

        /* Hilangkan garis horizontal antar baris data. */
        tbody td {
            border-top: 0;
            border-bottom: 0;
        }

        /* Khusus kolom Group: tampilkan garis horizontal antar baris (hanya kolom ini saja). */
        tbody td.col-group {
            border-bottom: 1px solid #000;
            background: #c9d1df !important;
            font-weight: bold;
        }

        .row-odd td {
            background: #c9d1df;
        }

        .row-even td {
            background: #eef2f8;
        }

        /* Zebra rows for summary tables (Grand Total + Rangkuman) */
        .zebra-table tbody tr:nth-child(odd) td {
            background: #c9d1df;
        }

        .zebra-table tbody tr:nth-child(even) td {
            background: #eef2f8;
        }

        .number {
            text-align: right;
            white-space: nowrap;
            font-family: "Calibri", "DejaVu Sans", sans-serif;
        }

        .center {
            text-align: center;
        }

        .totals-row td {
            font-weight: bold;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
        }

        tbody tr:last-child.totals-row td {
            border-bottom: 0;
        }

        /* Footer line to “close” the table on each page fragment when table is split across pages. */
        .tfoot-line td {
            border-top: 1px solid #000;
            padding: 0;
            height: 0;
            line-height: 0;
            font-size: 0;
        }

        .page-break {
            page-break-before: always;
        }

        .section-title {
            margin: 0 0 6px 0;
            font-size: 12px;
            font-weight: bold;
        }

        .grand-total-table {
            width: 420px !important;
            table-layout: fixed;
        }

        .rangkuman-table {
            width: 420px !important;
            table-layout: fixed;
        }

        .rangkuman-table th,
        .rangkuman-table td {
            padding: 2px 3px;
        }

        .rangkuman-group {
            page-break-inside: avoid;
        }

        .rangkuman-group-start td {
            border-top: 1px solid #000 !important;
        }

        .rangkuman-table td.jenis-cell {
            font-weight: bold;
            vertical-align: middle;
            background: #c9d1df !important;
            border-top: 1px solid #000 !important;
            border-bottom: 1px solid #000 !important;
        }

        .table-end-line td {
            border-top: 1px solid #000 !important;
            border-right: 0 !important;
            border-bottom: 0 !important;
            border-left: 0 !important;
            padding: 0 !important;
            height: 0 !important;
            line-height: 0 !important;
            background: #fff !important;
        }

        @include('reports.partials.pdf-footer-table-style')
    </style>
</head>

<body>
    @php
        $data = is_array($reportData ?? null) ? $reportData : [];
        $dateChunks = is_array($data['date_chunks'] ?? null) ? $data['date_chunks'] : [];
        $allDates = [];
        foreach ($dateChunks as $chunk) {
            if (!is_array($chunk)) {
                continue;
            }

            foreach ($chunk as $dateKey) {
                if (!in_array($dateKey, $allDates, true)) {
                    $allDates[] = $dateKey;
                }
            }
        }
        $isGroupBlocks = is_array($data['is_group_blocks'] ?? null) ? $data['is_group_blocks'] : [];
        $grandTotal = (float) ($data['grand_total'] ?? 0.0);
        $grandTotalsByIsGroup = is_array($data['grand_totals_by_is_group'] ?? null)
            ? $data['grand_totals_by_is_group']
            : [];
        $rangkuman = is_array($data['rangkuman'] ?? null) ? $data['rangkuman'] : [];
        $rangkumanItems = is_array($rangkuman['items'] ?? null) ? $rangkuman['items'] : [];
        $rangkumanTotalsByJenis = is_array($rangkuman['totals_by_jenis'] ?? null) ? $rangkuman['totals_by_jenis'] : [];
        $rangkumanGrandTotal = (float) ($rangkuman['grand_total'] ?? 0.0);
        $rangkumanGrouped = [];
        foreach ($rangkumanItems as $item) {
            $jenisKey = (string) ($item['jenis'] ?? '');
            $rangkumanGrouped[$jenisKey] ??= [];
            $rangkumanGrouped[$jenisKey][] = $item;
        }

        $generatedByName = $generatedBy?->name ?? 'sistem';
        $generatedAtText = $generatedAt->copy()->locale('id')->translatedFormat('d-M-y H:i');
        $start = \Carbon\Carbon::parse((string) $startDate)->locale('id')->translatedFormat('d-M-y');
        $end = \Carbon\Carbon::parse((string) $endDate)->locale('id')->translatedFormat('d-M-y');

        $eps = 0.0000001;
        $fmt = static fn(float $v): string => abs($v) < $eps ? '' : number_format($v, 4, '.', ',');
        $fmtTotal = static fn(float $v): string => abs($v) < $eps ? '' : number_format($v, 4, '.', ',');
        $fmtDim = static fn(float $v): string => rtrim(rtrim(number_format($v, 1, '.', ','), '0'), '.');

        $dateLabel = static function (string $key): string {
            try {
                return \Carbon\Carbon::parse($key)->format('d-M');
            } catch (\Throwable $exception) {
                return $key;
            }
        };

        $sumForDates = static function (array $values, array $dates): float {
            $sum = 0.0;
            foreach ($dates as $dk) {
                $sum += (float) ($values[$dk] ?? 0.0);
            }
            return $sum;
        };

        $fmtPct = static fn(float $v): string => abs($v) < $eps ? '' : number_format($v, 0, '.', ',') . '%';
    @endphp

    <h1 class="report-title">Laporan ST Sawmill / Hari / Tebal / Lebar</h1>
    <p class="report-subtitle">Periode {{ $start }} s/d {{ $end }}</p>

    @if ($allDates !== [] && $isGroupBlocks !== [])
        @foreach ($isGroupBlocks as $ig)
            @php
                $isGroupNo = (int) ($ig['is_group'] ?? 0);
                $groups = is_array($ig['groups'] ?? null) ? $ig['groups'] : [];
                $rowIndex = 0;
            @endphp

            <div class="group-title">Group : {{ $isGroupNo }}</div>

            <table style="margin-bottom: 12px;">
                <thead>
                    <tr>
                        <th rowspan="2" style="width: 140px;">Group</th>
                        <th rowspan="2" style="width: 44px;">Tebal</th>
                        <th rowspan="2" style="width: 44px;">Lebar</th>
                        <th colspan="{{ count($allDates) + 1 }}">Tanggal</th>
                    </tr>
                    <tr>
                        @foreach ($allDates as $dk)
                            <th style="width: 48px;">{{ $dateLabel($dk) }}</th>
                        @endforeach
                        <th style="width: 56px;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($groups as $g)
                        @php
                            $groupName = (string) ($g['name'] ?? '');
                            $tebalBlocks = is_array($g['tebal_blocks'] ?? null) ? $g['tebal_blocks'] : [];
                            $groupTotals = is_array($g['totals_by_date'] ?? null) ? $g['totals_by_date'] : [];

                            $groupRowspan = 1; // group total row
                            foreach ($tebalBlocks as $tb) {
                                $lebarRows = is_array($tb['lebar_rows'] ?? null) ? $tb['lebar_rows'] : [];
                                $groupRowspan += max(1, count($lebarRows)) + 1; // data rows + tebal total row
                            }

                            $printedGroup = false;
                        @endphp

                        @foreach ($tebalBlocks as $tb)
                            @php
                                $tebal = (float) ($tb['tebal'] ?? 0.0);
                                $lebarRows = is_array($tb['lebar_rows'] ?? null) ? $tb['lebar_rows'] : [];
                                $tebalTotals = is_array($tb['totals_by_date'] ?? null) ? $tb['totals_by_date'] : [];
                                $tebalRowspan = max(1, count($lebarRows)) + 1; // data + total row
                                $printedTebal = false;
                            @endphp

                            @forelse ($lebarRows as $lr)
                                @php
                                    $rowIndex++;
                                    $lebar = (float) ($lr['lebar'] ?? 0.0);
                                    $values = is_array($lr['values'] ?? null) ? $lr['values'] : [];
                                    $rowTotal = $sumForDates($values, $allDates);
                                @endphp
                                <tr class="{{ $rowIndex % 2 === 1 ? 'row-odd' : 'row-even' }}">
                                    @if (!$printedGroup)
                                        <td class="center col-group" rowspan="{{ $groupRowspan }}">{{ $groupName }}
                                        </td>
                                        @php $printedGroup = true; @endphp
                                    @endif
                                    @if (!$printedTebal)
                                        <td class="center" rowspan="{{ $tebalRowspan }}">
                                            {{ $fmtDim($tebal) }}
                                        </td>
                                        @php $printedTebal = true; @endphp
                                    @endif
                                    <td class="center">{{ $fmtDim($lebar) }}</td>
                                    @foreach ($allDates as $dk)
                                        <td class="number">{{ $fmt((float) ($values[$dk] ?? 0.0)) }}</td>
                                    @endforeach
                                    <td class="number">{{ $fmtTotal($rowTotal) }}</td>
                                </tr>
                            @empty
                                @php
                                    // Still render the tebal total row even if no width rows.
                                    $printedTebal = true;
                                @endphp
                            @endforelse

                            @php
                                $rowIndex++;
                                $tebalTotal = $sumForDates($tebalTotals, $allDates);
                            @endphp
                            <tr class="totals-row {{ $rowIndex % 2 === 1 ? 'row-odd' : 'row-even' }}">
                                <td class="center">Sub total</td>
                                @foreach ($allDates as $dk)
                                    <td class="number">{{ $fmtTotal((float) ($tebalTotals[$dk] ?? 0.0)) }}</td>
                                @endforeach
                                <td class="number">{{ $fmtTotal($tebalTotal) }}</td>
                            </tr>
                        @endforeach

                        @php
                            $rowIndex++;
                            $groupTotal = $sumForDates($groupTotals, $allDates);
                        @endphp
                        <tr class="totals-row {{ $rowIndex % 2 === 1 ? 'row-odd' : 'row-even' }}">
                            <td class="center" colspan="2">Total</td>
                            @foreach ($allDates as $dk)
                                <td class="number">{{ $fmtTotal((float) ($groupTotals[$dk] ?? 0.0)) }}</td>
                            @endforeach
                            <td class="number">{{ $fmtTotal($groupTotal) }}</td>
                        </tr>
                        @empty
                            <tr>
                                <td colspan="{{ 4 + count($allDates) }}" class="center">Tidak ada data.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            @endforeach
        @else
            <div class="center">Tidak ada data.</div>
        @endif

        @if ($isGroupBlocks !== [])
            @if ($rangkumanItems !== [])
                <div class="section-title">Rangkuman Grand Total</div>
                <table class="rangkuman-table zebra-table">
                    <thead>
                        <tr>
                            <th style="width: 160px;">Jenis Kayu</th>
                            <th style="width: 50px;">Tebal</th>
                            <th style="width: 80px;">Total</th>
                            <th style="width: 60px;">Persen</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($rangkumanGrouped as $jenis => $jenisItems)
                            @php
                                $jenisRowspan = count($jenisItems) + 1;
                                $jenisTotal = (float) ($rangkumanTotalsByJenis[$jenis] ?? 0.0);
                            @endphp

                            @foreach ($jenisItems as $idx => $it)
                                @php
                                    $tebal = (float) ($it['tebal'] ?? 0.0);
                                    $total = (float) ($it['total'] ?? 0.0);
                                    $percent = (float) ($it['percent'] ?? 0.0);
                                @endphp
                                <tr class="rangkuman-group{{ $idx === 0 ? ' rangkuman-group-start' : '' }}">
                                    @if ($idx === 0)
                                        <td rowspan="{{ $jenisRowspan }}" class="jenis-cell">{{ $jenis }}</td>
                                    @endif
                                    <td class="center">{{ $fmtDim($tebal) }}</td>
                                    <td class="number">{{ $fmtTotal($total) }}</td>
                                    <td class="center">{{ $fmtPct($percent) }}</td>
                                </tr>
                            @endforeach

                            <tr class="totals-row rangkuman-group">
                                <td class="center">Total</td>
                                <td class="number">{{ $fmtTotal($jenisTotal) }}</td>
                                <td class="center">100%</td>
                            </tr>
                        @endforeach

                        <tr class="totals-row">
                            <td colspan="2" class="center" style="background: none; font-size: 11px;">Total</td>
                            <td class="number" style="background: none; font-size: 11px;">
                                {{ $fmtTotal($rangkumanGrandTotal) }}</td>
                            <td class="center" style="background: none; font-size: 11px;">100%</td>
                        </tr>
                    </tbody>
                </table>
            @endif
        @endif

        @include('reports.partials.pdf-footer-table')
    </body>

    </html>
