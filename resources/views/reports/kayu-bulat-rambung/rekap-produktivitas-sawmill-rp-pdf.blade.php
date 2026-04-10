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
            border-collapse: collapse;
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

        .section-separator td {
            padding: 0 !important;
            height: 0 !important;
            line-height: 0 !important;
            border-top: 1px solid #000 !important;
            border-right: 1px solid #000 !important;
            border-bottom: 0 !important;
            border-left: 1px solid #000 !important;
            background: #fff !important;
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

        .receipt-block {
            margin: 0 0 12px 0;
        }

        .receipt-separator {
            border-top: 1px solid #000;
            margin: 8px 0 10px 0;
        }

        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 4px;
            table-layout: fixed;
        }

        .meta-table td {
            border: 0;
            padding: 0 4px 1px 0;
            vertical-align: top;
            text-align: left;
            background: transparent !important;
            word-break: normal;
        }

        .meta-line {
            white-space: nowrap;
        }

        .meta-line.right {
            text-align: right;
        }

        .meta-inline-label {
            font-weight: bold;
        }

        .table-wrap {
            margin-left: 0;
        }

        .bottom-section {
            width: 100%;
            margin: 6px 0 10px 0;
        }

        .bottom-layout {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin: 0;
        }

        .bottom-layout td {
            border: 0;
            padding: 0;
            vertical-align: top;
            background: transparent !important;
            word-break: normal;
        }

        .money-box {
            width: 100%;
            font-size: 11px;
            padding-left: 40px;
        }

        .money-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin: 0;
        }

        .money-table td {
            border: 0;
            padding: 0 0 2px 0;
            vertical-align: top;
            background: transparent !important;
        }

        .money-divider-row td {
            padding: 1px 0 2px 0;
        }

        .money-divider-line {
            border-top: 1px solid #000;
            height: 0;
            margin-left: 82px;
        }

        .money-label {
            width: 68px;
            font-weight: bold;
            text-align: left;
            white-space: nowrap;
        }

        .money-value {
            text-align: right;
            white-space: nowrap;
            font-family: "Calibri", "DejaVu Sans", sans-serif;
            width: 150px;
            font-weight: bold;
        }

        .money-flag-inline {
            font-weight: bold;
            white-space: nowrap;
            padding-left: 10px !important;
            text-align: left;
            width: 60px;
        }

        .btul-box {
            width: 100%;
            font-size: 11px;
            padding-left: 26px;
        }

        .btul-title {
            font-weight: normal;
            text-align: left;
            margin: 0;
            line-height: 1.15;
            width: 96px;
            white-space: normal;
            word-break: normal;
            overflow-wrap: normal;
        }

        .btul-wrap {
            width: 100%;
            margin-left: 0;
        }

        .btul-layout {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin: 0;
        }

        .btul-layout td {
            border: 0;
            padding: 0;
            vertical-align: top;
            background: transparent !important;
            word-break: normal;
        }

        .btul-text-cell {
            width: 108px;
            padding-right: 12px;
            padding-top: 4px;
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

        .rendemen-inline {
            margin: 2px 0 10px 0;
            text-align: right;
            font-size: 11px;
        }

        @include('reports.partials.pdf-footer-table-style')
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
                return \Carbon\Carbon::parse($raw)->locale('id')->translatedFormat('d-M-y');
            } catch (\Throwable $exception) {
                return $raw;
            }
        };
    @endphp

    <h1 class="report-title">Laporan Rekap Penerimaan ST Dari Sawmill + Costing (Rambung)</h1>
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

            <div class="receipt-block">
                <table class="meta-table">
                    <colgroup>
                        <col style="width: 33%">
                        <col style="width: 34%">
                        <col style="width: 33%">
                    </colgroup>
                    <tr>
                        <td class="meta-line">
                            @if ($noPen !== '')
                                <span class="meta-inline-label">No Pen ST</span> : {{ $noPen }}
                            @endif
                        </td>
                        <td class="meta-line">
                            @if ($supplier !== '')
                                <span class="meta-inline-label">Supplier</span> : {{ $supplier }}
                            @endif
                        </td>
                        <td class="meta-line right">
                            @if ($noKb !== '')
                                <span class="meta-inline-label">No.KB</span> : {{ $noKb }}
                                @if ($dateCreate !== '')
                                    ({{ $formatDateLong($dateCreate) }})
                                @endif
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="meta-line">
                            <span class="meta-inline-label">Tgl Penerimaan ST</span> :
                            {{ $tglPenerimaan !== '' ? $formatDateLong($tglPenerimaan) : ($dateLabel !== '' ? $dateLabel : '-') }}
                        </td>
                        <td class="meta-line">
                            @if ($jenisKayu !== '')
                                <span class="meta-inline-label">Jenis Kayu</span> : {{ $jenisKayu }}
                            @endif
                        </td>
                        <td class="meta-line right">
                            @if ($meja !== '')
                                <span class="meta-inline-label">Meja</span> : {{ $meja }}
                            @endif
                        </td>
                    </tr>
                </table>

                <div class="table-wrap">
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
                        <tbody>
                            @php $rowIndex = 0; @endphp

                            @if ($inputRows !== [])
                                @php $rowspan = count($inputRows); @endphp
                                @foreach ($inputRows as $line)
                                    @php $rowIndex++; @endphp
                                    <tr class="data-row {{ $rowIndex % 2 === 1 ? 'row-odd' : 'row-even' }}">
                                        @if ($loop->first)
                                            <td class="data-cell" rowspan="{{ $rowspan }}"
                                                style="font-weight: bold;">
                                                Input
                                            </td>
                                        @endif
                                        <td class="data-cell center">{{ $fmtTruck($line['jmlh_truk'] ?? '') }}</td>
                                        <td class="data-cell left" style="font-weight: bold;">
                                            {{ (string) ($line['grade'] ?? '') }}</td>
                                        <td class="data-cell number">{{ $fmtDetail((float) ($line['kb'] ?? 0.0), 4) }}
                                        </td>
                                        <td class="data-cell center">{{ $dash }}</td>
                                        <td class="data-cell number">
                                            {{ $fmtPercentDetail((float) ($line['percent'] ?? 0.0), 1) }}
                                        </td>
                                    </tr>
                                @endforeach
                            @endif

                            @if ($inputRows !== [] && $outputRows !== [])
                                <tr class="section-separator">
                                    <td colspan="6"></td>
                                </tr>
                            @endif

                            @if ($outputRows !== [])
                                @php $rowspan = count($outputRows); @endphp
                                @foreach ($outputRows as $line)
                                    @php $rowIndex++; @endphp
                                    <tr class="data-row {{ $rowIndex % 2 === 1 ? 'row-odd' : 'row-even' }}">
                                        @if ($loop->first)
                                            <td class="data-cell" rowspan="{{ $rowspan }}"
                                                style="font-weight: bold;">
                                                Output</td>
                                        @endif
                                        <td class="data-cell center">{{ $fmtTruck($line['jmlh_truk'] ?? '0') }}</td>
                                        <td class="data-cell right" style="font-weight: bold;">
                                            {{ (string) ($line['grade'] ?? '') }}</td>
                                        <td class="data-cell center">{{ $dash }}</td>
                                        <td class="data-cell number">{{ $fmtDetail((float) ($line['st'] ?? 0.0), 4) }}
                                        </td>
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
                </div>

                @if ($inputRows !== [] || $outputRows !== [])
                    <div class="rendemen-inline">
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
                    <div class="bottom-section">
                        <table class="bottom-layout">
                            <tr>
                                <td style="width: 50%;">
                                    <div class="money-box">
                                        <table class="money-table">
                                            <tr>
                                                <td class="money-label">ST</td>
                                                <td class="money-value">{{ $fmtMoney($moneySt) }}</td>
                                            </tr>
                                            <tr>
                                                <td class="money-label">KB</td>
                                                <td class="money-value">{{ $fmtMoney($moneyKb) }}</td>
                                            </tr>
                                            <tr>
                                                <td class="money-label">Upah</td>
                                                <td class="money-value">{{ $fmtMoney($moneyUpah) }}</td>
                                            </tr>
                                            <tr class="money-divider-row">
                                                <td colspan="2">
                                                    <div class="money-divider-line"></div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="money-label">Hasil</td>
                                                <td class="money-value">{{ $fmtMoney($moneyHasil) }}</td>
                                                <td class="money-flag-inline">
                                                    ({{ $moneyHasil < 0 ? 'RUGI' : 'LABA' }})</td>
                                            </tr>
                                        </table>
                                    </div>
                                </td>
                                <td style="width: 50%;">
                                    <div class="btul-box">
                                        <div class="btul-wrap">
                                            <table class="btul-layout">
                                                <tr>
                                                    <td class="btul-text-cell">
                                                        <div class="btul-title">Balok Timbang<br>Ulang</div>
                                                    </td>
                                                    <td>
                                                        <table class="mini-table">
                                                            <thead>
                                                                <tr>
                                                                    <th style="width: 105px;"></th>
                                                                    <th style="width: 45px;">KBTon</th>
                                                                    <th style="width: 45px;">STTon</th>
                                                                    <th style="width: 35px;">%</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                @if ($balokRows === [])
                                                                    <tr>
                                                                        <td class="label" colspan="4"
                                                                            style="text-align: center;">
                                                                            {{ $dash }}</td>
                                                                    </tr>
                                                                @else
                                                                    @foreach ($balokRows as $bline)
                                                                        @php
                                                                            $bLabel = trim(
                                                                                (string) ($bline['label'] ?? ''),
                                                                            );
                                                                            $bKb = (float) ($bline['kb'] ?? 0.0);
                                                                            $bSt = (float) ($bline['st'] ?? 0.0);
                                                                            $bPct = (float) ($bline['percent'] ?? 0.0);
                                                                            $isTotal =
                                                                                strpos($bLabel, 'Total') !== false;
                                                                            $hasCompleteData =
                                                                                abs($bKb) > 0.0000001 &&
                                                                                abs($bSt) > 0.0000001;
                                                                        @endphp
                                                                        @if ($isTotal || $hasCompleteData)
                                                                            <tr>
                                                                                <td class="label">{{ $bLabel }}
                                                                                </td>
                                                                                <td class="num">
                                                                                    {{ $fmtDetail($bKb, 2) }}</td>
                                                                                <td class="num">
                                                                                    {{ $fmtDetail($bSt, 2) }}</td>
                                                                                <td class="num">
                                                                                    {{ $fmtPercentDetail($bPct, 1) }}
                                                                                </td>
                                                                            </tr>
                                                                        @endif
                                                                    @endforeach
                                                                @endif
                                                            </tbody>
                                                        </table>
                                                    </td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </div>
                @endif
            </div>
            @if (!$loop->last)
                <div class="receipt-separator"></div>
            @endif
        @endforeach
    @empty
        <table class="report-table">
            <thead>
                <tr class="headers-row">
                    <th>Tidak ada data.</th>
                </tr>
            </thead>
            <tfoot>
                <tr class="table-end-line">
                    <td colspan="5"></td>
                </tr>
            </tfoot>
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

        $grandByGroup = is_array($data['grand_totals_by_group'] ?? null) ? $data['grand_totals_by_group'] : [];

        $grandBansaw = is_array($grandByGroup['bansaw'] ?? null)
            ? $grandByGroup['bansaw']
            : [
                'rows' => ['input' => [], 'output' => []],
                'totals' => ['kb_total' => 0.0, 'st_total' => 0.0, 'rendemen' => 0.0],
            ];
        $grandSlp = is_array($grandByGroup['slp'] ?? null)
            ? $grandByGroup['slp']
            : [
                'rows' => ['input' => [], 'output' => []],
                'totals' => ['kb_total' => 0.0, 'st_total' => 0.0, 'rendemen' => 0.0],
            ];

        $grandBansawInputRows = is_array($grandBansaw['rows']['input'] ?? null) ? $grandBansaw['rows']['input'] : [];
        $grandBansawOutputRows = is_array($grandBansaw['rows']['output'] ?? null) ? $grandBansaw['rows']['output'] : [];
        $bansawTotals = is_array($grandBansaw['totals'] ?? null) ? $grandBansaw['totals'] : [];
        $bansawKbTotal = (float) ($bansawTotals['kb_total'] ?? 0.0);
        $bansawStTotal = (float) ($bansawTotals['st_total'] ?? 0.0);
        $bansawRendemen = (float) ($bansawTotals['rendemen'] ?? 0.0);

        $grandSlpInputRows = is_array($grandSlp['rows']['input'] ?? null) ? $grandSlp['rows']['input'] : [];
        $grandSlpOutputRows = is_array($grandSlp['rows']['output'] ?? null) ? $grandSlp['rows']['output'] : [];
        $slpTotals = is_array($grandSlp['totals'] ?? null) ? $grandSlp['totals'] : [];
        $slpKbTotal = (float) ($slpTotals['kb_total'] ?? 0.0);
        $slpStTotal = (float) ($slpTotals['st_total'] ?? 0.0);
        $slpRendemen = (float) ($slpTotals['rendemen'] ?? 0.0);

        $grandMoneyAll = is_array($grand['money'] ?? null)
            ? $grand['money']
            : ['st' => 0.0, 'kb' => 0.0, 'upah' => 0.0, 'hasil' => 0.0];
        $grandMoneyBansaw = is_array($grandBansaw['money'] ?? null)
            ? $grandBansaw['money']
            : ['st' => 0.0, 'kb' => 0.0, 'upah' => 0.0, 'hasil' => 0.0];
        $grandMoneySlp = is_array($grandSlp['money'] ?? null)
            ? $grandSlp['money']
            : ['st' => 0.0, 'kb' => 0.0, 'upah' => 0.0, 'hasil' => 0.0];
    @endphp

    @if ($grandInputRows !== [] || $grandOutputRows !== [])
        <div class="date-separator"></div>
        <div class="group-title" style="margin-top: 25px; margin-bottom: 10px; text-align: center;">Grand Total
            Seluruh
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

        <ul>
            <li>
                <span class="money-label">ST</span>
                <span class="money-value">{{ $fmtMoney((float) ($grandMoneyAll['st'] ?? 0.0)) }}</span>
            </li>
            <li>
                <span class="money-label">KB</span>
                <span class="money-value">{{ $fmtMoney((float) ($grandMoneyAll['kb'] ?? 0.0)) }}</span>
            </li>
            <li>
                <span class="money-label">Upah</span>
                <span class="money-value">{{ $fmtMoney((float) ($grandMoneyAll['upah'] ?? 0.0)) }}</span>
            </li>
            <li class="money-divider"></li>
            <li>
                <span class="money-label">Hasil</span>
                <span class="money-value">{{ $fmtMoney((float) ($grandMoneyAll['hasil'] ?? 0.0)) }}</span>
                <span
                    class="money-flag-inline">{{ ((float) ($grandMoneyAll['hasil'] ?? 0.0)) < 0 ? '(RUGI)' : '(LABA)' }}</span>
            </li>
        </ul>
    @endif

    @if ($grandBansawInputRows !== [] || $grandBansawOutputRows !== [])
        <div class="date-separator"></div>
        <div class="group-title" style="margin-top: 25px; margin-bottom: 10px; text-align: center;">Grand Total BANSAW
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

                @if ($grandBansawInputRows !== [])
                    @php $rowspan = count($grandBansawInputRows); @endphp
                    @foreach ($grandBansawInputRows as $line)
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

                @if ($grandBansawOutputRows !== [])
                    @php $rowspan = count($grandBansawOutputRows); @endphp
                    @foreach ($grandBansawOutputRows as $line)
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
                    <td class="number">{{ $fmtTotal($bansawKbTotal, 4) }}</td>
                    <td class="number">{{ $fmtTotal($bansawStTotal, 4) }}</td>
                    <td class="number">{{ $fmtPercentTotal($bansawRendemen, 1) }}</td>
                </tr>
            </tbody>
        </table>

        <div style="margin: 0 0 10px 0; text-align: right;">
            <strong>RENDEMEN : {{ $fmtPercentTotal($bansawRendemen, 1) }}</strong>
        </div>

        <ul>
            <li>
                <span class="money-label">ST</span>
                <span class="money-value">{{ $fmtMoney((float) ($grandMoneyBansaw['st'] ?? 0.0)) }}</span>
            </li>
            <li>
                <span class="money-label">KB</span>
                <span class="money-value">{{ $fmtMoney((float) ($grandMoneyBansaw['kb'] ?? 0.0)) }}</span>
            </li>
            <li>
                <span class="money-label">Upah</span>
                <span class="money-value">{{ $fmtMoney((float) ($grandMoneyBansaw['upah'] ?? 0.0)) }}</span>
            </li>
            <li class="money-divider"></li>
            <li>
                <span class="money-label">Hasil</span>
                <span class="money-value">{{ $fmtMoney((float) ($grandMoneyBansaw['hasil'] ?? 0.0)) }}</span>
                <span
                    class="money-flag-inline">{{ ((float) ($grandMoneyBansaw['hasil'] ?? 0.0)) < 0 ? '(RUGI)' : '(LABA)' }}</span>
            </li>
        </ul>
    @endif

    @if ($grandSlpInputRows !== [] || $grandSlpOutputRows !== [])
        <div class="date-separator"></div>
        <div class="group-title" style="margin-top: 25px; margin-bottom: 10px; text-align: center;">Grand Total SLP
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

                @if ($grandSlpInputRows !== [])
                    @php $rowspan = count($grandSlpInputRows); @endphp
                    @foreach ($grandSlpInputRows as $line)
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

                @if ($grandSlpOutputRows !== [])
                    @php $rowspan = count($grandSlpOutputRows); @endphp
                    @foreach ($grandSlpOutputRows as $line)
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
                    <td class="number">{{ $fmtTotal($slpKbTotal, 4) }}</td>
                    <td class="number">{{ $fmtTotal($slpStTotal, 4) }}</td>
                    <td class="number">{{ $fmtPercentTotal($slpRendemen, 1) }}</td>
                </tr>
            </tbody>
        </table>

        <div style="margin: 0 0 10px 0; text-align: right;">
            <strong>RENDEMEN : {{ $fmtPercentTotal($slpRendemen, 1) }}</strong>
        </div>


        <ul>
            <li>
                <span class="money-label">ST</span>
                <span class="money-value">{{ $fmtMoney((float) ($grandMoneySlp['st'] ?? 0.0)) }}</span>
            </li>
            <li>
                <span class="money-label">KB</span>
                <span class="money-value">{{ $fmtMoney((float) ($grandMoneySlp['kb'] ?? 0.0)) }}</span>
            </li>
            <li>
                <span class="money-label">Upah</span>
                <span class="money-value">{{ $fmtMoney((float) ($grandMoneySlp['upah'] ?? 0.0)) }}</span>
            </li>
            <li class="money-divider"></li>
            <li>
                <span class="money-label">Hasil</span>
                <span class="money-value">{{ $fmtMoney((float) ($grandMoneySlp['hasil'] ?? 0.0)) }}</span>
                <span
                    class="money-flag-inline">{{ ((float) ($grandMoneySlp['hasil'] ?? 0.0)) < 0 ? '(RUGI)' : '(LABA)' }}</span>
            </li>
        </ul>
    @endif

    @include('reports.partials.pdf-footer-table')
</body>

</html>
