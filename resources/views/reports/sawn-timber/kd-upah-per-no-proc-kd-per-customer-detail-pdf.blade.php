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

        /* --- Gotenberg wave3 extras --- */
        .meta-layout,
        .meta-table,
        .note-table,
        .ratio-table {
            width: auto;
        }

        table.meta-layout,
        table.meta-table,
        table.note-table,
        table.ratio-table,
        table.meta-layout td,
        table.meta-table td,
        table.note-table td,
        table.ratio-table td,
        table.meta-layout th,
        table.meta-table th,
        .meta-label,
        .meta-separator,
        .meta-value {
            border: 0;
        }

        .group-title,
        .customer-title,
        .grade-output,
        .date-separator,
        .grade-title {
            font-style: italic;
            font-weight: bold;
            color: #9c111d;
        }

        .total-row td,
        .grand-total-row td,
        .row-last td,
        .before-total td,
        .totals-label {
            font-weight: bold;
        }

        .page-break {
            page-break-after: always;
        }
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

</body>

</html>
