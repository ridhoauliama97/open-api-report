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
            padding: 2px 3px;
            font-size: 10px;
            line-height: 1.15;
            white-space: nowrap;
        }

        .report-table th {
            text-align: center;
            font-weight: bold;
            background: #ffffff;
        }

        .report-table td {
            vertical-align: middle;
        }

        .report-table tbody td {
            border-top: 0;
            border-bottom: 0;
        }

        .report-table tbody tr.last-data-row td {
            border-bottom: 1px solid #000;
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

        .report-table .separator-cell {
            width: 4%;
            min-width: 16px;
            border: 0;
            background: #fff !important;
            padding: 0;
        }

        .summary-block {
            width: 100%;
            margin: 4px 0 10px 0;
        }

        .summary-table {
            width: 45%;
            border-collapse: collapse;
            font-family: "Noto Serif", serif;
            font-size: 10px;
            margin-top: 10px;
            margin-left: auto;
        }

        .summary-table td {
            padding: 0 2px 2px 2px;
            vertical-align: top;
        }

        .summary-label {
            text-align: right;
            white-space: nowrap;
            width: 70%;
        }

        .summary-value {
            font-family: "Calibri", "DejaVu Sans", sans-serif;
            text-align: right;
            white-space: nowrap;
            width: 30%;
            font-weight: bold;
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

        .signature-bottom-label-row td {
            padding-top: 6px;
            padding-bottom: 18px;
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

        $formatSize = static fn(int|float $value): string => number_format((float) $value, 2, '.', ',');
        $formatTon = static fn(int|float $value): string => number_format((float) $value, 4, '.', ',');
        $totalsByKeterangan = is_array($summary['totals_by_keterangan'] ?? null)
            ? $summary['totals_by_keterangan']
            : [];
        $jenisKayuSummary = trim((string) ($header['jenis_kayu'] ?? ''));
        $leftRowsCount = (int) ceil(count($rows) / 2);
        $rightStartNumber = $leftRowsCount + 1;
    @endphp

    <h1 class="report-title">Laporan Penerimaan Kayu Bulat</h1>

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
                <th style="width: 5%;">No</th>
                <th style="width: 7.5%;">Tebal</th>
                <th style="width: 7.5%;">Lebar</th>
                <th style="width: 7.5%;">Panjang</th>
                <th style="width: 9.5%;">Ton</th>
                {{-- <th style="width: 11%;">Keterangan</th> --}}
                <th class="separator-cell"></th>
                <th style="width: 5%;">No</th>
                <th style="width: 7.5%;">Tebal</th>
                <th style="width: 7.5%;">Lebar</th>
                <th style="width: 7.5%;">Panjang</th>
                <th style="width: 9.5%;">Ton</th>
                {{-- <th style="width: 11%;">Keterangan</th> --}}
            </tr>
        </thead>
        <tbody>
            @for ($index = 0; $index < $leftRowsCount; $index++)
                @php
                    $leftRow = $rows[$index] ?? [];
                    $rightRow = $rows[$leftRowsCount + $index] ?? null;
                @endphp
                <tr @class(['last-data-row' => $index === $leftRowsCount - 1])>
                    <td class="center">{{ $index + 1 }}</td>
                    <td class="number">{{ $formatSize((float) ($leftRow['Tebal'] ?? 0)) }}</td>
                    <td class="number">{{ $formatSize((float) ($leftRow['Lebar'] ?? 0)) }}</td>
                    <td class="number">{{ $formatSize((float) ($leftRow['Panjang'] ?? 0)) }}</td>
                    <td class="number" style="font-weight:bold;">{{ $formatTon((float) ($leftRow['Ton'] ?? 0)) }}</td>
                    {{-- <td class="center">{{ (string) ($leftRow['Ket'] ?? '') }}</td> --}}
                    <td class="separator-cell"></td>
                    @if (is_array($rightRow))
                        <td class="center">{{ $rightStartNumber + $index }}</td>
                        <td class="number">{{ $formatSize((float) ($rightRow['Tebal'] ?? 0)) }}</td>
                        <td class="number">{{ $formatSize((float) ($rightRow['Lebar'] ?? 0)) }}</td>
                        <td class="number">{{ $formatSize((float) ($rightRow['Panjang'] ?? 0)) }}</td>
                        <td class="number" style="font-weight:bold;">{{ $formatTon((float) ($rightRow['Ton'] ?? 0)) }}
                        </td>
                        {{-- <td class="center">{{ (string) ($rightRow['Ket'] ?? '') }}</td> --}}
                    @else
                        <td colspan="6"></td>
                    @endif
                </tr>
            @endfor
        </tbody>
    </table>

    <div class="summary-block">
        <table class="summary-table">
            @foreach ($totalsByKeterangan as $keterangan => $ton)
                <tr>
                    <td class="summary-label">KB {{ trim($jenisKayuSummary . ' ' . (string) $keterangan) }} :</td>
                    <td class="summary-value">{{ $formatTon((float) $ton) }}</td>
                </tr>
            @endforeach
            <tr>
                <td class="summary-label">Total :</td>
                <td class="summary-value">{{ $formatTon((float) ($summary['total_ton'] ?? 0)) }}</td>
            </tr>
        </table>
    </div>

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
