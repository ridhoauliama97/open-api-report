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

        .room-title {
            margin: 12px 0 6px 0;
            font-size: 12px;
            font-weight: bold;
        }

        .section-title {
            margin: 8px 0 4px 0;
            font-size: 11px;
            font-weight: bold;
        }

        .layout-table {
            width: 100%;
            border-collapse: collapse;
            border: 0;
            table-layout: fixed;
        }

        .layout-table td {
            border: 0;
            padding: 0;
            vertical-align: top;
        }

        .left-col {
            width: 33%;
            padding-right: 8px;
        }

        .right-col {
            width: 67%;
        }

        table.data-table {
            width: 100%;
            border-collapse: collapse;
            border-spacing: 0;
            page-break-inside: auto;
            border: 1px solid #000;
            table-layout: fixed;
        }

        thead {
            display: table-header-group;
        }

        tr {
            page-break-inside: avoid;
            page-break-after: auto;
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
            font-size: 11px;
            border-bottom: 1px solid #000;
            background: #fff;
        }

        /* Hilangkan garis horizontal antar baris data. */
        table.data-table tbody td {
            border-top: 0;
            border-bottom: 0;
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

        .meta-lines {
            margin-top: 4px;
            font-size: 10px;
        }

        .meta-table {
            width: 100%;
            border-collapse: collapse;
            border: 0;
        }

        .meta-table td {
            border: 0;
            padding: 1px 0;
            vertical-align: baseline;
        }

        .meta-table .label {
            width: 120px;
            font-weight: bold;
            white-space: nowrap;
        }

        .meta-table .sep {
            width: 10px;
            text-align: center;
        }

        .meta-table .value {
            text-align: right;
            font-family: "Calibri", "DejaVu Sans", sans-serif;
            white-space: nowrap;
        }

        .room-footer {
            margin-top: 8px;
            border-top: 1px solid #000;
            padding-top: 6px;
        }

        .room-footer-table {
            width: 100%;
            border-collapse: collapse;
            border: 0;
        }

        .room-footer-table td {
            border: 0;
            padding: 1px 0;
            vertical-align: baseline;
        }

        .room-footer-table .label {
            font-weight: bold;
            white-space: nowrap;
        }

        .room-footer-table .sep {
            width: 10px;
            text-align: center;
        }

        .room-footer-table .value {
            text-align: right;
            font-family: "Calibri", "DejaVu Sans", sans-serif;
            white-space: nowrap;
        }

        .room-footer .jenis-line {
            margin-top: 6px;
            text-align: center;
            font-weight: bold;
        }

        .page-break {
            page-break-after: always;
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
        $rooms = is_array($data['rooms'] ?? null) ? $data['rooms'] : [];

        $generatedByName = $generatedBy?->name ?? 'sistem';
        $generatedAtText = $generatedAt->copy()->locale('id')->translatedFormat('d-M-y H:i');
        $start = \Carbon\Carbon::parse((string) ($startDate ?? ''))->locale('id')->translatedFormat('d-M-y');
        $end = \Carbon\Carbon::parse((string) ($endDate ?? ''))->locale('id')->translatedFormat('d-M-y');

        $fmtTon = static function ($v): string {
            $n = (float) ($v ?? 0.0);
            if (abs($n) < 0.0000001) {
                return '';
            }
            return number_format($n, 4, '.', '');
        };

        $fmtPercent = static function ($v): string {
            $n = (float) ($v ?? 0.0);
            if (abs($n) < 0.0000001) {
                return '';
            }
            return number_format($n, 2, '.', '');
        };

        $fmtInt = static function ($v): string {
            $n = (int) ($v ?? 0);
            return $n <= 0 ? '' : (string) $n;
        };

        $fmtDim = static function ($v): string {
            $n = (float) ($v ?? 0.0);
            if (abs($n) < 0.0000001) {
                return '';
            }
            // Tebal/Lebar display without trailing .0 when possible.
            $isInt = abs($n - round($n)) < 0.0000001;
            return $isInt ? (string) ((int) round($n)) : number_format($n, 1, '.', '');
        };
    @endphp

    <h1 class="report-title">Laporan Rekap Kamar KD</h1>
    <p class="report-subtitle">Periode {{ $start }} s/d {{ $end }}</p>

    @forelse ($rooms as $roomIndex => $room)
        @php
            $noRuang = (int) ($room['no_ruang_kd'] ?? 0);
            $hari = (int) ($room['hari'] ?? 0);
            $jenisGroups = is_array($room['jenis_groups'] ?? null) ? $room['jenis_groups'] : [];
            $totals = is_array($room['totals'] ?? null) ? $room['totals'] : [];
            $jenisConcat = (string) ($room['jenis_concat'] ?? '');
        @endphp

        <div class="room-title">KD {{ $noRuang }}</div>

        @foreach ($jenisGroups as $g)
            @php
                $label = (string) ($g['label'] ?? '');
                $jenis = (string) ($g['jenis'] ?? '');
                $summaryRows = is_array($g['summary_rows'] ?? null) ? $g['summary_rows'] : [];
                $detailRows = is_array($g['detail_rows'] ?? null) ? $g['detail_rows'] : [];
                $gTotals = is_array($g['totals'] ?? null) ? $g['totals'] : [];
            @endphp

            <div class="section-title">{{ $label }} {{ $jenis }}</div>
            <table class="layout-table">
                <tr>
                    <td class="left-col">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th style="width: 34%;">Tebal</th>
                                    <th style="width: 33%;">Ton</th>
                                    <th style="width: 33%;">m3</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $i = 0; @endphp
                                @forelse ($summaryRows as $sr)
                                    @php $i++; @endphp
                                    <tr class="{{ $i % 2 === 1 ? 'row-odd' : 'row-even' }}">
                                        <td class="center">{{ $fmtDim($sr['Tebal'] ?? 0) }}</td>
                                        <td class="number">{{ $fmtTon($sr['Ton'] ?? 0) }}</td>
                                        <td class="number">{{ $fmtTon($sr['m3'] ?? 0) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="center">Tidak ada data.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            <tfoot>
                                <tr class="table-end-line">
                                    <td colspan="3"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </td>
                    <td class="right-col">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th style="width: 12%;">Tebal</th>
                                    <th style="width: 12%;">Lebar</th>
                                    <th style="width: 20%;">Ton</th>
                                    <th style="width: 18%;">Ave (Tebal)</th>
                                    <th style="width: 18%;">Ave (Panjang)</th>
                                    <th style="width: 20%;">% Capacity</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $j = 0; @endphp
                                @forelse ($detailRows as $dr)
                                    @php $j++; @endphp
                                    <tr class="{{ $j % 2 === 1 ? 'row-odd' : 'row-even' }}">
                                        <td class="center">{{ $fmtDim($dr['Tebal'] ?? 0) }}</td>
                                        <td class="center">{{ $fmtDim($dr['Lebar'] ?? 0) }}</td>
                                        <td class="number">{{ $fmtTon($dr['Ton'] ?? 0) }}</td>
                                        <td class="center">{{ $fmtDim($dr['AveTebal'] ?? 0) }}</td>
                                        <td class="center">{{ $fmtDim($dr['AvePanjang'] ?? 0) }}</td>
                                        <td class="number">{{ $fmtPercent($dr['pct_capacity'] ?? 0) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="center">Tidak ada data.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            <tfoot>
                                <tr class="table-end-line">
                                    <td colspan="6"></td>
                                </tr>
                            </tfoot>
                        </table>

                        <div class="meta-lines">
                            <table class="meta-table">
                                <tr>
                                    <td class="label">Jumlah (Ton)</td>
                                    <td class="sep">:</td>
                                    <td class="value">{{ $fmtTon($gTotals['ton'] ?? 0) }}</td>
                                </tr>
                                <tr>
                                    <td class="label">Jumlah (% Capacity)</td>
                                    <td class="sep">:</td>
                                    <td class="value">{{ $fmtPercent($gTotals['pct_capacity'] ?? 0) }}</td>
                                </tr>
                            </table>
                        </div>
                    </td>
                </tr>
            </table>
        @endforeach

        <div class="room-footer">
            <table class="room-footer-table">
                <tr>
                    <td style="width: 33%;">
                        <span class="label">Jumlah Hari</span><span class="sep">:</span>
                        <span class="value">{{ $fmtInt($hari) }}</span>
                    </td>
                    <td style="width: 34%;">
                        <span class="label">Jumlah (Ton)</span><span class="sep">:</span>
                        <span class="value">{{ $fmtTon($totals['jumlah_ton'] ?? 0) }}</span>
                    </td>
                    <td style="width: 33%;">
                        <span class="label">Jumlah (% Capacity)</span><span class="sep">:</span>
                        <span class="value">{{ $fmtPercent($totals['jumlah_pct_capacity'] ?? 0) }}</span>
                    </td>
                </tr>
                <tr>
                    <td style="width: 33%;">
                        <span class="label">Ave Hari</span><span class="sep">:</span>
                        <span class="value">{{ $fmtInt($hari) }}</span>
                    </td>
                    <td style="width: 34%;">
                        <span class="label">Total KD {{ $noRuang }}</span><span class="sep">:</span>
                        <span class="value">{{ $fmtTon($totals['jumlah_ton'] ?? 0) }}</span>
                    </td>
                    <td style="width: 33%;">
                        <span class="label">Ave Capacity KD {{ $noRuang }}</span><span class="sep">:</span>
                        <span class="value">{{ $fmtPercent($totals['ave_pct_capacity'] ?? 0) }}</span>
                    </td>
                </tr>
            </table>

            @if ($jenisConcat !== '')
                <div class="jenis-line">{{ $jenisConcat }}</div>
            @endif
        </div>

        @if ($roomIndex < count($rooms) - 1)
            <div class="page-break"></div>
        @endif
    @empty
        <div class="center">Tidak ada data.</div>
    @endforelse

    @include('reports.partials.pdf-footer-table')
</body>

</html>
