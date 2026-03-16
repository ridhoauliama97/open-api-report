<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
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

        .section-title {
            margin: 8px auto 4px;
            font-size: 11px;
            font-weight: bold;
            width: 100%;
        }

        .container-fluid {
            width: 100%;
            padding: 0;
            margin: 0;
        }

        .table-responsive {
            width: 100%;
            overflow-x: auto;
            margin-bottom: 6px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
            table-layout: fixed;
        }

        .report-table {
            border-collapse: collapse;
            border-spacing: 0;
            border: 2px solid #000;
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
        }

        th {
            text-align: center;
            font-weight: bold;
            background: #fff;
        }

        td.center {
            text-align: center;
        }

        td.number {
            text-align: right;
            font-family: "Calibri", "DejaVu Sans", sans-serif;
        }

        .headers-row th {
            font-weight: bold;
            font-size: 11px;
            border-top: 0;
            border-bottom: 1px solid #000;
        }

        .col-total {
            font-weight: bold;
        }

        .totals-row td {
            font-weight: bold;
        }

        .row-odd td {
            background: #c9d1df !important;
        }

        .row-even td {
            background: #eef2f8 !important;
        }

        /* Hide horizontal lines between data rows, keep vertical grid lines. */
        .report-table tbody tr.data-row td {
            border-top: 0 !important;
            border-bottom: 0 !important;
            border-left: 1px solid #000 !important;
            border-right: 1px solid #000 !important;
        }

        /* Keep totals row fully bordered. */
        .report-table tbody tr.totals-row td {
            border: 1px solid #000 !important;
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
        @include('reports.partials.pdf-footer-table-style')
    </style>
</head>

<body>
    @php
        $data = is_array($reportData ?? null) ? $reportData : [];
        $periods = is_array($data['periods'] ?? null) ? $data['periods'] : [];
        $summary = is_array($data['summary'] ?? null) ? $data['summary'] : [];
        $fmt = static fn(float $value): string => number_format($value, 2, '.', ',');
        $fmtBlankZero = static fn(float $value): string => abs($value) < 0.0000001
            ? ''
            : number_format($value, 2, '.', ',');
        $start = \Carbon\Carbon::parse($startDate)->locale('id')->translatedFormat('d-M-y');
        $end = \Carbon\Carbon::parse($endDate)->locale('id')->translatedFormat('d-M-y');

        $generatedByName = $generatedBy?->name ?? 'sistem';
        $generatedAtText = $generatedAt->copy()->locale('id')->translatedFormat('d-M-y H:i');

        $dateKeys = [];
        foreach ($periods as $period) {
            $raw = (string) ($period['key'] ?? ($period['label'] ?? ''));
            if ($raw !== '') {
                $dateKeys[] = $raw;
            }
        }
        $dateKeys = array_values(array_unique($dateKeys));
        sort($dateKeys);

        $dateLabels = [];
        foreach ($dateKeys as $dateKey) {
            try {
                $dateLabels[$dateKey] = \Carbon\Carbon::parse($dateKey)->locale('id')->translatedFormat('d-M');
            } catch (\Throwable $e) {
                $dateLabels[$dateKey] = $dateKey;
            }
        }

        $matrix = [];
        foreach ($periods as $period) {
            $dateKey = (string) ($period['key'] ?? ($period['label'] ?? ''));
            foreach ($period['rows'] ?? [] as $row) {
                $supplier = trim((string) ($row['NmSupplier'] ?? 'Tanpa Supplier'));
                $ton = (float) ($row['TonBerat'] ?? 0.0);
                if (!isset($matrix[$supplier])) {
                    $matrix[$supplier] = [
                        'supplier' => $supplier,
                        'by_date' => [],
                        'total' => 0.0,
                    ];
                }
                $matrix[$supplier]['by_date'][$dateKey] = ($matrix[$supplier]['by_date'][$dateKey] ?? 0.0) + $ton;
                $matrix[$supplier]['total'] += $ton;
            }
        }

        $supplierRows = array_values($matrix);
        usort($supplierRows, static function (array $a, array $b): int {
            $cmp = ($b['total'] ?? 0.0) <=> ($a['total'] ?? 0.0);
            if ($cmp !== 0) {
                return $cmp;
            }
            return strnatcasecmp((string) ($a['supplier'] ?? ''), (string) ($b['supplier'] ?? ''));
        });

        $columnTotals = array_fill_keys($dateKeys, 0.0);
        foreach ($supplierRows as $row) {
            foreach ($dateKeys as $dateKey) {
                $columnTotals[$dateKey] += (float) ($row['by_date'][$dateKey] ?? 0.0);
            }
        }
        $grandTotal = array_sum($columnTotals);
    @endphp

    <h1 class="report-title">Laporan Timeline KB - Harian (Rambung)</h1>
    <p class="report-subtitle">Periode {{ $start }} s/d {{ $end }}</p>

    <div class="container-fluid">
        <div class="table-responsive">
            <table class="report-table">
                <thead>
                    <tr class="headers-row">
                        <th class="col-left" style="width: 36px;">No</th>
                        <th class="col-left" style="width: 190px; text-align: left;">Nama Supplier</th>
                        @foreach ($dateKeys as $dateKey)
                            <th>{{ $dateLabels[$dateKey] ?? $dateKey }}</th>
                        @endforeach
                        <th class="col-total" style="width: 72px;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($supplierRows as $row)
                        <tr class="data-row {{ $loop->odd ? 'row-odd' : 'row-even' }}">
                            <td class="center col-left">{{ $loop->iteration }}</td>
                            <td class="col-left" style="text-align: left;">{{ $row['supplier'] ?? '' }}</td>
                            @foreach ($dateKeys as $dateKey)
                                <td class="number">{{ $fmtBlankZero((float) ($row['by_date'][$dateKey] ?? 0.0)) }}</td>
                            @endforeach
                            <td class="number col-total">{{ $fmtBlankZero((float) ($row['total'] ?? 0.0)) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ 3 + count($dateKeys) }}" class="center">Tidak ada data.</td>
                        </tr>
                    @endforelse

                    @if ($supplierRows !== [])
                        <tr class="totals-row">
                            <td colspan="2" class="center col-left">Total</td>
                            @foreach ($dateKeys as $dateKey)
                                <td class="number">{{ $fmtBlankZero((float) ($columnTotals[$dateKey] ?? 0.0)) }}</td>
                            @endforeach
                            <td class="number col-total">{{ $fmtBlankZero((float) $grandTotal) }}</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    @include('reports.partials.pdf-footer-table')
</body>

</html>
