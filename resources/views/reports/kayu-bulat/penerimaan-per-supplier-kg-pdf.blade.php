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
        }

        th {
            text-align: center;
            font-weight: 700;
            background: #ffffff;
            color: #000;
        }

        td.center {
            text-align: center;
        }

        td.number {
            text-align: right;
            white-space: nowrap;
            font-family: "Calibry", "Calibri", "DejaVu Sans", sans-serif;
        }

        .row-odd td {
            background: #c9d1df;
        }

        .row-even td {
            background: #eef2f8;
        }

        .totals-row td {
            font-weight: bold;
            font-size: 11px;
            border: 1px solid #000;
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

        .summary-page {
            page-break-before: auto;
            margin-top: 10px;
        }

        .summary-title {
            margin: 0 0 10px;
            font-size: 11px;
            font-weight: bold;
        }

        .summary-list,
        .notes-list {
            margin: 0;
            padding-left: 18px;
            font-size: 10px;
            line-height: 1.2;
        }

        .summary-list li,
        .notes-list li {
            margin: 0 0 2px;
        }

        .notes {
            margin-top: 10px;
        }

        .notes-line {
            margin: 0 0 2px;
            font-size: 10px;
        }

        @include('reports.partials.pdf-footer-table-style')
    </style>
</head>

<body>
    @php
        $data = is_array($reportData ?? null) ? $reportData : [];
        $suppliers = is_array($data['suppliers'] ?? null) ? $data['suppliers'] : [];
        $groupNames = is_array($data['group_names'] ?? null) ? $data['group_names'] : [];
        $summary = is_array($data['summary'] ?? null) ? $data['summary'] : [];
        $groupTotals = is_array($summary['group_totals'] ?? null) ? $summary['group_totals'] : [];
        $groupRatios = is_array($summary['group_ratios'] ?? null) ? $summary['group_ratios'] : [];
        $assumptions = is_array($summary['assumptions'] ?? null) ? $summary['assumptions'] : [];
        $calculations = is_array($summary['calculations'] ?? null) ? $summary['calculations'] : [];

        $generatedByName = $generatedBy?->name ?? 'sistem';
        $generatedAtText = $generatedAt->copy()->locale('id')->translatedFormat('d-M-y H:i');
        $start = \Carbon\Carbon::parse($startDate)->locale('id')->translatedFormat('d-M-y');
        $end = \Carbon\Carbon::parse($endDate)->locale('id')->translatedFormat('d-M-y');

        $fmtKg = static fn(float $value): string => number_format($value * 1000, 0, '.', ',');
        $fmtRatio = static fn(float $value): string => number_format($value, 0, '.', ',') . '%';
        $fmtRatio2 = static fn(float $value): string => number_format($value, 2, '.', ',') . '%';
        $dataCellStyle = 'border-top:none;border-bottom:none;border-left:1px solid #000;border-right:1px solid #000;';

        usort($suppliers, static function (array $left, array $right): int {
            return ((float) ($right['total_ton'] ?? 0.0)) <=> ((float) ($left['total_ton'] ?? 0.0));
        });
    @endphp

    <h1 class="report-title">Laporan Penerimaan Kayu Bulat Per-Supplier - Timbang KG</h1>
    <p class="report-subtitle">Periode {{ $start }} s/d {{ $end }}</p>

    <div class="container-fluid">
        <div class="table-responsive">
            <table class="table table-striped report-table">
                <thead>
                    <tr class="headers-row">
                        <th rowspan="2" style="width: 34px;">No</th>
                        <th rowspan="2" style="width: 190px;">Nama Supplier</th>
                        <th rowspan="2" style="width: 46px;">Jmlh Truk</th>
                        @foreach ($groupNames as $groupName)
                            <th colspan="2">{{ $groupName }}</th>
                        @endforeach
                        <th rowspan="2" style="width: 74px;">Total (Kg)</th>
                        <th rowspan="2" style="width: 62px;">Rasio</th>
                    </tr>
                    <tr class="headers-row">
                        @foreach ($groupNames as $groupName)
                            <th style="width: 72px;">Kg</th>
                            <th style="width: 48px;">%</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse ($suppliers as $supplier)
                        <tr class="data-row {{ $loop->odd ? 'row-odd' : 'row-even' }}">
                            <td class="center data-cell" style="{{ $dataCellStyle }}">{{ $loop->iteration }}</td>
                            <td class="data-cell" style="{{ $dataCellStyle }}">
                                {{ (string) ($supplier['supplier'] ?? '') }}</td>
                            <td class="center data-cell" style="{{ $dataCellStyle }}">
                                {{ (int) ($supplier['trucks'] ?? 0) }}
                            </td>
                            @foreach ($groupNames as $groupName)
                                @php
                                    $group = is_array($supplier['groups'][$groupName] ?? null)
                                        ? $supplier['groups'][$groupName]
                                        : ['ton' => 0.0, 'ratio' => 0.0];
                                @endphp
                                <td class="number data-cell" style="{{ $dataCellStyle }}">
                                    {{ $fmtKg((float) ($group['ton'] ?? 0.0)) }}</td>
                                <td class="number data-cell" style="{{ $dataCellStyle }}">
                                    {{ $fmtRatio((float) ($group['ratio'] ?? 0.0)) }}</td>
                            @endforeach
                            <td class="number data-cell" style="{{ $dataCellStyle }} font-weight:bold;">
                                {{ $fmtKg((float) ($supplier['total_ton'] ?? 0.0)) }}</td>
                            <td class="number data-cell" style="{{ $dataCellStyle }} font-weight:bold;">
                                {{ $fmtRatio((float) ($supplier['ratio'] ?? 0.0)) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ 5 + count($groupNames) * 2 }}" class="center">Tidak ada data.</td>
                        </tr>
                    @endforelse

                    @if ($suppliers !== [])
                        <tr class="totals-row">
                            <td colspan="3" style="text-align: center">Total</td>
                            @foreach ($groupNames as $groupName)
                                <td class="number">{{ $fmtKg((float) ($groupTotals[$groupName] ?? 0.0)) }}</td>
                                <td class="number"></td>
                            @endforeach
                            <td class="number">{{ $fmtKg((float) ($summary['total_ton'] ?? 0.0)) }}</td>
                            <td class="number">100%</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    <section class="summary-page">
        <h2 class="summary-title">Keterangan:</h2>
        <ul class="summary-list">
            <li>Jumlah Truk: {{ (int) ($summary['total_trucks'] ?? 0) }}</li>
            @foreach ($groupNames as $groupName)
                <li>Rasio {{ $groupName }}: {{ $fmtRatio2((float) ($groupRatios[$groupName] ?? 0.0)) }}</li>
            @endforeach
        </ul>

        <div class="notes">
            <p class="notes-line">{{ $start }} s/d {{ $end }} =
                {{ (int) ($summary['working_days'] ?? 0) }} hari,
                jumlah KB masuk per hari = {{ $fmtKg((float) ($summary['daily_ton'] ?? 0.0)) }} kg, dalam 25 hari
                estimasi masuk = {{ $fmtKg((float) ($summary['estimated_25_days_ton'] ?? 0.0)) }} kg.</p>

            <p class="notes-line"><strong>Asumsi:</strong></p>
            <ul class="notes-list">
                <li>Kapasitas racip 1 meja per hari =
                    {{ $fmtKg((float) ($assumptions['racip_per_meja_per_day'] ?? 0.0)) }} kg ST per hari.
                    Rendemen KB ke ST =
                    {{ number_format((float) ($assumptions['rendemen_kb_to_st'] ?? 0.0), 0, '.', ',') }}%.</li>
                <li>Konsumsi KB per meja per hari =
                    {{ $fmtKg((float) ($assumptions['consumption_per_meja_per_day'] ?? 0.0)) }} kg KB per hari.
                    Meja yang tersedia = {{ (int) ($assumptions['available_meja'] ?? 0) }} meja.</li>
                <li>Konsumsi KB per hari =
                    {{ $fmtKg((float) ($assumptions['consumption_per_day'] ?? 0.0)) }} kg KB per hari.</li>
            </ul>

            <p class="notes-line"><strong>Kalkulasi:</strong></p>
            <ul class="notes-list">
                <li>Untuk mengkonsumsi {{ $fmtKg((float) ($summary['estimated_25_days_ton'] ?? 0.0)) }} kg KB
                    diperlukan {{ number_format((float) ($calculations['needed_days'] ?? 0.0), 2, '.', ',') }} hari.
                </li>
                <li>Dalam horizon 25 hari dibutuhkan
                    {{ number_format((float) ($calculations['needed_meja_per_day'] ?? 0.0), 2, '.', ',') }}
                    meja sawmill per hari.</li>
            </ul>
        </div>
    </section>

    @include('reports.partials.pdf-footer-table')
</body>

</html>
