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

        th {
            text-align: center;
        }

        .meta-table {
            width: 100%;
            border: 0;
            margin-bottom: 8px;
        }

        .meta-table td {
            border: 0;
            padding: 1px 2px;
            vertical-align: top;
        }

        .meta-label {
            width: 68px;
            white-space: nowrap;
        }

        .meta-colon {
            width: 10px;
            text-align: center;
        }

        .spacer-cell {
            width: 24px;
        }

        .summary-row {
            margin: 20px 0 6px;
        }

        .summary-row span {
            margin-right: 12px;
        }

        .grade-title {
            margin: 16px 0 4px;
            font-size: 11px;
            font-style: italic;
            font-weight: bold;
            color: #9c111d;
        }

        .report-table {
            width: 180px;
            margin-bottom: 2px;
        }

        .report-table tbody tr:nth-child(odd) td {
            background: #c9d1df;
        }

        .report-table tbody tr:nth-child(even) td {
            background: #eef2f8;
        }

        .grand-total-line {
            width: 200px;
            border-top: 1px solid #000;
            margin: 8px 0 4px;
        }

        .grand-total-table {
            width: 200px;
            border: 0;
            margin: 0 0 14px;
        }

        .grand-total-table td {
            border: 0;
            padding: 0;
            font-size: 11px;
            font-weight: bold;
            vertical-align: top;
        }

        .grand-total-label {
            width: 58px;
            text-align: left;
        }

        .grand-total-value {
            text-align: right;
            white-space: nowrap;
        }

        .signature-table {
            width: 100%;
            border: 0;
            margin-top: 14px;
            table-layout: fixed;
        }

        .signature-table td {
            border: 0;
            width: 14.28%;
            text-align: center;
            vertical-align: top;
            padding: 0 2px;
        }

        .signature-label-row td {
            padding-bottom: 18px;
        }

        .signature-label {
            margin: 0;
        }

        .signature-placeholder-row td {
            padding-top: 50px;
        }

        .signature-placeholder-table {
            width: 100%;
            border: 0;
            border-collapse: collapse;
        }

        .signature-placeholder-table td {
            border: 0;
            padding: 0;
            font-size: 11px;
            font-weight: normal;
            text-align: center;
        }

        .signature-bracket {
            width: 100px;
        }

        .signature-space {
            width: 100px;
        }
    </style>
</head>

