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
            margin: 14mm 8mm 14mm 8mm;
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
            font-size: 12px;
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

        .empty-state {
            text-align: center;
            padding: 10px;
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

        @include('reports.partials.pdf-footer-table-style')
    </style>
</head>

<body>
    @php
        $data = is_array($reportData ?? null) ? $reportData : [];
        $header = is_array($data['header'] ?? null) ? $data['header'] : [];
        $products = is_array($data['products'] ?? null) ? $data['products'] : [];
        $lands = is_array($data['lands'] ?? null) ? $data['lands'] : [];
        $gpsPercentages = is_array($data['gps_percentages'] ?? null) ? $data['gps_percentages'] : [];
        $summary = is_array($data['summary'] ?? null) ? $data['summary'] : [];
        $fmtDim = static fn($value): string => $value === null ? '' : number_format((float) $value, 0, '.', ',');
        $fmtDec = static fn($value, int $decimals = 4): string => $value === null
            ? ''
            : number_format((float) $value, $decimals, '.', ',');
        $fmtPercent = static fn($value): string => $value === null
            ? ''
            : number_format((float) $value, 2, '.', ',') . '%';
        $fmtDate = static function ($value): string {
            if ($value === null || $value === '') {
                return '';
            }

            try {
                return \Carbon\Carbon::parse($value)->locale('id')->translatedFormat('d-M-y');
            } catch (\Throwable $exception) {
                return (string) $value;
            }
        };
        $gpsTotal = array_sum(array_map(static fn(array $row): float => (float) ($row['Total'] ?? 0), $gpsPercentages));
    @endphp

    <h1 class="report-title">Laporan Koordinat Tanah</h1>

    <table class="meta-grid">
        <tr>
            <td style="width: 50%;">
                <table>
                    <tr>
                        <td class="meta-label">No SPK</td>
                        <td class="meta-sep">:</td>
                        <td>{{ $header['NoSPK'] ?? ($noSpk ?? '-') }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">Tanggal</td>
                        <td class="meta-sep">:</td>
                        <td>{{ $fmtDate($header['Tanggal'] ?? null) }}</td>
                    </tr>
                </table>
            </td>
            <td style="width: 50%;">
                <table>
                    <tr>
                        <td class="meta-label">Buyer</td>
                        <td class="meta-sep">:</td>
                        <td>{{ $header['Buyer'] ?? '' }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">Tujuan</td>
                        <td class="meta-sep">:</td>
                        <td>{{ $header['Tujuan'] ?? '' }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="section-title">Produk SPK</div>
    <table class="report-table" style="margin-bottom: 10px;">
        <thead>
            <tr>
                <th style="width: 5%;">No</th>
                <th style="width: 14%;">Jenis</th>
                <th style="width: 17%;">Nama Barang Jadi</th>
                <th style="width: 8%;">Tebal</th>
                <th style="width: 8%;">Lebar</th>
                <th style="width: 10%;">Panjang</th>
                <th style="width: 8%;">Bundle</th>
                <th style="width: 12%;">Pcs/Bundle</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($products as $index => $row)
                <tr class="{{ ($index + 1) % 2 === 1 ? 'row-odd' : 'row-even' }}">
                    <td class="center">{{ $index + 1 }}</td>
                    <td>{{ $row['Jenis'] ?? '' }}</td>
                    <td>{{ $row['NamaBarangJadi'] ?? '' }}</td>
                    <td class="center">{{ $fmtDim($row['Tebal'] ?? null) }}</td>
                    <td class="center">{{ $fmtDim($row['Lebar'] ?? null) }}</td>
                    <td class="center">{{ $fmtDim($row['Panjang'] ?? null) }}</td>
                    <td class="center">{{ $fmtDim($row['Bundle'] ?? null) }}</td>
                    <td class="center">{{ $fmtDim($row['PcsPerBundle'] ?? null) }}</td>
                    <td>{{ $row['Keterangan'] ?? '' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="empty-state">Tidak ada data produk untuk SPK ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="section-title">Persentase Koordinat GPS</div>
    <table class="report-table" style="margin-bottom: 10px;">
        <thead>
            <tr>
                <th style="width: 5%;">No</th>
                <th style="width: 22%;">Jenis</th>
                <th style="width: 9%;">Total</th>
                <th style="width: 9%;">Persen</th>
                <th style="width: 20%;">Nama Pemilik</th>
                <th style="width: 8%;">Tahun</th>
                <th style="width: 27%;">Koordinat</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($gpsPercentages as $index => $row)
                <tr class="{{ ($index + 1) % 2 === 1 ? 'row-odd' : 'row-even' }}">
                    <td class="center">{{ $index + 1 }}</td>
                    <td>{{ $row['Jenis'] ?? '' }}</td>
                    <td class="number">{{ $fmtDec($row['Total'] ?? null) }}</td>
                    <td class="number">{{ $fmtPercent($row['Persen'] ?? null) }}</td>
                    <td>{{ $row['NamaPemilik'] ?? '' }}</td>
                    <td class="center">{{ $row['Tahun'] ?? '' }}</td>
                    <td>{{ $row['Koordinat'] ?? '' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="empty-state">Tidak ada data persentase koordinat GPS untuk SPK ini.</td>
                </tr>
            @endforelse
            @if (count($gpsPercentages) > 0)
                <tr>
                    <td colspan="2" class="center"
                        style="font-weight: bold; border-top: 1px solid #000; background: #fff;">Total
                    </td>
                    <td class="number" style="font-weight: bold; border-top: 1px solid #000; background: #fff;">
                        {{ $fmtDec($gpsTotal) }}</td>
                    <td colspan="4" style="border-top: 1px solid #000; background: #fff;">&nbsp;</td>
                </tr>
            @endif
        </tbody>
    </table>

    <div class="section-title">Daftar Koordinat Tanah</div>
    <table class="report-table" style="margin-bottom: 8px;">
        <thead>
            <tr>
                <th style="width: 5%;">No</th>
                <th style="width: 8%;">Periode</th>
                <th style="width: 11%;">Nama Tanah</th>
                <th style="width: 13%;">Nama Pemilik</th>
                <th style="width: 13%;">Desa/Kelurahan</th>
                <th style="width: 12%;">Kab/Kota</th>
                <th style="width: 10%;">Provinsi</th>
                <th style="width: 12%;">No Surat Tanah</th>
                <th style="width: 8%;">Luas</th>
                <th style="width: 12%;">Koordinat</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($lands as $index => $row)
                <tr class="{{ ($index + 1) % 2 === 1 ? 'row-odd' : 'row-even' }}">
                    <td class="center">{{ $index + 1 }}</td>
                    <td class="center">{{ $fmtDate($row['Periode'] ?? null) }}</td>
                    <td>{{ $row['NamaTanah'] ?? '' }}</td>
                    <td>{{ $row['NamaPemilik'] ?? '' }}</td>
                    <td>{{ $row['DesaKelurahan'] ?? '' }}</td>
                    <td>{{ $row['KabupatenKota'] ?? '' }}</td>
                    <td>{{ $row['Provinsi'] ?? '' }}</td>
                    <td>{{ $row['NoSuratTanah'] ?? '' }}</td>
                    <td class="number">{{ $fmtDim($row['Luas'] ?? null) }}</td>
                    <td>{{ $row['Koordinat'] ?? '' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="empty-state">Tidak ada data koordinat tanah untuk SPK ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div style="margin-top: 8px; font-size: 11px; font-weight: bold;">
        Ringkasan:
        {{ $summary['product_rows'] ?? 0 }} produk,
        {{ $summary['land_rows'] ?? 0 }} tanah,
        {{ $summary['gps_percentage_rows'] ?? 0 }} baris persentase GPS,
        {{ $summary['period_count'] ?? 0 }} periode sumber.
    </div>

    @include('reports.partials.pdf-footer-table')
</body>

</html>
