<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <style>
        * {
            box-sizing: border-box;
        }

        @page {
            margin: 18mm 8mm 18mm 8mm;
            footer: html_reportFooter;
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
            margin: 0 0 2px 0;
            font-size: 16px;
            font-weight: bold;
        }

        .report-subtitle {
            text-align: center;
            margin: 2px 0 20px 0;
            font-size: 12px;
            color: #444;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            border-spacing: 0;
            table-layout: fixed;
        }

        .summary-table {
            margin-bottom: 8px;
        }

        .summary-table td {
            border: 0;
            padding: 1px 2px;
            vertical-align: top;
        }

        .summary-label {
            width: 88px;
            font-weight: bold;
        }

        .summary-sep {
            width: 8px;
            text-align: center;
        }

        .report-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #000;
            margin-bottom: 8px;
            page-break-inside: auto;
        }

        .report-table thead {
            display: table-header-group;
        }

        .report-table tfoot {
            display: table-row-group;
        }

        .report-table tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }

        .report-table th,
        .report-table td {
            border: 0;
            border-left: 1px solid #000;
            padding: 2px 4px;
            vertical-align: top;
        }

        .report-table th:first-child,
        .report-table td:first-child {
            border-left: 0;
        }

        .report-table th {
            text-align: center;
            vertical-align: middle;
            font-weight: bold;
            border-bottom: 1px solid #000;
            padding-top: 4px;
            padding-bottom: 4px;
            line-height: 1.15;
        }

        .report-table tbody td {
            border-top: 0;
            border-bottom: 0;
        }

        .report-table tbody tr.data-row.row-odd td.data-cell {
            background: #c9d1df;
        }

        .report-table tbody tr.data-row.row-even td.data-cell {
            background: #eef2f8;
        }

        .report-table tbody tr.filler-row td.data-cell {
            height: 32px;
        }

        .report-table tfoot tr.total-row td {
            border-top: 1px solid #000;
            font-weight: bold;
            background: #fff;
        }

        .center {
            text-align: center;
        }

        .number {
            text-align: right;
            white-space: nowrap;
            font-family: "Calibri", "DejaVu Sans", sans-serif;
        }

        .signature-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #000;
        }

        .body-with-fixed-signature {
            padding-bottom: 40mm;
        }

        .signature-page {
            page-break-before: always;
            min-height: 240mm;
            position: relative;
        }

        .signature-fixed {
            position: fixed;
            left: 0;
            right: 0;
            bottom: 8mm;
        }

        .signature-fixed-page-break {
            position: absolute;
            left: 0;
            right: 0;
            bottom: 0;
        }

        .signature-table th,
        .signature-table td {
            border: 1px solid #000;
            padding: 3px 4px;
            text-align: center;
            vertical-align: middle;
        }

        .signature-role {
            font-weight: bold;
        }

        .signature-span-cell {
            padding: 0 !important;
            vertical-align: top !important;
        }

        .signature-inner {
            width: 100%;
            border-collapse: collapse;
            border: 0;
            table-layout: fixed;
        }

        .signature-inner td {
            border: 0 !important;
            text-align: center;
            vertical-align: bottom;
            padding: 0 4px !important;
        }

        .signature-inner-space {
            height: 86px;
            line-height: 86px;
            font-size: 1px;
        }

        .signature-inner-name {
            height: 22px;
            padding-bottom: 2px !important;
        }

        .signature-panel-label {
            text-align: center;
        }

        .signature-panel-value {
            text-align: right;
            padding-right: 6px;
            font-family: "Calibri", "DejaVu Sans", sans-serif;
        }

        @include('reports.partials.pdf-footer-table-style')
    </style>
</head>

