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
            font-size: 9px;
            line-height: 1.15;
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
            margin: 2px 0 20px 0;
            font-size: 12px;
            color: #636466;
        }

        .salesperson-header {
            font-size: 12px;
            font-weight: bold;
            font-style: italic;
            color: #9c111d;
            margin-top: 15px;
            margin-bottom: 4px;
        }

        .grand-total-header {
            font-size: 12px;
            font-weight: bold;
            color: #9c111d;
            margin-top: 15px;
            margin-bottom: 4px;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            page-break-inside: auto;
            border-spacing: 0;
            margin-bottom: 10px;
        }

        .data-table th,
        .data-table td {
            border-left: 1px solid #000;
            border-right: 1px solid #000;
            padding: 2px 3px;
            vertical-align: middle;
            word-wrap: break-word;
        }

        .data-table th {
            font-weight: bold;
            font-size: 8px;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            text-align: center;
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

        .number-negative {
            color: #9c111d;
        }

        .row-odd td {
            background: #c9d1df;
        }

        .row-even td {
            background: #eef2f8;
        }

        .subtotal-row td {
            font-weight: bold;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
        }

        .empty-row td {
            text-align: center;
            font-style: italic;
            background: #c9d1df;
            color: #9c111d;
            font-weight: bold;
            font-size: 10px;
            padding: 4px;
        }

        .header-value {
            font-weight: normal;
            text-align: right;
            border-bottom: 1px solid #000;
        }

        .header-target {
            font-weight: normal;
            text-align: right;
            white-space: nowrap;
            border-bottom: 1px solid #000;
        }

        .analysis-table {
            width: 100%;
            border-collapse: collapse;
            border-spacing: 0;
            margin-bottom: 10px;
        }

        .analysis-table th,
        .analysis-table td {
            border: 1px solid #000;
            padding: 2px 4px;
            vertical-align: middle;
        }

        .analysis-table th {
            font-weight: bold;
            font-size: 8px;
            text-align: center;
        }

        .section-header td {
            font-weight: bold;
            font-style: italic;
            color: #9c111d;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
        }

        .page-break {
            page-break-before: always;
        }
    </style>
</head>

<body>
    @php
        $sections = $reportData['sections'] ?? [];
        $grandTotalSection = $reportData['grand_total_section'] ?? null;
        $headerCompany = trim((string) ($company ?? ($reportData['company'] ?? '')));
        $headerTitle = trim((string) ($title ?? ($reportData['title'] ?? '')));
        $headerSubtitle = trim((string) ($reportData['period_label'] ?? ''));

        $grandTotals = $reportData['grand_totals'] ?? [];
        $grandTotalAllRp = $reportData['grand_total_all_rp'] ?? 0;

        $displayOrder = ['pf', 'pk1', 'pk2', 'enamel', 'fl', 'total'];
        $colLabels = [
            'total' => 'Total',
            'pf' => 'Plastik Furniture 1 & 2',
            'pk1' => 'Plastik Kabinet 1',
            'pk2' => 'Plastik Kabinet 2',
            'enamel' => 'Enamel',
            'fl' => 'Furniture Lipat',
        ];

        if (! function_exists('fmtNum')) {
            if (!function_exists('fmtNum_penjualan_per_kategori_barang_bulanan')) {
                function fmtNum_penjualan_per_kategori_barang_bulanan($value, $decimals = 0)
                {
                    $v = (float) $value;
                    if ($v == 0.0) {
                        return '0';
                    }
                    return number_format($v, $decimals, ',', '.');
                }
            }
        }

        if (! function_exists('fmtPct')) {
            function fmtPct($value)
            {
                return number_format((float) $value, 2, ',', '.') . '%';
            }
        }

        if (! function_exists('devClass')) {
            function devClass($value)
            {
                return (float) $value < 0 ? 'number-negative' : '';
            }
        }

        if (! function_exists('getQty')) {
            function getQty($r, $k)
            {
                return $k === 'total' ? $r['total_qty'] : $r[$k]['qty'];
            }
        }

        if (! function_exists('getRp')) {
            function getRp($r, $k)
            {
                return $k === 'total' ? $r['total_rp'] : $r[$k]['rp'];
            }
        }

        if (! function_exists('getDev')) {
            function getDev($r, $k)
            {
                if ($k === 'total') {
                    return $r['total_dev'];
                }
                return $r[$k . '_dev'];
            }
        }
    @endphp

    <h1 class="report-companyTitle">{{ $headerCompany }}</h1>
    <h1 class="report-title">{{ $headerTitle }}</h1>
    @if ($headerSubtitle !== '')
        <p class="report-subtitle">{{ $headerSubtitle }}</p>
    @endif

    @if (count($sections) > 0)
        @php $sectionIndex = 0; @endphp
        @foreach ($sections as $section)
            @php
                $sectionIndex++;
                $subtotals = [];
                foreach ($displayOrder as $k) {
                    if ($k === 'total') {
                        $subtotals[$k . '_qty'] = $section['total_qty_all'];
                        $subtotals[$k . '_rp'] = $section['total_rp_all'];
                        $subtotals[$k . '_dev'] = $section['total_dev'] ?? 0;
                    } else {
                        $subtotals[$k . '_qty'] = $section['cat_totals'][$k]['qty'];
                        $subtotals[$k . '_rp'] = $section['cat_totals'][$k]['rp'];
                        $subtotals[$k . '_dev'] = $section['cat_totals'][$k]['dev'] ?? 0;
                    }
                }
            @endphp

            @if ($sectionIndex > 1)
                <div class="page-break"></div>
            @endif

            <div class="salesperson-header">{{ $section['sales_name'] }}</div>

            {{-- Main Data Table with 4-row header (value, target, label, sub-header) --}}
            <table class="data-table">
                <thead>
                    {{-- Row 1: Category monthly target values --}}
                    <tr>
                        <th rowspan="4" style="width: 3%;">Tgl</th>
                        @foreach ($displayOrder as $k)
                            @php $headerRp = $k === 'total' ? ($section['monthly_target_total'] ?? 0) : ($section['monthly_target'][$k] ?? 0); @endphp
                            <th colspan="3" class="header-value">{{ fmtNum_penjualan_per_kategori_barang_bulanan($headerRp) }}</th>
                        @endforeach
                    </tr>
                    {{-- Row 2: Target Perhari --}}
                    <tr>
                        @foreach ($displayOrder as $k)
                            @php
                                $targetVal = $k === 'total' ? $section['target_total_per_hari'] : $section['target_per_hari'][$k];
                            @endphp
                            <th colspan="3" class="header-target">Target Perhari {{ fmtNum_penjualan_per_kategori_barang_bulanan($targetVal) }}</th>
                        @endforeach
                    </tr>
                    {{-- Row 3: Category names --}}
                    <tr>
                        @foreach ($displayOrder as $k)
                            <th colspan="3">{{ $colLabels[$k] }}</th>
                        @endforeach
                    </tr>
                    {{-- Row 4: Sub-headers --}}
                    <tr>
                        @foreach ($displayOrder as $k)
                            <th>Qty</th>
                            <th>Rp</th>
                            <th>Lebih (Kurang)</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @php $globalRow = 0; @endphp
                    @foreach ($section['daily_records'] as $rec)
                        @php $globalRow++; @endphp
                        <tr class="{{ $globalRow % 2 === 0 ? 'row-even' : 'row-odd' }}">
                            <td class="center">{{ $rec['day'] }}</td>
                            @foreach ($displayOrder as $k)
                                <td class="number">{{ fmtNum_penjualan_per_kategori_barang_bulanan(getQty($rec, $k)) }}</td>
                                <td class="number">{{ fmtNum_penjualan_per_kategori_barang_bulanan(getRp($rec, $k)) }}</td>
                                <td class="number {{ devClass(getDev($rec, $k)) }}">{{ fmtNum_penjualan_per_kategori_barang_bulanan(getDev($rec, $k)) }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                    {{-- Subtotal --}}
                    <tr class="subtotal-row">
                        <td class="center">Total</td>
                        @foreach ($displayOrder as $k)
                            <td class="number">{{ fmtNum_penjualan_per_kategori_barang_bulanan($subtotals[$k . '_qty']) }}</td>
                            <td class="number">{{ fmtNum_penjualan_per_kategori_barang_bulanan($subtotals[$k . '_rp']) }}</td>
                            <td class="number {{ devClass($subtotals[$k . '_dev']) }}">{{ fmtNum_penjualan_per_kategori_barang_bulanan($subtotals[$k . '_dev']) }}</td>
                        @endforeach
                    </tr>
                </tbody>
            </table>

            {{-- Weekly Analysis Table --}}
            @php $weeklyAnalysis = $section['weekly_analysis'] ?? []; @endphp
            @if (count($weeklyAnalysis) > 0)
                @php $weeks = [1, 2, 3, 4, 5]; @endphp
                <table class="analysis-table">
                    <thead>
                        <tr>
                            <th rowspan="2">Item Barang</th>
                            <th rowspan="2">Target</th>
                            @foreach ($weeks as $w)
                                <th colspan="2">Minggu {{ $w }}</th>
                            @endforeach
                            <th colspan="2">Total</th>
                        </tr>
                        <tr>
                            @for ($i = 0; $i <= count($weeks); $i++)
                                <th>Penjualan</th>
                                <th>%</th>
                            @endfor
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($weeklyAnalysis as $waRow)
                            @php
                                $isTotal = $waRow['category'] === 'total';
                                $weekSum = 0;
                                foreach ($weeks as $w) {
                                    $weekSum += $waRow['weeks'][$w]['penjualan'];
                                }
                                $totalPct = ($waRow['target'] ?? 0) > 0 ? ($weekSum / $waRow['target'] * 100) : 0;
                            @endphp
                            <tr class="{{ $isTotal ? 'subtotal-row' : '' }}">
                                <td>{{ $isTotal ? 'Total' : ($colLabels[$waRow['category']] ?? $waRow['category']) }}</td>
                                <td class="number">{{ fmtNum_penjualan_per_kategori_barang_bulanan($waRow['target']) }}</td>
                                @foreach ($weeks as $w)
                                    <td class="number">{{ fmtNum_penjualan_per_kategori_barang_bulanan($waRow['weeks'][$w]['penjualan']) }}</td>
                                    <td class="number">{{ fmtPct($waRow['weeks'][$w]['pct']) }}</td>
                                @endforeach
                                <td class="number">{{ fmtNum_penjualan_per_kategori_barang_bulanan($weekSum) }}</td>
                                <td class="number">{{ fmtPct($totalPct) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif

            {{-- Analisa Penjualan Harian Table --}}
            @php $dailyAnalysis = $section['daily_analysis'] ?? []; @endphp
            @if (count($dailyAnalysis) > 0)
                <table class="analysis-table">
                    <thead>
                        <tr>
                            <th colspan="4" class="section-header">Analisa Penjualan Harian</th>
                        </tr>
                        <tr>
                            <th>Item Barang</th>
                            <th>Rata - Rata</th>
                            <th>Terendah</th>
                            <th>Tertinggi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($dailyAnalysis as $daRow)
                            @php
                                $isTotal = $daRow['category'] === 'total';
                            @endphp
                            <tr class="{{ $isTotal ? 'subtotal-row' : '' }}">
                                <td>{{ $isTotal ? 'Total' : ($colLabels[$daRow['category']] ?? $daRow['category']) }}</td>
                                <td class="number">{{ fmtNum_penjualan_per_kategori_barang_bulanan($daRow['rata_rata']) }}</td>
                                <td class="number">{{ fmtNum_penjualan_per_kategori_barang_bulanan($daRow['terendah']) }}</td>
                                <td class="number">{{ fmtNum_penjualan_per_kategori_barang_bulanan($daRow['tertinggi']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        @endforeach

        {{-- Grand Total Section --}}
        @if ($grandTotalSection !== null)
            @php
                $gt = $grandTotalSection;
                $gtSubtotals = [];
                foreach ($displayOrder as $k) {
                    if ($k === 'total') {
                        $gtSubtotals[$k . '_qty'] = $gt['total_qty_all'];
                        $gtSubtotals[$k . '_rp'] = $gt['total_rp_all'];
                        $gtSubtotals[$k . '_dev'] = $gt['total_dev'] ?? 0;
                    } else {
                        $gtSubtotals[$k . '_qty'] = $gt['cat_totals'][$k]['qty'];
                        $gtSubtotals[$k . '_rp'] = $gt['cat_totals'][$k]['rp'];
                        $gtSubtotals[$k . '_dev'] = $gt['cat_totals'][$k]['dev'] ?? 0;
                    }
                }
            @endphp

            <div class="page-break"></div>
            <div class="grand-total-header">Grand Total :</div>

            {{-- GT Main Data Table --}}
            <table class="data-table">
                <thead>
                    <tr>
                        <th rowspan="4" style="width: 3%;">Tgl</th>
                        @foreach ($displayOrder as $k)
                            @php $headerRp = $k === 'total' ? ($gt['monthly_target_total'] ?? 0) : ($gt['monthly_target'][$k] ?? 0); @endphp
                            <th colspan="3" class="header-value">{{ fmtNum_penjualan_per_kategori_barang_bulanan($headerRp) }}</th>
                        @endforeach
                    </tr>
                    <tr>
                        @foreach ($displayOrder as $k)
                            @php
                                $targetVal = $k === 'total' ? $gt['target_total_per_hari'] : $gt['target_per_hari'][$k];
                            @endphp
                            <th colspan="3" class="header-target">Target Perhari {{ fmtNum_penjualan_per_kategori_barang_bulanan($targetVal) }}</th>
                        @endforeach
                    </tr>
                    <tr>
                        @foreach ($displayOrder as $k)
                            <th colspan="3">{{ $colLabels[$k] }}</th>
                        @endforeach
                    </tr>
                    <tr>
                        @foreach ($displayOrder as $k)
                            <th>Qty</th>
                            <th>Penjualan</th>
                            <th>Lebih (Kurang)</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @php $gtRow = 0; @endphp
                    @foreach ($gt['daily_records'] as $rec)
                        @php $gtRow++; @endphp
                        <tr class="{{ $gtRow % 2 === 0 ? 'row-even' : 'row-odd' }}">
                            <td class="center">{{ $rec['day'] }}</td>
                            @foreach ($displayOrder as $k)
                                <td class="number">{{ fmtNum_penjualan_per_kategori_barang_bulanan(getQty($rec, $k)) }}</td>
                                <td class="number">{{ fmtNum_penjualan_per_kategori_barang_bulanan(getRp($rec, $k)) }}</td>
                                <td class="number {{ devClass(getDev($rec, $k)) }}">{{ fmtNum_penjualan_per_kategori_barang_bulanan(getDev($rec, $k)) }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                    <tr class="subtotal-row">
                        <td class="center">Total :</td>
                        @foreach ($displayOrder as $k)
                            <td class="number">{{ fmtNum_penjualan_per_kategori_barang_bulanan($gtSubtotals[$k . '_qty']) }}</td>
                            <td class="number">{{ fmtNum_penjualan_per_kategori_barang_bulanan($gtSubtotals[$k . '_rp']) }}</td>
                            <td class="number {{ devClass($gtSubtotals[$k . '_dev']) }}">{{ fmtNum_penjualan_per_kategori_barang_bulanan($gtSubtotals[$k . '_dev']) }}
                            </td>
                        @endforeach
                    </tr>
                </tbody>
            </table>

            {{-- GT Weekly Analysis --}}
            @php $gtWeekly = $gt['weekly_analysis'] ?? []; @endphp
            @if (count($gtWeekly) > 0)
                @php $weeks = [1, 2, 3, 4, 5]; @endphp
                <table class="analysis-table">
                    <thead>
                        <tr>
                            <th rowspan="2">Item Barang</th>
                            <th rowspan="2">Target</th>
                            @foreach ($weeks as $w)
                                <th colspan="2">Minggu {{ $w }}</th>
                            @endforeach
                            <th colspan="2">Total</th>
                        </tr>
                        <tr>
                            @for ($i = 0; $i <= count($weeks); $i++)
                                <th>Penjualan</th>
                                <th>%</th>
                            @endfor
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($gtWeekly as $waRow)
                            @php
                                $isTotal = $waRow['category'] === 'total';
                                $weekSum = 0;
                                foreach ($weeks as $w) {
                                    $weekSum += $waRow['weeks'][$w]['penjualan'];
                                }
                                $totalPct = ($waRow['target'] ?? 0) > 0 ? ($weekSum / $waRow['target'] * 100) : 0;
                            @endphp
                            <tr class="{{ $isTotal ? 'subtotal-row' : '' }}">
                                <td>{{ $isTotal ? 'Total' : ($colLabels[$waRow['category']] ?? $waRow['category']) }}</td>
                                <td class="number">{{ fmtNum_penjualan_per_kategori_barang_bulanan($waRow['target']) }}</td>
                                @foreach ($weeks as $w)
                                    <td class="number">{{ fmtNum_penjualan_per_kategori_barang_bulanan($waRow['weeks'][$w]['penjualan']) }}</td>
                                    <td class="number">{{ fmtPct($waRow['weeks'][$w]['pct']) }}</td>
                                @endforeach
                                <td class="number">{{ fmtNum_penjualan_per_kategori_barang_bulanan($weekSum) }}</td>
                                <td class="number">{{ fmtPct($totalPct) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif

            {{-- GT Analisa Harian --}}
            @php $gtDaily = $gt['daily_analysis'] ?? []; @endphp
            @if (count($gtDaily) > 0)
                <table class="analysis-table">
                    <thead>
                        <tr>
                            <th colspan="4" class="section-header">Analisa Penjualan Harian</th>
                        </tr>
                        <tr>
                            <th>Item Barang</th>
                            <th>Rata - Rata</th>
                            <th>Terendah</th>
                            <th>Tertinggi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($gtDaily as $daRow)
                            @php
                                $isTotal = $daRow['category'] === 'total';
                            @endphp
                            <tr class="{{ $isTotal ? 'subtotal-row' : '' }}">
                                <td>{{ $isTotal ? 'Total' : ($colLabels[$daRow['category']] ?? $daRow['category']) }}</td>
                                <td class="number">{{ fmtNum_penjualan_per_kategori_barang_bulanan($daRow['rata_rata']) }}</td>
                                <td class="number">{{ fmtNum_penjualan_per_kategori_barang_bulanan($daRow['terendah']) }}</td>
                                <td class="number">{{ fmtNum_penjualan_per_kategori_barang_bulanan($daRow['tertinggi']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        @endif
    @else
        <table class="data-table">
            <tbody>
                <tr class="empty-row">
                    <td>Tidak ada data penjualan per kategori barang bulanan.</td>
                </tr>
            </tbody>
        </table>
    @endif

    @include('ascends.shared.partials.report-footer')
</body>

</html>
