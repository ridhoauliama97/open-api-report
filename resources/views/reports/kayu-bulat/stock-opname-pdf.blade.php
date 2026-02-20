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
            font-family: "Noto Serif", serif;
            font-size: 10px;
            line-height: 1.2;
            color: #000;
        }

        .report-title {
            text-align: center;
            margin: 0;
            font-size: 14px;
            font-weight: bold;
        }

        .report-subtitle {
            text-align: center;
            margin: 2px 0 8px 0;
            font-size: 10px;
            color: #555;
        }

        .section-title {
            margin: 10px 0 4px;
            font-size: 11px;
            font-weight: bold;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
            page-break-inside: auto;
        }

        th,
        td {
            border: 1px solid #666;
            padding: 3px 4px;
            vertical-align: middle;
        }

        th {
            text-align: center;
            font-weight: 700;
            background: #fff;
        }

        td.center {
            text-align: center;
        }

        .summary-table {
            width: 60%;
        }

        td.number {
            text-align: right;
            font-family: "Calibri", "DejaVu Sans", sans-serif;
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
        $groups = is_iterable($groupedRows ?? null)
            ? (is_array($groupedRows)
                ? $groupedRows
                : collect($groupedRows)->values()->all())
            : [];
        $summaryData = is_array($summary ?? null) ? $summary : [];
        $generatedByName = $generatedBy?->name ?? 'sistem';
        $generatedAtText = $generatedAt->copy()->locale('id')->translatedFormat('d M Y H:i');
    @endphp

    <h1 class="report-title">Laporan Stock Opname Kayu Bulat</h1>
    <p class="report-subtitle">Semua No. Kayu Balut Rentang 30 Hari Terakhir</p>

    <div class="section-title">Summary</div>
    <table class="summary-table">
        <tbody>
            <tr>
                <th style="width: 65%;">Keterangan</th>
                <th>Nilai</th>
            </tr>
            <tr>
                <td>Total Jumlah Data</td>
                <td class="center">{{ (int) ($summaryData['total_rows'] ?? 0) }} Baris</td>
            </tr>
            <tr>
                <td>Total No Kayu Bulat</td>
                <td class="center">{{ (int) ($summaryData['total_no_kayu_bulat'] ?? 0) }}</td>
            </tr>
            <tr>
                <td>Total Pcs</td>
                <td class="center">{{ number_format((float) ($summaryData['total_pcs'] ?? 0), 0, '.', '') }} Pcs</td>
            </tr>
            <tr>
                <td>Total Ton</td>
                <td class="center">{{ number_format((float) ($summaryData['total_ton'] ?? 0), 4, '.', '') }} Ton</td>
            </tr>
        </tbody>
    </table>

    @forelse ($groups as $group)
        @php
            $groupNoSo = (string) ($group['no_kayu_bulat'] ?? 'Tanpa NoKayuBulat');
            $rows = is_array($group['rows'] ?? null) ? $group['rows'] : [];
        @endphp
        <div class="section-title">No Kayu Bulat: {{ $groupNoSo }}</div>
        <table>
            <thead>
                <tr>
                    <th style="width: 30px;">No</th>
                    <th style="width: 82px;">No KB</th>
                    <th style="width: 72px;">Tanggal</th>
                    <th style="width: 72px;">Jenis Kayu</th>
                    <th style="width: 95px;">Supplier</th>
                    <th style="width: 170px;">No Suket</th>
                    <th style="width: 80px;">No Plat</th>
                    <th style="width: 52px;">No Truk</th>
                    <th style="width: 42px;">Tebal</th>
                    <th style="width: 42px;">Lebar</th>
                    <th style="width: 52px;">Panjang</th>
                    <th style="width: 38px;">Pcs</th>
                    <th style="width: 66px;">Jmlh Ton</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    <tr>
                        <td class="center">{{ $loop->iteration }}</td>
                        <td class="center">{{ (string) ($row['NoKayuBulat'] ?? '') }}</td>
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
                        <td>{{ strtoupper((string) ($row['JenisKayu'] ?? '')) }}</td>
                        <td>{{ (string) ($row['Supplier'] ?? '') }}</td>
                        <td>{{ (string) ($row['NoSuket'] ?? '') }}</td>
                        <td>{{ (string) ($row['NoPlat'] ?? '') }}</td>
                        <td class="center">{{ (string) ($row['NoTruk'] ?? '') }}</td>
                        <td class="number">{{ number_format((float) ($row['Tebal'] ?? 0), 0, '.', '') }}</td>
                        <td class="number">{{ number_format((float) ($row['Lebar'] ?? 0), 0, '.', '') }}</td>
                        <td class="number">{{ number_format((float) ($row['Panjang'] ?? 0), 0, '.', '') }}</td>
                        <td class="number">{{ number_format((float) ($row['Pcs'] ?? 0), 0, '.', '') }}</td>
                        <td class="number">{{ number_format((float) ($row['JmlhTon'] ?? 0), 4, '.', '') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @empty
        <table>
            <tbody>
                <tr>
                    <td class="center">Tidak ada data.</td>
                </tr>
            </tbody>
        </table>
    @endforelse

    <htmlpagefooter name="reportFooter">
        <div class="footer-wrap">
            <div class="footer-left">Dicetak oleh: {{ $generatedByName }} pada {{ $generatedAtText }}</div>
            <div class="footer-right">Halaman {PAGENO} dari {nbpg}</div>
        </div>
    </htmlpagefooter>
    <sethtmlpagefooter name="reportFooter" value="on" />
</body>

</html>
