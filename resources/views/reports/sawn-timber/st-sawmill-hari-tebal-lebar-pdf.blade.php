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
            margin: 12mm 10mm 14mm 10mm;
            footer: html_reportFooter;
        }

        body {
            margin: 0;
            font-family: "Noto Serif", serif;
            font-size: 9px;
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
        }

        thead {
            display: table-header-group;
        }

        tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 2px 3px;
            vertical-align: middle;
        }

        th {
            text-align: center;
            font-weight: bold;
        }

        .row-odd td {
            background: #c9d1df;
        }

        .row-even td {
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
        }

        .page-break {
            page-break-before: always;
        }

        .footer-wrap {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }

        .footer-left,
        .footer-right {
            font-size: 8px;
            font-style: italic;
        }

        .footer-right {
            text-align: right;
        }
    </style>
</head>

<body>
    @php
        $data = is_array($reportData ?? null) ? $reportData : [];
        $dateChunks = is_array($data['date_chunks'] ?? null) ? $data['date_chunks'] : [];
        $isGroupBlocks = is_array($data['is_group_blocks'] ?? null) ? $data['is_group_blocks'] : [];

        $generatedByName = $generatedBy?->name ?? 'sistem';
        $generatedAtText = $generatedAt->copy()->locale('id')->translatedFormat('d-M-y H:i');
        $start = \Carbon\Carbon::parse((string) $startDate)->locale('id')->translatedFormat('d-M-y');
        $end = \Carbon\Carbon::parse((string) $endDate)->locale('id')->translatedFormat('d-M-y');

        $eps = 0.0000001;
        $fmt = static fn(float $v): string => abs($v) < $eps ? '' : number_format($v, 4, '.', ',');
        $fmtTotal = static fn(float $v): string => number_format($v, 4, '.', ',');

        $dateLabel = static function (string $key): string {
            try {
                return \Carbon\Carbon::parse($key)->format('d/m/Y');
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
    @endphp

    <h1 class="report-title">Laporan ST Sawmill / Hari / Tebal / Lebar</h1>
    <p class="report-subtitle">Periode {{ $start }} s/d {{ $end }}</p>

    @forelse ($dateChunks as $chunkIndex => $chunkDates)
        @if ($chunkIndex > 0)
            <div class="page-break"></div>
        @endif

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
                        <th colspan="{{ count($chunkDates) + 1 }}">Tanggal</th>
                    </tr>
                    <tr>
                        @foreach ($chunkDates as $dk)
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
                                    $chunkTotal = $sumForDates($values, $chunkDates);
                                @endphp
                                <tr class="{{ $rowIndex % 2 === 1 ? 'row-odd' : 'row-even' }}">
                                    @if (!$printedGroup)
                                        <td class="center" rowspan="{{ $groupRowspan }}">{{ $groupName }}</td>
                                        @php $printedGroup = true; @endphp
                                    @endif
                                    @if (!$printedTebal)
                                        <td class="center" rowspan="{{ $tebalRowspan }}">
                                            {{ rtrim(rtrim(number_format($tebal, 1, '.', ','), '0'), '.') }}
                                        </td>
                                        @php $printedTebal = true; @endphp
                                    @endif
                                    <td class="center">{{ rtrim(rtrim(number_format($lebar, 1, '.', ','), '0'), '.') }}</td>
                                    @foreach ($chunkDates as $dk)
                                        <td class="number">{{ $fmt((float) ($values[$dk] ?? 0.0)) }}</td>
                                    @endforeach
                                    <td class="number">{{ $fmtTotal($chunkTotal) }}</td>
                                </tr>
                            @empty
                                @php
                                    // Still render the tebal total row even if no width rows.
                                    $printedTebal = true;
                                @endphp
                            @endforelse

                            @php
                                $rowIndex++;
                                $chunkTotal = $sumForDates($tebalTotals, $chunkDates);
                            @endphp
                            <tr class="totals-row {{ $rowIndex % 2 === 1 ? 'row-odd' : 'row-even' }}">
                                <td class="center">Total</td>
                                @foreach ($chunkDates as $dk)
                                    <td class="number">{{ $fmtTotal((float) ($tebalTotals[$dk] ?? 0.0)) }}</td>
                                @endforeach
                                <td class="number">{{ $fmtTotal($chunkTotal) }}</td>
                            </tr>
                        @endforeach

                        @php
                            $rowIndex++;
                            $chunkTotal = $sumForDates($groupTotals, $chunkDates);
                        @endphp
                        <tr class="totals-row {{ $rowIndex % 2 === 1 ? 'row-odd' : 'row-even' }}">
                            <td class="center"></td>
                            <td class="center">Total</td>
                            @foreach ($chunkDates as $dk)
                                <td class="number">{{ $fmtTotal((float) ($groupTotals[$dk] ?? 0.0)) }}</td>
                            @endforeach
                            <td class="number">{{ $fmtTotal($chunkTotal) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ 4 + count($chunkDates) }}" class="center">Tidak ada data.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        @endforeach
    @empty
        <div class="center">Tidak ada data.</div>
    @endforelse

    <htmlpagefooter name="reportFooter">
        <div class="footer-wrap">
            <div class="footer-left">Dicetak oleh: {{ $generatedByName }} pada {{ $generatedAtText }}</div>
            <div class="footer-right">Halaman {PAGENO} dari {nbpg}</div>
        </div>
    </htmlpagefooter>
    <sethtmlpagefooter name="reportFooter" value="on" />
</body>

</html>

