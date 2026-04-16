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
            margin: 20mm 10mm 20mm 10mm;
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

        .group-title {
            margin: 10px 0 2px 0;
            font-size: 12px;
            font-weight: bold;
            text-align: left;
        }

        .sub-title {
            margin: 0 0 6px 8px;
            font-size: 11px;
            font-weight: bold;
            text-align: left;
        }

        table.data-table {
            width: 100%;
            border-collapse: collapse;
            border-spacing: 0;
            font-size: 10px;
            border: 1px solid #000;
            table-layout: fixed;
            margin: 0 0 4px 8px;
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

        table.data-table th {
            text-align: center;
            font-weight: bold;
            background: #fff;
            font-size: 11px;
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

        @include('reports.partials.pdf-footer-table-style') .section-title {
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
        $generatedDate = $generatedAt->copy()->locale('id')->translatedFormat('d-M-y');

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

    <h1 class="report-title">Laporan ST Hidup Rambung MC1 dan MC2 (Detail)</h1>
    <p class="report-subtitle">Per {{ $generatedDate }}</p>

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
            <div class="sub-title">Total Masing-masing Jenis Stock</div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 4%;">No</th>
                        <th>Jenis Stock</th>
                        <th>Jumlah Batang (Pcs)</th>
                        <th>Ton</th>
                        <th>Kubik (m<sup>3</sup>)</th>
                    </tr>
                </thead>
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
            <div class="sub-title">Grand Total Seluruh Group Stock</div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 4%;">No</th>
                        <th>Group Stock</th>
                        <th>Jumlah Batang (Pcs)</th>
                        <th>Ton</th>
                        <th>Kubik (m<sup>3</sup>)</th>
                    </tr>
                </thead>
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


        @include('reports.partials.pdf-footer-table')
    </body>

    </html>
