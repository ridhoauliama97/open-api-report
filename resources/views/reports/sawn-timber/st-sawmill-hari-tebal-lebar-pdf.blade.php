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
            margin: 0;
            padding: 0;
        }

        body {
            margin: 0;
            font-family: "Noto Serif", serif;
            font-size: 10px;
            line-height: 1.2;
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
            margin: 2px 0 20px 0;
            font-size: 12px;
            color: #636466;
        }

        .section-title {
            margin: 14px 0 6px 0;
            font-size: 12px;
            font-weight: bold;
        }

        table {
            width: calc(100% - 2px);
            line-height: inherit;
            border-collapse: collapse;
            border-spacing: 0;
            border: 1px solid #000;
        }

        th,
        td {
            border: 1px solid #000;
            word-wrap: break-word;
            padding: 2px 2px;
        }

        td.center {
            text-align: center;
        }

        td.label {
            white-space: nowrap;
        }

        td.number {
            text-align: right;
            white-space: nowrap;
            font-family: "Calibri", "DejaVu Sans", sans-serif;
        }

        .row-odd td {
            background: #c9d1df;
        }

        .row-even td {
            background: #eef2f8;
        }

        .totals-row td {
            font-weight: bold;
        }

        .headers-row th {
            font-weight: bold;
        }

        /* --- Gotenberg wave3 extras --- */
        .meta-layout,
        .meta-table,
        .note-table,
        .ratio-table {
            width: auto;
        }

        table.meta-layout,
        table.meta-table,
        table.note-table,
        table.ratio-table,
        table.meta-layout td,
        table.meta-table td,
        table.note-table td,
        table.ratio-table td,
        table.meta-layout th,
        table.meta-table th,
        .meta-label,
        .meta-separator,
        .meta-value {
            border: 0;
        }

        .group-title,
        .customer-title,
        .grade-output,
        .date-separator,
        .grade-title {
            font-style: italic;
            font-weight: bold;
            color: #9c111d;
        }

        .total-row td,
        .grand-total-row td,
        .row-last td,
        .before-total td,
        .totals-label {
            font-weight: bold;
        }

        .page-break {
            page-break-after: always;
        }
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
                return \Carbon\Carbon::parse($key)->locale('id')->translatedFormat('d-M');
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
                $isGroupTotals = is_array($ig['totals_by_date'] ?? null) ? $ig['totals_by_date'] : [];
                $rowIndex = 0;
            @endphp

            <div class="group-title">Group : {{ $isGroupNo }}</div>

            <table style="margin-bottom: 12px;">
                <thead>
                    <tr>
                        <th rowspan="2" style="width: 15%;">Group</th>
                        <th rowspan="2" style="width: 40px;">Tebal</th>
                        <th rowspan="2" style="width: 40px;">Lebar</th>
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
                                        <td class="center col-group col-group-span" rowspan="{{ $groupRowspan }}">
                                            {{ $groupName }}
                                        </td>
                                        @php $printedGroup = true; @endphp
                                    @endif
                                    @if (!$printedTebal)
                                        <td class="center col-tebal-span" rowspan="{{ $tebalRowspan }}">
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
                                <td class="center" style="background: none;">Sub total</td>
                                @foreach ($allDates as $dk)
                                    <td class="number" style="background: none;">
                                        {{ $fmtTotal((float) ($tebalTotals[$dk] ?? 0.0)) }}</td>
                                @endforeach
                                <td class="number" style="background: none;">{{ $fmtTotal($tebalTotal) }}</td>
                            </tr>
                        @endforeach

                        @php
                            $rowIndex++;
                            $groupTotal = $sumForDates($groupTotals, $allDates);
                        @endphp
                        <tr class="totals-row {{ $rowIndex % 2 === 1 ? 'row-odd' : 'row-even' }}">
                            <td class="center" colspan="2" style="background: none;">Total</td>
                            @foreach ($allDates as $dk)
                                <td class="number" style="background: none;">
                                    {{ $fmtTotal((float) ($groupTotals[$dk] ?? 0.0)) }}</td>
                            @endforeach
                            <td class="number" style="background: none;">{{ $fmtTotal($groupTotal) }}</td>
                        </tr>
                        @empty
                            <tr>
                                <td colspan="{{ 4 + count($allDates) }}" class="center">Tidak ada data.</td>
                            </tr>
                        @endforelse

                        @if ($groups !== [])
                            @php
                                $rowIndex++;
                                $isGroupGrandTotal = $sumForDates($isGroupTotals, $allDates);
                            @endphp
                            <tr class="totals-row {{ $rowIndex % 2 === 1 ? 'row-odd' : 'row-even' }}">
                                <td class="center" colspan="3" style="background: none; font-size: 11px;">Grand Total
                                </td>
                                @foreach ($allDates as $dk)
                                    <td class="number" style="background: none; font-size: 11px;">
                                        {{ $fmtTotal((float) ($isGroupTotals[$dk] ?? 0.0)) }}
                                    </td>
                                @endforeach
                                <td class="number" style="background: none; font-size: 11px;">
                                    {{ $fmtTotal($isGroupGrandTotal) }}
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            @endforeach
        @else
            <div class="center">Tidak ada data.</div>
        @endif

        <div class="page-break"></div>
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
                            <td colspan="2" class="center" style="background: none; font-size: 11px;">Grand Total</td>
                            <td class="number" style="background: none; font-size: 11px;">
                                {{ $fmtTotal($rangkumanGrandTotal) }}</td>
                            <td class="center" style="background: none; font-size: 11px;">100%</td>
                        </tr>
                    </tbody>
                </table>
            @endif
        @endif

    </body>

    </html>
