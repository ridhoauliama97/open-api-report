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
        $layout = (string) ($data['layout'] ?? 'grade');
        $header = is_array($data['header'] ?? null) ? $data['header'] : [];
        $columns = is_array($data['length_columns'] ?? null) ? $data['length_columns'] : [];
        $flatTebalGroups = is_array($data['flat_tebal_groups'] ?? null) ? $data['flat_tebal_groups'] : [];
        $groups = is_array($data['grade_groups'] ?? null) ? $data['grade_groups'] : [];
        $summary = is_array($data['summary'] ?? null) ? $data['summary'] : [];
        $subSummary = is_array($data['sub_summary'] ?? null) ? $data['sub_summary'] : [];
        $subRows = is_array($subSummary['rows'] ?? null) ? $subSummary['rows'] : [];
        $generatedByName = $generatedBy?->name ?? 'sistem';
        $generatedAtText = $generatedAt->copy()->locale('id')->translatedFormat('d-M-y H:i');

        $formatDate = static function ($value): string {
            if ($value === null || trim((string) $value) === '') {
                return '-';
            }

            try {
                return \Carbon\Carbon::parse((string) $value)->locale('id')->translatedFormat('d-M-y');
            } catch (\Throwable) {
                return (string) $value;
            }
        };

        $formatNumber = static function ($value, int $decimals = 0): string {
            $number = (float) $value;
            return number_format($number, $decimals, '.', ',');
        };

        $formatSize = static function ($value): string {
            $text = number_format((float) $value, 1, '.', '');
            return str_ends_with($text, '') ? substr($text, 0, -2) : $text;
        };

        $formatBlankable = static function ($value, int $decimals): string {
            $number = (float) $value;
            return abs($number) < 0.0000001 ? '' : number_format($number, $decimals, '.', '');
        };

        $floorNumber = static function (float $value, int $decimals): float {
            $factor = 10 ** $decimals;
            return floor($value * $factor) / $factor;
        };

        $kbSummary = [
            'super' => 0.0,
            'mc' => 0.0,
            'samsam' => 0.0,
        ];
        foreach ($subRows as $subRow) {
            $name = strtoupper(trim((string) ($subRow['NamaGrade'] ?? '')));
            $berat = (float) ($subRow['Berat'] ?? 0.0);

            if (str_contains($name, 'STD') || str_contains($name, 'SUPER')) {
                $kbSummary['super'] += $berat;
            } elseif (str_contains($name, 'MC')) {
                $kbSummary['mc'] += $berat;
            } else {
                $kbSummary['samsam'] += $berat;
            }
        }

        $gradeTon = [];
        foreach ($groups as $group) {
            $gradeTon[strtoupper(trim((string) ($group['grade'] ?? '')))] = (float) ($group['total_ton'] ?? 0.0);
        }
        $totalKb = (float) ($subSummary['total_berat'] ?? array_sum($kbSummary));
        $totalStTon = (float) ($summary['total_ton'] ?? 0.0);
        $stdTon = (float) ($gradeTon['STD'] ?? 0.0);
        $mcTon =
            (float) ($gradeTon['MC 1'] ?? 0.0) +
            (float) ($gradeTon['MC1'] ?? 0.0) +
            (float) ($gradeTon['MC 2'] ?? 0.0) +
            (float) ($gradeTon['MC2'] ?? 0.0);
        $kbPerSt = $totalStTon > 0 ? $totalKb / $totalStTon : 0.0;
        $stdPerSt = $totalStTon > 0 ? ($stdTon / $totalStTon) * 100 : 0.0;
        $mcPerSt = $totalStTon > 0 ? ($mcTon / $totalStTon) * 100 : 0.0;

        $lineCounter = 0;
    @endphp

    <h1 class="report-title">Laporan Penerimaan ST Hasil Sawmill</h1>

    <table class="meta-layout">
        <tbody>
            <tr>
                <td style="width: 33%; padding-right: 8px;">
                    <table class="meta-block">
                        <tbody>
                            <tr>
                                <td class="meta-label">No. Penerimaan ST</td>
                                <td class="meta-separator">:</td>
                                <td>{{ $header['no_penerimaan_st'] ?? ($noPenSt ?? '-') }}</td>
                            </tr>
                            <tr>
                                <td class="meta-label">No. Kayu Bulat</td>
                                <td class="meta-separator">:</td>
                                <td>{{ $header['no_kayu_bulat'] ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td class="meta-label">Tanggal Laporan</td>
                                <td class="meta-separator">:</td>
                                <td>{{ $formatDate($header['tgl_laporan'] ?? null) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </td>
                <td style="width: 34%; padding: 0 8px;">
                    <table class="meta-block">
                        <tbody>
                            <tr>
                                <td class="meta-label">Supplier</td>
                                <td class="meta-separator">:</td>
                                <td>{{ $header['supplier'] ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td class="meta-label">No.Truk</td>
                                <td class="meta-separator">:</td>
                                <td>{{ $header['no_truk'] ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td class="meta-label">No. Plat</td>
                                <td class="meta-separator">:</td>
                                <td>{{ $header['no_plat'] ?? '-' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </td>
                <td style="width: 33%; padding-left: 8px;">
                    <table class="meta-block">
                        <tbody>
                            <tr>
                                <td class="meta-label">Jenis Kayu</td>
                                <td class="meta-separator">:</td>
                                <td>{{ $header['jenis_kayu'] ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td class="meta-label">No.Suket</td>
                                <td class="meta-separator">:</td>
                                <td>{{ $header['no_suket'] ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td class="meta-label">Tanggal Masuk</td>
                                <td class="meta-separator">:</td>
                                <td>{{ $formatDate($header['tgl_masuk'] ?? null) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </td>
            </tr>
        </tbody>
    </table>

    <table class="report-table">
        <thead>
            @if ($layout === 'flat')
                <tr class="headers-row">
                    <th style="width: 7%;" rowspan="2">Tebal</th>
                    <th style="width: 7%;" rowspan="2">Lebar</th>
                    <th style="width: 7%;" rowspan="2">@</th>
                    <th colspan="{{ count($columns) }}">Panjang</th>
                    <th style="width: 7%;" rowspan="2">Jumlah<br>Pcs</th>
                    <th style="width: 8%;" rowspan="2">Ton</th>
                </tr>
                <tr class="headers-row">
                    @foreach ($columns as $column)
                        <th>{{ $column['label'] ?? '' }}</th>
                    @endforeach
                </tr>
            @else
                <tr class="headers-row">
                    <th style="width: 15%;" rowspan="2">Nama Grade</th>
                    <th style="width: 6%;" rowspan="2">Tebal</th>
                    <th style="width: 6%;" rowspan="2">Lebar</th>
                    <th style="width: 5%;" rowspan="2">@</th>
                    <th colspan="{{ count($columns) }}">Panjang</th>
                    <th style="width: 7%;" rowspan="2">Jumlah<br>Pcs</th>
                    <th style="width: 8%;" rowspan="2">Ton</th>
                </tr>
                <tr class="headers-row">
                    @foreach ($columns as $column)
                        <th>{{ $column['label'] ?? '' }}</th>
                    @endforeach
                </tr>
            @endif
        </thead>
        <tbody>
            @if ($layout === 'flat')
                @forelse ($flatTebalGroups as $tebalGroup)
                    @php
                        $tebalRows = is_array($tebalGroup['rows'] ?? null) ? $tebalGroup['rows'] : [];
                        $tebalRowspan = count($tebalRows);
                    @endphp
                    @foreach ($tebalRows as $detailRow)
                        @php $lineCounter++; @endphp
                        <tr
                            class="data-row {{ $lineCounter % 2 === 1 ? 'row-odd' : 'row-even' }} {{ $loop->last ? 'before-total' : '' }}">
                            @if ($loop->first)
                                <td class="data-cell tebal-cell center" rowspan="{{ $tebalRowspan }}">
                                    {{ $formatNumber($tebalGroup['tebal'] ?? 0, 2) }}</td>
                            @endif
                            <td class="data-cell center">{{ $formatNumber($detailRow['lebar'] ?? 0, 2) }}</td>
                            <td class="data-cell center">{{ $detailRow['uom'] ?? '-' }}</td>
                            @foreach ($columns as $column)
                                @php $value = (int) (($detailRow['cells'][$column['key']] ?? 0)); @endphp
                                <td class="data-cell number">{{ $formatNumber($value) }}</td>
                            @endforeach
                            <td class="data-cell number" style="font-weight: bold;">
                                {{ $formatNumber($detailRow['total_pcs'] ?? 0) }}</td>
                            <td class="data-cell number" style="font-weight: bold;">
                                {{ $formatNumber($detailRow['total_ton'] ?? 0, 4) }}</td>
                        </tr>
                    @endforeach
                @empty
                    <tr class="data-row row-odd row-last">
                        <td class="data-cell center" colspan="{{ count($columns) + 5 }}">Tidak ada data.</td>
                    </tr>
                @endforelse

                @if ($flatTebalGroups !== [])
                    <tr class="total-row">
                        <td colspan="3" style="text-align: center;">Total</td>
                        @foreach ($columns as $column)
                            @php $value = (int) (($summary['totals'][$column['key']] ?? 0)); @endphp
                            <td class="number">{{ $formatNumber($value) }}</td>
                        @endforeach
                        <td class="number">{{ $formatNumber($summary['total_pcs'] ?? 0) }}</td>
                        <td class="number">{{ $formatNumber($summary['total_ton'] ?? 0, 4) }}</td>
                    </tr>
                @endif
            @else
                @forelse ($groups as $group)
                    @php
                        $tebalGroups = is_array($group['tebal_groups'] ?? null) ? $group['tebal_groups'] : [];
                        $gradeRowspan =
                            array_sum(array_map(static fn($item): int => count($item['rows'] ?? []), $tebalGroups)) + 1;
                        $printedGrade = false;
                    @endphp
                    @foreach ($tebalGroups as $tebalGroup)
                        @php
                            $tebalRows = is_array($tebalGroup['rows'] ?? null) ? $tebalGroup['rows'] : [];
                            $tebalRowspan = count($tebalRows);
                        @endphp
                        @foreach ($tebalRows as $detailRow)
                            @php $lineCounter++; @endphp
                            <tr
                                class="data-row {{ $lineCounter % 2 === 1 ? 'row-odd' : 'row-even' }} {{ $loop->last ? 'before-total' : '' }}">
                                @if (!$printedGrade)
                                    <td class="data-cell grade-cell text-cell" rowspan="{{ $gradeRowspan }}">
                                        {{ $group['grade'] ?? '-' }}</td>
                                    @php $printedGrade = true; @endphp
                                @endif
                                @if ($loop->first)
                                    <td class="data-cell tebal-cell center" rowspan="{{ $tebalRowspan }}">
                                        {{ $formatSize($tebalGroup['tebal'] ?? 0) }}</td>
                                @endif
                                <td class="data-cell center">{{ $formatSize($detailRow['lebar'] ?? 0) }}</td>
                                <td class="data-cell center">{{ $detailRow['uom'] ?? '-' }}</td>
                                @foreach ($columns as $column)
                                    @php $value = (int) (($detailRow['cells'][$column['key']] ?? 0)); @endphp
                                    <td class="data-cell number">{{ $value > 0 ? $formatNumber($value) : '' }}</td>
                                @endforeach
                                <td class="data-cell number">{{ $formatNumber($detailRow['total_pcs'] ?? 0) }}</td>
                                <td class="data-cell number">{{ $formatNumber($detailRow['total_ton'] ?? 0, 4) }}</td>
                            </tr>
                        @endforeach
                    @endforeach
                    <tr class="total-row">
                        <td colspan="3" style="text-align: center;">Total</td>
                        @foreach ($columns as $column)
                            @php $value = (int) (($group['totals'][$column['key']] ?? 0)); @endphp
                            <td class="number">{{ $value > 0 ? $formatNumber($value) : '' }}</td>
                        @endforeach
                        <td class="number">{{ $formatNumber($group['total_pcs'] ?? 0) }}</td>
                        <td class="number">{{ $formatNumber($group['total_ton'] ?? 0, 4) }}</td>
                    </tr>
                @empty
                    <tr class="data-row row-odd row-last">
                        <td class="data-cell center" colspan="{{ count($columns) + 6 }}">Tidak ada data.</td>
                    </tr>
                @endforelse

                @if ($groups !== [])
                    <tr class="grand-total-row">
                        <td colspan="4" style="text-align: center;">Grand Total</td>
                        @foreach ($columns as $column)
                            @php $value = (int) (($summary['totals'][$column['key']] ?? 0)); @endphp
                            <td class="number">{{ $value > 0 ? $formatNumber($value) : '' }}</td>
                        @endforeach
                        <td class="number">{{ $formatNumber($summary['total_pcs'] ?? 0) }}</td>
                        <td class="number">{{ $formatNumber($summary['total_ton'] ?? 0, 4) }}</td>
                    </tr>
                @endif
            @endif
        </tbody>
    </table>

    @if ($layout === 'flat')
        @php
            $kbTon = (float) ($summary['kb_ton'] ?? 0.0);
            $stTon = (float) ($summary['total_ton'] ?? 0.0);
            $exportStTon = (float) ($summary['export_ton'] ?? 0.0);
            $rendemenSt = $kbTon > 0 ? ($stTon / $kbTon) * 100 : 0.0;
            $rendemenExport = $kbTon > 0 ? ($exportStTon / $kbTon) * 100 : 0.0;
        @endphp
        <table class="note-layout">
            <tbody>
                <tr>
                    <td style="width: 50%; text-align: center;">
                        Rendemen ST vs KB&nbsp;&nbsp;=&nbsp;&nbsp;
                        {{ $formatNumber($stTon, 4) }} / {{ $formatNumber($kbTon, 4) }}
                        &nbsp;=&nbsp;&nbsp;{{ number_format($rendemenSt, 2, '.', '') }}%
                    </td>
                    <td style="width: 50%; text-align: center;">
                        Rendemen ST Export vs KB&nbsp;&nbsp;=&nbsp;&nbsp;
                        {{ $formatNumber($exportStTon, 4) }} / {{ $formatNumber($kbTon, 4) }}
                        &nbsp;=&nbsp;&nbsp;{{ number_format($rendemenExport, 2, '.', '') }}%
                    </td>
                </tr>
            </tbody>
        </table>
    @else
        <table class="note-layout">
            <tbody>
                <tr>
                    <td style="width: 35%;">
                        <table class="note-table">
                            <tbody>
                                <tr>
                                    <td class="note-label">RAMBUNG-SUPER (630)=</td>
                                    <td class="note-value">{{ $formatBlankable($kbSummary['super'] ?? 0, 4) }}</td>
                                </tr>
                                <tr>
                                    <td class="note-label">RAMBUNG-MC (200)=</td>
                                    <td class="note-value">{{ $formatBlankable($kbSummary['mc'] ?? 0, 4) }}</td>
                                </tr>
                                <tr>
                                    <td class="note-label">RAMBUNG-SAMSAM (0)=</td>
                                    <td class="note-value"></td>
                                </tr>
                                <tr>
                                    <td class="note-label">Jmlh KB&nbsp;=</td>
                                    <td class="note-value">{{ $formatNumber($totalKb, 2) }}</td>
                                </tr>
                                <tr>
                                    <td class="note-label">Jmlh KB /Ton ST&nbsp;=</td>
                                    <td class="note-value">{{ number_format($floorNumber($kbPerSt, 2), 2, '.', '') }}%
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </td>
                    <td style="width: 20%;"></td>
                    <td style="width: 45%; padding-top: 18px;">
                        <table class="ratio-table">
                            <tbody>
                                <tr>
                                    <td>STD / ST = {{ number_format($stdPerSt, 2, '.', '') }}%</td>
                                    <td>MC / ST = {{ $mcPerSt > 0 ? number_format($mcPerSt, 2, '.', '') . '%' : '' }}
                                    </td>
                                </tr>
                                <tr>
                                    <td>LOKAL STD / ST =</td>
                                    <td>LOKAL MC / ST =</td>
                                </tr>
                            </tbody>
                        </table>
                    </td>
                </tr>
            </tbody>
        </table>
    @endif

</body>

</html>
