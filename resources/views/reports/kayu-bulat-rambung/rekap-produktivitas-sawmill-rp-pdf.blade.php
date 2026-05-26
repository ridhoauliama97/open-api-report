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
            margin: 6px 0 0 0;
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

        .mini-table td.label-total {
            text-align: right;
            font-weight: bold;
        }

        .mini-table td.num {
            text-align: right;
            white-space: nowrap;
            font-family: "Calibri", "DejaVu Sans", sans-serif;
        }

        .group-summary-wrap {
            width: 295px;
            margin: 0 0 10px auto;
        }

        .summary-pair-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin: 0 0 10px 0;
        }

        .summary-pair-table td {
            border: 0;
            padding: 0;
            vertical-align: top;
            background: transparent !important;
        }

        .summary-pair-left {
            width: 50%;
            padding-right: 14px !important;
        }

        .summary-pair-right {
            width: 50%;
        }

        .group-summary-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            font-size: 10px;
        }

        .group-summary-table th,
        .group-summary-table td {
            border: 1px solid #000;
            padding: 3px 5px;
        }

        .group-summary-table th {
            text-align: center;
            font-weight: bold;
        }

        .group-summary-table td:first-child {
            text-align: left;
        }

        .group-summary-table td.num {
            text-align: right;
            white-space: nowrap;
            font-family: "Calibri", "DejaVu Sans", sans-serif;
        }

        .group-summary-total td {
            font-weight: bold;
        }

        .rendemen-inline {
            margin: 2px 0 10px 0;
            text-align: right;
            font-size: 11px;
        }

        .summary-section {
            page-break-before: always;
        }

        .summary-frame-table {
            width: 100%;
            height: 252mm;
            border-collapse: collapse;
            table-layout: fixed;
            margin: 0;
        }

        .summary-frame-cell {
            border: 1px solid #000 !important;
            padding: 4mm !important;
            height: 252mm;
            vertical-align: top;
            text-align: left !important;
            background: #fff !important;
        }

        .summary-section-heading-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin: 25px 0 14px 0;
        }

        .summary-section-heading-table td {
            border: 0 !important;
            padding: 0 !important;
            text-align: center !important;
            font-size: 12px;
            font-weight: bold;
            background: transparent !important;
        }

        .summary-section .money-box {
            width: 92mm !important;
            font-size: 11px;
        }

        .summary-section .money-table {
            table-layout: fixed;
        }

        .summary-section .money-label {
            width: 12mm;
            font-size: 11px;
        }

        .summary-section .money-value {
            width: 35mm;
            font-size: 11px;
        }

        .summary-section .money-flag-inline {
            width: 45mm;
            font-size: 11px;
            line-height: 1.2;
            padding-left: 12px !important;
            white-space: normal;
        }

        .summary-section .summary-money-compact {
            width: 92mm !important;
        }

        .summary-section .summary-money-compact .money-table {
            width: 92mm !important;
            table-layout: fixed;
        }

        .summary-section .summary-money-compact .money-label {
            width: 12mm !important;
        }

        .summary-section .summary-money-compact .money-value {
            width: 35mm !important;
        }

        .summary-section .summary-money-compact .money-flag-inline {
            width: 45mm !important;
            padding-left: 12px !important;
            white-space: normal;
        }

        .summary-rendemen-table {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
            table-layout: fixed;
        }

        .summary-rendemen-table td {
            border: 0;
            padding: 0 0 2px 0;
            text-align: right;
            font-size: 11px;
            background: transparent !important;
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
        $fmtMoney = static fn(float $value): string => number_format($value, 2, ',', '.');
        $fmtProfitPercent = static fn(float $hasil, float $st): string => abs($st) < 0.0000001
            ? '0.0%'
            : number_format(($hasil / $st) * 100, 1, '.', ',') . '%';

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
                                                <td class="money-value">{{ $fmtMoney($moneyHasil) }} </td>
                                                <td class="money-flag-inline"> &nbsp;
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
                                                        <div class="btul-title">Balok Timbang <br> Ulang</div>
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
                                                                            {{ $dash }}
                                                                        </td>
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
                                                                        @php
                                                                            $mejaNum = null;
                                                                            if (
                                                                                preg_match(
                                                                                    '/NoMeja\s+(\d+)/i',
                                                                                    $bLabel,
                                                                                    $mejaMatch,
                                                                                )
                                                                            ) {
                                                                                $mejaNum = (int) $mejaMatch[1];
                                                                            }
                                                                            $isMejaRow = $mejaNum !== null;
                                                                            $showBalokRow =
                                                                                $isTotal ||
                                                                                ($hasCompleteData &&
                                                                                    (!$isMejaRow || $mejaNum <= 10));
                                                                        @endphp
                                                                        @if ($showBalokRow)
                                                                            <tr>
                                                                                <td
                                                                                    class="{{ $isTotal ? 'label-total' : 'label' }}">
                                                                                    {{ $bLabel }}
                                                                                </td>
                                                                                <td class="num"
                                                                                    style="font-weight: bold;">
                                                                                    {{ $fmtDetail($bKb, 2) }}</td>
                                                                                <td class="num"
                                                                                    style="font-weight: bold;">
                                                                                    {{ $fmtDetail($bSt, 2) }}</td>
                                                                                <td class="num"
                                                                                    style="font-weight: bold;">
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

        $grandSummaryByGroup = is_array($data['grand_summary_by_group'] ?? null) ? $data['grand_summary_by_group'] : [];
        $summaryBansawTotals = is_array($grandSummaryByGroup['bansaw'] ?? null)
            ? $grandSummaryByGroup['bansaw']
            : $bansawTotals;
        $summarySlpTotals = is_array($grandSummaryByGroup['slp'] ?? null) ? $grandSummaryByGroup['slp'] : $slpTotals;
        $summaryBansawKbTotal = (float) ($summaryBansawTotals['kb_total'] ?? 0.0);
        $summaryBansawStTotal = (float) ($summaryBansawTotals['st_total'] ?? 0.0);
        $summaryBansawRendemen =
            $summaryBansawKbTotal > 0.0 ? ($summaryBansawStTotal / $summaryBansawKbTotal) * 100.0 : 0.0;
        $summarySlpKbTotal = (float) ($summarySlpTotals['kb_total'] ?? 0.0);
        $summarySlpStTotal = (float) ($summarySlpTotals['st_total'] ?? 0.0);
        $summarySlpRendemen = $summarySlpKbTotal > 0.0 ? ($summarySlpStTotal / $summarySlpKbTotal) * 100.0 : 0.0;
        $summaryKbTotal = $summaryBansawKbTotal + $summarySlpKbTotal;
        $summaryStTotal = $summaryBansawStTotal + $summarySlpStTotal;
        $summaryRendemen = $summaryKbTotal > 0.0 ? ($summaryStTotal / $summaryKbTotal) * 100.0 : 0.0;

        $grandSummaryRows = [
            [
                'group' => 'BANSAW',
                'kb' => $summaryBansawKbTotal,
                'st' => $summaryBansawStTotal,
                'rendemen' => $summaryBansawRendemen,
            ],
            [
                'group' => 'SLP',
                'kb' => $summarySlpKbTotal,
                'st' => $summarySlpStTotal,
                'rendemen' => $summarySlpRendemen,
            ],
            [
                'group' => 'Total',
                'kb' => $summaryKbTotal,
                'st' => $summaryStTotal,
                'rendemen' => $summaryRendemen,
            ],
        ];
    @endphp

    <div class="summary-section">
        <table class="summary-frame-table">
            <tr>
                <td class="summary-frame-cell">

                    @if ($grandBansawInputRows !== [] || $grandBansawOutputRows !== [])
                        <table class="summary-section-heading-table">
                            <tr>
                                <td>Total BANSAW</td>
                            </tr>
                        </table>

                        <table class="report-table">
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

                                @if ($grandBansawInputRows !== [])
                                    @php $rowspan = count($grandBansawInputRows); @endphp
                                    @foreach ($grandBansawInputRows as $line)
                                        @php $rowIndex++; @endphp
                                        <tr class="data-row {{ $rowIndex % 2 === 1 ? 'row-odd' : 'row-even' }}">
                                            @if ($loop->first)
                                                <td class="data-cell" rowspan="{{ $rowspan }}"
                                                    style="font-weight: bold;">
                                                    Input
                                                </td>
                                            @endif
                                            <td class="data-cell center">{{ $fmtTruck($line['jmlh_truk'] ?? '') }}
                                            </td>
                                            <td class="data-cell left">{{ (string) ($line['grade'] ?? '') }}</td>
                                            <td class="data-cell number">
                                                {{ $fmtDetail((float) ($line['kb'] ?? 0.0), 4) }}</td>
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
                                                <td class="data-cell" rowspan="{{ $rowspan }}"
                                                    style="font-weight: bold;">
                                                    Output</td>
                                            @endif
                                            <td class="data-cell center">{{ $fmtTruck($line['jmlh_truk'] ?? '0') }}
                                            </td>
                                            <td class="data-cell right">{{ (string) ($line['grade'] ?? '') }}</td>
                                            <td class="data-cell center">{{ $dash }}</td>
                                            <td class="data-cell number">
                                                {{ $fmtDetail((float) ($line['st'] ?? 0.0), 4) }}</td>
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

                        <table class="summary-rendemen-table">
                            <tr>
                                <td><strong>RENDEMEN : {{ $fmtPercentTotal($bansawRendemen, 1) }}</strong></td>
                            </tr>
                        </table>

                        <div style="width: 100%; font-size: 11px; text-align: left;">
                            <table align="left"
                                style="width: 92mm; border-collapse: collapse; table-layout: fixed; margin-left: 0; margin-right: auto; text-align: left;">
                                <tr>
                                    <td
                                        style="border: 0; padding: 0 0 2px 0; width: 12mm; font-weight: bold; text-align: left; white-space: nowrap;">
                                        ST</td>
                                    <td
                                        style="border: 0; padding: 0 0 2px 0; width: 35mm; font-weight: bold; text-align: right; white-space: nowrap; font-family: Calibri, 'DejaVu Sans', sans-serif;">
                                        {{ $fmtMoney((float) ($grandMoneyBansaw['st'] ?? 0.0)) }}
                                    </td>
                                    <td style="border: 0; padding: 0 0 2px 12px; width: 45mm;"></td>
                                </tr>
                                <tr>
                                    <td
                                        style="border: 0; padding: 0 0 2px 0; width: 12mm; font-weight: bold; text-align: left; white-space: nowrap;">
                                        KB</td>
                                    <td
                                        style="border: 0; padding: 0 0 2px 0; width: 35mm; font-weight: bold; text-align: right; white-space: nowrap; font-family: Calibri, 'DejaVu Sans', sans-serif;">
                                        {{ $fmtMoney((float) ($grandMoneyBansaw['kb'] ?? 0.0)) }}
                                    </td>
                                    <td style="border: 0; padding: 0 0 2px 12px; width: 45mm;"></td>
                                </tr>
                                <tr>
                                    <td
                                        style="border: 0; padding: 0 0 2px 0; width: 12mm; font-weight: bold; text-align: left; white-space: nowrap;">
                                        Upah</td>
                                    <td
                                        style="border: 0; padding: 0 0 2px 0; width: 35mm; font-weight: bold; text-align: right; white-space: nowrap; font-family: Calibri, 'DejaVu Sans', sans-serif;">
                                        {{ $fmtMoney((float) ($grandMoneyBansaw['upah'] ?? 0.0)) }}</td>
                                    <td style="border: 0; padding: 0 0 2px 12px; width: 45mm;"></td>
                                </tr>
                                <tr>
                                    <td style="border: 0; padding: 1px 0 2px 0; width: 12mm;"></td>
                                    <td style="border: 0; padding: 1px 0 2px 0; width: 35mm;">
                                        <div style="border-top: 1px solid #000; height: 0;"></div>
                                    </td>
                                    <td style="border: 0; padding: 1px 0 2px 12px; width: 45mm;"></td>
                                </tr>
                                <tr>
                                    <td
                                        style="border: 0; padding: 0 0 2px 0; width: 12mm; font-weight: bold; text-align: left; white-space: nowrap;">
                                        Hasil</td>
                                    <td
                                        style="border: 0; padding: 0 0 2px 0; width: 35mm; font-weight: bold; text-align: right; white-space: nowrap; font-family: Calibri, 'DejaVu Sans', sans-serif;">
                                        {{ $fmtMoney((float) ($grandMoneyBansaw['hasil'] ?? 0.0)) }} </td>
                                    <td
                                        style="border: 0; padding: 0 0 2px 12px; width: 45mm; font-weight: bold; text-align: left; white-space: normal;">
                                        ({{ ((float) ($grandMoneyBansaw['hasil'] ?? 0.0)) < 0 ? 'RUGI' : 'LABA' }}) |
                                        ({{ $fmtProfitPercent((float) ($grandMoneyBansaw['hasil'] ?? 0.0), (float) ($grandMoneyBansaw['st'] ?? 0.0)) }})
                                    </td>
                                </tr>
                            </table>
                        </div>
                    @endif

                    <hr>

                    @if ($grandSlpInputRows !== [] || $grandSlpOutputRows !== [])
                        <table class="summary-section-heading-table">
                            <tr>
                                <td>Total SLP</td>
                            </tr>
                        </table>

                        <table class="report-table">
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

                                @if ($grandSlpInputRows !== [])
                                    @php $rowspan = count($grandSlpInputRows); @endphp
                                    @foreach ($grandSlpInputRows as $line)
                                        @php $rowIndex++; @endphp
                                        <tr class="data-row {{ $rowIndex % 2 === 1 ? 'row-odd' : 'row-even' }}">
                                            @if ($loop->first)
                                                <td class="data-cell" rowspan="{{ $rowspan }}"
                                                    style="font-weight: bold;">
                                                    Input
                                                </td>
                                            @endif
                                            <td class="data-cell center">{{ $fmtTruck($line['jmlh_truk'] ?? '') }}
                                            </td>
                                            <td class="data-cell left">{{ (string) ($line['grade'] ?? '') }}</td>
                                            <td class="data-cell number">
                                                {{ $fmtDetail((float) ($line['kb'] ?? 0.0), 4) }}</td>
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
                                                <td class="data-cell" rowspan="{{ $rowspan }}"
                                                    style="font-weight: bold;">
                                                    Output</td>
                                            @endif
                                            <td class="data-cell center">{{ $fmtTruck($line['jmlh_truk'] ?? '0') }}
                                            </td>
                                            <td class="data-cell right">{{ (string) ($line['grade'] ?? '') }}</td>
                                            <td class="data-cell center">{{ $dash }}</td>
                                            <td class="data-cell number">
                                                {{ $fmtDetail((float) ($line['st'] ?? 0.0), 4) }}</td>
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

                        <table class="summary-rendemen-table">
                            <tr>
                                <td><strong>RENDEMEN : {{ $fmtPercentTotal($slpRendemen, 1) }}</strong></td>
                            </tr>
                        </table>


                        <div style="width: 100%; font-size: 11px; text-align: left;">
                            <table align="left"
                                style="width: 92mm; border-collapse: collapse; table-layout: fixed; margin-left: 0; margin-right: auto; text-align: left;">
                                <tr>
                                    <td
                                        style="border: 0; padding: 0 0 2px 0; width: 12mm; font-weight: bold; text-align: left; white-space: nowrap;">
                                        ST</td>
                                    <td
                                        style="border: 0; padding: 0 0 2px 0; width: 35mm; font-weight: bold; text-align: right; white-space: nowrap; font-family: Calibri, 'DejaVu Sans', sans-serif;">
                                        {{ $fmtMoney((float) ($grandMoneySlp['st'] ?? 0.0)) }}
                                    </td>
                                    <td style="border: 0; padding: 0 0 2px 12px; width: 45mm;"></td>
                                </tr>
                                <tr>
                                    <td
                                        style="border: 0; padding: 0 0 2px 0; width: 12mm; font-weight: bold; text-align: left; white-space: nowrap;">
                                        KB</td>
                                    <td
                                        style="border: 0; padding: 0 0 2px 0; width: 35mm; font-weight: bold; text-align: right; white-space: nowrap; font-family: Calibri, 'DejaVu Sans', sans-serif;">
                                        {{ $fmtMoney((float) ($grandMoneySlp['kb'] ?? 0.0)) }}
                                    </td>
                                    <td style="border: 0; padding: 0 0 2px 12px; width: 45mm;"></td>
                                </tr>
                                <tr>
                                    <td
                                        style="border: 0; padding: 0 0 2px 0; width: 12mm; font-weight: bold; text-align: left; white-space: nowrap;">
                                        Upah</td>
                                    <td
                                        style="border: 0; padding: 0 0 2px 0; width: 35mm; font-weight: bold; text-align: right; white-space: nowrap; font-family: Calibri, 'DejaVu Sans', sans-serif;">
                                        {{ $fmtMoney((float) ($grandMoneySlp['upah'] ?? 0.0)) }}
                                    </td>
                                    <td style="border: 0; padding: 0 0 2px 12px; width: 45mm;"></td>
                                </tr>
                                <tr>
                                    <td style="border: 0; padding: 1px 0 2px 0; width: 12mm;"></td>
                                    <td style="border: 0; padding: 1px 0 2px 0; width: 35mm;">
                                        <div style="border-top: 1px solid #000; height: 0;"></div>
                                    </td>
                                    <td style="border: 0; padding: 1px 0 2px 12px; width: 45mm;"></td>
                                </tr>
                                <tr>
                                    <td
                                        style="border: 0; padding: 0 0 2px 0; width: 12mm; font-weight: bold; text-align: left; white-space: nowrap;">
                                        Hasil</td>
                                    <td
                                        style="border: 0; padding: 0 0 2px 0; width: 35mm; font-weight: bold; text-align: right; white-space: nowrap; font-family: Calibri, 'DejaVu Sans', sans-serif;">
                                        {{ $fmtMoney((float) ($grandMoneySlp['hasil'] ?? 0.0)) }}
                                    </td>
                                    <td
                                        style="border: 0; padding: 0 0 2px 12px; width: 45mm; font-weight: bold; text-align: left; white-space: normal;">
                                        ({{ ((float) ($grandMoneySlp['hasil'] ?? 0.0)) < 0 ? 'RUGI' : 'LABA' }})
                                        |
                                        ({{ $fmtProfitPercent((float) ($grandMoneySlp['hasil'] ?? 0.0), (float) ($grandMoneySlp['st'] ?? 0.0)) }})
                                    </td>
                                </tr>
                            </table>
                        </div>
                    @endif
                    <hr>
                    @if ($grandInputRows !== [] || $grandOutputRows !== [])
                        <table class="summary-section-heading-table">
                            <tr>
                                <td>Grand Total Seluruh Grade</td>
                            </tr>
                        </table>

                        <table class="report-table">
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

                                @if ($grandInputRows !== [])
                                    @php $rowspan = count($grandInputRows); @endphp
                                    @foreach ($grandInputRows as $line)
                                        @php $rowIndex++; @endphp
                                        <tr class="data-row {{ $rowIndex % 2 === 1 ? 'row-odd' : 'row-even' }}">
                                            @if ($loop->first)
                                                <td class="data-cell" rowspan="{{ $rowspan }}"
                                                    style="font-weight: bold;">
                                                    Input
                                                </td>
                                            @endif
                                            <td class="data-cell center">{{ $fmtTruck($line['jmlh_truk'] ?? '') }}
                                            </td>
                                            <td class="data-cell left">{{ (string) ($line['grade'] ?? '') }}</td>
                                            <td class="data-cell number">
                                                {{ $fmtDetail((float) ($line['kb'] ?? 0.0), 4) }}</td>
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
                                                <td class="data-cell" rowspan="{{ $rowspan }}"
                                                    style="font-weight: bold;">
                                                    Output</td>
                                            @endif
                                            <td class="data-cell center">{{ $fmtTruck($line['jmlh_truk'] ?? '0') }}
                                            </td>
                                            <td class="data-cell right">{{ (string) ($line['grade'] ?? '') }}</td>
                                            <td class="data-cell center">{{ $dash }}</td>
                                            <td class="data-cell number">
                                                {{ $fmtDetail((float) ($line['st'] ?? 0.0), 4) }}</td>
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

                        <table class="summary-rendemen-table" style="margin-bottom: 10px;">
                            <tr>
                                <td><strong>RENDEMEN : {{ $fmtPercentTotal($grandRendemen, 1) }}</strong></td>
                            </tr>
                        </table>

                        <table class="summary-pair-table">
                            <tr>
                                <td class="summary-pair-left">
                                    <div class="money-box" style="padding-left: 0; width: 100%;">
                                        <table class="money-table">
                                            <tr>
                                                <td class="money-label">ST</td>
                                                <td class="money-value">
                                                    {{ $fmtMoney((float) ($grandMoneyAll['st'] ?? 0.0)) }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="money-label">KB</td>
                                                <td class="money-value">
                                                    {{ $fmtMoney((float) ($grandMoneyAll['kb'] ?? 0.0)) }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="money-label">Upah</td>
                                                <td class="money-value">
                                                    {{ $fmtMoney((float) ($grandMoneyAll['upah'] ?? 0.0)) }}
                                                </td>
                                            </tr>
                                            <tr class="money-divider-row">
                                                <td colspan="2">
                                                    <div class="money-divider-line"></div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="money-label">Hasil</td>
                                                <td class="money-value">
                                                    {{ $fmtMoney((float) ($grandMoneyAll['hasil'] ?? 0.0)) }}
                                                </td>
                                                <td class="money-flag-inline">
                                                    ({{ ((float) ($grandMoneyAll['hasil'] ?? 0.0)) < 0 ? 'RUGI' : 'LABA' }})
                                                    |
                                                    ({{ $fmtProfitPercent((float) ($grandMoneyAll['hasil'] ?? 0.0), (float) ($grandMoneyAll['st'] ?? 0.0)) }})
                                                </td>
                                            </tr>
                                        </table>
                                    </div>
                                </td>
                                <td class="summary-pair-right">
                                    <div class="group-summary-wrap">
                                        <table class="group-summary-table">
                                            <thead>
                                                <tr>
                                                    <th style="width: 36%;">Group</th>
                                                    <th style="width: 22%;">KBTon</th>
                                                    <th style="width: 22%;">STTon</th>
                                                    <th style="width: 20%;">%</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($grandSummaryRows as $summaryRow)
                                                    <tr
                                                        class="{{ $summaryRow['group'] === 'Total' ? 'group-summary-total' : '' }}">
                                                        <td>{{ $summaryRow['group'] }}</td>
                                                        <td class="num">
                                                            {{ $fmtDetail((float) $summaryRow['kb'], 2) }}</td>
                                                        <td class="num">
                                                            {{ $fmtDetail((float) $summaryRow['st'], 2) }}</td>
                                                        <td class="num">
                                                            {{ $fmtPercentDetail((float) $summaryRow['rendemen'], 1) }}
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </td>
                            </tr>
                        </table>
                    @endif
                </td>
            </tr>
        </table>
    </div>

    @include('reports.partials.pdf-footer-table')
</body>

</html>
