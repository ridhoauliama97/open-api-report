<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
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

        @page {
            margin: 14mm 10mm 14mm 10mm;
            footer: html_reportFooter;
        }

        body {
            margin: 0;
            font-family: "Noto Serif", serif;
            font-size: 10px;
            line-height: 1.15;
            color: #000;
        }

        .report-companyTitle {
            text-align: center;
            margin: 0 0 4px 0;
            font-size: 18px;
            font-weight: bold;
        }

        .report-title {
            text-align: center;
            margin: 0 0 20px 0;
            font-size: 16px;
            font-weight: bold;
        }

        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }

        .meta-table td {
            padding: 1px 2px;
            vertical-align: top;
        }

        .meta-label {
            width: 115px;
            font-weight: bold;
        }

        .meta-separator {
            width: 8px;
            text-align: center;
            font-weight: bold;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            page-break-inside: auto;
            border-spacing: 0;
            border: 1px solid #000;
        }

        .data-table th,
        .data-table td {
            border-left: 1px solid #000;
            border-right: 1px solid #000;
            padding: 2px 3px;
            vertical-align: middle;
            word-wrap: break-word;
        }

        .data-table th {
            font-weight: bold;
            font-size: 10px;
            text-align: center;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
        }

        .row-odd td {
            background: #c9d1df;
        }

        .row-even td {
            background: #eef2f8;
        }

        .empty-row td {
            text-align: center;
            font-style: italic;
            background: #c9d1df;
            font-weight: bold;
            font-size: 10px;
        }

        .row-late td {
            font-weight: bold;
            font-style: italic;
        }

        .check-box {
            width: 10px;
            height: 10px;
            margin: 0 auto;
            border-collapse: collapse;
        }

        .check-box td {
            width: 10px;
            height: 10px;
            border: 1px solid #000;
            padding: 0;
        }

        .summary-section {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
        }

        .summary-section td {
            vertical-align: top;
        }

        .summary-text {
            width: 58%;
            padding-top: 18px;
            line-height: 1.6;
        }

        .summary-check-wrap {
            width: 42%;
            text-align: right;
        }

        .summary-check-table {
            width: 250px;
            margin-left: auto;
            border-collapse: collapse;
        }

        .summary-check-table td,
        .summary-check-table th {
            border: 1px solid #000;
            padding: 4px 6px;
            text-align: center;
            font-weight: normal;
        }

        .signature-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 24px;
        }

        .signature-table td {
            vertical-align: top;
            padding: 0;
        }

        .signature-label {
            font-weight: bold;
        }

        .signature-left {
            width: 42%;
        }

        .signature-right {
            width: 58%;
        }

        .signature-line-table,
        .conclusion-line-table {
            border-collapse: collapse;
        }

        .signature-line-table {
            width: 135px;
            margin-top: 58px;
        }

        .conclusion-line-table {
            width: 100%;
            margin-top: 36px;
        }

        .signature-line-table td,
        .conclusion-line-table td {
            border-bottom: 1px solid #000;
            height: 18px;
            padding: 0;
            line-height: 0;
        }

        .center {
            text-align: center;
        }
    </style>
</head>

