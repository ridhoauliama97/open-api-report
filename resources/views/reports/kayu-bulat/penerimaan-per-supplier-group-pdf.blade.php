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
            margin: 20mm 10mm 20mm 10mm;
            footer: html_reportFooter;
        }

        body {
            margin: 0;
            font-family: "Noto Serif", serif;
            font-size: 10px;
            line-height: 1.2;
            color: #000;
        }

        /* .sheet {
            padding: 8px;
        } */

        .report-title {
            text-align: center;
            margin: 0;
            font-size: 16px;
            font-weight: bold;
        }

        .report-subtitle {
            text-align: center;
            margin: 4px 0 20px;
            font-size: 12px;
            color: #636466;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 4px;
            page-break-inside: auto;
            table-layout: fixed;
        }

        .report-table {
            border-collapse: collapse;
            border-spacing: 0;
            border: 0;
        }

        thead {
            display: table-header-group;
        }

        tfoot {
            display: table-footer-group;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 3px 4px;
            vertical-align: middle;
        }

        th {
            text-align: center;
            font-weight: bold;
            font-size: 11px;
        }

        td.center {
            text-align: center;
        }

        td.number {
            text-align: right;
            font-family: "Calibri", "DejaVu Sans", sans-serif;
            white-space: nowrap;
        }

        .headers-row th {
            font-weight: bold;
            font-size: 11px;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            border-left: 1px solid #000;
            border-right: 1px solid #000;
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

        .ratio-row td {
            border: none;
            font-size: 10px;
            padding: 3px 2px;
        }

        .notes-title {
            margin: 10px 0 2px;
            font-size: 12px;
            font-weight: bold;
            text-decoration: underline;
        }

        .notes-line {
            margin: 0;
            font-size: 10px;
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

        @include('reports.partials.pdf-footer-table-style')
    </style>
</head>

<body>
    @php
        $groupNames = array_values($reportData['group_names'] ?? []);
        if ($groupNames === []) {
            $groupNames = ['GROUP'];
        }

        $suppliers = is_array($reportData['suppliers'] ?? null) ? $reportData['suppliers'] : [];
        $summary = is_array($reportData['summary'] ?? null) ? $reportData['summary'] : [];
        $groupTotals = is_array($summary['group_totals'] ?? null) ? $summary['group_totals'] : [];

        $generatedByName = $generatedBy?->name ?? 'sistem';
        $generatedAtText = $generatedAt->copy()->locale('id')->translatedFormat('d-M-y H:i');

        $start = \Carbon\Carbon::parse($startDate)->locale('id')->translatedFormat('d-M-y');
        $end = \Carbon\Carbon::parse($endDate)->locale('id')->translatedFormat('d-M-y');

        $fmtTon = static fn(float $value): string => number_format($value, 4, '.', ',');
        $fmtRatio = static fn(float $value): string => number_format($value, 1, '.', ',') . '%';
        $fmtTonBlankZero = static fn(float $value): string => abs($value) < 0.0000001
            ? ''
            : number_format($value, 4, '.', ',');
        $fmtRatioBlankZero = static fn(float $value): string => abs($value) < 0.0000001
            ? ''
            : number_format($value, 1, '.', ',') . '%';

        $totalTon = (float) ($summary['total_ton'] ?? 0.0);
        $totalTrucks = (int) ($summary['total_trucks'] ?? 0);
        $workingDays = (int) ($summary['working_days'] ?? 1);
        $dailyTon = (float) ($summary['daily_ton'] ?? 0.0);
        $estimated25Days = (float) ($summary['estimated_25_days_ton'] ?? 0.0);

        // Rangkuman operasional berdasarkan catatan referensi.
        $mejaCapacityTonPerDay = 3.52;
        $containerNeedTonPerMonth = 137.0;
        $stPerContainerTon = 58.0;
        $racipCapacityTonPerMejaPerDay = 3.0;
        $availableMeja = 10;

        $neededMejaPerDay = $mejaCapacityTonPerDay > 0 ? $dailyTon / $mejaCapacityTonPerDay : 0.0;
        $neededStTonPerDayForContainer = $workingDays > 0 ? $containerNeedTonPerMonth / $workingDays : 0.0;
        $neededStTonPerDayFor2Container = $workingDays > 0 ? ($stPerContainerTon * 2) / $workingDays : 0.0;
        $racipCapacityPerDay = $racipCapacityTonPerMejaPerDay * $availableMeja;
        $neededRacipDays = $racipCapacityPerDay > 0 ? $containerNeedTonPerMonth / $racipCapacityPerDay : 0.0;
    @endphp

    <div class="sheet">
        <h1 class="report-title">Laporan Penerimaan Kayu Bulat Per-Supplier Berdasarkan Group Kayu</h1>
        <p class="report-subtitle">Periode {{ $start }} s/d {{ $end }}</p>

        <table class="report-table">
            <thead>
                <tr class="headers-row">
                    <th rowspan="2" style="width: 5%;">No</th>
                    <th rowspan="2" style="width: 25%">Nama Supplier</th>
                    <th rowspan="2" style="width: 8%;">Jumlah Truk</th>
                    @foreach ($groupNames as $groupName)
                        <th colspan="2">{{ $groupName }}</th>
                    @endforeach
                    <th rowspan="2" style="width: 10%;">Total (Ton)</th>
                    <th rowspan="2" style="width: 10%;">Rasio</th>
                </tr>
                <tr class="headers-row">
                    @foreach ($groupNames as $groupName)
                        <th style="width: 10%;">Ton</th>
                        <th style="width: 10%;">%</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse ($suppliers as $row)
                    <tr class="data-row {{ $loop->odd ? 'row-odd' : 'row-even' }}">
                        <td class="center data-cell">{{ $loop->iteration }}</td>
                        <td class="data-cell">{{ (string) ($row['supplier'] ?? '') }}</td>
                        <td class="center data-cell">{{ (int) ($row['trucks'] ?? 0) }}</td>
                        @foreach ($groupNames as $groupName)
                            @php
                                $groupCell = is_array($row['groups'][$groupName] ?? null)
                                    ? $row['groups'][$groupName]
                                    : ['ton' => 0, 'ratio' => 0];
                            @endphp
                            <td class="number data-cell">{{ $fmtTonBlankZero((float) ($groupCell['ton'] ?? 0.0)) }}</td>
                            <td class="number data-cell">{{ $fmtRatioBlankZero((float) ($groupCell['ratio'] ?? 0.0)) }}
                            </td>
                        @endforeach
                        <td class="number data-cell" style="font-weight: bold;">
                            {{ $fmtTonBlankZero((float) ($row['total_ton'] ?? 0.0)) }}</td>
                        <td class="number data-cell" style="font-weight:bold;">
                            {{ $fmtRatioBlankZero((float) ($row['ratio'] ?? 0.0)) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td class="center" colspan="{{ 5 + count($groupNames) * 2 }}">Tidak ada data.</td>
                    </tr>
                @endforelse

                @if ($suppliers !== [])
                    <tr class="totals-row">
                        <td colspan="2" class="center">Total</td>
                        <td class="center">{{ $totalTrucks }}</td>
                        @foreach ($groupNames as $groupName)
                            @php
                                $groupTotal = (float) ($groupTotals[$groupName] ?? 0.0);
                                $groupShare = $totalTon > 0 ? ($groupTotal / $totalTon) * 100 : 0.0;
                            @endphp
                            <td class="number">{{ $fmtTon($groupTotal) }}</td>
                            <td class="number">{{ $fmtRatio($groupShare) }}</td>
                        @endforeach
                        <td class="number">{{ $fmtTon($totalTon) }}</td>
                        <td class="number">100.0%</td>
                    </tr>
                @endif
            </tbody>
        </table>

        @if ($suppliers !== [])
            <table>
                <tbody>
                    @foreach ($groupNames as $groupName)
                        @php
                            $groupTotal = (float) ($groupTotals[$groupName] ?? 0.0);
                            $groupShare = $totalTon > 0 ? ($groupTotal / $totalTon) * 100 : 0.0;
                        @endphp
                        <tr class="ratio-row">
                            <td style="width: 160px;">Rasio {{ $groupName }}</td>
                            <td style="width: 20px;" class="center">=</td>
                            <td class="number" style="width: 100px;">{{ $fmtRatio($groupShare) }}</td>
                            <td></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <p class="notes-title">Keterangan :</p>
        <p class="notes-line">{{ $start }} s/d {{ $end }} ({{ $workingDays }} hari): rata-rata masuk
            {{ $fmtTon($dailyTon) }} ton/hari, estimasi 25 hari {{ $fmtTon($estimated25Days) }} ton.</p>
        <p class="notes-line">Dengan kapasitas 1 meja/hari = {{ number_format($mejaCapacityTonPerDay, 2, '.', ',') }}
            ton, kebutuhan meja/hari sekitar {{ number_format($neededMejaPerDay, 4, '.', ',') }} meja.</p>
        <p class="notes-line">Target container: kebutuhan {{ number_format($containerNeedTonPerMonth, 0, '.', ',') }}
            ton/bulan setara {{ number_format($neededStTonPerDayForContainer, 4, '.', ',') }} ton ST/hari; kebutuhan 2
            container ({{ number_format($stPerContainerTon * 2, 0, '.', ',') }} ton) sekitar
            {{ number_format($neededStTonPerDayFor2Container, 4, '.', ',') }} ton ST/hari.</p>
        <p class="notes-line">Kapasitas racip: {{ number_format($racipCapacityTonPerMejaPerDay, 0, '.', ',') }}
            ton/meja/hari x {{ $availableMeja }} meja = {{ number_format($racipCapacityPerDay, 0, '.', ',') }}
            ton/hari, sehingga butuh sekitar {{ number_format($neededRacipDays, 1, '.', ',') }} hari racip untuk
            memenuhi 2 container.</p>
        <p class="notes-line">Rekap periode: {{ (int) ($summary['total_suppliers'] ?? 0) }} supplier,
            {{ $totalTrucks }} truk, total {{ $fmtTon($totalTon) }} ton.</p>
    </div>

    @include('reports.partials.pdf-footer-table')
</body>

</html>
