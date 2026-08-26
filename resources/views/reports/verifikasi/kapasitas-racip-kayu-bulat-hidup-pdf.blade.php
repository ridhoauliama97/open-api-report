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

        .section-block {
            margin-bottom: 22px;
        }

        .strong {
            font-weight: bold;
        }

        .metrics-table {
            width: 62%;
            border: 0;
            margin-bottom: 14px;
        }

        .metrics-table td {
            border: 0 !important;
            padding: 3px 0 !important;
            vertical-align: top;
        }

        .metrics-label {
            width: 148px;
            white-space: nowrap;
        }

        .metrics-sep {
            width: 10px;
            text-align: center;
        }

        .rule {
            width: 240px;
            border-top: 1px solid #000;
            margin: 12px 0 12px 0;
        }

        .equation {
            margin-top: 6px;
            line-height: 1.4;
        }

        .conclusion {
            margin-top: 12px;
            font-size: 11.5px;
        }

        .conclusion-title {
            margin-bottom: 4px;
        }

        .empty-state {
            text-align: center;
            padding: 12px;
            font-style: italic;
            font-weight: bold;
            color: #9c111d;
            background: #c9d1df;
        }

        .summary-table {
            width: 100%;
            border: 0;
            margin-top: 0;
            margin-bottom: 12px;
        }

        .summary-table td {
            border: 0 !important;
            padding: 2px 4px 2px 0;
        }
    </style>
</head>

