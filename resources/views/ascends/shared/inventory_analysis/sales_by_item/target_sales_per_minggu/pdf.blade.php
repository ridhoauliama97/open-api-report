<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
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

        @page {
            margin: 14mm 10mm 14mm 10mm;
            footer: html_reportFooter;
        }

        body {
            margin: 0;
            font-family: "Noto Serif", serif;
            font-size: 8px;
            line-height: 1.1;
            color: #000;
        }

        .report-companyTitle {
            text-align: center;
            margin: 0 0 4px 0;
            font-size: 18px;
            font-weight: bold;
        }

        .report-title {
            text-align: center;
            margin: 0;
            font-size: 16px;
            font-weight: bold;
        }

        .report-subtitle {
            text-align: center;
            margin: 2px 0 12px 0;
            font-size: 12px;
            color: #636466;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            page-break-inside: auto;
        }

        .data-table th,
        .data-table td {
            border-left: 1px solid #000;
            border-right: 1px solid #000;
            padding: 1px 2px;
            vertical-align: middle;
        }

        .data-table th {
            font-weight: bold;
            font-size: 7px;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            text-align: center;
        }

        .data-table td {
            font-size: 8px;
        }

        .center {
            text-align: center;
        }

        .number {
            text-align: right;
        }

        .nowrap {
            white-space: nowrap;
        }

        .row-odd td {
            background: #c9d1df;
        }

        .row-even td {
            background: #eef2f8;
        }

        .week-total td {
            font-weight: bold;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            font-size: 8px;
        }

        .cumulative-row td {
            font-weight: bold;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            font-size: 8px;
        }

        .empty-row td {
            text-align: center;
            font-style: italic;
            background: #c9d1df;
            font-weight: bold;
            color: #9c111d;
            font-size: 8px;
        }
    </style>
</head>

