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

        .kb-block {
            margin: 0 0 14px 0;
            page-break-inside: avoid;
        }

        .kb-meta {
            width: 100%;
            margin-bottom: 6px;
            table-layout: fixed;
        }

        .kb-meta td {
            border: 0;
            padding: 1px 4px;
            vertical-align: top;
            background: transparent !important;
        }

        .kb-meta .meta-label {
            width: 92px;
            white-space: nowrap;
        }

        .kb-meta .meta-sep {
            width: 10px;
            text-align: center;
        }

        .compact-table {
            width: 88%;
            margin-left: 4px;
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

        @include('reports.partials.pdf-footer-table-style')
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
        $fmtDate = static function (mixed $value): string {
            $raw = trim((string) $value);

            if ($raw === '') {
                return '';
            }

            try {
                return \Illuminate\Support\Carbon::parse($raw)->format('d-M-y');
            } catch (\Throwable $e) {
                return $raw;
            }
        };
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
            $kb = trim((string) ($row['NoKayuBulat'] ?? ''));
            $groupKey = $kb !== '' ? $kb : 'Tanpa No KB';
            $groupedRows[$groupKey][] = $row;
        }

        $groupSummaries = [];
        foreach ($groupedRows as $kb => $kbRows) {
            $groupSummaries[$kb] = [
                'total_bruto' => array_sum(
                    array_map(static fn(array $row): float => (float) ($row['Bruto'] ?? 0.0), $kbRows),
                ),
                'total_tara' => array_sum(
                    array_map(static fn(array $row): float => (float) ($row['Tara'] ?? 0.0), $kbRows),
                ),
                'total_berat' => array_sum(
                    array_map(static fn(array $row): float => (float) ($row['Berat'] ?? 0.0), $kbRows),
                ),
            ];
        }

        $grandTotal = (float) ($summary['total_berat'] ?? 0.0);
    @endphp

    <h1 class="report-title">Laporan Saldo Hidup Kayu Bulat - Timbang KG</h1>
    <p class="report-subtitle"></p>

    <div class="container-fluid">
        @forelse ($groupedRows as $kb => $kbRows)
            @php
                $kbSummary = $groupSummaries[$kb] ?? ['total_bruto' => 0.0, 'total_tara' => 0.0, 'total_berat' => 0.0];
                $firstRow = $kbRows[0] ?? [];
                $supplierRowspans = [];
                $rowCount = count($kbRows);

                for ($index = 0; $index < $rowCount; $index++) {
                    $supplierName = trim((string) ($kbRows[$index]['NmSupplier'] ?? ''));

                    if ($index > 0 && $supplierName === trim((string) ($kbRows[$index - 1]['NmSupplier'] ?? ''))) {
                        $supplierRowspans[$index] = 0;
                        continue;
                    }

                    $span = 1;
                    for ($nextIndex = $index + 1; $nextIndex < $rowCount; $nextIndex++) {
                        $nextSupplierName = trim((string) ($kbRows[$nextIndex]['NmSupplier'] ?? ''));
                        if ($nextSupplierName !== $supplierName) {
                            break;
                        }

                        $span++;
                    }

                    $supplierRowspans[$index] = $span;
                }
            @endphp
            <div class="kb-block">
                <table class="kb-meta">
                    <tbody>
                        <tr>
                            <td class="meta-label">No.Kayu Bulat</td>
                            <td class="meta-sep">:</td>
                            <td>{{ $firstRow['NoKayuBulat'] ?? $kb }}</td>
                            <td class="meta-label">No.Truk</td>
                            <td class="meta-sep">:</td>
                            <td>{{ $firstRow['NoTruk'] ?? '' }}</td>
                        </tr>
                        <tr>
                            <td class="meta-label">Tanggal</td>
                            <td class="meta-sep">:</td>
                            <td>{{ $fmtDate($firstRow['DateCreate'] ?? '') }}</td>
                            <td class="meta-label">No.Suket</td>
                            <td class="meta-sep">:</td>
                            <td>{{ $firstRow['Suket'] ?? '' }}</td>
                        </tr>
                        <tr>
                            <td class="meta-label">Jenis Kayu</td>
                            <td class="meta-sep">:</td>
                            <td>{{ $firstRow['JenisKayu'] ?? '' }}</td>
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>
                    </tbody>
                </table>

                <div class="table-responsive">
                    <table class="table table-striped report-table compact-table">
                        <thead>
                            <tr class="headers-row">
                                <th style="width: 29%;">Supplier</th>
                                <th style="width: 13%;">Bruto</th>
                                <th style="width: 13%;">Tara</th>
                                <th style="width: 29%;">Grade</th>
                                <th style="width: 16%;">Berat (Ton)</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($kbRows as $row)
                                <tr class="data-row {{ $loop->odd ? 'row-odd' : 'row-even' }}">
                                    @if (($supplierRowspans[$loop->index] ?? 0) > 0)
                                        <td class="data-cell center" rowspan="{{ $supplierRowspans[$loop->index] }}"
                                            style="{{ $dataCellStyle }} vertical-align: middle;">
                                            {{ $row['NmSupplier'] ?? '' }}
                                        </td>
                                    @endif
                                    <td class="number data-cell" style="{{ $dataCellStyle }}">
                                        {{ $fmt((float) ($row['Bruto'] ?? 0.0)) }}</td>
                                    <td class="number data-cell" style="{{ $dataCellStyle }}">
                                        {{ $fmt((float) ($row['Tara'] ?? 0.0)) }}</td>
                                    <td class="data-cell" style="{{ $dataCellStyle }}">{{ $row['NamaGrade'] ?? '' }}
                                    </td>
                                    <td class="number data-cell" style="{{ $dataCellStyle }} font-weight:bold;">
                                        {{ $fmtTon((float) ($row['Berat'] ?? 0.0)) }}</td>
                                </tr>
                            @endforeach
                            <tr class="totals-row">
                                <td colspan="4" class="number" style="text-align:right;">Jumlah:</td>
                                <td class="number">{{ $fmtTon($kbSummary['total_berat']) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
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
    </section>

    @include('reports.partials.pdf-footer-table')
</body>

</html>
