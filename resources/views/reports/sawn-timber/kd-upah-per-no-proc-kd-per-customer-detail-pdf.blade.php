<!DOCTYPE html>
<html lang="id">

<head>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif:ital,wght@0,100..900;1,100..900&display=swap"
        rel="stylesheet">
    <meta charset="utf-8">
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
            margin: 0;
            font-size: 16px;
            font-weight: bold;
        }

        .meta-table {
            width: 100%;
            margin: 14px 0 10px 0;
            border-collapse: collapse;
        }

        .meta-table td {
            border: 0;
            padding: 1px 4px;
            vertical-align: top;
        }

        .meta-label {
            width: 14%;
            white-space: nowrap;
        }

        .meta-separator {
            width: 2%;
            text-align: center;
        }

        .meta-value {
            width: 34%;
        }

        table.data-table {
            width: 100%;
            border-collapse: collapse;
            border-spacing: 0;
            border: 1px solid #000;
            table-layout: fixed;
        }

        thead {
            display: table-header-group;
        }

        tfoot {
            display: table-footer-group;
        }

        tr {
            page-break-inside: avoid;
        }

        table.data-table th,
        table.data-table td {
            border: 0;
            border-left: 1px solid #000;
            padding: 2px 3px;
            vertical-align: middle;
        }

        table.data-table th:first-child,
        table.data-table td:first-child {
            border-left: 0;
        }

        table.data-table th {
            text-align: center;
            font-weight: bold;
            font-size: 10px;
            border-bottom: 1px solid #000;
            background: #fff;
        }

        table.data-table tbody td {
            border-top: 0;
            border-bottom: 0;
        }

        table.data-table tfoot td {
            border-top: 1px solid #000;
            font-weight: bold;
            font-size: 11px;
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

        .summary-table {
            width: 46%;
            margin-top: 14px;
            border-collapse: collapse;
        }

        .summary-table td {
            border: 0;
            padding: 2px 4px;
        }

        @include('reports.partials.pdf-footer-table-style');
    </style>
</head>

<body>
    @php
        $data = is_array($reportData ?? null) ? $reportData : [];
        $rows = is_array($data['rows'] ?? null) ? $data['rows'] : [];
        $header = is_array($data['header'] ?? null) ? $data['header'] : [];
        $summary = is_array($data['summary'] ?? null) ? $data['summary'] : [];

        $generatedByName = $generatedBy?->name ?? 'sistem';
        $generatedAtText = $generatedAt->copy()->locale('id')->translatedFormat('d-M-y H:i');

        $fmtM3 = static function ($v): string {
            $n = (float) ($v ?? 0.0);
            return number_format($n, 4, '.', ',');
        };

        $fmtNumber = static function ($v, int $decimals = 0): string {
            $n = (float) ($v ?? 0.0);
            return number_format($n, $decimals, '.', ',');
        };

        $fmtDate = static function ($v): string {
            $t = is_string($v) ? trim($v) : '';
            if ($t === '') {
                return '';
            }

            try {
                return \Carbon\Carbon::parse($t)->locale('id')->translatedFormat('d-M-y');
            } catch (\Throwable $e) {
                return $t;
            }
        };
    @endphp

    <h1 class="report-title">Laporan KD Upah Per-No.Proses KD Per-Cutomer Detail</h1>

    <table class="meta-table">
        <tbody>
            <tr>
                <td class="meta-label">Customer</td>
                <td class="meta-separator">:</td>
                <td class="meta-value">{{ $header['NamaCustomer'] ?? '-' }}</td>
                <td class="meta-label">No.Proses KD</td>
                <td class="meta-separator">:</td>
                <td class="meta-value">{{ $header['NoProcKD'] ?? '-' }}</td>
            </tr>
            <tr>
                <td class="meta-label">No.Ruang KD</td>
                <td class="meta-separator">:</td>
                <td class="meta-value">{{ $header['NoRuangKD'] ?? '-' }}</td>
                <td class="meta-label">Jenis Kayu</td>
                <td class="meta-separator">:</td>
                <td class="meta-value">{{ $header['Jenis'] ?? '-' }}</td>
            </tr>
            <tr>
                <td class="meta-label">Tanggal Masuk</td>
                <td class="meta-separator">:</td>
                <td class="meta-value">{{ $fmtDate($header['TglMasuk'] ?? '') }}</td>
                <td class="meta-label">Tanggal Keluar</td>
                <td class="meta-separator">:</td>
                <td class="meta-value">{{ $fmtDate($header['TglKeluar'] ?? '') }}</td>
            </tr>
        </tbody>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 5%;">No</th>
                <th style="width: 14%;">No ST</th>
                <th style="width: 18%;">Jenis Kayu</th>
                <th style="width: 10%;">Tebal</th>
                <th style="width: 10%;">Lebar</th>
                <th style="width: 10%;">Panjang</th>
                <th style="width: 13%;">Pcs</th>
                <th style="width: 20%;">M3</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr class="{{ $loop->iteration % 2 === 1 ? 'row-odd' : 'row-even' }}">
                    <td class="center">{{ $loop->iteration }}</td>
                    <td class="center">{{ $row['NoST'] ?? '' }}</td>
                    <td>{{ $row['Jenis'] ?? '' }}</td>
                    <td class="number">{{ $fmtNumber($row['Tebal'] ?? 0, 2) }}</td>
                    <td class="number">{{ $fmtNumber($row['Lebar'] ?? 0, 2) }}</td>
                    <td class="number">{{ $fmtNumber($row['Panjang'] ?? 0, 2) }}</td>
                    <td class="number">{{ number_format((int) ($row['JmlhBatang'] ?? 0), 0, '.', ',') }}</td>
                    <td class="number" style="font-weight: bold;">{{ $fmtM3($row['M3'] ?? 0) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="center">Tidak ada data.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="6" class="center">Total</td>
                <td class="number">{{ number_format((int) ($summary['total_pcs'] ?? 0), 0, '.', ',') }}</td>
                <td class="number">{{ $fmtM3($summary['grand_total_m3'] ?? 0) }}</td>
            </tr>
        </tfoot>
    </table>

    @include('reports.partials.pdf-footer-table')
</body>

</html>