<body>
    @php
        $reportData = is_array($reportData ?? null) ? $reportData : [];
        $header = is_array($reportData['header'] ?? null) ? $reportData['header'] : [];
        $groups = is_array($reportData['groups'] ?? null) ? $reportData['groups'] : [];
        $summary = is_array($reportData['summary'] ?? null) ? $reportData['summary'] : [];
        $generatedByName = $generatedBy?->name ?? 'sistem';
        $generatedAtText = $generatedAt->copy()->locale('id')->translatedFormat('d-M-y H:i');

        $formatDate = static function (?string $value): string {
            $raw = trim((string) $value);
            if ($raw === '') {
                return '';
            }

            try {
                return \Carbon\Carbon::parse($raw)->locale('id')->translatedFormat('d-M-y');
            } catch (\Throwable $exception) {
                return $raw;
            }
        };

        $formatInt = static fn(int|float $value): string => number_format((float) $value, 0, '.', ',');
        $formatWeight = static fn(int|float $value): string => number_format((float) $value, 0, '.', ',');
    @endphp

    <h1 class="report-title">Laporan Penerimaan Kayu Bulat - (KG)</h1>

    <table class="meta-table">
        <tr>
            <td class="meta-label">Nomor</td>
            <td class="meta-colon">:</td>
            <td>{{ (string) ($header['no_kayu_bulat'] ?? '') }}</td>
            <td class="spacer-cell"></td>
            <td class="meta-label">Jenis Kayu</td>
            <td class="meta-colon">:</td>
            <td>{{ (string) ($header['jenis_kayu'] ?? '') }}</td>
        </tr>
        <tr>
            <td class="meta-label">Tanggal</td>
            <td class="meta-colon">:</td>
            <td>{{ $formatDate((string) ($header['tanggal'] ?? '')) }}</td>
            <td class="spacer-cell"></td>
            <td class="meta-label">No.Plat</td>
            <td class="meta-colon">:</td>
            <td>{{ (string) ($header['no_plat'] ?? '') }}</td>
        </tr>
        <tr>
            <td class="meta-label">Supplier</td>
            <td class="meta-colon">:</td>
            <td>{{ (string) ($header['supplier'] ?? '') }}</td>
            <td class="spacer-cell"></td>
            <td class="meta-label">No.Suket</td>
            <td class="meta-colon">:</td>
            <td>{{ (string) ($header['no_suket'] ?? '') }}</td>
        </tr>
    </table>

    <div class="summary-row">
        <span>Bruto : {{ $formatWeight((float) ($header['bruto'] ?? 0)) }}</span> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
        <span>Tara : {{ $formatWeight((float) ($header['tara'] ?? 0)) }}</span>
    </div>

    @foreach ($groups as $group)
        <div class="grade-title">Nama Grade : {{ (string) ($group['grade_name'] ?? '') }}</div>
        <table class="report-table">
            <thead>
                <tr>
                    <th style="width: 28px;">No</th>
                    <th style="width: 70px;">Pcs</th>
                    <th style="width: 72px;">Berat</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($group['rows'] ?? [] as $row)
                    <tr>
                        <td class="center">{{ (int) ($row['no'] ?? 0) }}</td>
                        <td class="number">{{ $formatInt((int) ($row['pcs'] ?? 0)) }}</td>
                        <td class="number">{{ $formatWeight((float) ($row['berat'] ?? 0)) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tr class="totals-row">
                <td class="center"><strong>Jumlah</strong></td>
                <td class="number"><strong>{{ $formatInt((int) ($group['totals']['pcs'] ?? 0)) }}</strong></td>
                <td class="number"><strong>{{ $formatWeight((float) ($group['totals']['berat'] ?? 0)) }}</strong>
                </td>
            </tr>
        </table>
    @endforeach

    <div class="grand-total-line"></div>
    <table class="grand-total-table">
        <tr>
            <td class="grand-total-label">Total :</td>
            <td class="grand-total-value" style="width: 70px;">{{ $formatInt((int) ($summary['total_pcs'] ?? 0)) }}
            </td>
            <td class="grand-total-value" style="width: 72px;">
                {{ $formatWeight((float) ($summary['total_berat'] ?? 0)) }}
            </td>
        </tr>
    </table>

    <table class="signature-table">
        <tr class="signature-label-row">
            <td>
                <div class="signature-label">Ukur 1 ;</div>
            </td>
            <td>
                <div class="signature-label">Ukur 2 ;</div>
            </td>
            <td>
                <div class="signature-label">Tally Tulis ;</div>
            </td>
            <td>
                <div class="signature-label">Diperiksa Oleh ;</div>
            </td>
            <td>
                <div class="signature-label">QC Oleh ;</div>
            </td>
            <td>
                <div class="signature-label">Diinput Oleh ;</div>
            </td>
            <td>
                <div class="signature-label">Supir ;</div>
            </td>
        </tr>
        <tr class="signature-placeholder-row">
            <td>
                <table class="signature-placeholder-table">
                    <tr>
                        <td class="signature-bracket">(</td>
                        <td class="signature-space"></td>
                        <td class="signature-bracket">)</td>
                    </tr>
                </table>
            </td>
            <td>
                <table class="signature-placeholder-table">
                    <tr>
                        <td class="signature-bracket">(</td>
                        <td class="signature-space"></td>
                        <td class="signature-bracket">)</td>
                    </tr>
                </table>
            </td>
            <td>
                <table class="signature-placeholder-table">
                    <tr>
                        <td class="signature-bracket">(</td>
                        <td class="signature-space"></td>
                        <td class="signature-bracket">)</td>
                    </tr>
                </table>
            </td>
            <td>
                <table class="signature-placeholder-table">
                    <tr>
                        <td class="signature-bracket">(</td>
                        <td class="signature-space"></td>
                        <td class="signature-bracket">)</td>
                    </tr>
                </table>
            </td>
            <td>
                <table class="signature-placeholder-table">
                    <tr>
                        <td class="signature-bracket">(</td>
                        <td class="signature-space"></td>
                        <td class="signature-bracket">)</td>
                    </tr>
                </table>
            </td>
            <td>
                <table class="signature-placeholder-table">
                    <tr>
                        <td class="signature-bracket">(</td>
                        <td class="signature-space"></td>
                        <td class="signature-bracket">)</td>
                    </tr>
                </table>
            </td>
            <td>
                <table class="signature-placeholder-table">
                    <tr>
                        <td class="signature-bracket">(</td>
                        <td class="signature-space"></td>
                        <td class="signature-bracket">)</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>
