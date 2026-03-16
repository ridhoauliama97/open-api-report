<!DOCTYPE html>
<html lang="id">

<head>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif:ital,wght@0,100..900;1,100..900&display=swap"
        rel="stylesheet">
    <meta charset="utf-8">
    <style>
        * {
            box-sizing: border-box;
        }

        @page {
            margin: 12mm 10mm 14mm 10mm;
            footer: html_reportFooter;
        }

        body {
            margin: 0;
            font-family: "Noto Serif", serif;
            font-size: 10px;
            line-height: 1.15;
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

        table.data-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 10px;
            border: 1px solid #000;
            table-layout: fixed;
        }

        thead {
            display: table-header-group;
        }

        tfoot {
            display: table-footer-group;
        }

        tr {
            page-break-inside: avoid;
        }

        table.data-table th,
        table.data-table td {
            border: 0;
            border-left: 1px solid #000;
            border-top: 0;
            border-bottom: 0;
            padding: 2px 3px;
            vertical-align: middle;
        }

        table.data-table th:first-child,
        table.data-table td:first-child {
            border-left: 0;
        }

        table.data-table th {
            text-align: center;
            font-weight: bold;
            background: #fff;
            font-size: 10px;
            white-space: nowrap;
            border-bottom: 1px solid #000;
        }

        .center {
            text-align: center;
        }

        .number {
            text-align: right;
            white-space: nowrap;
            font-family: "Calibri", "DejaVu Sans", sans-serif;
        }

        table.data-table tbody tr.data-row td.data-cell {
            border-top: 0 !important;
            border-bottom: 0 !important;
        }

        .table-end-line td {
            border-top: 1px solid #000 !important;
            border-right: 0 !important;
            border-bottom: 0 !important;
            border-left: 0 !important;
            padding: 0 !important;
            height: 0 !important;
            line-height: 0 !important;
            background: #fff !important;
        }

        .row-odd td {
            background: #c9d1df;
        }

        .row-even td {
            background: #eef2f8;
        }

        .footer-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .footer-table td {
            font-size: 8px;
            font-style: italic;
            padding: 0;
            border: 0;
            vertical-align: bottom;
        }

        .footer-print {
            width: 65%;
        }

        .footer-spacer {
            width: 10%;
        }

        .footer-page-cell {
            width: 25%;
            text-align: right;
            white-space: nowrap;
        }

        .footer-page {
            display: inline-block;
            position: relative;
            right: -4px;
        }
    </style>
</head>

<body>
    @php
        $data = is_array($reportData ?? null) ? $reportData : [];
        $rows = is_array($data['rows'] ?? null) ? $data['rows'] : [];

        $generatedByName = $generatedBy?->name ?? 'sistem';
        $generatedAtText = $generatedAt->copy()->locale('id')->translatedFormat('d-M-y H:i');

        $fmtDimInt = static function ($v): string {
            $n = (float) ($v ?? 0);
            if (abs($n) < 0.0000001) {
                return '';
            }
            return (string) ((int) round($n));
        };

        $fmtInt = static function ($v): string {
            $n = (int) ($v ?? 0);
            return $n === 0 ? '' : (string) number_format($n, 0, '.', ',');
        };
    @endphp

    <h1 class="report-title">Laporan ST Hidup Kering</h1>
    <p class="report-subtitle">
        {{-- Hari &gt;= {{ (int) ($hari ?? 0) }} | Mode: {{ (string) ($mode ?? 'INCLUDE') }} --}}
    </p>

    <table class="data-table">
        <colgroup>
            <col style="width: 6%;"> {{-- No --}}
            <col style="width: 16%;"> {{-- No ST --}}
            <col style="width: 8%;"> {{-- Tebal --}}
            <col style="width: 8%;"> {{-- Lebar --}}
            <col style="width: 12%;"> {{-- Jmlh Batang --}}
            <col style="width: 10%;"> {{-- Lokasi --}}
            <col style="width: 10%;"> {{-- Usia --}}
            <col style="width: 22%;"> {{-- Jenis --}}
            <col style="width: 8%;"> {{-- BB --}}
        </colgroup>
        <thead>
            <tr>
                <th>No</th>
                <th>No ST</th>
                <th>Tebal (mm) </th>
                <th>Lebar</th>
                <th>Jumlah Batang (Pcs)</th>
                <th>Lokasi</th>
                <th>Usia (Hari)</th>
                <th>Jenis</th>
                <th>BB</th>
            </tr>
        </thead>
        @if ($rows !== [])
            <tfoot>
                <tr class="table-end-line">
                    <td colspan="9"></td>
                </tr>
            </tfoot>
        @endif
        <tbody>
            @forelse ($rows as $r)
                @php $rowIndex = ($loop->index ?? 0) + 1; @endphp
                <tr class="data-row {{ $rowIndex % 2 === 1 ? 'row-odd' : 'row-even' }}">
                    <td class="center data-cell">{{ $rowIndex }}</td>
                    <td class="center data-cell">{{ (string) ($r['NoST'] ?? '') }}</td>
                    <td class="center data-cell">{{ $fmtDimInt($r['Tebal'] ?? 0) }}</td>
                    <td class="center data-cell">{{ $fmtDimInt($r['Lebar'] ?? 0) }}</td>
                    <td class="center data-cell">{{ $fmtInt($r['JmlhBatang'] ?? 0) }}</td>
                    <td class="center data-cell">{{ (string) ($r['IdLokasi'] ?? '') }}</td>
                    <td class="center data-cell">{{ $fmtInt($r['UsiaHari'] ?? 0) }}</td>
                    <td class="data-cell">{{ (string) ($r['Jenis'] ?? '') }}</td>
                    <td class="center data-cell">{{ (string) ($r['BB'] ?? '') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="center">Tidak ada data.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <htmlpagefooter name="reportFooter">
        <table class="footer-table">
            <colgroup>
                <col style="width: 25%;">
                <col style="width: 25%;">
                <col style="width: 25%;">
                <col style="width: 25%;">
            </colgroup>
            <tr>
                <td colspan="2" class="footer-print">Dicetak oleh: {{ $generatedByName }} pada
                    {{ $generatedAtText }}</td>
                <td class="footer-spacer"></td>
                <td class="footer-page-cell"><span class="footer-page">Halaman {PAGENO} dari {nbpg}</span></td>
            </tr>
        </table>
    </htmlpagefooter>
    <sethtmlpagefooter name="reportFooter" value="on" />
</body>

</html>
