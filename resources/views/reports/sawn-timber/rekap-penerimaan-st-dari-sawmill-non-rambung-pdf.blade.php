<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif:ital,wght@0,100..900;1,100..900&display=swap"
        rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
        }

        @page {
            margin: 16mm 10mm 16mm 10mm;
            footer: html_reportFooter;
        }

        body {
            margin: 0;
            font-family: "Noto Serif", serif;
            font-size: 10px;
            line-height: 1.25;
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
            margin: 2px 0 16px 0;
            font-size: 12px;
            color: #636466;
        }

        .group-title {
            margin: 10px 0 6px 0;
            font-size: 12px;
            font-weight: bold;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            page-break-inside: auto;
            table-layout: fixed;
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
            padding: 2px 4px;
            vertical-align: middle;
            text-align: left;
            word-break: break-word;
        }

        th {
            text-align: center;
            font-weight: bold;
            font-size: 10px;
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

        td.center {
            text-align: center;
        }


        tfoot {
            display: table-footer-group;
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
        $groups = is_array($data['supplier_groups'] ?? null) ? $data['supplier_groups'] : [];
        $rows = is_array($data['rows'] ?? null) ? $data['rows'] : [];
        $schema = is_array($data['column_schema'] ?? null) ? $data['column_schema'] : [];
        $summaries = is_array($data['supplier_summaries'] ?? null) ? $data['supplier_summaries'] : [];
        $grand = is_array($data['grand_totals'] ?? null) ? $data['grand_totals'] : [];

        $generatedByName = $generatedBy?->name ?? 'sistem';
        $generatedAtText = $generatedAt->copy()->locale('id')->translatedFormat('d-M-y H:i');
        $start = \Carbon\Carbon::parse((string) $startDate)->locale('id')->translatedFormat('d-M-y');
        $end = \Carbon\Carbon::parse((string) $endDate)->locale('id')->translatedFormat('d-M-y');

        $toFloat = static function (mixed $value): ?float {
            if ($value === null) {
                return null;
            }

            if (is_numeric($value)) {
                return (float) $value;
            }

            if (!is_string($value)) {
                return null;
            }

            $normalized = trim(str_replace(' ', '', $value));
            if ($normalized === '') {
                return null;
            }

            if (str_contains($normalized, ',') && str_contains($normalized, '.')) {
                if (strrpos($normalized, ',') > strrpos($normalized, '.')) {
                    $normalized = str_replace('.', '', $normalized);
                    $normalized = str_replace(',', '.', $normalized);
                } else {
                    $normalized = str_replace(',', '', $normalized);
                }
            } elseif (str_contains($normalized, ',')) {
                $normalized = str_replace(',', '.', $normalized);
            }

            return is_numeric($normalized) ? (float) $normalized : null;
        };

        $formatNumber = static fn(float $value, int $decimals): string => number_format($value, $decimals, '.', ',');

        $formatDateCell = static function (mixed $value): string {
            if ($value === null || $value === '') {
                return '';
            }

            if ($value instanceof \DateTimeInterface) {
                try {
                    return \Carbon\Carbon::instance($value)->locale('id')->translatedFormat('d M Y');
                } catch (\Throwable $exception) {
                    return (string) $value;
                }
            }

            $raw = trim((string) $value);
            if ($raw === '') {
                return '';
            }

            try {
                return \Carbon\Carbon::parse($raw)->locale('id')->translatedFormat('d M Y');
            } catch (\Throwable $exception) {
                return $raw;
            }
        };

        $formatBySpec = static function (mixed $value, array $spec) use (
            $toFloat,
            $formatNumber,
            $formatDateCell,
        ): string {
            $type = strtolower((string) ($spec['type'] ?? 'text'));
            $decimals = isset($spec['decimals']) ? (int) $spec['decimals'] : 2;

            if ($type === 'date') {
                return $formatDateCell($value);
            }

            if ($type === 'number') {
                $n = $toFloat($value);
                return $n === null ? '' : $formatNumber($n, $decimals);
            }

            if ($type === 'percent') {
                $n = $toFloat($value);
                if ($n === null) {
                    return '';
                }

                // Accept both ratio (0.xx) and percent (66.xx).
                $percent = $n <= 1.5 ? $n * 100.0 : $n;

                return $formatNumber($percent, $decimals) . '%';
            }

            return trim((string) ($value ?? ''));
        };

        $cellClassBySpec = static function (array $spec): string {
            $type = strtolower((string) ($spec['type'] ?? 'text'));

            return in_array($type, ['number', 'percent'], true) ? 'number' : '';
        };
    @endphp

    <h1 class="report-title">Laporan Rekap Penerimaan ST Dari Sawmill (Non Rambung)</h1>
    <div class="report-subtitle">Periode: {{ $start }} s/d {{ $end }}</div>

    @forelse ($groups as $group)
        @php
            $supplierName = (string) ($group['supplier'] ?? 'Tanpa Supplier');
            $groupRows = is_array($group['rows'] ?? null) ? $group['rows'] : [];
            $supplierSummary = null;
            foreach ($summaries as $s) {
                if ((string) ($s['supplier'] ?? '') === $supplierName) {
                    $supplierSummary = $s;
                    break;
                }
            }
            $kbTotal = (float) ($supplierSummary['kb_total'] ?? 0.0);
            $stTotal = (float) ($supplierSummary['st_total'] ?? 0.0);
            $diaAvg = $supplierSummary['ave_dia'] ?? null;
            $tblAvg = $supplierSummary['ave_tbl'] ?? null;
            $rendPct = $supplierSummary['rend_percent'] ?? null;
        @endphp

        <div class="group-title">{{ $supplierName }}</div>

        <table>
            <thead>
                <tr>
                    <th style="width: 4%;">No</th>
                    @foreach ($schema as $colSpec)
                        <th style="width: 8%;">{{ (string) ($colSpec['label'] ?? ($colSpec['key'] ?? '')) }}</th>
                    @endforeach
                </tr>
            </thead>

            <tbody>
                @php $rowIndex = 0; @endphp
                @forelse ($groupRows as $row)
                    @php
                        $rowIndex++;
                        $rowData = is_array($row ?? null) ? $row : (array) $row;
                    @endphp
                    <tr class="{{ $rowIndex % 2 === 1 ? 'row-odd' : 'row-even' }}">
                        <td class="center">{{ $rowIndex }}</td>
                        @foreach ($schema as $colSpec)
                            @php
                                $key = (string) ($colSpec['key'] ?? '');
                                $cell = $key !== '' ? $rowData[$key] ?? null : null;
                            @endphp
                            <td class="{{ $cellClassBySpec($colSpec) }}">{{ $formatBySpec($cell, $colSpec) }}</td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ max(1, count($schema) + 1) }}" style="text-align: center;">Tidak ada data</td>
                    </tr>
                @endforelse

                @if ($groupRows !== [])
                    @php
                        $tonKbIndex = null;
                        foreach ($schema as $index => $colSpec) {
                            if (strcasecmp((string) ($colSpec['key'] ?? ''), 'Ton (KB)') === 0) {
                                $tonKbIndex = $index;
                                break;
                            }
                        }
                        $labelSpan = $tonKbIndex !== null ? 1 + $tonKbIndex : 1;
                    @endphp
                    <tr>
                        <td class="number" style="text-align: center;" colspan="{{ $labelSpan }}">
                            <strong>Total :</strong>
                        </td>
                        @foreach ($schema as $index => $colSpec)
                            @if ($tonKbIndex !== null && $index < $tonKbIndex)
                                @continue
                            @endif
                            @php
                                $k = strtolower((string) ($colSpec['key'] ?? ''));
                                $v = '';
                                if ($k === 'ton (kb)') {
                                    $v = $formatNumber($kbTotal, 4);
                                } elseif ($k === 'ton (st)') {
                                    $v = $formatNumber($stTotal, 4);
                                } elseif ($k === 'ave dia') {
                                    $v = $diaAvg === null ? '' : $formatNumber((float) $diaAvg, 1);
                                } elseif ($k === 'ave tbl') {
                                    $v = $tblAvg === null ? '' : $formatNumber((float) $tblAvg, 1);
                                } elseif ($k === 'rend st-kb') {
                                    $v = $rendPct === null ? '' : $formatNumber((float) $rendPct, 2) . '%';
                                }
                            @endphp
                            <td class="{{ $v !== '' ? 'number' : '' }}"><strong>{{ $v }}</strong></td>
                        @endforeach
                    </tr>
                @endif
            </tbody>
        </table>
    @empty
        <div style="text-align: center; color: #636466;">Tidak ada data pada periode ini.</div>
    @endforelse

    @if ($rows !== [] && $schema !== [])
        @php
            $tonKbIndex = null;
            foreach ($schema as $index => $colSpec) {
                if (strcasecmp((string) ($colSpec['key'] ?? ''), 'Ton (KB)') === 0) {
                    $tonKbIndex = $index;
                    break;
                }
            }
            $labelSpan = $tonKbIndex !== null ? 1 + $tonKbIndex : 1;

            $grandKb = (float) ($grand['kb_total'] ?? 0.0);
            $grandSt = (float) ($grand['st_total'] ?? 0.0);
            $grandDia = $grand['ave_dia'] ?? null;
            $grandTbl = $grand['ave_tbl'] ?? null;
            $grandRend = $grand['rend_percent'] ?? null;
        @endphp

        <table>
            <tbody>
                <tr>
                    <td class="center" colspan="{{ $labelSpan }}" style="width: 52%;"><strong>Grand Total </strong>
                    </td>
                    @foreach ($schema as $index => $colSpec)
                        @if ($tonKbIndex !== null && $index < $tonKbIndex)
                            @continue
                        @endif
                        @php
                            $k = strtolower((string) ($colSpec['key'] ?? ''));
                            $v = '';
                            if ($k === 'ton (kb)') {
                                $v = $formatNumber($grandKb, 4);
                            } elseif ($k === 'ton (st)') {
                                $v = $formatNumber($grandSt, 4);
                            } elseif ($k === 'ave dia') {
                                $v = $grandDia === null ? '' : $formatNumber((float) $grandDia, 1);
                            } elseif ($k === 'ave tbl') {
                                $v = $grandTbl === null ? '' : $formatNumber((float) $grandTbl, 1);
                            } elseif ($k === 'rend st-kb') {
                                $v = $grandRend === null ? '' : $formatNumber((float) $grandRend, 1) . '%';
                            }
                        @endphp
                        <td class="{{ $v !== '' ? 'number' : '' }}" style="width: 8%;">
                            <strong>{{ $v }}</strong>
                        </td>
                    @endforeach
                </tr>
            </tbody>
        </table>

        @php
            $historis = [
                'diameters' => [2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 19, 23],
                'rows' => [
                    '2"' => [null, null, 73, 70, 68, 65, 65, 62, 60, 64, 47, 61, 56, 56, null, null, null, null],
                    '3"' => [221, 121, 107, 97, 91, 86, 82, 78, 78, 75, 74, 73, 63, 73, 69, null, 71, 43],
                ],
            ];
        @endphp

        <div class="group-title" style="text-align: center; margin-top: 18px;">Tabel Rata-rata Rendemen Secara Historis
            Per-Maret 2019</div>
        <table>
            <thead>
                <tr>
                    <th rowspan="2" style="width: 70px;">Potongan</th>
                    <th colspan="{{ count($historis['diameters']) }}">Diameter</th>
                </tr>
                <tr>
                    @foreach ($historis['diameters'] as $d)
                        <th>{{ $d }}</th>
                    @endforeach
                </tr>
            </thead>

            <tbody>
                @php $ri = 0; @endphp
                @foreach ($historis['rows'] as $potong => $vals)
                    @php $ri++; @endphp
                    <tr class="{{ $ri % 2 === 1 ? 'row-odd' : 'row-even' }}">
                        <td class="center">{{ $potong }}</td>
                        @foreach ($vals as $val)
                            <td class="number">{{ $val === null ? '' : (string) $val }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="group-title" style="text-align: center; margin-top: 14px;">RANGKUMAN SUPPLIER</div>
        <table>
            <thead>
                <tr>
                    <th style="width: 220px;">Supplier</th>
                    <th style="width: 75px;">Ton (KB)</th>
                    <th style="width: 70px;">Persen KB</th>
                    <th style="width: 75px;">Ton (ST)</th>
                    <th style="width: 70px;">Persen ST</th>
                    <th style="width: 60px;">Ave Dia</th>
                    <th style="width: 60px;">Ave Tbl</th>
                    <th style="width: 80px;">Rend ST-KB</th>
                </tr>
            </thead>

            <tbody>
                @php $si = 0; @endphp
                @foreach ($summaries as $s)
                    @php
                        $si++;
                        $kb = (float) ($s['kb_total'] ?? 0.0);
                        $st = (float) ($s['st_total'] ?? 0.0);
                        $kbPct = $s['kb_percent'] ?? null;
                        $stPct = $s['st_percent'] ?? null;
                        $dia = $s['ave_dia'] ?? null;
                        $tbl = $s['ave_tbl'] ?? null;
                        $rend = $s['rend_percent'] ?? null;
                    @endphp
                    <tr class="{{ $si % 2 === 1 ? 'row-odd' : 'row-even' }}">
                        <td>{{ (string) ($s['supplier'] ?? '') }}</td>
                        <td class="number">{{ $formatNumber($kb, 4) }}</td>
                        <td class="number">{{ $kbPct === null ? '' : $formatNumber((float) $kbPct, 2) . '%' }}</td>
                        <td class="number">{{ $formatNumber($st, 4) }}</td>
                        <td class="number">{{ $stPct === null ? '' : $formatNumber((float) $stPct, 2) . '%' }}</td>
                        <td class="number">{{ $dia === null ? '' : $formatNumber((float) $dia, 1) }}</td>
                        <td class="number">{{ $tbl === null ? '' : $formatNumber((float) $tbl, 1) }}</td>
                        <td class="number">{{ $rend === null ? '' : $formatNumber((float) $rend, 2) . '%' }}</td>
                    </tr>
                @endforeach
                <tr>
                    <td class="center"><strong> Total :</strong></td>
                    <td class="number"><strong>{{ $formatNumber($grandKb, 4) }}</strong></td>
                    <td></td>
                    <td class="number"><strong>{{ $formatNumber($grandSt, 4) }}</strong></td>
                    <td></td>
                    <td class="number">
                        <strong>{{ $grandDia === null ? '' : $formatNumber((float) $grandDia, 1) }}</strong>
                    </td>
                    <td class="number">
                        <strong>{{ $grandTbl === null ? '' : $formatNumber((float) $grandTbl, 1) }}</strong>
                    </td>
                    <td class="number">
                        <strong>{{ $grandRend === null ? '' : $formatNumber((float) $grandRend, 2) . '%' }}</strong>
                    </td>
                </tr>
            </tbody>
        </table>
    @endif

    @include('reports.partials.pdf-footer-table')
</body>

</html>
