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

        .group-title {
            margin: 8px 0 4px 0;
            font-size: 12px;
            font-weight: bold;
        }

        .date-separator {
            border-top: 1px solid #000;
            margin: 10px 0 8px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
            page-break-inside: auto;
            table-layout: fixed;
        }

        .report-table {
            border-collapse: separate;
            border-spacing: 0;
            border: 1px solid #000;
        }

        thead {
            display: table-header-group;
        }

        tfoot {
            display: table-footer-group;
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
            text-align: center;
            word-break: break-word;
        }

        th {
            text-align: center;
            font-weight: bold;
            color: #000;
            font-size: 11px;
        }

        td.left {
            text-align: left;
        }

        td.right {
            text-align: right;
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

        .headers-row th {
            font-weight: bold;
            font-size: 11px;
            border-top: 0;
            border-bottom: 1px solid #000;
        }

        .report-table tbody tr.data-row td.data-cell {
            border-top: 0 !important;
            border-bottom: 0 !important;
            border-left: 1px solid #000 !important;
            border-right: 1px solid #000 !important;
        }

        .totals-row td {
            font-weight: bold;
            font-size: 11px;
            border: 1px solid #000;
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

        .footer-wrap {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }

        .footer-left,
        .footer-right {
            font-size: 8px;
            font-style: italic;
        }

        .footer-right {
            text-align: right;
        }

        .bottom-section {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 16px;
            margin: 0 0 10px 0;
        }

        .money-box {
            width: 50%;
            font-size: 11px;
        }

        .money-list {
            list-style: none;
            margin: 0;
            padding: 0;
            width: 100%;
            border: 1px solid #000;
        }

        .money-list>li {
            padding: 3px 6px;
        }

        .money-list>li+li {
            border-top: 1px solid #000;
        }

        .money-list>li.money-divider {
            border-top: 2px solid #000;
        }

        .money-item {
            display: flex;
            align-items: flex-start;
        }

        .money-label {
            width: 80px;
            font-weight: bold;
            text-align: left;
        }

        .money-value {
            flex: 1 1 auto;
            text-align: right;
            white-space: nowrap;
            font-family: "Calibri", "DejaVu Sans", sans-serif;
        }

        .money-flag-inline {
            font-weight: bold;
        }

        .btul-box {
            width: 50%;
            font-size: 11px;
        }

        .btul-title {
            font-weight: bold;
            text-align: left;
            margin: 0 0 4px 0;
        }

        .mini-table {
            border-collapse: collapse;
            width: 100%;
            table-layout: fixed;
        }

        .mini-table th,
        .mini-table td {
            border: 1px solid #000;
            padding: 2px 4px;
            font-size: 10px;
        }

        .mini-table th {
            text-align: center;
            font-weight: bold;
        }

        .mini-table td.label {
            text-align: left;
        }

        .mini-table td.num {
            text-align: right;
            white-space: nowrap;
            font-family: "Calibri", "DejaVu Sans", sans-serif;
        }
    </style>
</head>

<body>
    @php
        $data = is_array($reportData ?? null) ? $reportData : [];
        $dateGroups = is_array($data['date_groups'] ?? null) ? $data['date_groups'] : [];
        $generatedByName = $generatedBy?->name ?? 'sistem';
        $generatedAtText = $generatedAt->copy()->locale('id')->translatedFormat('d-M-y H:i');
        $start = \Carbon\Carbon::parse((string) $startDate)->locale('id')->translatedFormat('d-M-y');
        $end = \Carbon\Carbon::parse((string) $endDate)->locale('id')->translatedFormat('d-M-y');

        // For detail rows: hide zeros / missing values as blank.
        $fmtDetail = static fn(float $value, int $decimals = 2): string => abs($value) < 0.0000001
            ? ''
            : number_format($value, $decimals, '.', ',');
        $fmtPercentDetail = static fn(float $value, int $decimals = 1): string => abs($value) < 0.0000001
            ? ''
            : number_format($value, $decimals, '.', ',') . '%';

        // For totals/footer rows: keep values visible even if zero.
        $fmtTotal = static fn(float $value, int $decimals = 2): string => number_format($value, $decimals, '.', ',');
        $fmtPercentTotal = static fn(float $value, int $decimals = 1): string => number_format(
            $value,
            $decimals,
            '.',
            ',',
        ) . '%';

        // Currency-like totals (Rp): always show values.
        $fmtMoney = static fn(float $value): string => number_format($value, 2, '.', ',');

        // N/A cell placeholder (user wants blank).
        $dash = '';

        $fmtTruck = static function (mixed $value): string {
            $raw = trim((string) ($value ?? ''));
            if ($raw === '' || $raw === '0' || $raw === '0.0') {
                return '';
            }
            return $raw;
        };

        $formatDateLong = static function (?string $value): string {
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
    @endphp

    <h1 class="report-title">Laporan Rekap Produktivitas Sawmill (Rambung)</h1>
    <p class="report-subtitle">Periode {{ $start }} s/d {{ $end }}</p>

    @forelse ($dateGroups as $group)
        @if (!$loop->first)
            <div class="date-separator"></div>
        @endif
        @php
            $dateLabel = (string) ($group['date_label'] ?? ($group['date_key'] ?? ''));
            $receipts = is_array($group['receipts'] ?? null) ? $group['receipts'] : [];
        @endphp

        <div class="group-title">Tgl Penerimaan ST :
            {{ $dateLabel }}</div>

        @foreach ($receipts as $receipt)
            @php
                $meta = is_array($receipt['meta'] ?? null) ? $receipt['meta'] : [];
                $noPen = trim((string) ($meta['no_pen_st'] ?? ''));
                $noKb = trim((string) ($meta['no_kayu_bulat'] ?? ''));
                $dateCreate = trim((string) ($meta['date_create'] ?? ''));
                $tglPenerimaan = trim((string) ($meta['tgl_penerimaan_st'] ?? ''));
                $meja = trim((string) ($meta['meja'] ?? ''));
                $supplier = trim((string) ($meta['supplier'] ?? ''));
                $noTruk = trim((string) ($meta['no_truk'] ?? ''));
                $jenisKayu = trim((string) ($meta['jenis_kayu'] ?? ''));

                $rowsByKategori = is_array($receipt['rows'] ?? null)
                    ? $receipt['rows']
                    : ['input' => [], 'output' => []];
                $inputRows = is_array($rowsByKategori['input'] ?? null) ? $rowsByKategori['input'] : [];
                $outputRows = is_array($rowsByKategori['output'] ?? null) ? $rowsByKategori['output'] : [];

                $totals = is_array($receipt['totals'] ?? null) ? $receipt['totals'] : [];
                $kbTotal = (float) ($totals['kb_total'] ?? 0.0);
                $stTotal = (float) ($totals['st_total'] ?? 0.0);
                $rendemen = (float) ($totals['rendemen'] ?? 0.0);

                $money = is_array($receipt['money'] ?? null) ? $receipt['money'] : [];
                $moneySt = (float) ($money['st'] ?? 0.0);
                $moneyKb = (float) ($money['kb'] ?? 0.0);
                $moneyUpah = (float) ($money['upah'] ?? 0.0);
                $moneyHasil = (float) ($money['hasil'] ?? 0.0);
                $moneyFlag = $moneyHasil < 0 ? 'RUGI' : 'LABA';

                $balokRows = is_array($receipt['balok_timbang_ulang'] ?? null) ? $receipt['balok_timbang_ulang'] : [];
            @endphp

            @if ($noPen !== '' || $noKb !== '')
                <div style="margin: 2px 0 2px 0;">
                    <strong>No Penerimaan ST</strong> : {{ $noPen !== '' ? $noPen : '-' }}
                </div>
            @endif

            <div style="margin: 0 0 2px 0;">
                @if ($noKb !== '')
                    <strong>No Kayu Bulat</strong> : {{ $noKb }}
                @endif
            </div>

            <div style="margin: 0 0 2px 0;">
                {{-- <strong>Tgl Penerimaan ST</strong> :
                {{ $tglPenerimaan !== '' ? $formatDateLong($tglPenerimaan) : ($dateLabel !== '' ? $dateLabel : '-') }} --}}
                @if ($meja !== '')
                    <strong>Meja</strong> : {{ $meja }}
                @endif
            </div>

            <div style="margin: 0 0 2px 0;">
                <strong>Supplier</strong> : {{ $supplier !== '' ? $supplier : '-' }}
            </div>

            <div style="margin: 0 0 6px 0;">
                <strong>Jenis Kayu</strong> : {{ $jenisKayu !== '' ? $jenisKayu : '-' }}
            </div>

            <table class="report-table">
                <colgroup>
                    <col style="width: 72px;">
                    <col style="width: 58px;">
                    <col style="width: 220px;">
                    <col style="width: 60px;">
                    <col style="width: 60px;">
                    <col style="width: 55px;">
                </colgroup>
                <thead>
                    <tr class="headers-row">
                        <th>Kategori</th>
                        <th>Jumlah Truk</th>
                        <th>Grade</th>
                        <th>KB (Ton)</th>
                        <th>ST (Ton)</th>
                        <th>%</th>
                    </tr>
                </thead>
                <tfoot>
                    <tr class="table-end-line">
                        <td colspan="6"></td>
                    </tr>
                </tfoot>
                <tbody>
                    @php $rowIndex = 0; @endphp

                    @if ($inputRows !== [])
                        @php $rowspan = count($inputRows); @endphp
                        @foreach ($inputRows as $line)
                            @php $rowIndex++; @endphp
                            <tr class="data-row {{ $rowIndex % 2 === 1 ? 'row-odd' : 'row-even' }}">
                                @if ($loop->first)
                                    <td class="data-cell" rowspan="{{ $rowspan }}" style="font-weight: bold;">
                                        Input
                                    </td>
                                @endif
                                <td class="data-cell center">{{ $fmtTruck($line['jmlh_truk'] ?? '') }}</td>
                                <td class="data-cell left">{{ (string) ($line['grade'] ?? '') }}</td>
                                <td class="data-cell number">{{ $fmtDetail((float) ($line['kb'] ?? 0.0), 4) }}</td>
                                <td class="data-cell center">{{ $dash }}</td>
                                <td class="data-cell number">
                                    {{ $fmtPercentDetail((float) ($line['percent'] ?? 0.0), 1) }}
                                </td>
                            </tr>
                        @endforeach
                    @endif

                    @if ($outputRows !== [])
                        @php $rowspan = count($outputRows); @endphp
                        @foreach ($outputRows as $line)
                            @php $rowIndex++; @endphp
                            <tr class="data-row {{ $rowIndex % 2 === 1 ? 'row-odd' : 'row-even' }}">
                                @if ($loop->first)
                                    <td class="data-cell" rowspan="{{ $rowspan }}" style="font-weight: bold;">
                                        Output</td>
                                @endif
                                <td class="data-cell center">{{ $fmtTruck($line['jmlh_truk'] ?? '0') }}</td>
                                <td class="data-cell right">{{ (string) ($line['grade'] ?? '') }}</td>
                                <td class="data-cell center">{{ $dash }}</td>
                                <td class="data-cell number">{{ $fmtDetail((float) ($line['st'] ?? 0.0), 4) }}</td>
                                <td class="data-cell number">
                                    {{ $fmtPercentDetail((float) ($line['percent'] ?? 0.0), 1) }}
                                </td>
                            </tr>
                        @endforeach
                    @endif

                    @if ($inputRows === [] && $outputRows === [])
                        <tr class="data-row row-odd">
                            <td colspan="6" class="data-cell center">Tidak ada data.</td>
                        </tr>
                    @else
                        <tr class="totals-row">
                            <td colspan="3" style="text-align: center;">Total</td>
                            <td class="number">{{ $fmtTotal($kbTotal, 4) }}</td>
                            <td class="number">{{ $fmtTotal($stTotal, 4) }}</td>
                            <td class="number">{{ $fmtPercentTotal($rendemen, 1) }}</td>
                        </tr>
                    @endif
                </tbody>
            </table>

            @if ($inputRows !== [] || $outputRows !== [])
                <div style="margin: 0 0 10px 0; text-align: right;">
                    <strong>RENDEMEN : {{ $fmtPercentTotal($rendemen, 1) }}</strong>
                </div>
            @endif

            @php
                $hasMoney =
                    abs($moneySt) > 0.0000001 ||
                    abs($moneyKb) > 0.0000001 ||
                    abs($moneyUpah) > 0.0000001 ||
                    abs($moneyHasil) > 0.0000001;
            @endphp

            @if (($inputRows !== [] || $outputRows !== []) && ($hasMoney || $balokRows !== []))
                <strong>ST :</strong> {{ $fmtMoney($moneySt) }}
                <br>
                <strong>KB :</strong> {{ $fmtMoney($moneyKb) }}
                <br>
                <strong>Upah :</strong> {{ $fmtMoney($moneyUpah) }}
                <br>
                <strong>Hasil :</strong> {{ $fmtMoney($moneyHasil) }}
                <span> <strong>({{ $moneyFlag }})</strong></span>

                <div class="btul-box" style="margin-top: 5px;">
                    <div class="btul-title">Average</div>
                    <table class="mini-table">
                        <thead>
                            <tr>
                                <th style="width: 130px;"></th>
                                <th style="width: 60px;">KB (Ton)</th>
                                <th style="width: 60px;">ST (Ton)</th>
                                <th style="width: 55px;">%</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if ($balokRows === [])
                                <tr>
                                    <td class="label" colspan="4" style="text-align: center;">
                                        {{ $dash }}</td>
                                </tr>
                            @else
                                @foreach ($balokRows as $bline)
                                    @php
                                        $bLabel = trim((string) ($bline['label'] ?? ''));
                                        $bKb = (float) ($bline['kb'] ?? 0.0);
                                        $bSt = (float) ($bline['st'] ?? 0.0);
                                        $bPct = (float) ($bline['percent'] ?? 0.0);
                                    @endphp
                                    <tr>
                                        <td class="label">{{ $bLabel }}</td>
                                        <td class="num">{{ $fmtDetail($bKb, 2) }}</td>
                                        <td class="num">{{ $fmtDetail($bSt, 2) }}</td>
                                        <td class="num">{{ $fmtPercentDetail($bPct, 1) }}</td>
                                    </tr>
                                @endforeach
                            @endif
                        </tbody>
                    </table>
                </div>
                </div>
            @endif
        @endforeach
    @empty
        <table class="report-table">
            <thead>
                <tr class="headers-row">
                    <th>Tidak ada data.</th>
                </tr>
            </thead>
            <tbody>
                <tr class="data-row row-odd">
                    <td class="data-cell">Tidak ada data.</td>
                </tr>
            </tbody>
        </table>
    @endforelse

    @php
        $grand = is_array($data['grand_totals'] ?? null) ? $data['grand_totals'] : null;
        $grandRowsByKategori = is_array($grand['rows'] ?? null) ? $grand['rows'] : ['input' => [], 'output' => []];
        $grandInputRows = is_array($grandRowsByKategori['input'] ?? null) ? $grandRowsByKategori['input'] : [];
        $grandOutputRows = is_array($grandRowsByKategori['output'] ?? null) ? $grandRowsByKategori['output'] : [];
        $grandTotals = is_array($grand['totals'] ?? null) ? $grand['totals'] : [];
        $grandKbTotal = (float) ($grandTotals['kb_total'] ?? 0.0);
        $grandStTotal = (float) ($grandTotals['st_total'] ?? 0.0);
        $grandRendemen = (float) ($grandTotals['rendemen'] ?? 0.0);
    @endphp

    @if ($grandInputRows !== [] || $grandOutputRows !== [])
        <div class="date-separator"></div>
        <div class="group-title" style="margin-top: 25px; margin-bottom: 10px; text-align: center;">Grand Total Seluruh
            Grade</div>

        <table class="report-table">
            <colgroup>
                <col style="width: 72px;">
                <col style="width: 58px;">
                <col style="width: 220px;">
                <col style="width: 60px;">
                <col style="width: 60px;">
                <col style="width: 55px;">
            </colgroup>
            <thead>
                <tr class="headers-row">
                    <th>Kategori</th>
                    <th>Jumlah Truk</th>
                    <th>Grade</th>
                    <th>KB (Ton)</th>
                    <th>ST (Ton)</th>
                    <th>%</th>
                </tr>
            </thead>
            <tfoot>
                <tr class="table-end-line">
                    <td colspan="6"></td>
                </tr>
            </tfoot>
            <tbody>
                @php $rowIndex = 0; @endphp

                @if ($grandInputRows !== [])
                    @php $rowspan = count($grandInputRows); @endphp
                    @foreach ($grandInputRows as $line)
                        @php $rowIndex++; @endphp
                        <tr class="data-row {{ $rowIndex % 2 === 1 ? 'row-odd' : 'row-even' }}">
                            @if ($loop->first)
                                <td class="data-cell" rowspan="{{ $rowspan }}" style="font-weight: bold;">
                                    Input
                                </td>
                            @endif
                            <td class="data-cell center">{{ $fmtTruck($line['jmlh_truk'] ?? '') }}</td>
                            <td class="data-cell left">{{ (string) ($line['grade'] ?? '') }}</td>
                            <td class="data-cell number">{{ $fmtDetail((float) ($line['kb'] ?? 0.0), 4) }}</td>
                            <td class="data-cell center">{{ $dash }}</td>
                            <td class="data-cell number">
                                {{ $fmtPercentDetail((float) ($line['percent'] ?? 0.0), 1) }}
                            </td>
                        </tr>
                    @endforeach
                @endif

                @if ($grandOutputRows !== [])
                    @php $rowspan = count($grandOutputRows); @endphp
                    @foreach ($grandOutputRows as $line)
                        @php $rowIndex++; @endphp
                        <tr class="data-row {{ $rowIndex % 2 === 1 ? 'row-odd' : 'row-even' }}">
                            @if ($loop->first)
                                <td class="data-cell" rowspan="{{ $rowspan }}" style="font-weight: bold;">
                                    Output</td>
                            @endif
                            <td class="data-cell center">{{ $fmtTruck($line['jmlh_truk'] ?? '0') }}</td>
                            <td class="data-cell right">{{ (string) ($line['grade'] ?? '') }}</td>
                            <td class="data-cell center">{{ $dash }}</td>
                            <td class="data-cell number">{{ $fmtDetail((float) ($line['st'] ?? 0.0), 4) }}</td>
                            <td class="data-cell number">
                                {{ $fmtPercentDetail((float) ($line['percent'] ?? 0.0), 1) }}
                            </td>
                        </tr>
                    @endforeach
                @endif

                <tr class="totals-row">
                    <td colspan="3" style="text-align: center;">Grand Total</td>
                    <td class="number">{{ $fmtTotal($grandKbTotal, 4) }}</td>
                    <td class="number">{{ $fmtTotal($grandStTotal, 4) }}</td>
                    <td class="number">{{ $fmtPercentTotal($grandRendemen, 1) }}</td>
                </tr>
            </tbody>
        </table>

        <div style="margin: 0 0 10px 0; text-align: right;">
            <strong>RENDEMEN : {{ $fmtPercentTotal($grandRendemen, 1) }}</strong>
        </div>
    @endif

    <htmlpagefooter name="reportFooter">
        <div class="footer-wrap">
            <div class="footer-left">Dicetak oleh: {{ $generatedByName }} pada {{ $generatedAtText }}</div>
            <div class="footer-right">Halaman {PAGENO} dari {nbpg}</div>
        </div>
    </htmlpagefooter>
    <sethtmlpagefooter name="reportFooter" value="on" />
</body>

</html>
