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
            margin: 24mm 10mm 18mm 10mm;
            footer: html_reportFooter;
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

        .report-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
            page-break-inside: auto;
        }

        .report-table th,
        .report-table td {
            border: 1px solid #000;
            border: 1px solid #000;
            padding: 2px 4px;
            vertical-align: middle;
        }

        .report-table tbody td {
            border-top: 0 !important;
            border-bottom: 0 !important;
        }

        .report-table thead th {
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
        }

        .report-table tbody tr.row-odd td {
            background: #c9d1df;
        }

        .report-table tbody tr.row-even td {
            background: #eef2f8;
        }

        .report-table tbody tr:first-child td {
            border-top: 0;
            padding-top: 3px;
        }

        .report-table tbody tr.row-last td {
            border: 1px solid #000 !important;
        }

        .header-line-1 th {
            border: 1px solid #000;
            border: 1px solid #000;
        }

        .summary-page {
            page-break-before: auto;
            margin-top: 8px;
        }

        .summary-title {
            margin: 0 0 10px;
            font-size: 11px;
            font-weight: bold;
        }

        .summary-list {
            margin: 0;
            padding: 0;
            list-style: none;
            font-size: 10px;
            font-weight: bold;
            line-height: 1.1;
        }

        .summary-list li {
            margin: 0 0 1px;
            white-space: nowrap;
        }

        .summary-dot {
            display: inline-block;
            width: 4px;
            height: 4px;
            margin-right: 8px;
            border-radius: 999px;
            vertical-align: middle;
        }

        .summary-label {
            display: inline-block;
            width: 168px;
            vertical-align: middle;
        }

        .summary-separator {
            display: inline-block;
            width: 10px;
            text-align: center;
            vertical-align: middle;
        }

        .summary-value {
            display: inline-block;
            vertical-align: middle;
        }

        .footer-wrap {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }

        .footer-left {
            font-size: 8px;
            font-style: italic;
        }

        .footer-right {
            font-size: 8px;
            font-style: italic;
            text-align: right;
        }
    </style>
</head>

<body>
    @php
        $rowsData = is_iterable($rows ?? null) ? (is_array($rows) ? $rows : collect($rows)->values()->all()) : [];
        $summaryData = is_array($summary ?? null) ? $summary : [];
        $start = \Carbon\Carbon::parse($startDate)->locale('id')->translatedFormat('d-M-y');
        $end = \Carbon\Carbon::parse($endDate)->locale('id')->translatedFormat('d-M-y');
        $generatedByName = $generatedBy?->name ?? 'sistem';
        $generatedAtText = $generatedAt->copy()->locale('id')->translatedFormat('d-M-y H:i');
    @endphp

    <h1 class="report-title">Laporan Kayu Bulat Hidup</h1>
    <p class="report-subtitle">Periode {{ $start }} s/d {{ $end }}</p>

    <table class="report-table">

        <thead>
            <tr class="header-line-1">
                <th>No</th>
                <th>Tanggal</th>
                <th>Supplier</th>
                <th>Nomor<br>Truk</th>
                <th>Jenis</th>
                <th>Batang Balok Masuk</th>
                <th>Batang Balok Terpakai</th>
                <th>Fisik Batang Balok<br>Di Lapangan</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rowsData as $row)
                <tr class="{{ $loop->odd ? 'row-odd' : 'row-even' }} {{ $loop->last ? 'row-last' : '' }}">
                    <td class="center">{{ $loop->iteration }}</td>
                    <td class="center">
                        @php
                            $tanggal = $row['Tanggal'] ?? null;
                            $tanggalText = '';
                            if ($tanggal) {
                                try {
                                    $tanggalText = \Carbon\Carbon::parse((string) $tanggal)
                                        ->locale('id')
                                        ->translatedFormat('d-M-y');
                                } catch (\Throwable $exception) {
                                    $tanggalText = (string) $tanggal;
                                }
                            }
                        @endphp
                        {{ $tanggalText }}
                    </td>
                    <td>{{ (string) ($row['Supplier'] ?? '') }}</td>
                    <td class="number" style="text-align: center;">
                        @php
                            $noTrukRaw = (string) ($row['NoTruk'] ?? '');
                            $noTrukNumeric = str_replace(',', '', $noTrukRaw);
                            $noTrukText = is_numeric($noTrukNumeric)
                                ? number_format((float) $noTrukNumeric, 0, '', '')
                                : $noTrukRaw;
                        @endphp
                        {{ $noTrukText }}
                    </td>
                    <td>{{ (string) ($row['Jenis'] ?? '') }}</td>
                    <td class="number">{{ number_format((float) ($row['BatangBalokMasuk'] ?? 0), 0, '.', ',') }}</td>
                    <td class="number">{{ number_format((float) ($row['BatangBalokTerpakai'] ?? 0), 0, '.', ',') }}
                    </td>
                    <td class="number">
                        @php
                            $fisik = (float) ($row['FisikBatangBalokDiLapangan'] ?? 0);
                        @endphp
                        {{ $fisik > 0 ? number_format($fisik, 0, '.', ',') : '' }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="center">Tidak ada data.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <section class="summary-page">
        <h2 class="summary-title">Keterangan:</h2>
        <ul class="summary-list">
            <li>
                <span class="summary-dot"></span>
                <span class="summary-label">Total Seluruh Data</span>
                <span class="summary-separator">:</span>
                <span class="summary-value">{{ (int) ($summaryData['total_rows'] ?? 0) }}</span>
            </li>
            <li>
                <span class="summary-dot"></span>
                <span class="summary-label">Total Balok Masuk</span>
                <span class="summary-separator">:</span>
                <span
                    class="summary-value">{{ number_format((float) ($summaryData['total_pcs'] ?? 0), 0, '.', ',') }}</span>
            </li>
            <li>
                <span class="summary-dot"></span>
                <span class="summary-label">Total Balok Terpakai</span>
                <span class="summary-separator">:</span>
                <span
                    class="summary-value">{{ number_format((float) ($summaryData['total_blk_terpakai'] ?? 0), 0, '.', ',') }}</span>
            </li>
            <li>
                <span class="summary-dot"></span>
                <span class="summary-label">Total Fisik Di Lapangan</span>
                <span class="summary-separator">:</span>
                <span
                    class="summary-value">{{ number_format((float) ($summaryData['total_fisik_lapangan'] ?? 0), 0, '.', ',') }}</span>
            </li>
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
