<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif:ital,wght@0,100..900;1,100..900&display=swap"
        rel="stylesheet">
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
        .diagram-kategori-table, .group-summary-table, .mini-table, .money-table, .rendemen-total-table, .report-table, .summary-pair-table, .summary-rendemen-table {
            border: 1px solid #000;
            border-collapse: collapse;
            width: calc(100% - 2px);
            line-height: inherit;
        }
.diagram-kategori-table th, .diagram-kategori-table td, .group-summary-table th, .group-summary-table td, .mini-table th, .mini-table td, .money-table th, .money-table td, .rendemen-total-table th, .rendemen-total-table td, .report-table th, .report-table td, .summary-pair-table th, .summary-pair-table td, .summary-rendemen-table th, .summary-rendemen-table td {
            border: 1px solid #000;
            word-wrap: break-word;
            padding: 2px;
            vertical-align: top;
        }

        .diagram-kategori-table th { font-weight: bold; background-color: #eef2f8; }

        .group-summary-table th { font-weight: bold; background-color: #eef2f8; }

        .mini-table th { font-weight: bold; background-color: #eef2f8; }

        .money-table th { font-weight: bold; background-color: #eef2f8; }

        .rendemen-total-table th { font-weight: bold; background-color: #eef2f8; }

        .report-table th { font-weight: bold; background-color: #eef2f8; }

        .summary-pair-table th { font-weight: bold; background-color: #eef2f8; }

        .summary-rendemen-table th { font-weight: bold; background-color: #eef2f8; }
.bottom-layout, .btul-layout, .diagram-frame, .meta-table, .ringkasan-table, .summary-frame-table, .summary-section-heading-table {
            border-collapse: collapse;
        }
.bottom-layout td, .btul-layout td, .diagram-frame td, .meta-table td, .ringkasan-table td, .summary-frame-table td, .summary-section-heading-table td { padding: 1px 2px; vertical-align: top; }
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
    <p class="report-subtitle">Periode {{ $start }} s/d {{ $end }}@isset($supplier)
        | Supplier: {{ $supplier }}
    @endisset
</p>

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

            $rowsByKategori = is_array($receipt['rows'] ?? null) ? $receipt['rows'] : ['input' => [], 'output' => []];
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
                            <span class="meta-attachment-label">No Pen ST</span> : {{ $noPen }}
                        @endif
                    </td>
                    <td class="meta-line">
                        @if ($supplier !== '')
                            <span class="meta-attachment-label">Supplier</span> : {{ $supplier }}
                        @endif
                    </td>
                    <td class="meta-line right">
                        @if ($noKb !== '')
                            <span class="meta-attachment-label">No.KB</span> : {{ $noKb }}
                            @if ($dateCreate !== '')
                                ({{ $formatDateLong($dateCreate) }})
                            @endif
                        @endif
                    </td>
                </tr>
                <tr>
                    <td class="meta-line">
                        <span class="meta-attachment-label">Tgl Penerimaan ST</span> :
                        {{ $tglPenerimaan !== '' ? $formatDateLong($tglPenerimaan) : ($dateLabel !== '' ? $dateLabel : '-') }}
                    </td>
                    <td class="meta-line">
                        @if ($jenisKayu !== '')
                            <span class="meta-attachment-label">Jenis Kayu</span> : {{ $jenisKayu }}
                        @endif
                    </td>
                    <td class="meta-line right">
                        @if ($meja !== '')
                            <span class="meta-attachment-label">Meja</span> : {{ $meja }}
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
                                    <td class="data-cell number" style="font-weight: bold;">
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
                <div class="rendemen-attachment">
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
                                            <td class="money-flag-attachment"> &nbsp;
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
                                                                        $isTotal = strpos($bLabel, 'Total') !== false;
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
        @php
            $inputGradeNames = array_map(static fn (array $l): string => (string) ($l['grade'] ?? ''), $inputRows);
            $inputKeterangan = implode(', ', array_filter($inputGradeNames));
            $outputGradeNames = array_map(static fn (array $l): string => (string) ($l['grade'] ?? ''), $outputRows);
            $outputKeterangan = implode(', ', array_filter($outputGradeNames));
            $chartData = is_array($receipt['chart_data'] ?? null) ? $receipt['chart_data'] : [];
            $chartColorByGrade = [];
            foreach ($chartData as $chartItem) {
                $chartColorByGrade[strtoupper(trim((string) ($chartItem['label'] ?? '')))] = (string) ($chartItem['color'] ?? '');
            }
        @endphp
        @if (($inputRows !== [] || $outputRows !== []) && ($receipt['chart_svg'] ?? '') !== '')
            <div class="diagram-section">
                <table class="diagram-frame">
                    <tr>
                        <td class="frame-banner">DIAGRAM RENDEMEN</td>
                    </tr>
                    <tr>
                        <td class="frame-content" style="padding: 0;">
                            <table style="width: 100%; border-collapse: collapse; table-layout: fixed;">
                                <tr>
                                    <td class="diagram-chart-cell" style="padding-bottom: 0;">
                                        <table class="rendemen-total-table">
                                            <tr>
                                                <td class="rendemen-total-label-cell">
                                                    <h2>
                                                        RENDEMEN TOTAL
                                                    </h2>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="rendemen-total-value-cell">
                                                    <h1>
                                                        {{ $fmtPercentTotal($rendemen, 1) }}
                                                    </h1>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                    <td class="diagram-side-cell"></td>
                                </tr>
                                <tr>
                                    <td class="diagram-chart-cell">
                                        <div class="diagram-chart-wrap">
                                            {!! $receipt['chart_svg'] ?? '' !!}
                                        </div>
                                    </td>
                                    <td class="diagram-side-cell">
                                        <table class="diagram-kategori-table" style="width: 100%;">
                                            <thead>
                                                <tr>
                                                    <th>KATEGORI</th>
                                                    <th>ST (TON)</th>
                                                    <th>%</th>
                                                    <th>RENDEMEN</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @php
                                                    $sortedOutputRows = $outputRows;
                                                    usort($sortedOutputRows, static fn (array $a, array $b): int => (float) ($b['percent'] ?? 0) <=> (float) ($a['percent'] ?? 0));
                                                @endphp
                                                @forelse ($sortedOutputRows as $bLine)
                                                    @php
                                                        $bGrade = trim((string) ($bLine['grade'] ?? ''));
                                                        $bColor = $chartColorByGrade[strtoupper($bGrade)] ?? '';
                                                        $bSt = (float) ($bLine['st'] ?? 0.0);
                                                        $bPct = (float) ($bLine['percent'] ?? 0.0);
                                                    @endphp
                                                    <tr>
                                                        <td class="left">
                                                            @if ($bColor !== '')
                                                                <span class="category-swatch" style="background-color: {{ $bColor }};">
                                                                    &nbsp;
                                                                </span>
                                                            @endif
                                                            &nbsp;&nbsp; {{ $bGrade }}
                                                        </td>
                                                        <td class="num">{{ $fmtDetail($bSt, 4) }}</td>
                                                        <td class="num">{{ $fmtPercentDetail($bPct, 1) }}</td>
                                                        <td class="num">{{ $fmtPercentTotal($rendemen, 1) }}</td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="4" style="text-align: center;">{{ $dash }}</td>
                                                    </tr>
                                                @endforelse
                                                <tr>
                                                    <td class="left total-row">TOTAL</td>
                                                    <td class="num total-row">{{ $fmtTotal($stTotal, 4) }}</td>
                                                    <td class="num total-row">100.0%</td>
                                                    <td class="num total-row">{{ $fmtPercentTotal($rendemen, 1) }}</td>
                                                </tr>
                                            </tbody>
                                        </table>

                                        <br> <br> <br>

                                            <table class="ringkasan-table" style="width:100%">
                                                <thead>
                                                    <tr>
                                                        <td class="ringkasan-head">Ringkasan</td>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td>Total Input (KB) : {{ $fmtTotal($kbTotal, 4) }} Ton</td>
                                                    </tr>
                                                    <tr>
                                                        <td>Total Output (ST) : {{ $fmtTotal($stTotal, 4) }} Ton</td>
                                                    </tr>
                                                    <tr>
                                                        <td>Rendemen : <span class="ringkasan-rendemen-highlight">{{ $fmtPercentTotal($rendemen, 1) }}</span></td>
                                                    </tr>
                                                </tbody>
                                                {{-- <tr>
                                                    <td colspan="3" class="ringkasan-head">RINGKASAN</td>
                                                </tr>
                                                <tr>
                                                    <td class="ringkasan-label">Total Input (KB)</td>
                                                    <td class="ringkasan-eq">&nbsp;</td>
                                                    <td class="ringkasan-value">{{ $fmtTotal($kbTotal, 4) }} Ton</td>
                                                </tr>
                                                <tr>
                                                    <td class="ringkasan-label">Total Output (ST)</td>
                                                    <td class="ringkasan-eq">&nbsp;</td>
                                                    <td class="ringkasan-value">{{ $fmtTotal($stTotal, 4) }} Ton</td>
                                                </tr>
                                                <tr>
                                                    <td class="ringkasan-label">Rendemen</td>
                                                    <td class="ringkasan-eq">&nbsp;</td>
                                                    <td class="ringkasan-value"><span class="ringkasan-rendemen-highlight">{{ $fmtPercentTotal($rendemen, 1) }}</span></td>
                                                </tr> --}}
                                                <tr>
                                                    <td class="ringkasan-formula">
                                                        Rendemen = (Total Output ST / KB) x 100% = ({{ $fmtTotal($stTotal, 4) }} / {{ $fmtTotal($kbTotal, 4) }}) x 100% = {{ $fmtPercentTotal($rendemen, 1) }}
                                                    </td>
                                                </tr>
                                            </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>

                <div class="keterangan-box">
                    <h3 class="keterangan-title">Keterangan:</h3>
                    <div class="keterangan-line">
                        <span class="keterangan-label">Input</span> : {{ $inputKeterangan !== '' ? $inputKeterangan : '-' }}
                    </div>
                    <div class="keterangan-line">
                        <span class="keterangan-label">Output</span> : {{ $outputKeterangan !== '' ? $outputKeterangan : '-' }}
                    </div>
                </div>
            </div>
        @endif
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

                            @if ($inputRows !== [] && $outputRows !== [])
                            <tr class="section-separator">
                                <td colspan="6"></td>
                            </tr>
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
                                        <td class="data-cell number" style="font-weight: bold;">
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

                            @if ($inputRows !== [] && $outputRows !== [])
                            <tr class="section-separator">
                                <td colspan="6"></td>
                            </tr>
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
                                        <td class="data-cell number" style="font-weight: bold;">
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

                            @if ($inputRows !== [] && $outputRows !== [])
                            <tr class="section-separator">
                                <td colspan="6"></td>
                            </tr>
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
                                        <td class="data-cell number" style="font-weight: bold;">
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
                                            <td class="money-flag-attachment">
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

</body>

</html>
