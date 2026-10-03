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

        table {
            width: 100%;
            border-collapse: collapse;
            border-spacing: 0;
            table-layout: fixed;
        }

        .page-block {
            page-break-after: always;
        }

        .page-block.last-page {
            page-break-after: auto;
        }

        .report-title {
            margin: 0 0 10px 0;
            text-align: center;
            font-size: 15px;
            font-weight: bold;
        }

        .meta-grid {
            margin-bottom: 4px;
        }

        .meta-grid td {
            border: 0;
            padding: 0;
            vertical-align: top;
        }

        .meta-pane-left {
            width: 50%;
            padding-right: 6px;
        }

        .meta-pane-right {
            width: 26%;
            padding-left: 6px;
        }

        .meta-table td {
            border: 0;
            padding: 2px 0;
            vertical-align: top;
            font-size: 10px;
        }

        .meta-label {
            width: 72px;
            font-weight: bold;
        }

        .meta-sep {
            width: 10px;
            text-align: center;
        }

        .section-title-grid {
            margin: 10px 0 2px 0;
        }

        .section-title-grid td {
            border: 0;
            text-align: center;
            font-size: 12px;
            font-weight: bold;
            padding: 0 0 2px 0;
        }

        .split-grid td {
            border: 0;
            padding: 0;
            vertical-align: top;
        }

        .split-grid .left-pane {
            width: 50%;
            padding-right: 8px;
        }

        .split-grid .right-pane {
            width: 50%;
            padding-left: 8px;
        }

        .section-heading {
            margin: 0 0 2px 0;
            font-size: 11px;
            font-weight: bold;
            line-height: 1.1;
        }

        .detail-table {
            border: 1px solid #000;
        }

        .detail-table th,
        .detail-table td {
            border: 1px solid #000;
            padding: 3px 4px;
            font-size: 10px;
        }

        .detail-table thead th {
            text-align: center;
            font-weight: bold;
            line-height: 1.1;
        }

        .detail-table tbody td {
            border-top: 0;
            border-bottom: 0;
            vertical-align: middle;
        }

        .detail-table tbody tr:nth-child(odd) td {
            background: #eef2f8;
        }

        .detail-table tbody tr:nth-child(even) td {
            background: #cfd8e6;
        }

        .detail-table tfoot td {
            font-weight: bold;
            font-size: 11px;
        }

        .center {
            text-align: center;
        }

        .number {
            text-align: right;
            white-space: nowrap;
            font-family: "Calibri", "DejaVu Sans", sans-serif;
        }

        .total-label {
            text-align: center;
        }

        .rendemen-line {
            margin: 6px 0 0 0;
            font-size: 11px;
        }
    </style>
</head>

