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
            margin: 10px 0 4px 0;
            font-size: 12px;
            font-weight: bold;
        }

        .group-title {
            margin: 8px 0 4px 0;
            font-size: 11px;
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
            border-collapse: separate;
            border-spacing: 0;
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
            font-weight: bold;
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

        .summary-list {
            margin: 0;
            padding-left: 18px;
            font-size: 10px;
            line-height: 1.2;
        }

        .summary-list li {
            margin: 0 0 2px;
        }
    </style>
</head>

<body>
    @php
        $data = is_array($reportData ?? null) ? $reportData : [];
        $rows = is_array($data['rows'] ?? null) ? $data['rows'] : [];
        $subRows = is_array($data['sub_rows'] ?? null) ? $data['sub_rows'] : [];
        $summary = is_array($data['summary'] ?? null) ? $data['summary'] : [];
        $fmt = static fn(float $value): string => number_format($value, 0, '.', ',');
        $fmtTon = static fn(float $value): string => number_format($value, 4, '.', ',');
        $fmtRatio = static fn(float $value): string => number_format($value, 2, '.', ',') . '%';
        $generatedByName = $generatedBy?->name ?? 'sistem';
        $generatedAtText = $generatedAt->copy()->locale('id')->translatedFormat('d-M-y H:i');
        $dataCellStyle = 'border-top:none;border-bottom:none;border-left:1px solid #000;border-right:1px solid #000;';

        $sortTruckValue = static function (mixed $truck): array {
            $raw = trim((string) $truck);
            $normalized = preg_replace('/[^0-9]/', '', $raw) ?? '';

            if ($normalized !== '' && is_numeric($normalized)) {
                return [0, (int) $normalized, $raw];
            }

            return [1, PHP_INT_MAX, $raw];
        };

        usort($rows, static function (array $a, array $b) use ($sortTruckValue): int {
            $left = $sortTruckValue($a['NoTruk'] ?? '');
            $right = $sortTruckValue($b['NoTruk'] ?? '');

            $compare = $left[0] <=> $right[0];
            if ($compare !== 0) {
                return $compare;
            }

            $compare = $left[1] <=> $right[1];
            if ($compare !== 0) {
                return $compare;
            }

            return strnatcasecmp((string) $left[2], (string) $right[2]);
        });

        $groupedRows = [];
        foreach ($rows as $row) {
            $truck = trim((string) ($row['NoTruk'] ?? ''));
            $groupKey = $truck !== '' ? $truck : 'Tanpa No Truk';
            $groupedRows[$groupKey][] = $row;
        }

        $groupSummaries = [];
        foreach ($groupedRows as $truck => $truckRows) {
            $groupSummaries[$truck] = [
                'total_bruto' => array_sum(
                    array_map(static fn(array $row): float => (float) ($row['Bruto'] ?? 0.0), $truckRows),
                ),
                'total_tara' => array_sum(
                    array_map(static fn(array $row): float => (float) ($row['Tara'] ?? 0.0), $truckRows),
                ),
                'total_berat' => array_sum(
                    array_map(static fn(array $row): float => (float) ($row['Berat'] ?? 0.0), $truckRows),
                ),
            ];
        }

        $grandTotal = (float) ($summary['total_berat'] ?? 0.0);
    @endphp

    <h1 class="report-title">Laporan Saldo Hidup Kayu Bulat - Timbang KG</h1>
    <p class="report-subtitle"></p>

    <div class="container-fluid">
        @forelse ($groupedRows as $truck => $truckRows)
            @php $truckSummary = $groupSummaries[$truck] ?? ['total_bruto' => 0.0, 'total_tara' => 0.0, 'total_berat' => 0.0]; @endphp
            <div class="group-title">No Truk: {{ $truck }}</div>
            <div class="table-responsive">
                <table class="table table-striped report-table">
                    <thead>
                        <tr class="headers-row">
                            <th style="width: 28px;">No</th>
                            <th style="width: 70px;">No KB</th>
                            <th style="width: 54px;">Tanggal</th>
                            <th style="width: 54px;">Jenis</th>
                            <th style="width: 88px;">Nama Grade</th>
                            <th style="width: 80px;">Suket</th>
                            <th style="width: 78px;">Supplier</th>
                            <th style="width: 55px;">Bruto</th>
                            <th style="width: 55px;">Tara</th>
                            <th style="width: 55px;">Berat (Ton)</th>
                        </tr>
                    </thead>
                    <tfoot>
                        <tr class="table-end-line">
                            <td colspan="10"></td>
                        </tr>
                    </tfoot>
                    <tbody>
                        @foreach ($truckRows as $row)
                            <tr class="data-row {{ $loop->odd ? 'row-odd' : 'row-even' }}">
                                <td class="center data-cell" style="{{ $dataCellStyle }}">{{ $loop->iteration }}</td>
                                <td class="data-cell" style="{{ $dataCellStyle }}">{{ $row['NoKayuBulat'] ?? '' }}</td>
                                <td class="center data-cell" style="{{ $dataCellStyle }}">{{ $row['DateCreate'] ?? '' }}
                                </td>
                                <td class="data-cell" style="{{ $dataCellStyle }}">{{ $row['JenisKayu'] ?? '' }}</td>
                                <td class="data-cell" style="{{ $dataCellStyle }}">{{ $row['NamaGrade'] ?? '' }}</td>
                                <td class="data-cell" style="{{ $dataCellStyle }}">{{ $row['Suket'] ?? '' }}</td>
                                <td class="data-cell" style="{{ $dataCellStyle }}">{{ $row['NmSupplier'] ?? '' }}</td>
                                <td class="number data-cell" style="{{ $dataCellStyle }}">
                                    {{ $fmt((float) ($row['Bruto'] ?? 0.0)) }}</td>
                                <td class="number data-cell" style="{{ $dataCellStyle }}">
                                    {{ $fmt((float) ($row['Tara'] ?? 0.0)) }}</td>
                                <td class="number data-cell" style="{{ $dataCellStyle }}">
                                    {{ $fmtTon((float) ($row['Berat'] ?? 0.0)) }}</td>
                            </tr>
                        @endforeach
                        <tr class="totals-row">
                            <td colspan="7" class="center">Total </td>
                            <td class="number">{{ $fmt($truckSummary['total_bruto']) }}</td>
                            <td class="number">{{ $fmt($truckSummary['total_tara']) }}</td>
                            <td class="number">{{ $fmtTon($truckSummary['total_berat']) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        @empty
            <div class="table-responsive">
                <table class="table table-striped report-table">
                    <tbody>
                        <tr>
                            <td class="center">Tidak ada data.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        @endforelse
    </div>

    <section class="summary-page">
        <div class="section-title">Ringkasan Grade</div>
        <div class="table-responsive" style="width: 48%;">
            <table class="table table-striped report-table">
                <thead>
                    <tr class="headers-row">
                        <th style="width: 34px;">No</th>
                        <th>Nama Grade</th>
                        <th style="width: 90px;">Berat</th>
                        <th style="width: 70px;">Rasio</th>
                    </tr>
                </thead>
                <tfoot>
                    <tr class="table-end-line">
                        <td colspan="4"></td>
                    </tr>
                </tfoot>
                <tbody>
                    @forelse ($subRows as $row)
                        @php $berat = (float) ($row['Berat'] ?? 0.0); @endphp
                        <tr class="data-row {{ $loop->odd ? 'row-odd' : 'row-even' }}">
                            <td class="center data-cell" style="{{ $dataCellStyle }}">{{ $loop->iteration }}</td>
                            <td class="data-cell" style="{{ $dataCellStyle }}">{{ $row['NamaGrade'] ?? '' }}</td>
                            <td class="number data-cell" style="{{ $dataCellStyle }}">{{ $fmtTon($berat) }}</td>
                            <td class="number data-cell" style="{{ $dataCellStyle }}">
                                {{ $fmtRatio($grandTotal > 0 ? ($berat / $grandTotal) * 100 : 0.0) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="center">Tidak ada data.</td>
                        </tr>
                    @endforelse
                    @if ($subRows !== [])
                        <tr class="totals-row">
                            <td colspan="2" class="center">Grand Total</td>
                            <td class="number">{{ $fmtTon($grandTotal) }}</td>
                            <td class="number">100.00%</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>

        <h2 class="summary-title">Keterangan:</h2>
        <ul class="summary-list">
            <li>Total baris data : {{ (int) ($summary['total_rows'] ?? 0) }}</li>
            <li>Total bruto : {{ $fmt((float) ($summary['total_bruto'] ?? 0.0)) }}</li>
            <li>Total tara : {{ $fmt((float) ($summary['total_tara'] ?? 0.0)) }}</li>
            <li>Total berat (Ton) : {{ $fmtTon((float) ($summary['total_berat'] ?? 0.0)) }}</li>
            <li>Total group No Truk : {{ count($groupedRows) }}</li>
            <li>Total kayu bulat unik : {{ (int) ($summary['total_distinct_logs'] ?? 0) }}</li>
        </ul>
    </section>

    <htmlpagefooter name="reportFooter">
        <div class="footer-wrap">
            <div class="footer-left">Dicetak oleh: {{ $generatedByName }} pada {{ $generatedAtText }}</div>
            <div class="footer-right">Halaman {PAGENO} dari {nbpg}</div>
        </div>
    </htmlpagefooter>
    <sethtmlpagefooter name="reportFooter" value="on" />
</body>

</html>
