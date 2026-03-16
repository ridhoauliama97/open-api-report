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

        .group-title {
            margin: 10px 0 6px 0;
            font-size: 12px;
            font-weight: bold;
            text-align: center;
        }

        .sub-title {
            margin: 10px 0 6px 0;
            font-size: 11px;
            font-weight: bold;
            text-align: left;
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

        /* Hilangkan garis horizontal antar baris data. */
        table.data-table tbody tr.data-row td.data-cell {
            border-top: 0 !important;
            border-bottom: 0 !important;
        }

        table.data-table tbody tr.totals-row td {
            border-top: 1px solid #000;
            font-weight: bold;
            background: #fff;
        }

        /* Tfoot tipis untuk "garis akhir tabel" ketika page break terjadi di tengah data. */
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

        /* Nudge page text to align with table's right outer border. */
        .footer-page {
            display: inline-block;
            position: relative;
            right: -4px;
        }

        .section-title {
            margin: 14px 0 6px 0;
            font-size: 12px;
            font-weight: bold;
        }
    </style>
</head>

<body>
    @php
        $data = is_array($reportData ?? null) ? $reportData : [];
        $groups = is_array($data['groups'] ?? null) ? $data['groups'] : [];
        $summaryTables = is_array($data['summary_tables'] ?? null) ? $data['summary_tables'] : [];

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

        $fmt4 = static function ($v): string {
            $n = (float) ($v ?? 0);
            if (abs($n) < 0.0000001) {
                return '';
            }
            return number_format($n, 4, '.', '');
        };
    @endphp

    <h1 class="report-title">Laporan ST Rambung MC1 dan MC2 (Detail)</h1>

    @forelse ($groups as $group)
        @php
            $jenis = (string) ($group['jenis'] ?? '-');
            $subgroups = is_array($group['subgroups'] ?? null) ? $group['subgroups'] : [];
        @endphp

        <div class="group-title">{{ $jenis }}</div>

        @foreach ($subgroups as $sg)
            @php
                $label = (string) ($sg['label'] ?? '');
                $rows = is_array($sg['rows'] ?? null) ? $sg['rows'] : [];
                $totals = is_array($sg['totals'] ?? null) ? $sg['totals'] : [];
            @endphp

            @if ($label !== '')
                <div class="sub-title">{{ $label }}</div>
            @endif

            <table class="data-table">
                <colgroup>
                    <col style="width: 6%;"> {{-- No --}}
                    <col style="width: 22%;"> {{-- No ST --}}
                    <col style="width: 8%;"> {{-- Tebal --}}
                    <col style="width: 8%;"> {{-- Lebar --}}
                    <col style="width: 8%;"> {{-- Panjang --}}
                    <col style="width: 10%;"> {{-- Pcs --}}
                    <col style="width: 19%;"> {{-- Ton --}}
                    <col style="width: 19%;"> {{-- Kubik --}}
                </colgroup>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>No ST</th>
                        <th>Tebal </th>
                        <th>Lebar </th>
                        <th>Panjang</th>
                        <th>Jumlah Batang (pcs)</th>
                        <th>Ton</th>
                        <th>Kubik (m<sup>3</sup>)</th>
                    </tr>
                </thead>
                @if ($rows !== [])
                    <tfoot>
                        <tr class="table-end-line">
                            <td colspan="8"></td>
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
                            <td class="center data-cell">{{ $fmtDimInt($r['Panjang'] ?? 0) }}</td>
                            <td class="center data-cell">{{ $fmtInt($r['Pcs'] ?? 0) }}</td>
                            <td class="number data-cell">{{ $fmt4($r['Ton'] ?? 0) }}</td>
                            <td class="number data-cell">{{ $fmt4($r['Kubik'] ?? 0) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="center">Tidak ada data.</td>
                        </tr>
                    @endforelse
                    @if ($rows !== [])
                        <tr class="totals-row">
                            <td colspan="5" class="number" style="text-align: center;">Total {{ $label }}
                            </td>
                            <td class="center">{{ $fmtInt($totals['pcs'] ?? 0) }}</td>
                            <td class="number">{{ $fmt4($totals['ton'] ?? 0) }}</td>
                            <td class="number">{{ $fmt4($totals['kubik'] ?? 0) }}</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        @endforeach
        @empty
            <div class="center">Tidak ada data.</div>
        @endforelse

        @php
            $tableSummaryRows = is_array($summaryTables['tables'] ?? null) ? $summaryTables['tables'] : [];
            $groupSummaryRows = is_array($summaryTables['groups'] ?? null) ? $summaryTables['groups'] : [];
            $grand = is_array($summaryTables['grand'] ?? null) ? $summaryTables['grand'] : [];
        @endphp

        @if ($tableSummaryRows !== [] || $groupSummaryRows !== [])
            <div class="section-title">Rangkuman</div>
        @endif

        @if ($tableSummaryRows !== [])
            <div class="sub-title">Total Masing-masing Tabel</div>
            <table class="data-table">
                <colgroup>
                    <col style="width: 6%;"> {{-- No --}}
                    <col style="width: 34%;"> {{-- Jenis --}}
                    <col style="width: 26%;"> {{-- Tabel --}}
                    <col style="width: 10%;"> {{-- Pcs --}}
                    <col style="width: 12%;"> {{-- Ton --}}
                    <col style="width: 12%;"> {{-- Kubik --}}
                </colgroup>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Tabel</th>
                        <th>Jumlah Batang (Pcs)</th>
                        <th>Ton</th>
                        <th>Kubik (m<sup>3</sup>)</th>
                    </tr>
                </thead>
                <tfoot>
                    <tr class="table-end-line">
                        <td colspan="6"></td>
                    </tr>
                </tfoot>
                <tbody>
                    @foreach ($tableSummaryRows as $sr)
                        @php $rowIndex = ($loop->index ?? 0) + 1; @endphp
                        <tr class="data-row {{ $rowIndex % 2 === 1 ? 'row-odd' : 'row-even' }}">
                            <td class="center data-cell">{{ $rowIndex }}</td>
                            <td class="data-cell">{{ (string) ($sr['tabel'] ?? '') }}</td>
                            <td class="center data-cell">{{ $fmtInt($sr['pcs'] ?? 0) }}</td>
                            <td class="number data-cell">{{ $fmt4($sr['ton'] ?? 0) }}</td>
                            <td class="number data-cell">{{ $fmt4($sr['kubik'] ?? 0) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        @if ($groupSummaryRows !== [])
            <div class="sub-title">Total Seluruh Group</div>
            <table class="data-table">
                <colgroup>
                    <col style="width: 6%;"> {{-- No --}}
                    <col style="width: 58%;"> {{-- Group --}}
                    <col style="width: 10%;"> {{-- Pcs --}}
                    <col style="width: 13%;"> {{-- Ton --}}
                    <col style="width: 13%;"> {{-- Kubik --}}
                </colgroup>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Group (Jenis Kayu)</th>
                        <th>Jumlah Batang (Pcs)</th>
                        <th>Ton</th>
                        <th>Kubik (m<sup>3</sup>)</th>
                    </tr>
                </thead>
                <tfoot>
                    <tr class="table-end-line">
                        <td colspan="5"></td>
                    </tr>
                </tfoot>
                <tbody>
                    @foreach ($groupSummaryRows as $sr)
                        @php $rowIndex = ($loop->index ?? 0) + 1; @endphp
                        <tr class="data-row {{ $rowIndex % 2 === 1 ? 'row-odd' : 'row-even' }}">
                            <td class="center data-cell">{{ $rowIndex }}</td>
                            <td class="data-cell">{{ (string) ($sr['jenis'] ?? '') }}</td>
                            <td class="center data-cell">{{ $fmtInt($sr['pcs'] ?? 0) }}</td>
                            <td class="number data-cell">{{ $fmt4($sr['ton'] ?? 0) }}</td>
                            <td class="number data-cell">{{ $fmt4($sr['kubik'] ?? 0) }}</td>
                        </tr>
                    @endforeach
                    <tr class="totals-row">
                        <td colspan="2" style="text-align: center">Grand Total</td>
                        <td class="center">{{ $fmtInt($grand['pcs'] ?? 0) }}</td>
                        <td class="number">{{ $fmt4($grand['ton'] ?? 0) }}</td>
                        <td class="number">{{ $fmt4($grand['kubik'] ?? 0) }}</td>
                    </tr>
                </tbody>
            </table>
        @endif


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
