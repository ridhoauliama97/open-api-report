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
            <tr>
                <th>No</th>
                <th>Tanggal</th>
                <th>Supplier</th>
                <th>Nomor<br>Truk</th>
                <th>Jenis</th>
                <th>Batang Balok <br> Masuk</th>
                <th>Batang Balok <br> Terpakai</th>
                <th>Fisik Batang Balok<br>Di Lapangan</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rowsData as $row)
                <tr class="data-row {{ $loop->odd ? 'row-odd' : 'row-even' }} {{ $loop->last ? 'row-last' : '' }}">
                    <td class="center data-cell">{{ $loop->iteration }}</td>
                    <td class="center data-cell">
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
                    <td class="data-cell">{{ (string) ($row['Supplier'] ?? '') }}</td>
                    <td class="number data-cell" style="text-align: center;">
                        @php
                            $noTrukRaw = (string) ($row['NoTruk'] ?? '');
                            $noTrukNumeric = str_replace(',', '', $noTrukRaw);
                            $noTrukText = is_numeric($noTrukNumeric)
                                ? number_format((float) $noTrukNumeric, 0, '', '')
                                : $noTrukRaw;
                        @endphp
                        {{ $noTrukText }}
                    </td>
                    <td class="data-cell">{{ (string) ($row['Jenis'] ?? '') }}</td>
                    <td class="number data-cell">
                        {{ number_format((float) ($row['BatangBalokMasuk'] ?? 0), 0, '.', ',') }}</td>
                    <td class="number data-cell">
                        {{ number_format((float) ($row['BatangBalokTerpakai'] ?? 0), 0, '.', ',') }}
                    </td>
                    <td class="number data-cell" style="font-weight:bold;">
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

    <section class="summary-page" style="page-break-before: auto; margin-top: 10px;">
        <h2 class="summary-title">Rangkuman :</h2>
        <ul class="summary-list">
            <li>
                Total Balok Masuk: {{ number_format((float) ($summaryData['total_pcs'] ?? 0), 0, '.', ',') }}
            </li>
            <li>
                Total Balok Terpakai:
                {{ number_format((float) ($summaryData['total_blk_terpakai'] ?? 0), 0, '.', ',') }}
            </li>
            <li>
                Total Fisik Di Lapangan:
                {{ number_format((float) ($summaryData['total_fisik_lapangan'] ?? 0), 0, '.', ',') }}
            </li>
        </ul>
    </section>
</body>

</html>
