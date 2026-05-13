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
            margin: 14mm 10mm 14mm 10mm;
            footer: html_reportFooter;
        }

        body {
            margin: 0;
            font-family: "Noto Serif", serif;
            font-size: 10px;
            line-height: 1.15;
            color: #000;
        }

        .report-title {
            text-align: center;
            margin: 2px 0 20px 0;
            font-size: 16px;
            font-weight: bold;
        }

        .meta-grid {
            width: 100%;
            margin-bottom: 10px;
        }

        .meta-grid td {
            border: 0 !important;
            padding: 2px 6px 2px 0;
            vertical-align: top;
        }

        .meta-label {
            width: 68px;
            white-space: nowrap;
        }

        .meta-sep {
            width: 10px;
            text-align: center;
        }

        .section-title {
            margin: 10px 0 6px 0;
            font-size: 11px;
            font-weight: bold;
        }

        .report-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #000;
            page-break-inside: auto;
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
            border: 0;
            border-left: 1px solid #000;
            padding: 2px 4px;
            vertical-align: middle;
        }

        th:first-child,
        td:first-child {
            border-left: 0;
        }

        th {
            text-align: center;
            font-weight: bold;
            border-bottom: 1px solid #000;
        }

        tbody td {
            border-top: 0;
            border-bottom: 0;
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

        .number {
            text-align: right;
            white-space: nowrap;
            font-family: "Calibri", "DejaVu Sans", sans-serif;
        }

        .total-line {
            width: 100%;
            margin: 4px 0 0;
            border-collapse: collapse;
        }

        .total-line td {
            border: 0;
            padding: 1px 4px;
        }

        .total-label {
            width: 82%;
            text-align: right;
        }

        .total-value {
            width: 18%;
            text-align: right;
            white-space: nowrap;
            font-family: "Calibri", "DejaVu Sans", sans-serif;
        }

        .grand-total {
            margin-top: 8px;
            font-weight: bold;
        }

        .empty-state {
            padding: 10px;
            text-align: center;
            font-style: italic;
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

        @include('reports.partials.pdf-footer-table-style');
    </style>
</head>

<body>
    @php
        $data = is_array($reportData ?? null) ? $reportData : [];
        $header = is_array($data['header'] ?? null) ? $data['header'] : [];
        $groups = is_array($data['jenis_groups'] ?? null) ? $data['jenis_groups'] : [];
        $summary = is_array($data['summary'] ?? null) ? $data['summary'] : [];

        $fmtDate = static function ($value): string {
            if ($value === null || trim((string) $value) === '') {
                return '-';
            }

            try {
                return \Carbon\Carbon::parse((string) $value)->locale('id')->translatedFormat('d-M-y');
            } catch (\Throwable) {
                return (string) $value;
            }
        };

        $fmtInt = static fn($value): string => $value === null ? '' : number_format((float) $value, 0, '.', ',');
        $fmtM3 = static fn($value): string => number_format((float) $value, 4, '.', ',');
    @endphp

    <h1 class="report-title">Laporan Penjualan Barang Jadi (M3)</h1>

    <table class="meta-grid">
        <tr>
            <td style="width: 50%;">
                <table>
                    <tr>
                        <td class="meta-label">Tanggal</td>
                        <td class="meta-sep">:</td>
                        <td>{{ $fmtDate($header['tanggal'] ?? null) }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">Buyer</td>
                        <td class="meta-sep">:</td>
                        <td>{{ $header['buyer'] ?? '-' }}</td>
                    </tr>
                </table>
            </td>
            <td style="width: 50%;">
                <table>
                    <tr>
                        <td class="meta-label">No SPK</td>
                        <td class="meta-sep">:</td>
                        <td>{{ $header['no_spk'] ?? '-' }}</td>
                    </tr>
                    {{-- <tr>
                        <td class="meta-label">No Jual</td>
                        <td class="meta-sep">:</td>
                        <td>{{ $header['no_bj_jual'] ?? ($noJual ?? '-') }}</td>
                    </tr> --}}
                </table>
            </td>
        </tr>
    </table>

    @forelse ($groups as $group)
        <div class="section-title">Jenis Kayu : {{ $group['jenis'] ?? '-' }}</div>
        <table class="report-table">
            <thead>
                <tr>
                    <th style="width: 7%;">No</th>
                    <th style="width: 34%;">Nama Barang Jadi</th>
                    <th style="width: 10%;">Tebal</th>
                    <th style="width: 10%;">Lebar</th>
                    <th style="width: 11%;">Panjang</th>
                    <th style="width: 12%;">Pcs</th>
                    <th style="width: 16%;">M3</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($group['rows'] ?? [] as $row)
                    <tr class="{{ $loop->iteration % 2 === 1 ? 'row-odd' : 'row-even' }}">
                        <td class="center">{{ $row['No'] ?? $loop->iteration }}</td>
                        <td>{{ $row['NamaBarangJadi'] ?? '-' }}</td>
                        <td class="number">{{ $fmtInt($row['Tebal'] ?? null) }}</td>
                        <td class="number">{{ $fmtInt($row['Lebar'] ?? null) }}</td>
                        <td class="number">{{ $fmtInt($row['Panjang'] ?? null) }}</td>
                        <td class="number">{{ $fmtInt($row['Pcs'] ?? null) }}</td>
                        <td class="number">{{ $fmtM3($row['M3'] ?? 0) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <table class="total-line">
            <tbody>
                @foreach ($group['product_totals'] ?? [] as $name => $total)
                    <tr>
                        <td class="total-label">Jmlh / {{ $name }} :</td>
                        <td class="total-value">{{ $fmtM3($total) }}</td>
                    </tr>
                @endforeach
                <tr>
                    <td class="total-label">Jmlh / {{ $group['jenis'] ?? '-' }} :</td>
                    <td class="total-value">{{ $fmtM3($group['total_m3'] ?? 0) }}</td>
                </tr>
            </tbody>
        </table>
    @empty
        <table class="report-table">
            <tbody>
                <tr>
                    <td class="empty-state">Tidak ada data.</td>
                </tr>
            </tbody>
        </table>
    @endforelse

    @if ($groups !== [])
        <table class="total-line grand-total">
            <tbody>
                <tr>
                    <td class="total-label">Grand Total :</td>
                    <td class="total-value">{{ $fmtM3($summary['grand_total_m3'] ?? 0) }}</td>
                </tr>
            </tbody>
        </table>
    @endif

    @include('reports.partials.pdf-footer-table')
</body>

</html>
