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
            margin: 14mm 10mm 14mm 10mm;
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
            margin: 0 0 20px 0px;
            font-size: 16px;
            font-weight: bold;
        }

        .meta-table,
        .report-table,
        .signature-table {
            width: 100%;
            border-collapse: collapse;
        }

        .meta-table {
            margin-bottom: 14px;
        }

        .meta-table td {
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

        .report-table {
            width: 100%;
            margin-bottom: 6px;
        }

        .report-table th,
        .report-table td {
            border: 1px solid #000;
            padding: 3px 5px;
        }

        .report-table th {
            text-align: center;
            font-weight: bold;
            background: #ffffff;
        }

        .report-table td {
            vertical-align: middle;
        }

        .report-table td.center {
            text-align: center;
        }

        .report-table td.number {
            text-align: right;
            white-space: nowrap;
            font-family: "Calibri", "DejaVu Sans", sans-serif;
        }

        .report-table tbody tr:nth-child(odd) td {
            background: #c9d1df;
        }

        .report-table tbody tr:nth-child(even) td {
            background: #eef2f8;
        }

        .report-table tbody tr.totals-row td {
            font-weight: bold;
            background: #fff !important;
            font-size: 11px;
        }

        .report-table .totals-label {
            text-align: center;
        }

        .signature-table {
            margin-top: 14px;
            table-layout: fixed;
        }

        .signature-table td {
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
            border-collapse: collapse;
        }

        .signature-placeholder-table td {
            padding: 0;
            font-family: "Calibri", "DejaVu Sans", sans-serif;
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

        @include('reports.partials.pdf-footer-table-style');
    </style>
</head>

<body>
    @php
        $reportData = is_array($reportData ?? null) ? $reportData : [];
        $header = is_array($reportData['header'] ?? null) ? $reportData['header'] : [];
        $rows = is_array($reportData['rows'] ?? null) ? $reportData['rows'] : [];
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

        $formatSize = static fn(int|float $value): string => number_format((float) $value, 0, '.', '');
        $formatTon = static fn(int|float $value): string => number_format((float) $value, 4, '.', ',');
    @endphp

    <h1 class="report-title">Laporan Penerimaan Kayu Bulat - (Int Ton)</h1>

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

    <table class="report-table">
        <thead>
            <tr>
                <th style="width: 8%;">No</th>
                <th style="width: 12%;">No Log</th>
                <th style="width: 13%;">Tebal</th>
                <th style="width: 13%;">Lebar</th>
                <th style="width: 13%;">Panjang</th>
                <th style="width: 17%;">Ton</th>
                <th style="width: 24%;">Ket</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                <tr>
                    <td class="center">{{ $loop->iteration }}</td>
                    <td class="center">{{ (int) ($row['NoLog'] ?? 0) }}</td>
                    <td class="number">{{ $formatSize((float) ($row['Tebal'] ?? 0)) }}</td>
                    <td class="number">{{ $formatSize((float) ($row['Lebar'] ?? 0)) }}</td>
                    <td class="number">{{ $formatSize((float) ($row['Panjang'] ?? 0)) }}</td>
                    <td class="number">{{ $formatTon((float) ($row['Ton'] ?? 0)) }}</td>
                    <td class="center">{{ (string) ($row['Ket'] ?? '') }}</td>
                </tr>
            @endforeach
            <tr class="totals-row">
                <td colspan="5" class="totals-label">Total</td>
                <td class="number">{{ $formatTon((float) ($summary['total_ton'] ?? 0)) }}</td>
                <td></td>
            </tr>
        </tbody>
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

    @include('reports.partials.pdf-footer-table', [
        'generatedByName' => $generatedByName,
        'generatedAtText' => $generatedAtText,
    ])
</body>

</html>
