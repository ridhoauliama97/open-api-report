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
            padding-bottom: 40mm;
        }

        .report-title {
            text-align: center;
            margin: 0 0 2px 0;
            font-size: 16px;
            font-weight: bold;
        }

        .report-subtitle {
            text-align: center;
            margin: 2px 0 10px 0;
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

        .signature-fixed {
            position: fixed;
            left: 0;
            right: 0;
            bottom: 8mm;
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
        $formatPercent = static fn($value): string => number_format((float) $value, 1, '.', ',') . '%';
    @endphp

    <h1 class="report-title">Laporan Harian Hasil Broker Produksi</h1>
    <p class="report-subtitle"></p>

    <table class="summary-table">
        <tr>
            <td class="summary-label">No Produksi</td>
            <td class="summary-sep">:</td>
            <td>{{ $meta['no_produksi'] ?? '' }}</td>
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
                <th colspan="5">Hasil Broker</th>
                <th colspan="3">Downtime</th>
            </tr>
            <tr>
                <th rowspan="2">Nama Bahan</th>
                <th rowspan="2">Qty<br>(Kg)</th>
                <th rowspan="2">%</th>
                <th rowspan="2">Nama Barang</th>
                <th colspan="3">Bagus</th>
                <th rowspan="2">Reject</th>
                <th rowspan="2">Jam<br>Berhenti</th>
                <th rowspan="2">Durasi<br>(Menit)</th>
                <th rowspan="2">Keterangan</th>
            </tr>
            <tr>
                <th>Nomor<br>Label</th>
                <th>Qty<br>(Kg)</th>
                <th>Hasil<br>Cek QC</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($detailRows as $row)
                <tr class="data-row {{ $loop->odd ? 'row-odd' : 'row-even' }}">
                    <td class="data-cell">{{ $row['input_nama_bahan'] !== '' ? $row['input_nama_bahan'] : '' }}</td>
                    <td class="data-cell number">
                        {{ $row['input_qty'] !== null ? $formatNumber($row['input_qty']) : '' }}
                    </td>
                    <td class="data-cell number">
                        {{ $row['input_percent'] !== null ? $formatPercent($row['input_percent']) : '' }}
                    </td>
                    <td class="data-cell">{{ $row['output_nama_barang'] !== '' ? $row['output_nama_barang'] : '' }}</td>
                    <td class="data-cell">{{ $row['output_nomor_label'] !== '' ? $row['output_nomor_label'] : '' }}</td>
                    <td class="data-cell number">
                        {{ $row['output_qty'] !== null ? $formatNumber($row['output_qty']) : '' }}
                    </td>
                    <td class="data-cell center">
                        {{ $row['output_hasil_cek_qc'] !== '' ? $row['output_hasil_cek_qc'] : '' }}
                    </td>
                    <td class="data-cell number">
                        {{ $row['reject_qty'] !== null ? $formatNumber($row['reject_qty']) : '' }}
                    </td>
                    <td class="data-cell center">{{ $row['downtime_jam_berhenti'] }}</td>
                    <td class="data-cell center">{{ $row['downtime_durasi'] }}</td>
                    <td class="data-cell">{{ $row['downtime_keterangan'] }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td class="number">&nbsp;</td>
                <td class="number">{{ $formatNumber($totals['input_qty'] ?? 0) }}</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td class="number">{{ $formatNumber($totals['output_qty'] ?? 0) }}</td>
                <td>&nbsp;</td>
                <td class="number">{{ $formatNumber($totals['reject_qty'] ?? 0) }}</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
            </tr>
        </tfoot>
    </table>

    <div class="signature-fixed">
        <table class="signature-table">
            <thead>
                <tr>
                    <th style="width: 18%;">Di Buat Oleh,</th>
                    <th colspan="2" style="width: 50%;">Di Periksa Oleh,</th>
                    <th style="width: 18%;">Di Setujui Oleh</th>
                    <th colspan="2" rowspan="2" style="width: 14%;">Jumlah Anggota</th>
                </tr>
                <tr>
                    <th class="signature-role">Operator</th>
                    <th class="signature-role" style="width: 25%;">Ka. Regu Broker</th>
                    <th class="signature-role" style="width: 25%;">Ka. Div, Cuci & Broker</th>
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
                                    {{ $approvals['operator'] !== '' ? $approvals['operator'] : '&nbsp;' }}
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
                                    {!! ($approvals['ka_regu_broker'] ?? '') !== '' ? e($approvals['ka_regu_broker']) : '&nbsp;' !!}
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
                                    {!! ($approvals['ka_div_broker'] ?? '') !== '' ? e($approvals['ka_div_broker']) : '&nbsp;' !!}
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

    @include('reports.partials.pdf-footer-table', [
        'generatedByName' => $generatedByName,
        'generatedAt' => $generatedAt,
    ])
</body>

</html>
