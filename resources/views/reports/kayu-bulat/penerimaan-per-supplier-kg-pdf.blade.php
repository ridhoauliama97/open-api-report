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

        .summary-title {
            margin: 10px 0 4px;
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

        .notes-line {
            margin: 0 0 2px;
            font-size: 10px;
        }
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
                        <th rowspan="2" style="width: 3%;">No</th>
                        <th rowspan="2" style="width: 17%;">Nama Supplier</th>
                        <th rowspan="2" style="width: 6%;">Jmlh Truk</th>
                        @foreach ($groupNames as $groupName)
                            <th colspan="2">{{ $groupName }}</th>
                        @endforeach
                        <th rowspan="2" style="width: 8%;">Total (Kg)</th>
                        <th rowspan="2" style="width: 7%;">Rasio</th>
                    </tr>
                    <tr class="headers-row">
                        @foreach ($groupNames as $groupName)
                            <th style="width: 8%;">Kg</th>
                            <th style="width: 8%;">%</th>
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
</body>

</html>