<body>
    @php
        $meta = $report['meta'] ?? [];
        $inputRows = $report['input_rows'] ?? [];
        $outputRows = $report['output_rows'] ?? [];
        $repairRows = $report['repair_rows'] ?? [];
        $afkirRows = $report['afkir_rows'] ?? [];
        $totals = $report['totals'] ?? [];
        $generatedByName = $generatedBy->name ?? 'sistem';

        $fmtInt = static fn($value): string => $value === null ? '' : number_format((float) $value, 0, '.', ',');
        $fmt4 = static fn($value): string => $value === null ? '' : number_format((float) $value, 4, '.', '');
        $fmtPercent = static fn($value): string => $value === null
            ? '-'
            : number_format((float) $value, 2, '.', '') . '%';

        $emptyTotals = ['count' => 0, 'jmlh_batang' => 0, 'kubik' => 0];
        $inputTotals = $totals['input'] ?? $emptyTotals;
        $outputTotals = $totals['output'] ?? $emptyTotals;
        $repairTotals = $totals['repair'] ?? $emptyTotals;
        $afkirTotals = $totals['afkir'] ?? $emptyTotals;
        $rendemen = $totals['rendemen'] ?? null;

        $pageHeightMm = 297 - 16 - 18;
        $reservedHeightMm = 34;
        $rowHeightMm = 4.9;

        // Tabel Repair dan Afkir ditumpuk di bawah Input/Output pada halaman yang sama,
        // jadi tinggi yang tersedia dibagi dua hanya ketika tabel kedua memang berisi data.
        $tableBlocks = ($repairRows !== [] || $afkirRows !== []) ? 2 : 1;
        $rowsPerPage = max(1, (int) floor(($pageHeightMm - $reservedHeightMm) / ($rowHeightMm * $tableBlocks)));

        $inputChunks = array_values(array_chunk($inputRows, $rowsPerPage)) ?: [[]];
        $outputChunks = array_values(array_chunk($outputRows, $rowsPerPage)) ?: [[]];
        $repairChunks = array_values(array_chunk($repairRows, $rowsPerPage)) ?: [[]];
        $afkirChunks = array_values(array_chunk($afkirRows, $rowsPerPage)) ?: [[]];

        $pageCount = max(
            count($inputChunks),
            count($outputChunks),
            count($repairChunks),
            count($afkirChunks),
        );
    @endphp

    @for ($pageIndex = 0; $pageIndex < $pageCount; $pageIndex++)
        @php
            $pageInputRows = $inputChunks[$pageIndex] ?? [];
            $pageOutputRows = $outputChunks[$pageIndex] ?? [];
            $pageRepairRows = $repairChunks[$pageIndex] ?? [];
            $pageAfkirRows = $afkirChunks[$pageIndex] ?? [];
            $isLastInputPage = $pageIndex === count($inputChunks) - 1;
            $isLastOutputPage = $pageIndex === count($outputChunks) - 1;
            $isLastRepairPage = $pageIndex === count($repairChunks) - 1;
            $isLastAfkirPage = $pageIndex === count($afkirChunks) - 1;
            $isLastPage = $pageIndex === $pageCount - 1;
            $showEmptyMessage = $pageIndex === 0;
        @endphp

        <div class="page-block {{ $isLastPage ? 'last-page' : '' }}">
            <h1 class="report-title">Laporan Produksi Per Nomor Produksi Finger Joint</h1>

            <table class="meta-grid">
                <tr>
                    <td class="meta-pane-left">
                        <table class="meta-table">
                            <tr>
                                <td class="meta-label">No Produksi</td>
                                <td class="meta-sep">:</td>
                                <td>{{ $meta['no_produksi'] ?? '' }}</td>
                            </tr>
                            <tr>
                                <td class="meta-label">Tanggal</td>
                                <td class="meta-sep">:</td>
                                <td>{{ isset($meta['tanggal']) && $meta['tanggal'] instanceof \Carbon\Carbon ? $meta['tanggal']->format('d-M-y') : '' }}
                                </td>
                            </tr>
                            <tr>
                                <td class="meta-label">Mesin</td>
                                <td class="meta-sep">:</td>
                                <td>{{ $meta['nama_mesin'] ?? '' }}</td>
                            </tr>
                            <tr>
                                <td class="meta-label">Operator</td>
                                <td class="meta-sep">:</td>
                                <td>{{ $meta['operator'] ?? '' }}</td>
                            </tr>
                        </table>
                    </td>
                    <td></td>
                    <td class="meta-pane-right">
                        <table class="meta-table">
                            <tr>
                                <td class="meta-label">Shift</td>
                                <td class="meta-sep">:</td>
                                <td>{{ $meta['shift'] ?? '' }}</td>
                            </tr>
                            <tr>
                                <td class="meta-label">Jam Kerja</td>
                                <td class="meta-sep">:</td>
                                <td>{{ $fmtInt($meta['jam_kerja'] ?? null) }}</td>
                            </tr>
                            <tr>
                                <td class="meta-label">Anggota</td>
                                <td class="meta-sep">:</td>
                                <td>{{ $fmtInt($meta['anggota'] ?? null) }}</td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>

            <table class="section-title-grid">
                <tr>
                    <td>Input</td>
                    <td>Output</td>
                </tr>
            </table>

            <table class="split-grid">
                <tr>
                    <td class="left-pane">
                        <p class="section-heading">Input : {{ $meta['input_label'] ?? 'INPUT' }}</p>
                        @include('reports.partials.produksi-detail-table', [
                            'rows' => $pageInputRows,
                            'totals' => $inputTotals,
                            'emptyMessage' => 'Data input tidak tersedia.',
                            'showEmpty' => $showEmptyMessage,
                            'showTotal' => $isLastInputPage,
                            'fmtInt' => $fmtInt,
                            'fmt4' => $fmt4,
                        ])
                    </td>
                    <td class="right-pane">
                        <p class="section-heading">Output : {{ $meta['output_label'] ?? 'OUTPUT' }}</p>
                        @include('reports.partials.produksi-detail-table', [
                            'rows' => $pageOutputRows,
                            'totals' => $outputTotals,
                            'emptyMessage' => 'Data output tidak tersedia.',
                            'showEmpty' => $showEmptyMessage,
                            'showTotal' => $isLastOutputPage,
                            'fmtInt' => $fmtInt,
                            'fmt4' => $fmt4,
                        ])
                    </td>
                </tr>
            </table>

            @if ($isLastPage)
                <p class="rendemen-line">
                    Rendemen :
                    {{ $fmt4($outputTotals['kubik'] ?? 0) }}
                    /
                    {{ $fmt4($inputTotals['kubik'] ?? 0) }}
                    =
                    {{ $fmtPercent($rendemen) }}
                </p>
            @endif

            <table class="section-title-grid">
                <tr>
                    <td>Repair</td>
                    <td>Afkir</td>
                </tr>
            </table>

            <table class="split-grid">
                <tr>
                    <td class="left-pane">
                        <p class="section-heading">Repair</p>
                        @include('reports.partials.produksi-detail-table', [
                            'rows' => $pageRepairRows,
                            'totals' => $repairTotals,
                            'emptyMessage' => 'Data repair tidak tersedia.',
                            'showEmpty' => $showEmptyMessage,
                            'showTotal' => $isLastRepairPage,
                            'fmtInt' => $fmtInt,
                            'fmt4' => $fmt4,
                        ])
                    </td>
                    <td class="right-pane">
                        <p class="section-heading">Afkir</p>
                        @include('reports.partials.produksi-detail-table', [
                            'rows' => $pageAfkirRows,
                            'totals' => $afkirTotals,
                            'emptyMessage' => 'Data afkir tidak tersedia.',
                            'showEmpty' => $showEmptyMessage,
                            'showTotal' => $isLastAfkirPage,
                            'fmtInt' => $fmtInt,
                            'fmt4' => $fmt4,
                        ])
                    </td>
                </tr>
            </table>
        </div>
    @endfor

    @include('reports.partials.pdf-reference-footer')
</body>

</html>
