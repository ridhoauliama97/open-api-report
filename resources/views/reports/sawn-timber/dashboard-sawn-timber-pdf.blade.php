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
            margin: 16mm 8mm 16mm 8mm;
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

        .report-note {
            margin: 0 0 8px 0;
            font-size: 8.5px;
            text-align: center;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            page-break-inside: auto;
        }

        .report-table {
            border-collapse: separate;
            border-spacing: 0;
            border-top: 0;
            border-right: 0;
            border-bottom: 1px solid #000;
            border-left: 1px solid #000;
            table-layout: fixed;
            margin-bottom: 10px;
        }

        thead {
            display: table-header-group;
        }

        tfoot {
            display: table-row-group;
        }

        tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 2px 3px;
            vertical-align: middle;
        }

        th {
            text-align: center;
            font-weight: bold;
            font-size: 8.5px;
            background: #fff;
        }

        .report-table td {
            font-size: 8.5px;
        }

        .report-table th:first-child,
        .report-table td:first-child {
            border-left: 0;
        }

        .report-table .headers-row th {
            border-top: 1px solid #000;
            border-right: 1px solid #000;
            border-bottom: 1px solid #000;
            border-left: 0;
        }

        .report-table tbody tr.data-row td.data-cell {
            border-top: 0;
            border-bottom: 0;
            border-left: 0;
            border-right: 1px solid #000;
        }

        .row-odd td {
            background: #c9d1df;
        }

        .row-even td {
            background: #eef2f8;
        }

        .center {
            text-align: center;
        }

        .label-cell {
            white-space: nowrap;
            font-weight: bold;
        }

        .number {
            text-align: right;
            white-space: nowrap;
            font-family: "Calibri", "DejaVu Sans", sans-serif;
        }

        .subheader-row th {
            font-size: 7.5px;
            font-weight: normal;
        }

        .total-stock-row td,
        .total-ctr-row td {
            font-weight: bold;
            font-size: 8.5px;
            border-top: 1px solid #000;
            border-right: 1px solid #000;
            border-bottom: 0;
            border-left: 0;
            background: #fff;
        }

        .total-stock-row td {
            border-bottom: 1px solid #000;
        }

        .total-ctr-row td {
            border-top: 0;
        }

        .overall-table {
            width: 180px;
            margin-top: 2px;
        }

        .overall-table td {
            font-weight: bold;
            font-size: 10px;
            border: 1px solid #000;
            padding: 3px 6px;
        }

        .overall-table .number {
            font-size: 10px;
        }

        .table-end-line td {
            border-top: 0 !important;
            border-right: 0 !important;
            border-bottom: 0 !important;
            border-left: 0 !important;
            padding: 0 !important;
            height: 0 !important;
            line-height: 0 !important;
            background: transparent !important;
        }

        @include('reports.partials.pdf-footer-table-style')
    </style>
</head>

