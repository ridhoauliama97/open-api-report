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
            margin: 10mm 8mm 12mm 8mm;
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
            margin: 0 0 20px 0;
            text-align: center;
            font-size: 16px;
            font-weight: bold;
        }

        .meta-layout {
            width: 100%;
            margin: 0 0 8px;
            table-layout: fixed;
        }

        .meta-layout td {
            border: 0;
            padding: 0;
            vertical-align: top;
        }

        .meta-block {
            width: 100%;
            table-layout: fixed;
        }

        .meta-block td {
            border: 0;
            padding: 0 0 2px;
            font-size: 10px;
            vertical-align: top;
        }

        .meta-label {
            width: 82px;
            white-space: nowrap;
        }

        .meta-separator {
            width: 8px;
            text-align: center;
        }

        .length-caption {
            margin: 3px 0 2px 0;
            text-align: center;
            font-size: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            border-spacing: 0;
            table-layout: fixed;
        }

        .report-table {
            border: 1px solid #000;
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
        }

        th {
            text-align: center;
            font-weight: bold;
            font-size: 11px;
            background: #fff;
        }

        .headers-row th {
            border-top: 0;
            border-bottom: 1px solid #000;
        }

        .row-odd td {
            background: #c9d1df;
        }

        .row-even td {
            background: #eef2f8;
        }

        .report-table tbody tr.data-row td.data-cell {
            border-top: none !important;
            border-bottom: none !important;
            border-left: 1px solid #000 !important;
            border-right: 1px solid #000 !important;
        }

        .report-table tbody tr.row-last td.data-cell,
        .report-table tbody tr.before-total td.data-cell {
            border-bottom: 1px solid #000 !important;
        }

        .grade-cell {
            vertical-align: middle;
            text-align: center;
            font-weight: bold;
            line-height: 1.2;
        }

        .tebal-cell {
            vertical-align: top;
        }

        .number,
        .center {
            text-align: center;
            white-space: nowrap;
            font-family: "Calibri", "DejaVu Sans", sans-serif;
        }

        .text-cell {
            text-align: left;
        }

        .total-row td {
            font-weight: bold;
            font-size: 10px;
            background: #fff;
            border: 1px solid #000;
        }

        .grand-total-row td {
            font-weight: bold;
            font-size: 11px;
            background: #fff;
            border: 1px solid #000;
        }

        .note-layout {
            width: 100%;
            margin-top: 8px;
            table-layout: fixed;
        }

        .note-layout td {
            border: 0;
            padding: 0;
            font-size: 10px;
            vertical-align: top;
        }

        .note-table {
            width: 100%;
            margin-top: 10px;
            table-layout: fixed;
        }

        .note-table td {
            border: 0;
            padding: 0 0 3px;
        }

        .note-label {
            text-align: right;
            white-space: nowrap;
        }

        .note-value {
            width: 74px;
            padding-left: 8px !important;
            text-align: right;
            white-space: nowrap;
            font-family: "Calibri", "DejaVu Sans", sans-serif;
        }

        .ratio-table {
            width: 100%;
            table-layout: fixed;
        }

        .ratio-table td {
            border: 0;
            padding: 0 0 8px;
            white-space: nowrap;
        }

        @include('reports.partials.pdf-footer-table-style');
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

    @include('reports.partials.pdf-footer-table')
</body>

</html>
