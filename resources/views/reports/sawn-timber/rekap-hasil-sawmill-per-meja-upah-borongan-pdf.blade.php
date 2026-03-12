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
            margin: 14mm 10mm 16mm 10mm;
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
            margin: 2px 0 20px 0;
            font-size: 12px;
            color: #636466;
        }

        .meja-title {
            margin: 8px 0 3px;
            font-size: 11px;
            font-weight: bold;
        }

        .session-meta {
            margin: 0 0 3px;
            font-size: 9px;
            font-weight: bold;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
            page-break-inside: auto;
        }

        .report-table {
            border-collapse: separate;
            border-spacing: 0;
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
            border: 1px solid #000;
            padding: 2px 3px;
            vertical-align: middle;
        }

        th {
            text-align: center;
            font-weight: bold;
            background: #fff;
        }

        .headers-row th {
            font-size: 9px;
            border-top: 0;
            border-bottom: 1px solid #000;
        }

        .report-table tbody tr.data-row td.data-cell {
            border-top: 0 !important;
            border-bottom: 0 !important;
            border-left: 1px solid #000 !important;
            border-right: 1px solid #000 !important;
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
            font-size: 11px;
            border: 1px solid #000;
            background: #fff;
        }

        .table-end-line td {
            border-top: 2px solid #000 !important;
            border-right: 0 !important;
            border-bottom: 0 !important;
            border-left: 0 !important;
            padding: 0 !important;
            height: 0 !important;
            line-height: 0 !important;
            background: #fff !important;
        }

        .summary-title {
            margin: 0 0 5px;
            font-size: 11px;
            font-weight: bold;
        }

        .condition-title {
            margin: 8px 0 3px;
            font-size: 10px;
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
        $mainGroups = is_iterable($groupedRows ?? null)
            ? (is_array($groupedRows)
                ? $groupedRows
                : collect($groupedRows)->values()->all())
            : [];
        $subGroups = is_iterable($groupedSubRows ?? null)
            ? (is_array($groupedSubRows)
                ? $groupedSubRows
                : collect($groupedSubRows)->values()->all())
            : [];
        $startText = \Carbon\Carbon::parse((string) ($startDate ?? now()))->locale('id')->translatedFormat('d/m/Y');
        $endText = \Carbon\Carbon::parse((string) ($endDate ?? now()))->locale('id')->translatedFormat('d/m/Y');
        $generatedByName = $generatedBy?->name ?? 'sistem';
        $generatedAtText = $generatedAt->copy()->locale('id')->translatedFormat('d/m/Y H:i:s');

        $formatDate = static function ($value): string {
            if ($value === null || $value === '') {
                return '';
            }

            try {
                return \Carbon\Carbon::parse((string) $value)->locale('id')->translatedFormat('d M Y');
            } catch (\Throwable $exception) {
                return (string) $value;
            }
        };

        $formatNumber = static function ($value, int $precision = 4): string {
            return number_format((float) $value, $precision, '.', ',');
        };

        $detectCategory = static function (array $row): string {
            $jenis = strtoupper(trim((string) ($row['Jenis'] ?? '')));
            $tebal = (float) ($row['Tebal'] ?? 0);

            if (str_contains($jenis, 'STD')) {
                if (in_array((int) round($tebal), [14, 16, 18, 23], true)) {
                    return 'RB STD (Tbl 14/16/18/23)';
                }

                return 'RB STD';
            }

            return 'RB MC + Lain-Lain';
        };

        $findSubGroup = static function (array $groups, int $noMeja) {
            foreach ($groups as $group) {
                if ((int) ($group['no_meja'] ?? 0) === $noMeja) {
                    return $group;
                }
            }

            return null;
        };

        $flattenDateGroups = static function (array $dateGroups): array {
            $out = [];
            foreach ($dateGroups as $dg) {
                $rows = is_array($dg['rows'] ?? null) ? $dg['rows'] : [];
                foreach ($rows as $r) {
                    $out[] = $r;
                }
            }
            return $out;
        };

        // Build summary per special Condition (uses sub-report SM when available).
        $conditionSummaries = [];
        foreach ($mainGroups as $group) {
            $noMeja = (int) ($group['no_meja'] ?? 0);
            $namaMeja = (string) ($group['nama_meja'] ?? 'Meja ' . $noMeja);
            $dateGroups = is_array($group['date_groups'] ?? null) ? $group['date_groups'] : [];
            $rowsData = $flattenDateGroups($dateGroups);

            $subGroup = $findSubGroup($subGroups, $noMeja);
            $subDateGroups = is_array($subGroup['date_groups'] ?? null) ? $subGroup['date_groups'] : [];
            $subRowsData = $flattenDateGroups($subDateGroups);

            $smByCondition = [];
            foreach ($subRowsData as $subRow) {
                $condition = trim((string) ($subRow['Condition'] ?? ''));
                $condition = $condition !== '' ? $condition : 'NORMAL';
                $sm = (float) ($subRow['SM'] ?? 0);
                if (!array_key_exists($condition, $smByCondition) || $sm > $smByCondition[$condition]) {
                    $smByCondition[$condition] = $sm;
                }
            }

            foreach ($rowsData as $row) {
                $condition = trim((string) ($row['Condition'] ?? ''));
                $condition = $condition !== '' ? $condition : 'NORMAL';
                $category = $detectCategory($row);

                if (!isset($conditionSummaries[$condition][$noMeja])) {
                    $conditionSummaries[$condition][$noMeja] = [
                        'no_meja' => $noMeja,
                        'nama_meja' => $namaMeja,
                        'RB STD (Tbl 14/16/18/23)' => 0.0,
                        'RB STD' => 0.0,
                        'RB MC + Lain-Lain' => 0.0,
                        'Jumlah' => 0.0,
                        'SM' => $smByCondition[$condition] ?? 0.0,
                    ];
                }

                $ton = (float) ($row['TonRacip'] ?? 0);
                $conditionSummaries[$condition][$noMeja][$category] += $ton;
                $conditionSummaries[$condition][$noMeja]['Jumlah'] += $ton;
            }
        }
    @endphp

    <h1 class="report-title">Laporan Rekap Hasil Sawmill Per-Meja (Upah Borongan)</h1>
    <p class="report-subtitle">Dari {{ $startText }} Sampai {{ $endText }}</p>

    @foreach ($mainGroups as $group)
        @php
            $noMeja = (int) ($group['no_meja'] ?? 0);
            $namaMeja = (string) ($group['nama_meja'] ?? 'Meja ' . $noMeja);
            $dateGroups = is_array($group['date_groups'] ?? null) ? $group['date_groups'] : [];

            $mejaTotals = [
                'RB STD (Tbl 14/16/18/23)' => 0.0,
                'RB STD' => 0.0,
                'RB MC + Lain-Lain' => 0.0,
                'Jumlah' => 0.0,
            ];
        @endphp

        <p class="meja-title">No Meja: {{ $noMeja }} {{ $namaMeja }}</p>

        @foreach ($dateGroups as $dg)
            @php
                $dateKey = (string) ($dg['date'] ?? '');
                $rowsData = is_array($dg['rows'] ?? null) ? $dg['rows'] : [];
                $runningNo = 1;

                $pairedRows = [];
                $buffer = [];
                foreach ($rowsData as $row) {
                    $buffer[] = [
                        'no' => $runningNo++,
                        'row' => $row,
                    ];
                    if (count($buffer) === 2) {
                        $pairedRows[] = $buffer;
                        $buffer = [];
                    }

                    $category = $detectCategory($row);
                    $ton = (float) ($row['TonRacip'] ?? 0);
                    $mejaTotals[$category] += $ton;
                    $mejaTotals['Jumlah'] += $ton;
                }
                if ($buffer !== []) {
                    $pairedRows[] = $buffer;
                }

                $operators = [];
                foreach ($rowsData as $row) {
                    $op = trim((string) ($row['Operator'] ?? ''));
                    if ($op !== '') {
                        $operators[$op] = true;
                    }
                }
                $operatorText = implode(', ', array_keys($operators));
            @endphp

            <p class="session-meta">
                Tanggal: {{ $formatDate($dateKey) }}
                @if ($operatorText !== '')
                    | Operator: {{ $operatorText }}
                @endif
            </p>

            <table class="report-table">
                <thead>
                    <tr class="headers-row">
                        <th style="width: 4%;">No</th>
                        <th style="width: 18%;">Jenis Kayu</th>
                        <th style="width: 6%;">Tebal</th>
                        <th style="width: 6%;">Lebar</th>
                        <th style="width: 5%;">UOM</th>
                        <th style="width: 8%;">Ton Racip</th>
                        <th style="width: 4%;">No</th>
                        <th style="width: 18%;">Jenis Kayu</th>
                        <th style="width: 6%;">Tebal</th>
                        <th style="width: 6%;">Lebar</th>
                        <th style="width: 5%;">UOM</th>
                        <th style="width: 8%;">Ton Racip</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($pairedRows as $pair)
                        @php
                            $left = $pair[0] ?? null;
                            $right = $pair[1] ?? null;
                        @endphp
                        <tr class="data-row {{ $loop->odd ? 'row-odd' : 'row-even' }}">
                            <td class="data-cell center">{{ $left['no'] ?? '' }}</td>
                            <td class="data-cell">{{ $left['row']['Jenis'] ?? '' }}</td>
                            <td class="data-cell center">{{ $left['row']['Tebal'] ?? '' }}</td>
                            <td class="data-cell center">{{ $left['row']['Lebar'] ?? '' }}</td>
                            <td class="data-cell center">{{ $left['row']['UOM'] ?? '' }}</td>
                            <td class="data-cell number">
                                {{ isset($left['row']) ? $formatNumber($left['row']['TonRacip'] ?? 0) : '' }}</td>
                            <td class="data-cell center">{{ $right['no'] ?? '' }}</td>
                            <td class="data-cell">{{ $right['row']['Jenis'] ?? '' }}</td>
                            <td class="data-cell center">{{ $right['row']['Tebal'] ?? '' }}</td>
                            <td class="data-cell center">{{ $right['row']['Lebar'] ?? '' }}</td>
                            <td class="data-cell center">{{ $right['row']['UOM'] ?? '' }}</td>
                            <td class="data-cell number">
                                {{ isset($right['row']) ? $formatNumber($right['row']['TonRacip'] ?? 0) : '' }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="table-end-line">
                        <td colspan="12"></td>
                    </tr>
                </tfoot>
            </table>
        @endforeach

        <table class="report-table">
            <tbody>
                <tr class="totals-row">
                    <td>RB STD (Tbl 14/16/18/23) : {{ $formatNumber($mejaTotals['RB STD (Tbl 14/16/18/23)']) }}</td>
                    <td>RB STD : {{ $formatNumber($mejaTotals['RB STD']) }}</td>
                    <td>RB MC + Lain-Lain : {{ $formatNumber($mejaTotals['RB MC + Lain-Lain']) }}</td>
                    <td class="number">Jmlh (Ton) /Meja [{{ $noMeja }}] :
                        {{ $formatNumber($mejaTotals['Jumlah']) }}</td>
                </tr>
                <tr class="table-end-line">
                    <td colspan="4"></td>
                </tr>
            </tbody>
        </table>
    @endforeach

    @if (count($conditionSummaries) > 0)
        <div class="page-break"></div>
        <p class="summary-title">Rangkuman/Meja</p>

        @foreach ($conditionSummaries as $condition => $rowsByMeja)
            @php
                ksort($rowsByMeja, SORT_NUMERIC);
                $grand = [
                    'RB STD (Tbl 14/16/18/23)' => 0.0,
                    'RB STD' => 0.0,
                    'RB MC + Lain-Lain' => 0.0,
                    'Jumlah' => 0.0,
                    'SM' => 0.0,
                ];
            @endphp

            <p class="condition-title">{{ $condition }}</p>
            <table class="report-table">
                <thead>
                    <tr class="headers-row">
                        <th style="width: 24%;">No.Meja</th>
                        <th style="width: 14%;">RB STD (Tbl 14/16/18/23)</th>
                        <th style="width: 14%;">RMBG STD</th>
                        <th style="width: 14%;">RMBG MC + Lainnya</th>
                        <th style="width: 14%;">Jumlah</th>
                        <th style="width: 8%;">SM</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rowsByMeja as $row)
                        @php
                            $grand['RB STD (Tbl 14/16/18/23)'] += (float) ($row['RB STD (Tbl 14/16/18/23)'] ?? 0);
                            $grand['RB STD'] += (float) ($row['RB STD'] ?? 0);
                            $grand['RB MC + Lain-Lain'] += (float) ($row['RB MC + Lain-Lain'] ?? 0);
                            $grand['Jumlah'] += (float) ($row['Jumlah'] ?? 0);
                            $grand['SM'] += (float) ($row['SM'] ?? 0);
                        @endphp
                        <tr class="data-row {{ $loop->odd ? 'row-odd' : 'row-even' }}">
                            <td class="data-cell">
                                {{ $row['no_meja'] ?? '' }} {{ $row['nama_meja'] ?? '' }}
                            </td>
                            <td class="data-cell number">{{ $formatNumber($row['RB STD (Tbl 14/16/18/23)'] ?? 0) }}
                            </td>
                            <td class="data-cell number">{{ $formatNumber($row['RB STD'] ?? 0) }}</td>
                            <td class="data-cell number">{{ $formatNumber($row['RB MC + Lain-Lain'] ?? 0) }}</td>
                            <td class="data-cell number">{{ $formatNumber($row['Jumlah'] ?? 0) }}</td>
                            <td class="data-cell number">{{ $formatNumber($row['SM'] ?? 0) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="totals-row">
                        <td class="center">Total</td>
                        <td class="number">{{ $formatNumber($grand['RB STD (Tbl 14/16/18/23)']) }}</td>
                        <td class="number">{{ $formatNumber($grand['RB STD']) }}</td>
                        <td class="number">{{ $formatNumber($grand['RB MC + Lain-Lain']) }}</td>
                        <td class="number">{{ $formatNumber($grand['Jumlah']) }}</td>
                        <td class="number">{{ $formatNumber($grand['SM']) }}</td>
                    </tr>
                    <tr class="table-end-line">
                        <td colspan="6"></td>
                    </tr>
                </tfoot>
            </table>
        @endforeach
    @endif

    <htmlpagefooter name="reportFooter">
        <div class="footer-wrap">
            <div class="footer-left">Dicetak oleh: {{ $generatedByName }} pada {{ $generatedAtText }}</div>
            <div class="footer-right">Halaman {PAGENO} dari {nbpg}</div>
        </div>
    </htmlpagefooter>
    <sethtmlpagefooter name="reportFooter" value="on" />
</body>

</html>