<body>
    @php
        $chartData = is_array($chartData ?? null) ? $chartData : [];
        $dates = array_values(is_array($chartData['dates'] ?? null) ? $chartData['dates'] : []);
        $types = array_values(is_array($chartData['types'] ?? null) ? $chartData['types'] : []);
        $seriesByType = is_array($chartData['series_by_type'] ?? null) ? $chartData['series_by_type'] : [];
        $stockByType = is_array($chartData['stock_by_type'] ?? null) ? $chartData['stock_by_type'] : [];
        $stockTotals = is_array($chartData['stock_totals'] ?? null)
            ? $chartData['stock_totals']
            : ['s_akhir' => 0.0, 'ctr' => 0.0];
        $pdfTruncatedTypes = (bool) ($chartData['pdf_truncated_types'] ?? false);
        $pdfOriginalTypeCount = (int) ($chartData['pdf_original_type_count'] ?? count($types));
        $generatedByName = $generatedBy?->name ?? 'sistem';
        $generatedAtText = $generatedAt->copy()->locale('id')->translatedFormat('d-M-y H:i');

        $eps = 0.0000001;
        $fmt1 = static fn($value): string => number_format((float) ($value ?? 0), 1, '.', ',');
        $fmt2 = static fn($value): string => number_format((float) ($value ?? 0), 2, '.', ',');
        $fmtPct = static fn($value): string => number_format((float) ($value ?? 0), 1, '.', ',') . '%';
        $displayValue = static function ($value) use ($fmt1, $eps): string {
            return abs((float) ($value ?? 0)) < $eps ? '' : $fmt1($value);
        };
        $dateLabel = static function (string $value): string {
            try {
                return \Carbon\Carbon::parse($value)->locale('id')->translatedFormat('d-M');
            } catch (\Throwable $exception) {
                return $value;
            }
        };

        $typeCount = max(1, count($types));
        $dateColumnWidth = 7.5;
        $pairWidth = (100 - $dateColumnWidth) / $typeCount;
        $metricWidth = $pairWidth / 2;
    @endphp

    <h1 class="report-title">Laporan Dashboard Sawn Timber</h1>
    <p class="report-subtitle">
        Dari {{ \Carbon\Carbon::parse($startDate)->locale('id')->translatedFormat('d-M-y') }} s/d
        {{ \Carbon\Carbon::parse($endDate)->locale('id')->translatedFormat('d-M-y') }}
    </p>

    @if ($pdfTruncatedTypes)
        <p class="report-note">
            Menampilkan {{ count($types) }} dari {{ $pdfOriginalTypeCount }} jenis teratas agar render PDF tetap stabil.
        </p>
    @endif

    @if ($dates !== [] && $types !== [])
        <table class="report-table">
            <colgroup>
                <col style="width: {{ number_format($dateColumnWidth, 4, '.', ',') }}%;">
                @foreach ($types as $type)
                    <col style="width: {{ number_format($metricWidth, 4, '.', ',') }}%;">
                    <col style="width: {{ number_format($metricWidth, 4, '.', ',') }}%;">
                @endforeach
            </colgroup>
            <thead>
                <tr class="headers-row">
                    <th rowspan="2"></th>
                    @foreach ($types as $type)
                        <th colspan="2">{{ $type }}</th>
                    @endforeach
                </tr>
                <tr class="headers-row subheader-row">
                    @foreach ($types as $type)
                        <th>Masuk</th>
                        <th>Keluar</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($dates as $dateIndex => $date)
                    <tr class="data-row {{ $loop->odd ? 'row-odd' : 'row-even' }}">
                        <td class="data-cell label-cell">{{ $dateLabel((string) $date) }}</td>
                        @foreach ($types as $type)
                            @php
                                $series = is_array($seriesByType[$type] ?? null) ? $seriesByType[$type] : [];
                                $inValue = (float) ($series['in'][$dateIndex] ?? 0 ?: 0);
                                $outValue = (float) ($series['out'][$dateIndex] ?? 0 ?: 0);
                            @endphp
                            <td class="data-cell number">{{ $displayValue($inValue) }}</td>
                            <td class="data-cell number">{{ $displayValue($outValue) }}</td>
                        @endforeach
                    </tr>
                @endforeach
                <tr class="total-stock-row">
                    <td class="label-cell">S Akhir</td>
                    @foreach ($types as $type)
                        @php
                            $stockRow = is_array($stockByType[$type] ?? null) ? $stockByType[$type] : [];
                            $stockValue = (float) ($stockRow['s_akhir'] ?? 0);
                            $stockPercent =
                                (float) ($stockTotals['s_akhir'] ?? 0) > 0
                                    ? ($stockValue / (float) $stockTotals['s_akhir']) * 100
                                    : 0;
                        @endphp
                        <td class="number">{{ $displayValue($stockValue) }}</td>
                        <td class="number">{{ $stockValue === 0.0 ? '' : $fmtPct($stockPercent) }}</td>
                    @endforeach
                </tr>
                <tr class="total-ctr-row">
                    <td class="label-cell"># Ctr</td>
                    @foreach ($types as $type)
                        @php
                            $stockRow = is_array($stockByType[$type] ?? null) ? $stockByType[$type] : [];
                            $ctrValue = (float) ($stockRow['ctr'] ?? 0);
                        @endphp
                        <td colspan="2" class="number" style="text-align: center;">
                            {{ abs($ctrValue) < $eps ? '' : $fmt2($ctrValue) }}</td>
                    @endforeach
                </tr>
            </tbody>
            <tfoot>
                <tr class="table-end-line">
                    <td colspan="{{ 1 + count($types) * 2 }}"></td>
                </tr>
            </tfoot>
        </table>

        <div style="font-weight: bold; margin: 6px 0 4px 0;">Total</div>
        <table class="overall-table">
            <tbody>
                <tr>
                    <td>S Akhir</td>
                    <td class="number">{{ $fmt1($stockTotals['s_akhir'] ?? 0) }}</td>
                </tr>
                <tr>
                    <td># Ctr</td>
                    <td class="number">{{ $fmt2($stockTotals['ctr'] ?? 0) }}</td>
                </tr>
            </tbody>
        </table>
    @else
        <table class="report-table">
            <tbody>
                <tr>
                    <td class="center">Tidak ada data.</td>
                </tr>
            </tbody>
        </table>
    @endif

    @include('reports.partials.pdf-footer-table')
</body>

</html>
