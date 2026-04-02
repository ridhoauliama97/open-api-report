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
            margin: 12mm 8mm 14mm 8mm;
            footer: html_reportFooter;
        }

        body {
            margin: 0;
            font-family: "Noto Serif", serif;
            font-size: 10px;
            line-height: 1.25;
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
            margin: 2px 0 18px 0;
            font-size: 12px;
            color: #636466;
        }

        .layout-table {
            width: 100%;
            border-collapse: collapse;
        }

        .layout-table td {
            vertical-align: top;
            padding: 0;
        }

        .left-col {
            width: 42%;
            padding-right: 14px;
        }

        .right-col {
            width: 58%;
            padding-left: 14px;
        }

        .section-title {
            margin: 0 0 6px 0;
            font-size: 11px;
            font-weight: bold;
            text-decoration: underline;
        }

        .report-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #000;
            margin-bottom: 18px;
        }

        th,
        td {
            border-left: 1px solid #000;
            padding: 3px 5px;
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

        .number {
            text-align: right;
            white-space: nowrap;
            font-family: "Calibri", "DejaVu Sans", sans-serif;
        }

        .summary-line {
            margin: 0 0 2px 0;
            font-size: 11px;
        }

        .summary-label {
            display: inline-block;
            width: 132px;
        }

        .rule {
            width: 180px;
            border-top: 1px solid #000;
            margin: 8px 0 6px 0;
        }

        .block {
            margin-bottom: 28px;
        }

        .conclusion {
            margin-top: 8px;
            font-size: 11px;
        }

        .bottom-summary {
            margin-top: 8px;
            font-size: 11px;
        }

        .empty-state {
            text-align: center;
            padding: 12px;
            font-style: italic;
        }

        @include('reports.partials.pdf-footer-table-style')
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

    <table class="layout-table">
        <tr>
            <td class="left-col">
                <div class="block">
                    <div class="section-title">Saldo Kayu Bulat Non Rambung :</div>
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>Jenis Kayu</th>
                                <th style="width: 70px;">Ton</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($nonRambung['rows'] ?? [] as $row)
                                <tr>
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
                                <td class="number"><strong>{{ $fmtTon4($nonRambung['total_ton'] ?? 0) }}</strong></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="block">
                    <div class="section-title">Saldo Kayu Bulat Rambung :</div>
                    <table class="report-table" style="margin-bottom: 0;">
                        <thead>
                            <tr>
                                <th>Nama Grade</th>
                                <th style="width: 70px;">Berat</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rambung['rows'] ?? [] as $row)
                                <tr>
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
                                <td class="number"><strong>{{ $fmtTon2($rambung['total_berat'] ?? 0) }}</strong></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </td>
            <td class="right-col">
                <div class="block">
                    <div class="section-title">Kapasitas Racip Sawmill :</div>
                    <p class="summary-line"><span class="summary-label">Jmlh HK</span>: {{ $fmtInt($capacity['jmlh_hk'] ?? 0) }} hari</p>
                    <p class="summary-line"><span class="summary-label">Jmlh Meja Sawmill</span>: {{ $fmtInt($capacity['jmlh_meja'] ?? 0) }} meja</p>
                    <p class="summary-line"><span class="summary-label">Jmlh Meja /Hari</span>: {{ $fmtDay($capacity['meja_per_hari'] ?? 0) }} meja/hari</p>
                    <p class="summary-line"><span class="summary-label">Total Ton</span>: {{ $fmtTon4($capacity['total_ton'] ?? 0) }}</p>
                    <p class="summary-line"><span class="summary-label">Ton/Hari</span>: {{ $fmtDay($capacity['ton_per_hari'] ?? 0) }}</p>
                    <p class="summary-line"><span class="summary-label">Ton/Hari/Meja</span>: {{ $fmtTon4($capacity['ton_per_hari_meja'] ?? 0) }}</p>
                    <div class="rule"></div>
                    <p class="summary-line">
                        Rendemen Kayu (Non Rambung) :
                        {{ number_format((float) ($nonRambung['rendemen_percent'] ?? 0), 0, '.', ',') }}% x
                        {{ $fmtTon4($nonRambung['total_ton'] ?? 0) }} =
                        {{ $fmtTon4($nonRambung['effective_ton'] ?? 0) }} Ton
                    </p>
                    <div class="conclusion">
                        <div><u>Kesimpulan :</u></div>
                        <div>
                            Diperlukan Waktu : <strong>{{ $fmtDay($nonRambung['required_days'] ?? 0) }} Hari Kerja (Sawmill)</strong>
                            Untuk Menyelesaikan Kayu Bulat : {{ $fmtTon4($nonRambung['total_ton'] ?? 0) }} Ton (inch)
                        </div>
                    </div>
                </div>

                <div class="block">
                    <div class="section-title">Kapasitas Racip Sawmill Rambung :</div>
                    <p class="summary-line"><span class="summary-label">Jmlh HK</span>: {{ $fmtInt($capacity['jmlh_hk'] ?? 0) }} hari</p>
                    <p class="summary-line"><span class="summary-label">Jmlh Meja Sawmill</span>: {{ $fmtInt($capacity['jmlh_meja'] ?? 0) }} meja</p>
                    <p class="summary-line"><span class="summary-label">Jmlh Meja /Hari</span>: {{ $fmtDay($capacity['meja_per_hari'] ?? 0) }} meja/hari</p>
                    <p class="summary-line"><span class="summary-label">Total Ton</span>: {{ $fmtTon4($capacity['total_ton'] ?? 0) }}</p>
                    <p class="summary-line"><span class="summary-label">Ton/Hari</span>: {{ $fmtDay($capacity['ton_per_hari'] ?? 0) }}</p>
                    <p class="summary-line"><span class="summary-label">Ton/Hari/Meja</span>: {{ $fmtTon4($capacity['ton_per_hari_meja'] ?? 0) }}</p>
                    <div class="rule"></div>
                    <p class="summary-line">
                        Rendemen Kayu (Rambung) :
                        {{ number_format((float) ($rambung['rendemen_percent'] ?? 0), 0, '.', ',') }}% x
                        {{ $fmtTon2($rambung['total_berat'] ?? 0) }} =
                        {{ $fmtTon2($rambung['effective_ton'] ?? 0) }} Ton
                    </p>
                    <div class="conclusion">
                        <div><u>Kesimpulan :</u></div>
                        <div>
                            Diperlukan Waktu : <strong>{{ $fmtDay($rambung['required_days'] ?? 0) }} Hari Kerja (Sawmill)</strong>
                            Untuk Menyelesaikan Kayu Bulat (Rambung) : {{ $fmtTon2($rambung['total_berat'] ?? 0) }} Ton (Kg)
                        </div>
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <div class="section-title" style="margin-top: 4px;">Rangkuman :</div>
    <div class="bottom-summary">
        Diperlukan Waktu : <strong>{{ $fmtDay($summary['required_days'] ?? 0) }} Hari Kerja (Sawmill)</strong> Untuk
        Menyelesaikan Kayu Bulat (Non Rambung) dan (Rambung)
    </div>

    @include('reports.partials.pdf-footer-table')
</body>

</html>
