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
            margin: 18mm 10mm 18mm 10mm;
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
            margin: 2px 0 2px 0;
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

        @include('reports.partials.pdf-footer-table-style')
    </style>
</head>

<body>
    @php
        $meta = $report['meta'] ?? [];
        $inputRows = $report['input_rows'] ?? [];
        $outputRows = $report['output_rows'] ?? [];
        $totals = $report['totals'] ?? [];
        $generatedByName = $generatedBy->name ?? 'sistem';

        $fmtInt = static fn($value): string => $value === null ? '' : number_format((float) $value, 0, '.', ',');
        $fmt4 = static fn($value): string => $value === null ? '' : number_format((float) $value, 4, '.', '');
        $fmtPercent = static fn($value): string => $value === null
            ? '-'
            : number_format((float) $value, 2, '.', '') . '%';

        $inputTotals = $totals['input'] ?? ['count' => 0, 'jmlh_batang' => 0, 'kubik' => 0];
        $outputTotals = $totals['output'] ?? ['count' => 0, 'jmlh_batang' => 0, 'kubik' => 0];
        $rendemen = $totals['rendemen'] ?? null;

        $pageHeightMm = 297 - 16 - 18;
        $reservedHeightMm = 34;
        $rowHeightMm = 4.9;
        $rowsPerPage = max(1, (int) floor(($pageHeightMm - $reservedHeightMm) / $rowHeightMm));
        $inputChunks = array_values(array_chunk($inputRows, $rowsPerPage));
        $outputChunks = array_values(array_chunk($outputRows, $rowsPerPage));

        if ($inputChunks === []) {
            $inputChunks = [[]];
        }

        if ($outputChunks === []) {
            $outputChunks = [[]];
        }

        $pageCount = max(count($inputChunks), count($outputChunks));
    @endphp

    @for ($pageIndex = 0; $pageIndex < $pageCount; $pageIndex++)
        @php
            $pageInputRows = $inputChunks[$pageIndex] ?? [];
            $pageOutputRows = $outputChunks[$pageIndex] ?? [];
            $isLastInputPage = $pageIndex === count($inputChunks) - 1;
            $isLastOutputPage = $pageIndex === count($outputChunks) - 1;
            $isLastPage = $pageIndex === $pageCount - 1;
        @endphp

        <div class="page-block {{ $isLastPage ? 'last-page' : '' }}">
            <h1 class="report-title">Laporan Produksi Per Nomor Produksi</h1>

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
                                <td>{{ isset($meta['tanggal']) && $meta['tanggal'] instanceof \Carbon\Carbon ? $meta['tanggal']->format('d/m/Y') : '' }}
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
                        <table class="detail-table">
                            <thead>
                                <tr>
                                    <th style="width: 20%;">No Label</th>
                                    <th style="width: 12%;">Tebal (mm)</th>
                                    <th style="width: 12%;">Lebar (mm)</th>
                                    <th style="width: 18%;">Panjang (ft)</th>
                                    <th style="width: 19%;">Jmlh Batang</th>
                                    <th style="width: 19%;">Kubik</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if ($pageInputRows === [] && $pageIndex === 0)
                                    <tr>
                                        <td class="center" colspan="6">Data input tidak tersedia.</td>
                                    </tr>
                                @else
                                    @foreach ($pageInputRows as $row)
                                        <tr>
                                            <td>{{ $row['no_label'] ?? '' }}</td>
                                            <td class="center">{{ $fmtInt($row['tebal'] ?? null) }}</td>
                                            <td class="center">{{ $fmtInt($row['lebar'] ?? null) }}</td>
                                            <td class="number">{{ $fmtInt($row['panjang'] ?? null) }}</td>
                                            <td class="number">{{ $fmtInt($row['jmlh_batang'] ?? null) }}</td>
                                            <td class="number">{{ $fmt4($row['kubik'] ?? null) }}</td>
                                        </tr>
                                    @endforeach
                                @endif
                            </tbody>
                            @if ($isLastInputPage)
                                <tfoot>
                                    <tr>
                                        <td class="center">{{ $fmtInt($inputTotals['count'] ?? 0) }}</td>
                                        <td class="total-label" colspan="3">Total :</td>
                                        <td class="number">{{ $fmtInt($inputTotals['jmlh_batang'] ?? 0) }}</td>
                                        <td class="number">{{ $fmt4($inputTotals['kubik'] ?? 0) }}</td>
                                    </tr>
                                </tfoot>
                            @endif
                        </table>
                    </td>
                    <td class="right-pane">
                        <p class="section-heading">Output : {{ $meta['output_label'] ?? 'OUTPUT' }}</p>
                        <table class="detail-table">
                            <thead>
                                <tr>
                                    <th style="width: 20%;">No Label</th>
                                    <th style="width: 12%;">Tebal (mm)</th>
                                    <th style="width: 12%;">Lebar (mm)</th>
                                    <th style="width: 18%;">Panjang (ft)</th>
                                    <th style="width: 19%;">Jmlh Batang</th>
                                    <th style="width: 19%;">Kubik</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if ($pageOutputRows === [] && $pageIndex === 0)
                                    <tr>
                                        <td class="center" colspan="6">Data output tidak tersedia.</td>
                                    </tr>
                                @else
                                    @foreach ($pageOutputRows as $row)
                                        <tr>
                                            <td>{{ $row['no_label'] ?? '' }}</td>
                                            <td class="center">{{ $fmtInt($row['tebal'] ?? null) }}</td>
                                            <td class="center">{{ $fmtInt($row['lebar'] ?? null) }}</td>
                                            <td class="number">{{ $fmtInt($row['panjang'] ?? null) }}</td>
                                            <td class="number">{{ $fmtInt($row['jmlh_batang'] ?? null) }}</td>
                                            <td class="number">{{ $fmt4($row['kubik'] ?? null) }}</td>
                                        </tr>
                                    @endforeach
                                @endif
                            </tbody>
                            @if ($isLastOutputPage)
                                <tfoot>
                                    <tr>
                                        <td class="center">{{ $fmtInt($outputTotals['count'] ?? 0) }}</td>
                                        <td class="total-label" colspan="3">Total :</td>
                                        <td class="number">{{ $fmtInt($outputTotals['jmlh_batang'] ?? 0) }}</td>
                                        <td class="number">{{ $fmt4($outputTotals['kubik'] ?? 0) }}</td>
                                    </tr>
                                </tfoot>
                            @endif
                        </table>
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
        </div>
    @endfor

    @include('reports.partials.pdf-reference-footer')
</body>

</html>
