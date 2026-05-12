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

        .report-subtitle {
            text-align: center;
            margin: 2px 0 20px 0;
            font-size: 12px;
            color: #636466;
        }

        .meta-table {
            width: 100%;
            margin: 0 0 8px 0;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .meta-table td {
            border: 0;
            padding: 0 8px 3px 0;
            vertical-align: top;
        }

        .meta-label {
            width: 82px;
            white-space: nowrap;
            font-weight: bold;
        }

        .meta-sep {
            width: 8px;
            text-align: center;
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
            border: 1px solid #000;
            padding: 2px 3px;
            vertical-align: middle;
            font-size: 10px;
        }

        table.data-table th {
            text-align: center;
            font-weight: bold;
            background: #fff;
            white-space: nowrap;
        }

        table.data-table tbody td {
            border-top: 0;
            border-bottom: 0;
        }

        table.data-table tbody tr:last-child td {
            border-bottom: 1px solid #000;
        }

        .center {
            text-align: center;
        }

        .number {
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
            font-size: 11px;
            font-weight: bold;
            border: 1px solid #000 !important;
            background: #fff !important;
        }
    </style>
</head>

<body>
    @php
        $data = is_array($reportData ?? null) ? $reportData : [];
        $header = is_array($data['header'] ?? null) ? $data['header'] : [];
        $rows = is_array($data['rows'] ?? null) ? $data['rows'] : [];
        $summary = is_array($data['summary'] ?? null) ? $data['summary'] : [];

        $generatedByName = $generatedBy?->name ?? 'sistem';
        $generatedAtText = $generatedAt->copy()->locale('id')->translatedFormat('d-M-y H:i');

        $formatDate = static function ($value): string {
            if ($value === null || trim((string) $value) === '') {
                return '-';
            }

            try {
                return \Carbon\Carbon::parse($value)->locale('id')->translatedFormat('d-M-y');
            } catch (\Throwable) {
                return (string) $value;
            }
        };

        $formatDim = static function ($value): string {
            return number_format((float) ($value ?? 0), 1, '.', ',');
        };

        $formatInt = static function ($value): string {
            return number_format((float) ($value ?? 0), 0, '.', ',');
        };

        $formatTon = static function ($value): string {
            return number_format((float) ($value ?? 0), 4, '.', ',');
        };
    @endphp

    <h1 class="report-title">Laporan Stok Opname ST Detail Pada KD</h1>
    <p class="report-subtitle"></p>

    <table class="meta-table">
        <tbody>
            <tr>
                <td class="meta-label">No KD</td>
                <td class="meta-sep">:</td>
                <td>{{ $header['NoProcKD'] ?? ($noProcKd ?? '-') }}</td>
                <td class="meta-label">Ruang KD</td>
                <td class="meta-sep">:</td>
                <td>{{ $header['NoRuangKD'] ?? '-' }}</td>
            </tr>
            <tr>
                <td class="meta-label">Tanggal Masuk</td>
                <td class="meta-sep">:</td>
                <td>{{ $formatDate($header['TglMasuk'] ?? null) }}</td>
                <td class="meta-label">Tanggal Keluar</td>
                <td class="meta-sep">:</td>
                <td>{{ $formatDate($header['TglKeluar'] ?? null) }}</td>
            </tr>
        </tbody>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 4%;">No</th>
                <th style="width: 10%;">No ST</th>
                <th style="width: 8%;">Tanggal</th>
                <th style="width: 20%;">Jenis</th>
                <th style="width: 10%;">No KB</th>
                <th style="width: 7%;">Tebal</th>
                <th style="width: 7%;">Lebar</th>
                <th style="width: 7%;">Panjang</th>
                <th style="width: 6%;">UOM<br>Lebar & Tebal</th>
                <th style="width: 7%;">UOM<br>Panjang</th>
                <th style="width: 7%;">Pcs</th>
                <th style="width: 7%;">Ton</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                @php $rowIndex = ($loop->index ?? 0) + 1; @endphp
                <tr class="data-row {{ $rowIndex % 2 === 1 ? 'row-odd' : 'row-even' }}">
                    <td class="center">{{ $rowIndex }}</td>
                    <td class="center">{{ $row['NoST'] ?? '' }}</td>
                    <td class="center">{{ $formatDate($row['DateCreate'] ?? null) }}</td>
                    <td>{{ $row['Jenis'] ?? '' }}</td>
                    <td class="center">{{ $row['NoKayuBulat'] ?? '' }}</td>
                    <td class="number">{{ $formatDim($row['Tebal'] ?? 0) }}</td>
                    <td class="number">{{ $formatDim($row['Lebar'] ?? 0) }}</td>
                    <td class="number">{{ $formatDim($row['Panjang'] ?? 0) }}</td>
                    <td class="center">{{ $row['UOMLebar'] ?? '' }}</td>
                    <td class="center">{{ $row['UOMPanjang'] ?? '' }}</td>
                    <td class="number" style="font-weight: bold;">{{ $formatInt($row['JmlhBatang'] ?? 0) }}</td>
                    <td class="number" style="font-weight: bold;">{{ $formatTon($row['Ton'] ?? 0) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="12" class="center">Tidak ada data.</td>
                </tr>
            @endforelse
            <tr class="totals-row">
                <td colspan="10" class="center">Total</td>
                <td class="number">{{ $formatInt($summary['total_pcs'] ?? 0) }}</td>
                <td class="number">{{ $formatTon($summary['total_ton'] ?? 0) }}</td>
            </tr>
        </tbody>
    </table>

    @include('reports.partials.pdf-footer-table')
</body>

</html>