<body>
    @php
        $data = is_array($reportData ?? null) ? $reportData : [];
        $nonRambung = is_array($data['non_rambung'] ?? null) ? $data['non_rambung'] : [];
        $rambung = is_array($data['rambung'] ?? null) ? $data['rambung'] : [];
        $capacity = is_array($data['capacity'] ?? null) ? $data['capacity'] : [];
        $summary = is_array($data['summary'] ?? null) ? $data['summary'] : [];
        $start = \Carbon\Carbon::parse($startDate)->locale('id')->translatedFormat('d-M-y');
        $end = \Carbon\Carbon::parse($endDate)->locale('id')->translatedFormat('d-M-y');
        $fmtTon4 = static fn($value): string => number_format((float) $value, 4, '.', ',');
        $fmtTon2 = static fn($value): string => number_format((float) $value, 2, '.', ',');
        $fmtInt = static fn($value): string => number_format((float) $value, 0, '.', ',');
        $fmtDay = static fn($value): string => number_format((float) $value, 2, '.', ',');
    @endphp

    <h1 class="report-title">Laporan Kapasitas Racip Kayu Bulat Hidup (Ton)</h1>
    <div class="report-subtitle">Periode {{ $start }} s/d {{ $end }}</div>

    <div class="section-block">
        <div class="section-title">Saldo Kayu Bulat Non Rambung :</div>
        <table class="report-table" style="width: 42%; margin-bottom: 12px;">
            <thead>
                <tr>
                    <th>Jenis Kayu</th>
                    <th style="width: 70px;">Ton</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($nonRambung['rows'] ?? [] as $row)
                    <tr class="{{ $loop->odd ? 'row-odd' : 'row-even' }}">
                        <td>{{ ($row['JenisKayu'] ?? '') !== '' ? $row['JenisKayu'] : '-' }}</td>
                        <td class="number">{{ $fmtTon4($row['Ton'] ?? 0) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2" class="empty-state">Tidak ada data.</td>
                    </tr>
                @endforelse
                <tr>
                    <td></td>
                    <td class="number strong">{{ $fmtTon4($nonRambung['total_ton'] ?? 0) }}</td>
                </tr>
            </tbody>
        </table>

        <div class="section-title">Kapasitas Racip Sawmill :</div>
        <table class="metrics-table">
            <tbody>
                <tr>
                    <td class="metrics-label">Jmlh HK</td>
                    <td class="metrics-sep">:</td>
                    <td>{{ $fmtInt($capacity['jmlh_hk'] ?? 0) }} hari</td>
                </tr>
                <tr>
                    <td class="metrics-label">Jmlh Meja Sawmill</td>
                    <td class="metrics-sep">:</td>
                    <td>{{ $fmtInt($capacity['jmlh_meja'] ?? 0) }} meja</td>
                </tr>
                <tr>
                    <td class="metrics-label">Jmlh Meja /Hari</td>
                    <td class="metrics-sep">:</td>
                    <td>{{ $fmtDay($capacity['meja_per_hari'] ?? 0) }} meja/hari</td>
                </tr>
                <tr>
                    <td class="metrics-label">Total Ton</td>
                    <td class="metrics-sep">:</td>
                    <td>{{ $fmtTon4($capacity['total_ton'] ?? 0) }}</td>
                </tr>
                <tr>
                    <td class="metrics-label">Ton/Hari</td>
                    <td class="metrics-sep">:</td>
                    <td>{{ $fmtDay($capacity['ton_per_hari'] ?? 0) }}</td>
                </tr>
                <tr>
                    <td class="metrics-label">Ton/Hari/Meja</td>
                    <td class="metrics-sep">:</td>
                    <td>{{ $fmtTon4($capacity['ton_per_hari_meja'] ?? 0) }}</td>
                </tr>
            </tbody>
        </table>
        <div class="rule"></div>
        <div class="equation">
            Rendemen Kayu (Non Rambung) :
            {{ number_format((float) ($nonRambung['rendemen_percent'] ?? 0), 0, '.', ',') }}% x
            {{ $fmtTon4($nonRambung['total_ton'] ?? 0) }} =
            {{ $fmtTon4($nonRambung['effective_ton'] ?? 0) }} Ton
        </div>
        <div class="conclusion">
            <div class="conclusion-title strong">Kesimpulan :</div>
            <div>
                Diperlukan Waktu : <span class="strong">{{ $fmtDay($nonRambung['required_days'] ?? 0) }}
                    Hari Kerja (Sawmill)</span>
                Untuk Menyelesaikan Kayu Bulat : {{ $fmtTon4($nonRambung['total_ton'] ?? 0) }} Ton (inch)
            </div>
        </div>
    </div>

    <div class="section-block">
        <div class="section-title">Saldo Kayu Bulat Rambung :</div>
        <table class="report-table" style="width: 42%; margin-bottom: 12px;">
            <thead>
                <tr>
                    <th>Nama Grade</th>
                    <th style="width: 70px;">Berat</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rambung['rows'] ?? [] as $row)
                    <tr class="{{ $loop->odd ? 'row-odd' : 'row-even' }}">
                        <td>{{ ($row['NamaGrade'] ?? '') !== '' ? $row['NamaGrade'] : '-' }}</td>
                        <td class="number">{{ $fmtTon2($row['Berat'] ?? 0) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="2" class="empty-state">Tidak ada data.</td>
                    </tr>
                @endforelse
                <tr>
                    <td></td>
                    <td class="number strong">{{ $fmtTon2($rambung['total_berat'] ?? 0) }}</td>
                </tr>
            </tbody>
        </table>

        <div class="section-title">Kapasitas Racip Sawmill Rambung :</div>
        <table class="metrics-table">
            <tbody>
                <tr>
                    <td class="metrics-label">Jmlh HK</td>
                    <td class="metrics-sep">:</td>
                    <td>{{ $fmtInt($capacity['jmlh_hk'] ?? 0) }} hari</td>
                </tr>
                <tr>
                    <td class="metrics-label">Jmlh Meja Sawmill</td>
                    <td class="metrics-sep">:</td>
                    <td>{{ $fmtInt($capacity['jmlh_meja'] ?? 0) }} meja</td>
                </tr>
                <tr>
                    <td class="metrics-label">Jmlh Meja /Hari</td>
                    <td class="metrics-sep">:</td>
                    <td>{{ $fmtDay($capacity['meja_per_hari'] ?? 0) }} meja/hari</td>
                </tr>
                <tr>
                    <td class="metrics-label">Total Ton</td>
                    <td class="metrics-sep">:</td>
                    <td>{{ $fmtTon4($capacity['total_ton'] ?? 0) }}</td>
                </tr>
                <tr>
                    <td class="metrics-label">Ton/Hari</td>
                    <td class="metrics-sep">:</td>
                    <td>{{ $fmtDay($capacity['ton_per_hari'] ?? 0) }}</td>
                </tr>
                <tr>
                    <td class="metrics-label">Ton/Hari/Meja</td>
                    <td class="metrics-sep">:</td>
                    <td>{{ $fmtTon4($capacity['ton_per_hari_meja'] ?? 0) }}</td>
                </tr>
            </tbody>
        </table>
        <div class="rule"></div>
        <div class="equation">
            Rendemen Kayu (Rambung) :
            {{ number_format((float) ($rambung['rendemen_percent'] ?? 0), 0, '.', ',') }}% x
            {{ $fmtTon2($rambung['total_berat'] ?? 0) }} =
            {{ $fmtTon2($rambung['effective_ton'] ?? 0) }} Ton
        </div>
        <div class="conclusion">
            <div class="conclusion-title strong">Kesimpulan :</div>
            <div>
                Diperlukan Waktu : <span class="strong">{{ $fmtDay($rambung['required_days'] ?? 0) }}
                    Hari Kerja (Sawmill)</span>
                Untuk Menyelesaikan Kayu Bulat (Rambung) : {{ $fmtTon2($rambung['total_berat'] ?? 0) }} Ton
                (Kg)
            </div>
        </div>
    </div>

    <div class="section-title" style="margin-top: 4px;">Rangkuman :</div>
    <table class="summary-table">
        <tbody>
            <tr>
                <td style="width: 128px;">Diperlukan Waktu</td>
                <td style="width: 12px;">:</td>
                <td><span class="strong">{{ $fmtDay($summary['required_days'] ?? 0) }} Hari Kerja (Sawmill)</span>
                    Untuk
                    Menyelesaikan Kayu Bulat (Non Rambung) dan (Rambung)</td>
            </tr>
        </tbody>
    </table>
</body>

</html>
