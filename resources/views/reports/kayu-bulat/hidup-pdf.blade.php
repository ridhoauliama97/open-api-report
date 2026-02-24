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

        body {
            margin: 0;
            font-family:"Noto Serif", serif;
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
            margin: 20px 0 4px;
            font-size: 12px;
            font-weight: bold;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
            page-break-inside: auto;
        }

        thead,
        tr {
            border: 1px solid #000;
        }

        th,
        td {
            border: 1px solid #666;
            padding: 3px 4px;
            vertical-align: middle;
        }

        thead,
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
            font-family:"Calibri","DejaVu Sans", sans-serif;
        }

        .row-odd td {
            background: #c9d1df;
        }

        .row-even td {
            background: #eef2f8;
        }

        .summary-table {
            width: 60%;
            font-weight: bold;
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
    
        .headers-row th {
            font-weight: bold;
            font-size: 11px;
            border: 1.5px solid #000;
        }
    
        .totals-row td {
            font-weight: bold;
            font-size: 11px;
            border: 1.5px solid #000;
        }
    </style>
</head>

<body>
    @php
        $rowsData = is_iterable($rows ?? null) ? (is_array($rows) ? $rows : collect($rows)->values()->all()) : [];
        $summaryData = is_array($summary ?? null) ? $summary : [];
        $start = \Carbon\Carbon::parse($startDate)->locale('id')->translatedFormat('d M Y');
        $end = \Carbon\Carbon::parse($endDate)->locale('id')->translatedFormat('d M Y');
        $generatedByName = $generatedBy?->name ?? 'sistem';
        $generatedAtText = $generatedAt->copy()->locale('id')->translatedFormat('d M Y H:i');
    @endphp

    <h1 class="report-title">Laporan Kayu Bulat Hidup</h1>
    <p class="report-subtitle">Periode {{ $start }} s/d {{ $end }}</p>

    <table>
        <thead>
            <tr class="headers-row" style="border: 1.5px solid #000">
                <th>No</th>
                <th>Tanggal</th>
                <th>Supplier</th>
                <th>No Truk</th>
                <th>Jenis</th>
                <th>Batang Balok Masuk</th>
                <th>Batang Balok Terpakai</th>
                <th>Fisik Batang Balok Di Lapangan</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rowsData as $row)
                <tr class="{{ $loop->odd ? 'row-odd' : 'row-even' }}">
                    <td class="center">{{ $loop->iteration }}</td>
                    <td class="center">
                        @php
                            $tanggal = $row['Tanggal'] ?? null;
                            $tanggalText = '';
                            if ($tanggal) {
                                try {
                                    $tanggalText = \Carbon\Carbon::parse((string) $tanggal)->format('d M Y');
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
                                ? number_format((float) $noTrukNumeric, 0, '.', ',')
                                : $noTrukRaw;
                        @endphp
                        {{ $noTrukText }}
                    </td>
                    <td>{{ (string) ($row['Jenis'] ?? '') }}</td>
                    <td class="number">{{ number_format((float) ($row['BatangBalokMasuk'] ?? 0), 0, '.', ',') }}</td>
                    <td class="number">{{ number_format((float) ($row['BatangBalokTerpakai'] ?? 0), 0, '.', ',') }}</td>
                    <td class="number">
                        @php
                            $fisik = (float) ($row['FisikBatangBalokDiLapangan'] ?? 0);
                        @endphp
                        {{ $fisik > 0 ? number_format($fisik, 0, '.', '') : '' }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="center">Tidak ada data.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="section-title">Summary : </div>
    <table class="summary-table">
        <tbody>
            <tr>
                <th style="width: 65%; font-weight: bold;">Keterangan</th>
                <th style="font-weight: bold;">Nilai</th>
            </tr>
            <tr>
                <td>Total Jumlah Data</td>
                <td class="center">{{ (int) ($summaryData['total_rows'] ?? 0) }} Baris Data</td>
            </tr>
            <tr>
                <td>Total Balok Masuk</td>
                <td class="center">{{ number_format((float) ($summaryData['total_pcs'] ?? 0), 0, '.', ',') }}
                    Batang Balok
                </td>
            </tr>
            <tr>
                <td>Total Balok Terpakai</td>
                <td class="center">{{ number_format((float) ($summaryData['total_blk_terpakai'] ?? 0), 0, '.', ',') }}
                    Batang Balok
                </td>
            </tr>
            <tr>
                <td>Total Fisik Di Lapangan</td>
                <td class="center">
                    {{ number_format((float) ($summaryData['total_fisik_lapangan'] ?? 0), 0, '.', ',') }}
                    Batang Balok
                </td>
            </tr>
        </tbody>
    </table>

    <htmlpagefooter name="reportFooter">
        <div class="footer-wrap">
            <div class="footer-left">Dicetak oleh: {{ $generatedByName }} pada {{ $generatedAtText }}</div>
            <div class="footer-right">Halaman {PAGENO} dari {nbpg}</div>
        </div>
    </htmlpagefooter>
    <sethtmlpagefooter name="reportFooter" value="on" />
</body>

</html>
