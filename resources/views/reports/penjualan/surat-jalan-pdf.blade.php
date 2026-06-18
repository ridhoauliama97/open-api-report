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
            margin: 14mm 10mm 42mm 10mm;
            footer: html_reportFooter;
        }

        body {
            margin: 0;
            font-family: "Noto Serif", serif;
            font-size: 10px;
            line-height: 1.15;
            color: #000;
        }

        .document-title {
            margin: 0 0 10px;
            text-align: center;
            font-size: 18px;
            font-weight: bold;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
        }

        .header-table td {
            border: 0;
            padding: 0;
            vertical-align: top;
        }

        .recipient-name {
            margin-top: 2px;
            font-size: 16px;
            font-weight: bold;
        }

        .vehicle {
            margin-top: 8px;
        }

        .meta-table {
            width: auto;
            border-collapse: collapse;
            margin-left: auto;
        }

        .meta-table td {
            border: 0;
            padding: 1px 0;
        }

        .meta-date {
            text-align: right;
        }

        .meta-label {
            width: 112px;
            white-space: nowrap;
            text-align: right;
        }

        .meta-sep {
            width: 12px;
            text-align: center;
        }

        .meta-value {
            min-width: 170px;
            text-align: right;
        }

        .top-line {
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            height: 3px;
            margin: 4px 0 7px;
        }

        .report-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #000;
            page-break-inside: auto;
        }

        thead {
            display: table-header-group;
        }

        tfoot {
            display: table-footer-group;
        }

        tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }

        th,
        td {
            border: 0;
            border-left: 1px solid #000;
            padding: 2px 4px;
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

        tbody td {
            border-top: 0;
            border-bottom: 0;
        }

        tbody tr.date-separator td {
            border-top: 1px solid #000;
        }

        tfoot td {
            border-top: 1px solid #000;
            font-weight: bold;
            background: #fff;
        }

        .row-odd td {
            background: #c9d1df;
        }

        .row-even td {
            background: #eef2f8;
        }

        .center {
            text-align: center;
        }

        .number {
            text-align: right;
            white-space: nowrap;
            font-family: "Calibri", "DejaVu Sans", sans-serif;
        }

        .nowrap {
            white-space: nowrap;
        }

        .empty-state {
            padding: 10px;
            text-align: center;
            font-style: italic;
        }

        .signature-table {
            width: 100%;
            border-collapse: collapse;
        }

        .signature-table td {
            border: 0;
            padding: 5px 8px 0;
            text-align: center;
            vertical-align: top;
            font-size: 10px;
            line-height: 1.15;
        }

        .signature-label {
            line-height: 1.15;
        }

        .signature-top-line {
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            height: 3px;
            line-height: 3px;
            margin-bottom: 4px;
        }

        .signature-line {
            display: inline-block;
            width: 112px;
            font-family: "Calibri", "DejaVu Sans", sans-serif;
            font-size: 10px;
            line-height: 1;
            letter-spacing: 0;
            white-space: nowrap;
        }

        .signature-space td {
            height: 28px;
            line-height: 28px;
            padding: 0;
        }

        .signature-lines td {
            padding-top: 0;
            padding-bottom: 18px;
        }

        .total-row td {
            font-weight: bold;
            font-size: 11px;
        }
    </style>
</head>

<body>
    @php
        $data = is_array($reportData ?? null) ? $reportData : [];
        $rows = is_array($data['rows'] ?? null) ? $data['rows'] : [];
        $header = is_array($data['header'] ?? null) ? $data['header'] : [];
        $summary = is_array($data['summary'] ?? null) ? $data['summary'] : [];

        $fmtHeaderDate = static function ($value): string {
            if ($value === null || trim((string) $value) === '') {
                return '-';
            }

            try {
                return \Carbon\Carbon::parse((string) $value)->locale('id')->translatedFormat('d-M-y');
            } catch (\Throwable) {
                return (string) $value;
            }
        };

        $fmtTableDate = static function ($value): string {
            if ($value === null || trim((string) $value) === '') {
                return '';
            }

            try {
                return \Carbon\Carbon::parse((string) $value)->locale('id')->translatedFormat('d-M-y');
            } catch (\Throwable) {
                return (string) $value;
            }
        };

        $fmtDimension = static fn($value): string => $value === null ? '' : number_format((float) $value, 2, ',', '.');
        $fmtInt = static fn($value): string => $value === null ? '' : number_format((float) $value, 0, '.', ',');
        $fmtVolume = static fn($value): string => number_format((float) $value, 4, '.', '');
    @endphp

    <h1 class="document-title">SURAT JALAN</h1>

    <table class="header-table">
        <tr>
            <td style="width: 50%;">
                <div>Kepada Yth.</div>
                <div class="recipient-name">{{ $header['buyer'] ?? '-' }}</div>
                <div class="vehicle">Nomor Kendaraan : {{ $header['no_plat'] ?? '-' }}</div>
            </td>
            <td style="width: 50%; padding-left: 24px;">
                <table class="meta-table">
                    <tr>
                        <td colspan="3" class="meta-date">Medan, {{ $fmtHeaderDate($generatedAt ?? now()) }}
                        </td>
                    </tr>
                    <tr>
                        <td class="meta-label">No.Surat Jalan</td>
                        <td class="meta-sep">:</td>
                        <td class="meta-value">{{ $header['no_surat_jalan'] ?? ($noJual ?? '-') }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">Jenis Kendaraan</td>
                        <td class="meta-sep">:</td>
                        <td class="meta-value">{{ $header['jenis_kendaraan'] ?? '-' }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="top-line"></div>

    <table class="report-table">
        <thead>
            <tr>
                <th style="width: 11%;">Tanggal</th>
                <th style="width: 10%;">No ST</th>
                <th style="width: 23%;">Jenis Kayu</th>
                <th style="width: 6%;">Tebal</th>
                <th style="width: 6%;">Lebar</th>
                <th style="width: 6%;">UOM</th>
                <th style="width: 7%;">Panjang</th>
                <th style="width: 6%;">UOM</th>
                <th style="width: 7%;">Pcs</th>
                <th style="width: 9%;">M3</th>
                <th style="width: 9%;">Ton</th>
            </tr>
        </thead>
        <tbody>
            @php
                $previousTanggal = null;
            @endphp
            @forelse ($rows as $row)
                @php
                    $currentTanggal = (string) ($row['Tanggal'] ?? '');
                    $isDateSeparator = $previousTanggal !== null && $currentTanggal !== $previousTanggal;
                    $previousTanggal = $currentTanggal;
                @endphp
                <tr
                    class="{{ $loop->iteration % 2 === 1 ? 'row-odd' : 'row-even' }} {{ $isDateSeparator ? 'date-separator' : '' }}">
                    <td class="nowrap center">{{ $fmtTableDate($row['DisplayTanggal'] ?? '') }}</td>
                    <td class="nowrap">{{ $row['NoST'] ?? '-' }}</td>
                    <td>{{ $row['JenisKayu'] ?? '-' }}</td>
                    <td class="number">{{ $fmtDimension($row['Tebal'] ?? null) }}</td>
                    <td class="number">{{ $fmtDimension($row['Lebar'] ?? null) }}</td>
                    <td class="center">{{ $row['UOMTblLebar'] ?? '-' }}</td>
                    <td class="number">{{ $fmtDimension($row['Panjang'] ?? null) }}</td>
                    <td class="center">{{ $row['UOMPanjang'] ?? '-' }}</td>
                    <td class="number">{{ $fmtInt($row['Pcs'] ?? null) }}</td>
                    <td class="number" style="font-weight: bold;">{{ $fmtVolume($row['M3'] ?? 0) }}</td>
                    <td class="number" style="font-weight: bold;">{{ $fmtVolume($row['Ton'] ?? 0) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="11" class="empty-state">Tidak ada data.</td>
                </tr>
            @endforelse
        </tbody>
        @if ($rows !== [])
            <tfoot>
                <tr class="total-row">
                    <td colspan="9" class="center">Total :</td>
                    <td class="number">{{ $fmtVolume($summary['total_m3'] ?? 0) }}</td>
                    <td class="number">{{ $fmtVolume($summary['total_ton'] ?? 0) }}</td>
                </tr>
            </tfoot>
        @endif
    </table>

    @php
        $footerGeneratedByName = $generatedByName ?? ($generatedBy?->name ?? ($generatedBy?->Username ?? 'sistem'));
        $footerGeneratedAtText =
            $generatedAtText ??
            (isset($generatedAt) && method_exists($generatedAt, 'copy')
                ? $generatedAt->copy()->locale('id')->translatedFormat('d-M-y H:i')
                : now()->locale('id')->translatedFormat('d-M-y H:i'));
    @endphp

    <htmlpagefooter name="reportFooter">
        <div class="signature-top-line"></div>
        <table class="signature-table">
            <tr>
                <td rowspan="2" style="width: 17%;">
                    <div class="signature-label">Hormat Kami,</div>
                </td>
                <td rowspan="2" style="width: 17%;">
                    <div class="signature-label">Bagian Gudang,</div>
                </td>
                <td rowspan="2" style="width: 22%;">
                    <div class="signature-label">Terima Kasih,</div>
                </td>
                <td colspan="2" style="width: 28%; padding-bottom: 0;">
                    <div class="signature-label">Diantar Oleh,</div>
                </td>
                <td rowspan="2" style="width: 16%;">
                    <div class="signature-label">Diterima Oleh,</div>
                </td>
            </tr>
            <tr>
                <td style="width: 14%; padding-top: 0;">Supir,</td>
                <td style="width: 14%; padding-top: 0;">Kernet,</td>
            </tr>
            <tr class="signature-space">
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
            <tr class="signature-lines">
                <td>
                    <span class="signature-line">______________</span>
                </td>
                <td>
                    <span class="signature-line">______________</span>
                </td>
                <td>
                    <span class="signature-line">______________</span>
                </td>
                <td>
                    <span class="signature-line">______________</span>
                </td>
                <td>
                    <span class="signature-line">______________</span>
                </td>
                <td>
                    <span class="signature-line">______________</span>
                </td>
            </tr>
        </table>
        <table class="footer-table"
            style="width: 100%; border-collapse: collapse; border-spacing: 0; table-layout: fixed; border: 0; margin: 2px 0 0; padding: 0;">
            <colgroup>
                <col style="width: 68%;">
                <col style="width: 32%;">
            </colgroup>
            <tr>
                <td class="footer-print"
                    style="border: 0; background: transparent; padding: 0; margin: 0; vertical-align: bottom; text-align: left; white-space: nowrap; font-family: 'Noto Serif', serif; font-size: 8px; font-style: italic; font-weight: normal;">
                    Dicetak oleh: {{ $footerGeneratedByName }} pada {{ $footerGeneratedAtText }}
                </td>
                <td class="footer-page-cell"
                    style="border: 0; background: transparent; padding: 0; margin: 0; vertical-align: bottom; text-align: right; white-space: nowrap; font-family: 'Noto Serif', serif; font-size: 8px; font-style: italic; font-weight: normal;">
                    Halaman {PAGENO} dari {nbpg}
                </td>
            </tr>
        </table>
    </htmlpagefooter>
    <sethtmlpagefooter name="reportFooter" value="on" />
</body>

</html>