<body>
    @php
        $weekly = $reportData['weekly'] ?? [];
        $cumulative = $reportData['cumulative'] ?? [];
        $monthlyTarget = (float) ($reportData['monthly_target'] ?? 0);
        $generatedAtText = \Carbon\Carbon::parse($generatedAt ?? now())
            ->locale('id')->translatedFormat('d-M-y H:i');
        $generatedByName = trim((string) ($reportData['printed_by'] ?? ''));
        $headerCompany = trim((string) ($company ?? $reportData['company'] ?? ''));
        $headerSubtitle = trim((string) ($reportData['period_label'] ?? ''));

        $families = ['ENAMEL', 'FURNITURE LIPAT', 'PLASTIK FURNITURE 1', 'PLASTIK FURNITURE 2', 'PLASTIK KABINET 1', 'PLASTIK KABINET 2'];
        $familyLabels = ['ENAMEL', 'FL', 'PF1', 'PF2', 'PKB1', 'PKB2'];

        $fmt = function ($v) {
            return $v != 0 ? number_format($v, 2, ',', '.') : '-';
        };

        $fmtQty = function ($v) {
            return $v != 0 ? number_format($v, 1, ',', '.') : '-';
        };

        $fmtPersen = function ($v) {
            return $v != 0 ? number_format($v, 1, ',', '.') : '-';
        };
    @endphp

    <h1 class="report-companyTitle">{{ $headerCompany }}</h1>
    <h1 class="report-title">{{ $title ?? $reportData['title'] }}</h1>
    <p class="report-subtitle">{{ $headerSubtitle }}</p>

    @if (count($weekly) > 0)
        <table class="data-table">
            <thead>
                <tr>
                    <th rowspan="2" style="width: 6%">Week</th>
                    @foreach ($familyLabels as $fl)
                        <th colspan="3" style="width: 11%">{{ $fl }}</th>
                    @endforeach
                    <th colspan="3" style="width: 11%">Total</th>
                </tr>
                <tr>
                    @foreach ($families as $fam)
                        <th style="width: 3.5%">Qty</th>
                        <th style="width: 4%">Rp</th>
                        <th style="width: 3.5%">%</th>
                    @endforeach
                    <th style="width: 3.5%">Qty</th>
                    <th style="width: 4%">Rp</th>
                    <th style="width: 3.5%">%</th>
                </tr>
            </thead>
            <tbody>
                @for ($w = 1; $w <= 5; $w++)
                    @php
                        $wd = $weekly[$w] ?? null;
                        $rowClass = $w % 2 === 0 ? 'row-even' : 'row-odd';
                    @endphp
                    @if ($wd !== null)
                        <tr class="{{ $rowClass }}">
                            <td class="center">{{ $w }}</td>
                            @foreach ($families as $fam)
                                @php $fd = $wd['families'][$fam] ?? ['qty' => 0, 'rp' => 0, 'persen' => 0]; @endphp
                                <td class="number nowrap">{{ $fmtQty($fd['qty']) }}</td>
                                <td class="number nowrap">{{ $fmt($fd['rp']) }}</td>
                                <td class="number nowrap">{{ $fmtPersen($fd['persen']) }}</td>
                            @endforeach
                            <td class="number nowrap">{{ $fmtQty($wd['total']['qty']) }}</td>
                            <td class="number nowrap">{{ $fmt($wd['total']['rp']) }}</td>
                            <td class="number nowrap">{{ $fmtPersen($wd['total']['persen']) }}</td>
                        </tr>
                    @endif
                @endfor

                @php $weekTotal = $weekly['total'] ?? null; @endphp
                @if ($weekTotal !== null)
                    <tr class="week-total">
                        <td class="center">WEEK</td>
                        @foreach ($families as $fam)
                            @php $fd = $weekTotal['families'][$fam] ?? ['qty' => 0, 'rp' => 0, 'persen' => 0]; @endphp
                            <td class="number nowrap">{{ $fmtQty($fd['qty']) }}</td>
                            <td class="number nowrap">{{ $fmt($fd['rp']) }}</td>
                            <td class="number nowrap">{{ $fmtPersen($fd['persen']) }}</td>
                        @endforeach
                        <td class="number nowrap">{{ $fmtQty($weekTotal['total']['qty']) }}</td>
                        <td class="number nowrap">{{ $fmt($weekTotal['total']['rp']) }}</td>
                        <td class="number nowrap">{{ $fmtPersen($weekTotal['total']['persen']) }}</td>
                    </tr>
                @endif

                @php $cumIdx = 0; @endphp
                @foreach ($cumulative as $ck => $cd)
                    @php
                        $rowClass = $cumIdx % 2 === 0 ? 'row-even cumulative-row' : 'row-odd cumulative-row';
                        $cumIdx++;
                    @endphp
                    <tr class="{{ $rowClass }}">
                        <td class="center">Total {{ str_replace('w1_w', 'W1 s/d W', $ck) }}</td>
                        @foreach ($families as $fam)
                            @php $fd = $cd['families'][$fam] ?? ['qty' => 0, 'rp' => 0, 'persen' => 0]; @endphp
                            <td class="number nowrap">{{ $fmtQty($fd['qty']) }}</td>
                            <td class="number nowrap">{{ $fmt($fd['rp']) }}</td>
                            <td class="number nowrap">{{ $fmtPersen($fd['persen']) }}</td>
                        @endforeach
                        <td class="number nowrap">{{ $fmtQty($cd['total']['qty']) }}</td>
                        <td class="number nowrap">{{ $fmt($cd['total']['rp']) }}</td>
                        <td class="number nowrap">{{ $fmtPersen($cd['total']['persen']) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <table class="data-table">
            <tbody>
                <tr class="empty-row">
                    <td colspan="22">Tidak ada data penjualan.</td>
                </tr>
            </tbody>
        </table>
    @endif

    @include('ascends.shared.partials.report-footer')
</body>

</html>
