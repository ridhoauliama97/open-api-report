<!DOCTYPE html>
<html lang="id">

<head>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif:ital,wght@0,100..900;1,100..900&display=swap"
        rel="stylesheet">
    <meta charset="utf-8">
    <style>
        body {
            font-family: "Noto Serif", serif;
            font-size: 10px;
            margin: 0;
            padding: 0;
        }
        .report-companyTitle {
            font-size: 18px;
            font-weight: bold;
            text-align: center;
            margin: 0 0 4px;
        }
        .report-title {
            font-size: 16px;
            font-weight: bold;
            text-align: center;
            margin: 0;
        }
        .report-subtitle {
            font-size: 12px;
            color: #636466;
            text-align: center;
            margin: 2px 0 20px;
        }
        .data-table {
            border: 1px solid #000;
            border-collapse: collapse;
            width: calc(100% - 2px);
            table-layout: fixed;
        }
        .data-table th,
        .data-table td {
            border: 1px solid #000;            padding: 1px 2px;
            vertical-align: top;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
        .data-table th {
            background-color: #eef2f8;
            font-weight: bold;
        }
        .section-header td {
            font-weight: bold;
            font-style: italic;
            color: #9c111d;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding-left: 4px;
        }
        .sub-section-header td {
            font-weight: bold;
            color: #9c111d;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding-left: 4px;
        }
        .item-row td {
            padding-left: 4px;
        }
        .row-odd td {
            background-color: #c9d1df;
        }
        .row-even td {
            background-color: #eef2f8;
        }
        .subtotal-row td {
            font-weight: bold;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
        }
        .grand-total-row td {
            font-weight: bold;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
        }
        .empty-row td {
            font-style: italic;
            font-weight: bold;
            color: #9c111d;
            background-color: #c9d1df;
        }
        .number {
            text-align: right;
        }
        .nowrap {
            white-space: nowrap;
        }
        .number-negative {
            color: #9c111d;
        }
    
        /* standardized table borders */
        .layout-table, .meta-table, .room-footer-table {
            border-collapse: collapse;
        }
.layout-table td, .meta-table td, .room-footer-table td { padding: 1px 2px; vertical-align: top; }
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

    </body>

</html>