<body>
    @php
        $rows = array_values($rows ?? ($reportData['rows'] ?? []));
        $generatedAtText = \Carbon\Carbon::parse($generatedAt ?? now())
            ->locale('id')
            ->translatedFormat('d-M-y H:i');
        $generatedByName = trim((string) ($reportData['printed_by'] ?? ''));
        $group = trim((string) ($reportData['group'] ?? $company ?? ''));
        $summary = $reportData['summary'] ?? [];
        $presentNoLate = $summary['present_no_late'] ?? ['count' => 0, 'percent' => 0];
        $late = $summary['late'] ?? ['count' => 0, 'percent' => 0];
        $notPresent = $summary['not_present'] ?? ['count' => 0, 'percent' => 0];
    @endphp

    @include('ascends.shared.partials.report-header')

    <table class="meta-table">
        <tr>
            <td class="meta-label">Divisi</td>
            <td class="meta-separator">:</td>
            <td>{{ $group }}</td>
            <td class="meta-label">Tanggal</td>
            <td class="meta-separator">:</td>
            <td>{{ (string) ($reportData['report_date'] ?? '') }}</td>
        </tr>
        <tr>
            <td class="meta-label">Penanggung Jawab</td>
            <td class="meta-separator">:</td>
            <td>{{ (string) ($reportData['responsible_person'] ?? '') }}</td>
            <td class="meta-label">Tema</td>
            <td class="meta-separator">:</td>
            <td>{{ (string) ($reportData['theme'] ?? '') }}</td>
        </tr>
        <tr>
            <td class="meta-label">Tamu</td>
            <td class="meta-separator">:</td>
            <td>{{ (string) ($reportData['guests'] ?? '') }}</td>
            <td class="meta-label">Jam</td>
            <td class="meta-separator">:</td>
            <td>{{ (string) ($reportData['time'] ?? '') }}</td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th rowspan="2" style="width: 5%;">No</th>
                <th rowspan="2" style="width: 30%;">Nama</th>
                <th rowspan="2" style="width: 12%;">Jam Masuk</th>
                <th rowspan="2" style="width: 16%;">Briefing</th>
                <th colspan="3">Telat / Tidak Briefing</th>
            </tr>
            <tr>
                <th style="width: 9%;">Sakit</th>
                <th style="width: 9%;">Izin</th>
                <th style="width: 9%;">Alfa</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                @php
                    $rowClass = $loop->odd ? 'row-odd' : 'row-even';
                    if ((string) ($row['is_late'] ?? '') === '1') {
                        $rowClass .= ' row-late';
                    }
                @endphp
                <tr class="{{ $rowClass }}">
                    <td class="center" @if ($loop->last) style="border-bottom: 1px solid #000;" @endif>
                        {{ $loop->iteration }}
                    </td>
                    <td @if ($loop->last) style="border-bottom: 1px solid #000;" @endif>{{ (string) ($row['Nama'] ?? '') }}
                    </td>
                    <td class="center" @if ($loop->last) style="border-bottom: 1px solid #000;" @endif>
                        {{ (string) ($row['Jam Masuk'] ?? '') }}
                    </td>
                    <td class="center" @if ($loop->last) style="border-bottom: 1px solid #000;" @endif>
                        <table class="check-box">
                            <tr>
                                <td>&nbsp;</td>
                            </tr>
                        </table>
                    </td>
                    <td class="center" @if ($loop->last) style="border-bottom: 1px solid #000;" @endif>
                        {{ (string) ($row['Sakit'] ?? '') }}
                    </td>
                    <td class="center" @if ($loop->last) style="border-bottom: 1px solid #000;" @endif>
                        {{ (string) ($row['Izin'] ?? '') }}
                    </td>
                    <td class="center" @if ($loop->last) style="border-bottom: 1px solid #000;" @endif>
                        {{ (string) ($row['Alfa'] ?? '') }}
                    </td>
                </tr>
            @empty
                <tr class="empty-row">
                    <td colspan="7">Tidak ada data.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <table class="summary-section">
        <tr>
            <td class="summary-text">
                <div>Akumulasi Hadir Tidak Telat = {{ $presentNoLate['count'] }}
                    ({{ $presentNoLate['percent'] }}%)</div>
                <div>Akumulasi Telat
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    = {{ $late['count'] }} ({{ $late['percent'] }}%)</div>
                <div>Akumulasi Tidak Hadir &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; =
                    {{ $notPresent['count'] }}
                    ({{ $notPresent['percent'] }}%)
                </div>
            </td>
            <td class="summary-check-wrap">
                <table class="summary-check-table">
                    <tr>
                        <th colspan="2">Check Jumlah</th>
                        <th rowspan="2">Selisih</th>
                    </tr>
                    <tr>
                        <td>ABH</td>
                        <td>Foto</td>
                        {{-- <td></td> --}}
                    </tr>
                    <tr>
                        <td style="height: 32px;"></td>
                        <td></td>
                        <td></td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="signature-table">
        <tr>
            <td class="signature-left">
                <span class="signature-label">Penanggung Jawab</span>
            </td>
            <td class="signature-right">
                <span class="signature-label">Kesimpulan Briefing</span>
            </td>
        </tr>
        <tr>
            <td class="signature-left">
                <table class="signature-line-table">
                    <tr>
                        <td>&nbsp;</td>
                    </tr>
                </table>
            </td>
            <td class="signature-right">
                <table class="conclusion-line-table">
                    <tr>
                        <td>&nbsp;</td>
                    </tr>
                    <tr>
                        <td>&nbsp;</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    @include('reports.partials.pdf-footer-table')
</body>

</html>