<body>
    @php
        $meta = $report['meta'] ?? [];
        $detailRows = $report['detail_rows'] ?? [];
        $blankRowCount = (int) ($report['blank_row_count'] ?? 0);
        $totals = $report['totals'] ?? [];
        $approvals = $report['approvals'] ?? [];
        $attendance = $report['attendance'] ?? [];
        $generatedByName = $generatedBy->name ?? 'sistem';
        $formatNumber = static fn($value, int $decimals = 2): string => number_format(
            (float) $value,
            $decimals,
            '.',
            ',',
        );
        $textOrBlank = static fn($value): string => trim((string) $value) !== '' ? e(trim((string) $value)) : '&nbsp;';
        $totalInputQty = (float) ($totals['input_qty'] ?? 0);
        $shouldMoveSignatureToNewPage = count($detailRows) > 25;
    @endphp

    @if (!$shouldMoveSignatureToNewPage)
        <div class="body-with-fixed-signature">
    @endif

    <h1 class="report-title">Laporan Harian Hasil Packing Produksi</h1>
    <p class="report-subtitle"></p>

    <table class="summary-table">
        <tr>
            <td class="summary-label">No Packing</td>
            <td class="summary-sep">:</td>
            <td>{{ $meta['no_packing'] ?? '' }}</td>
        </tr>
        <tr>
            <td class="summary-label">Tanggal</td>
            <td class="summary-sep">:</td>
            <td>{{ isset($meta['tanggal']) && $meta['tanggal'] instanceof \Carbon\Carbon ? $meta['tanggal']->format('d-M-y') : '' }}
            </td>
        </tr>
        <tr>
            <td class="summary-label">Nama Mesin</td>
            <td class="summary-sep">:</td>
            <td>{{ $meta['nama_mesin'] ?? '' }}</td>
        </tr>
        <tr>
            <td class="summary-label">Shift</td>
            <td class="summary-sep">:</td>
            <td>{{ $meta['shift'] ?? '' }}</td>
        </tr>
    </table>

    <table class="report-table">
        <thead>
            <tr>
                <th colspan="3">Pemakaian Bahan</th>
                <th colspan="4">Hasil Packing</th>
                <th colspan="3">Downtime</th>
            </tr>
            <tr>
                <th rowspan="2">Nama Bahan</th>
                <th rowspan="2">Qty<br>()</th>
                <th rowspan="2">%</th>
                <th rowspan="2">Nama Barang</th>
                <th colspan="3">Bagus</th>
                <th rowspan="2">Jam<br>Berhenti</th>
                <th rowspan="2">Durasi<br>(Menit)</th>
                <th rowspan="2">Keterangan</th>
            </tr>
            <tr>
                <th>Jumlah<br>Label</th>
                <th>Qty<br>(Pcs)</th>
                <th>Berat<br>(Kg)</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($detailRows as $row)
                @php
                    $inputQty = $row['input_qty'] ?? null;
                    $inputPercentage =
                        $inputQty !== null && $totalInputQty > 0
                            ? $formatNumber((((float) $inputQty) / $totalInputQty) * 100, 2) . '%'
                            : '';
                @endphp
                <tr class="data-row {{ $loop->odd ? 'row-odd' : 'row-even' }}">
                    <td class="data-cell">{!! $textOrBlank($row['input_nama_barang'] ?? '') !!}</td>
                    <td class="data-cell number">
                        {{ $row['input_qty'] !== null ? $formatNumber($row['input_qty'], 2) : '' }}
                    </td>
                    <td class="data-cell number">{{ $inputPercentage }}</td>
                    <td class="data-cell">{!! $textOrBlank($row['output_nama_barang'] ?? '') !!}</td>
                    <td class="data-cell number">{!! $textOrBlank($row['output_nomor_label'] ?? '') !!}</td>
                    <td class="data-cell number">
                        {{ $row['output_qty'] !== null ? $formatNumber($row['output_qty'], 2) : '' }}
                    </td>
                    <td class="data-cell number">
                        {{ $row['output_berat'] !== null ? $formatNumber($row['output_berat']) : '' }}
                    </td>
                    <td class="data-cell">&nbsp;</td>
                    <td class="data-cell">&nbsp;</td>
                    <td class="data-cell">&nbsp;</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td>&nbsp;</td>
                <td class="number">{{ $formatNumber($totals['input_qty'] ?? 0, 2) }}</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td class="number">{{ $formatNumber($totals['output_qty'] ?? 0, 2) }}</td>
                <td class="number">{{ $formatNumber($totals['output_berat'] ?? 0) }}</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
            </tr>
        </tfoot>
    </table>

    @if (!$shouldMoveSignatureToNewPage)
        </div>
        <div class="signature-fixed">
        @else
            <div class="signature-page">
                <div class="signature-fixed-page-break">
    @endif
    <table class="signature-table">
        <thead>
            <tr>
                <th>Di Buat Oleh,</th>
                <th colspan="2">Di Periksa Oleh,</th>
                <th>Di Setujui Oleh</th>
                <th colspan="2" rowspan="2">Jumlah Anggota</th>
            </tr>
            <tr>
                <th class="signature-role">Operator</th>
                <th class="signature-role">Ka. Regu Packing</th>
                <th class="signature-role">Ka. Div, Produksi Inject</th>
                <th class="signature-role">Ka. Dept, Produksi</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="signature-span-cell" rowspan="3">
                    <table class="signature-inner">
                        <tr>
                            <td class="signature-inner-space">&nbsp;</td>
                        </tr>
                        <tr>
                            <td class="signature-inner-name">
                                {!! ($approvals['operator'] ?? '') !== '' ? e($approvals['operator']) : '&nbsp;' !!}
                            </td>
                        </tr>
                    </table>
                </td>
                <td class="signature-span-cell" rowspan="3">
                    <table class="signature-inner">
                        <tr>
                            <td class="signature-inner-space">&nbsp;</td>
                        </tr>
                        <tr>
                            <td class="signature-inner-name">
                                {!! ($approvals['ka_regu_packing'] ?? '') !== '' ? e($approvals['ka_regu_packing']) : '&nbsp;' !!}
                            </td>
                        </tr>
                    </table>
                </td>
                <td class="signature-span-cell" rowspan="3">
                    <table class="signature-inner">
                        <tr>
                            <td class="signature-inner-space">&nbsp;</td>
                        </tr>
                        <tr>
                            <td class="signature-inner-name">
                                {!! ($approvals['ka_div_packing'] ?? '') !== '' ? e($approvals['ka_div_packing']) : '&nbsp;' !!}
                            </td>
                        </tr>
                    </table>
                </td>
                <td class="signature-span-cell" rowspan="3">
                    <table class="signature-inner">
                        <tr>
                            <td class="signature-inner-space">&nbsp;</td>
                        </tr>
                        <tr>
                            <td class="signature-inner-name">
                                {!! ($approvals['ka_dept_produksi'] ?? '') !== '' ? e($approvals['ka_dept_produksi']) : '&nbsp;' !!}
                            </td>
                        </tr>
                    </table>
                </td>
                <td class="signature-panel-label">Hadir</td>
                <td class="signature-panel-value">{{ (int) ($attendance['hadir'] ?? 0) }}</td>
            </tr>
            <tr>
                <td class="signature-panel-label">Absen</td>
                <td class="signature-panel-value">{{ (int) ($attendance['absen'] ?? 0) }}</td>
            </tr>
            <tr>
                <td class="signature-panel-label"><strong>Total</strong></td>
                <td class="signature-panel-value"><strong>{{ (int) ($attendance['total'] ?? 0) }}</strong></td>
            </tr>
        </tbody>
    </table>
    </div>
    @if ($shouldMoveSignatureToNewPage)
        </div>
    @endif

    @include('reports.partials.pdf-footer-table', [
        'generatedByName' => $generatedByName,
        'generatedAt' => $generatedAt,
    ])
</body>

</html>
