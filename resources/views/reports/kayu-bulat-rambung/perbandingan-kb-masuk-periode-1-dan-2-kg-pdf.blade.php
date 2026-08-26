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
            margin: 0;
            padding: 0;
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

        .section-title {
            margin: 14px 0 6px 0;
            font-size: 12px;
            font-weight: bold;
        }

        table {
            width: calc(100% - 2px);
            line-height: inherit;
            border-collapse: collapse;
            border-spacing: 0;
            border: 1px solid #000;
        }

        th,
        td {
            border: 1px solid #000;
            word-wrap: break-word;
            padding: 2px 2px;
        }

        td.center {
            text-align: center;
        }

        td.label {
            white-space: nowrap;
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

        .totals-row td {
            font-weight: bold;
        }

        .headers-row th {
            font-weight: bold;
        }

        td.left {
            text-align: left;
        }

        .trend-up {
            color: #0b8f3c;
            font-weight: bold;
        }

        .trend-down {
            color: #d11a2a;
            font-weight: bold;
        }

        .trend-flat {
            color: #636466;
            font-weight: bold;
        }
    </style>
</head>

<body>
    @php
        $data = is_array($reportData ?? null) ? $reportData : [];
        $groups = is_array($data['supplier_groups'] ?? null) ? $data['supplier_groups'] : [];
        $summary = is_array($data['summary'] ?? null) ? $data['summary'] : [];

        $generatedByName = $generatedBy?->name ?? 'sistem';
        $generatedAtText = $generatedAt->copy()->locale('id')->translatedFormat('d-M-y H:i');
        $period1StartText = \Carbon\Carbon::parse($period1StartDate)->locale('id')->translatedFormat('d-M-y');
        $period1EndText = \Carbon\Carbon::parse($period1EndDate)->locale('id')->translatedFormat('d-M-y');
        $period2StartText = \Carbon\Carbon::parse($period2StartDate)->locale('id')->translatedFormat('d-M-y');
        $period2EndText = \Carbon\Carbon::parse($period2EndDate)->locale('id')->translatedFormat('d-M-y');

        $formatNumber = static function ($value): string {
            $number = is_numeric($value)
                ? (float) $value
                : (is_string($value) && is_numeric(str_replace(',', '.', $value))
                    ? (float) str_replace(',', '.', $value)
                    : 0.0);
            if (abs($number) < 0.0000001) {
                return '';
            }
            return number_format($number, 4, '.', ',');
        };

        $formatPercent = static function ($value): string {
            $number = is_numeric($value) ? (float) $value : 0.0;
            if (abs($number) < 0.0000001) {
                return '';
            }
            return number_format($number, 0, '.', ',');
        };

        $grandTon1 = (float) ($summary['grand_ton1'] ?? 0.0);
        $grandTon2 = (float) ($summary['grand_ton2'] ?? 0.0);
        $grandPercent = (float) ($summary['grand_percent'] ?? 0.0);
    @endphp

    <h1 class="report-title">Laporan Perbanding KB Masuk Periode 1 dan 2 - Timbang KG</h1>
    <p class="report-subtitle">
        Periode 1: {{ $period1StartText }} s/d {{ $period1EndText }}
    </p>
    <p class="report-subtitle" style="margin-bottom: 14px;">
        Periode 2: {{ $period2StartText }} s/d {{ $period2EndText }}
    </p>

    <table class="report-table">
        <thead>
            <tr class="headers-row">
                <th>No</th>
                <th>Nama Supplier</th>
                <th>No.Tlp/HP</th>
                <th>Nama Grade</th>
                <th>Ton1</th>
                <th>Ton2</th>
                <th>Persen (%)</th>
            </tr>
        </thead>
        <tbody>
            @php $supplierNo = 0; @endphp
            @forelse ($groups as $group)
                @php
                    $supplier = (string) ($group['supplier'] ?? '');
                    $phone = (string) ($group['phone'] ?? '');
                    $lines = is_array($group['rows'] ?? null) ? $group['rows'] : [];
                    if ($lines === []) {
                        $lines = [['grade' => '', 'ton1' => 0.0, 'ton2' => 0.0, 'percent' => 0.0]];
                    }
                    $rowspan = max(1, count($lines));
                    $totals = is_array($group['totals'] ?? null) ? $group['totals'] : [];
                    $t1Total = (float) ($totals['ton1'] ?? 0.0);
                    $t2Total = (float) ($totals['ton2'] ?? 0.0);
                    $pTotal = (float) ($totals['percent'] ?? 0.0);

                    $supplierNo++;
                    $rowClass = $supplierNo % 2 === 1 ? 'row-odd' : 'row-even';
                @endphp

                @foreach ($lines as $line)
                    @php
                        $ton1 = (float) ($line['ton1'] ?? 0.0);
                        $ton2 = (float) ($line['ton2'] ?? 0.0);
                        $percent = (float) ($line['percent'] ?? 0.0);
                        $trendClass = $percent > 0 ? 'trend-up' : ($percent < 0 ? 'trend-down' : 'trend-flat');
                        $percentText = $formatPercent($percent);
                    @endphp

                    <tr class="data-row {{ $rowClass }}">
                        @if ($loop->first)
                            <td class="center data-cell" rowspan="{{ $rowspan }}">{{ $supplierNo }}</td>
                        @endif
                        @if ($loop->first)
                            <td class="left data-cell" rowspan="{{ $rowspan }}">{{ $supplier }}</td>
                            <td class="center data-cell" rowspan="{{ $rowspan }}">{{ $phone }}</td>
                        @endif
                        <td class="left data-cell">{{ (string) ($line['grade'] ?? '') }}</td>
                        <td class="number data-cell">{{ $formatNumber($ton1) }}</td>
                        <td class="number data-cell">{{ $formatNumber($ton2) }}</td>
                        <td class="number data-cell {{ $trendClass }}">
                            @if ($percentText !== '')
                                <span style="display:attachment-block; width:10px; text-align:center;">
                                    {!! $percent > 0 ? '&uarr;' : ($percent < 0 ? '&darr;' : '&#61;') !!}
                                </span>
                                {{ $percentText . '%' }}
                            @endif
                        </td>
                    </tr>
                @endforeach
            @empty
                <tr>
                    <td class="center" colspan="7">Tidak ada data.</td>
                </tr>
            @endforelse

            <tr class="totals-row">
                <td colspan="4" class="center">Total</td>
                <td class="number">{{ $formatNumber($grandTon1) }}</td>
                <td class="number">{{ $formatNumber($grandTon2) }}</td>
                <td
                    class="number {{ $grandPercent > 0 ? 'trend-up' : ($grandPercent < 0 ? 'trend-down' : 'trend-flat') }}">
                    @php $grandPercentText = $formatPercent($grandPercent); @endphp
                    @if ($grandPercentText !== '')
                        <span style="display:attachment-block; width:10px; text-align:center;">
                            {!! $grandPercent > 0 ? '&uarr;' : ($grandPercent < 0 ? '&darr;' : '&#61;') !!}
                        </span>
                        {{ $grandPercentText . '%' }}
                    @endif
                </td>
            </tr>
        </tbody>
    </table>
</body>

</html>
