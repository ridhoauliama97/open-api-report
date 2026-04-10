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
            margin: 2px 0 20px 0;
            font-size: 12px;
            color: #636466;
        }

        .section-title {
            margin: 10px 0 4px 0;
            font-size: 11px;
            font-weight: bold;
        }

        table.data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
            border: 1px solid #000;
            table-layout: fixed;
        }

        thead {
            display: table-header-group;
        }

        tfoot {
            display: table-footer-group;
        }

        tr {
            page-break-inside: avoid;
        }

        table.data-table th,
        table.data-table td {
            border: 0;
            border-left: 1px solid #000;
            padding: 2px 3px;
            vertical-align: middle;
        }

        table.data-table th:first-child,
        table.data-table td:first-child {
            border-left: 0;
        }

        table.data-table th {
            text-align: center;
            font-weight: bold;
            font-size: 10px;
            border-bottom: 1px solid #000;
            background: #fff;
        }

        /* Hilangkan garis horizontal antar baris data. */
        table.data-table tbody td {
            border-top: 0;
            border-bottom: 0;
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

        .row-odd td {
            background: #c9d1df;
        }

        .row-even td {
            background: #eef2f8;
        }

        .center {
            text-align: center;
        }

        .number {
            text-align: right;
            white-space: nowrap;
            font-family: "Calibri", "DejaVu Sans", sans-serif;
        }

        .cell-split {
            width: 100%;
            text-align: center;
        }

        .cell-left {
            text-align: left;
        }

        .cell-right {
            text-align: right;
        }

        /* mPDF: flexbox tidak selalu konsisten, gunakan float + clearfix. */
        .cell-split::after {
            content: "";
            display: block;
            clear: both;
        }

        .strong {
            background: #fff;
        }

        .strong td {
            font-weight: bold;
            font-size: 11px;
        }

        @include('reports.partials.pdf-footer-table-style')
    </style>
</head>

<body>
    @php
        $data = is_array($reportData ?? null) ? $reportData : [];
        $machines = is_array($data['machines'] ?? null) ? $data['machines'] : [];

        $generatedByName = $generatedBy?->name ?? 'sistem';
        $generatedAtText = $generatedAt->copy()->locale('id')->translatedFormat('d-M-y H:i');

        $start = \Carbon\Carbon::parse((string) ($startDate ?? ''))->locale('id')->translatedFormat('d-M-y');
        $end = \Carbon\Carbon::parse((string) ($endDate ?? ''))->locale('id')->translatedFormat('d-M-y');

        $eps = 0.0000001;
        $fmtDate = static fn(string $v): string => $v === '' ? '' : \Carbon\Carbon::parse($v)->format('d-M-y');
        $fmtVal = static fn(?float $v): string => $v === null || !is_finite($v) || abs($v) < $eps
            ? ''
            : number_format($v, 1, '.', '');
        $fmtPct = static fn(?float $v): string => $v === null || !is_finite($v) || abs($v) < $eps
            ? ''
            : number_format($v, 1, '.', '') . '%';
        $fmtTarget = static fn(?float $v): string => $v === null || !is_finite($v) || abs($v) < $eps
            ? ''
            : (abs($v - round($v)) < 0.00001
                ? (string) (int) round($v)
                : number_format($v, 1, '.', ''));
    @endphp

    <h1 class="report-title">Laporan Output Produksi S4S Per Grade</h1>
    <p class="report-subtitle">Periode {{ $start }} s/d {{ $end }}</p>

    @foreach ($machines as $machine)
        @php
            $namaMesin = (string) ($machine['nama_mesin'] ?? '');
            $jnsColumns = is_array($machine['jns_columns'] ?? null) ? $machine['jns_columns'] : [];
            $rows = is_array($machine['rows'] ?? null) ? $machine['rows'] : [];
            $summaryRows = is_array($machine['summary_rows'] ?? null) ? $machine['summary_rows'] : [];
            $targetDefault = (float) ($machine['target_default'] ?? 0.0);

            // Total columns count (for layout): Tanggal + (each grade + total) + Total-Target
            $colCount = 1; // Tanggal
            foreach ($jnsColumns as $g) {
                if (!is_array($g)) {
                    continue;
                }
                $grades = is_array($g['grades'] ?? null) ? $g['grades'] : [];
                $colCount += count($grades) + 1; // + Total per Jns
            }
            $colCount += 1; // Total - Target
        @endphp

        <div class="section-title">{{ $namaMesin }}</div>

        <table class="data-table">
            <thead>
                <tr>
                    <th rowspan="2" style="width: 70px;">Tanggal</th>
                    @foreach ($jnsColumns as $group)
                        @continue(!is_array($group))
                        @php
                            $jns = (string) ($group['jns'] ?? '');
                            $grades = is_array($group['grades'] ?? null) ? $group['grades'] : [];
                            $span = count($grades) + 1;
                        @endphp
                        <th colspan="{{ $span }}">{{ $jns }}</th>
                    @endforeach
                    <th rowspan="2" style="width: 80px;">Total - Target</th>
                </tr>
                <tr>
                    @foreach ($jnsColumns as $group)
                        @continue(!is_array($group))
                        @php
                            $grades = is_array($group['grades'] ?? null) ? $group['grades'] : [];
                        @endphp
                        @foreach ($grades as $grade)
                            <th>{{ $grade }}</th>
                        @endforeach
                        <th>Total</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @php $rowIndex = 0; @endphp
                @foreach ($rows as $r)
                    @php
                        $rowIndex++;
                        $cls = $rowIndex % 2 === 1 ? 'row-odd' : 'row-even';
                        $cells = is_array($r['cells'] ?? null) ? $r['cells'] : [];
                    @endphp
                    <tr class="{{ $cls }}">
                        <td class="center">{{ $fmtDate((string) ($r['date'] ?? '')) }}</td>
                        @foreach ($jnsColumns as $group)
                            @continue(!is_array($group))
                            @php
                                $jns = (string) ($group['jns'] ?? '');
                                $grades = is_array($group['grades'] ?? null) ? $group['grades'] : [];
                                $jnsMap = $cells[$jns] ?? null;
                                $jnsMap = is_array($jnsMap) ? $jnsMap : [];
                            @endphp
                            @foreach ($grades as $grade)
                                @php
                                    $cell = $jnsMap[$grade] ?? null;
                                    $v = is_array($cell) ? (float) ($cell['value'] ?? 0.0) : 0.0;
                                    $p = is_array($cell) ? (float) ($cell['percent'] ?? 0.0) : 0.0;
                                @endphp
                                <td>
                                    <div class="cell-split">
                                        <span class="cell-left">{{ $fmtVal($v) }} &nbsp;&nbsp;&nbsp;</span>
                                        <span class="cell-right">{{ $fmtPct($p) }}</span>
                                    </div>
                                </td>
                            @endforeach
                            @php
                                $totalCell = $jnsMap['__TOTAL__'] ?? null;
                                $tv = is_array($totalCell) ? (float) ($totalCell['value'] ?? 0.0) : 0.0;
                                $tp = is_array($totalCell) ? (float) ($totalCell['percent'] ?? 100.0) : 100.0;
                            @endphp
                            <td class="number" style="font-weight: bold;">
                                <div class="cell-split">
                                    <span class="cell-left">{{ $fmtVal($tv) }} &nbsp;&nbsp;&nbsp;</span>
                                    <span class="cell-right">{{ $fmtPct($tp) }}</span>
                                </div>
                            </td>
                        @endforeach
                        @php $gt = (float) ($r['grand_total'] ?? 0.0); @endphp
                        <td class="number" style="font-weight: bold;">
                            <div class="cell-split">
                                <span class="cell-left">{{ $fmtVal($gt) }} &nbsp;&nbsp;&nbsp;</span>
                                <span
                                    class="cell-right">{{ abs($gt) < $eps ? '' : $fmtTarget((float) ($r['target'] ?? 0.0)) }}</span>
                            </div>
                        </td>
                    </tr>
                @endforeach

                @if ($rows !== [] && $summaryRows !== [])
                    <tr class="table-end-line">
                        <td colspan="{{ $colCount }}"></td>
                    </tr>
                @endif

                @foreach ($summaryRows as $srIndex => $sr)
                    @php
                        $rowIndex++;
                        $cls = $rowIndex % 2 === 1 ? 'row-odd' : 'row-even';
                        $label = (string) ($sr['label'] ?? '');
                        $r = is_array($sr['row'] ?? null) ? $sr['row'] : [];
                        $cells = is_array($r['cells'] ?? null) ? $r['cells'] : [];
                    @endphp
                    @if ($srIndex > 0)
                        <tr class="table-end-line">
                            <td colspan="{{ $colCount }}"></td>
                        </tr>
                    @endif
                    <tr class="{{ $cls }} strong">
                        <td class="center">{{ $label }}</td>
                        @foreach ($jnsColumns as $group)
                            @continue(!is_array($group))
                            @php
                                $jns = (string) ($group['jns'] ?? '');
                                $grades = is_array($group['grades'] ?? null) ? $group['grades'] : [];
                                $jnsMap = $cells[$jns] ?? null;
                                $jnsMap = is_array($jnsMap) ? $jnsMap : [];
                            @endphp
                            @foreach ($grades as $grade)
                                @php
                                    $cell = $jnsMap[$grade] ?? null;
                                    $v = is_array($cell) ? (float) ($cell['value'] ?? 0.0) : 0.0;
                                    $p = is_array($cell) ? (float) ($cell['percent'] ?? 0.0) : 0.0;
                                @endphp
                                <td class="number">
                                    <div class="cell-split">
                                        <span class="cell-left">{{ $fmtVal($v) }}</span>
                                        <span class="cell-right">{{ $label === 'Total' ? $fmtPct($p) : '' }}</span>
                                    </div>
                                </td>
                            @endforeach
                            @php
                                $totalCell = $jnsMap['__TOTAL__'] ?? null;
                                $tv = is_array($totalCell) ? (float) ($totalCell['value'] ?? 0.0) : 0.0;
                                $tp = is_array($totalCell) ? (float) ($totalCell['percent'] ?? 100.0) : 100.0;
                            @endphp
                            <td class="number">
                                <div class="cell-split">
                                    <span class="cell-left">{{ $fmtVal($tv) }}</span>
                                    <span class="cell-right">{{ $label === 'Total' ? $fmtPct($tp) : '' }}</span>
                                </div>
                            </td>
                        @endforeach
                        @php $gt = (float) ($r['grand_total'] ?? 0.0); @endphp
                        <td class="number">
                            <div class="cell-split">
                                <span class="cell-left">{{ $fmtVal($gt) }}</span>
                                <span class="cell-right">{{ abs($gt) < $eps ? '' : $fmtTarget($targetDefault) }}</span>
                            </div>
                        </td>
                    </tr>
                @endforeach

                @if ($rows === [] && $summaryRows === [])
                    <tr>
                        <td colspan="{{ $colCount }}" class="center">Tidak ada data.</td>
                    </tr>
                @endif
            </tbody>
        </table>
    @endforeach

    @include('reports.partials.pdf-footer-table')
</body>

</html>
