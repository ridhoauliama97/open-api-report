<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif:ital,wght@0,100..900;1,100..900&display=swap"
        rel="stylesheet">
    <style>
        body {
            font-family: "Noto Serif", serif;
            font-size: 10px;
            margin: 0;
            padding: 0;
        }
        .report-companyTitle {
            font-size: 18px;
            font-weight: bold;
            text-align: center;
            margin: 0 0 4px;
        }
        .report-title {
            font-size: 16px;
            font-weight: bold;
            text-align: center;
            margin: 0;
        }
        .report-subtitle {
            font-size: 12px;
            color: #636466;
            text-align: center;
            margin: 2px 0 20px;
        }
        .data-table {
            border: 1px solid #000;
            border-collapse: collapse;
            width: calc(100% - 2px);
            table-layout: fixed;
        }
        .data-table th,
        .data-table td {
            border: 1px solid #000;            padding: 1px 2px;
            vertical-align: top;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
        .data-table th {
            background-color: #eef2f8;
            font-weight: bold;
        }
        .section-header td {
            font-weight: bold;
            font-style: italic;
            color: #9c111d;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding-left: 4px;
        }
        .sub-section-header td {
            font-weight: bold;
            color: #9c111d;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding-left: 4px;
        }
        .item-row td {
            padding-left: 4px;
        }
        .row-odd td {
            background-color: #c9d1df;
        }
        .row-even td {
            background-color: #eef2f8;
        }
        .subtotal-row td {
            font-weight: bold;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
        }
        .grand-total-row td {
            font-weight: bold;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
        }
        .empty-row td {
            font-style: italic;
            font-weight: bold;
            color: #9c111d;
            background-color: #c9d1df;
        }
        .number {
            text-align: right;
        }
        .nowrap {
            white-space: nowrap;
        }
        .number-negative {
            color: #9c111d;
        }
    
        /* standardized table borders */
        .report-table {
            border: 1px solid #000;
            border-collapse: collapse;
            width: calc(100% - 2px);
            line-height: inherit;
        }
.report-table th, .report-table td {
            border: 1px solid #000;
            word-wrap: break-word;
            padding: 2px;
            vertical-align: top;
        }

        .report-table th { font-weight: bold; background-color: #eef2f8; }
.footer-table, .header-table, .meta-table, .signature-table {
            border-collapse: collapse;
        }
.footer-table td, .header-table td, .meta-table td, .signature-table td { padding: 1px 2px; vertical-align: top; }
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
