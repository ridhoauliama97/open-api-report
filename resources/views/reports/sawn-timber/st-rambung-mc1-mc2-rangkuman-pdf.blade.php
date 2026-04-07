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

        .sub-title {
            margin: 20px 0 6px 0;
            font-size: 11px;
            font-weight: bold;
            text-align: left;
        }

        .section-title {
            margin: 14px 0 6px 0;
            font-size: 11px;
            font-weight: bold;
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

        @include('reports.partials.pdf-footer-table-style')
    </style>
</head>

<body>
    @php
        $data = is_array($reportData ?? null) ? $reportData : [];
        $summaryTables = is_array($data['summary_tables'] ?? null) ? $data['summary_tables'] : [];
        $tableSummaryRows = is_array($summaryTables['tables'] ?? null) ? $summaryTables['tables'] : [];
        $groupSummaryRows = is_array($summaryTables['groups'] ?? null) ? $summaryTables['groups'] : [];
        $grand = is_array($summaryTables['grand'] ?? null) ? $summaryTables['grand'] : [];

        $generatedByName = $generatedBy?->name ?? 'sistem';
        $generatedAtText = $generatedAt->copy()->locale('id')->translatedFormat('d-M-y H:i');

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

    <h1 class="report-title">Laporan ST Rambung MC1 dan MC2 (Rangkuman)</h1>

    <div class="sub-title">Total Masing-masing Jenis Stock</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 4%;">No</th>
                <th style="width: 55%">Jenis Stock</th>
                <th style="width: 17%">Jumlah Batang (Pcs)</th>
                <th style="width: 12%">Ton</th>
                <th style="width: 12%">Kubik (m<sup>3</sup>)</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($tableSummaryRows as $sr)
                @php $rowIndex = ($loop->index ?? 0) + 1; @endphp
                <tr class="data-row {{ $rowIndex % 2 === 1 ? 'row-odd' : 'row-even' }}">
                    <td class="center data-cell">{{ $rowIndex }}</td>
                    <td class="data-cell">{{ (string) ($sr['tabel'] ?? '') }}</td>
                    <td class="center data-cell">{{ $fmtInt($sr['pcs'] ?? 0) }}</td>
                    <td class="number data-cell">{{ $fmt4($sr['ton'] ?? 0) }}</td>
                    <td class="number data-cell">{{ $fmt4($sr['kubik'] ?? 0) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="center">Tidak ada data.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="sub-title">Grand Total Seluruh Group Stock</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 4%;">No</th>
                <th style="width: 55%">Group Stock</th>
                <th style="width: 17%">Jumlah Batang (Pcs)</th>
                <th style="width: 12%">Ton</th>
                <th style="width: 12%">Kubik (m<sup>3</sup>)</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($groupSummaryRows as $sr)
                @php $rowIndex = ($loop->index ?? 0) + 1; @endphp
                <tr class="data-row {{ $rowIndex % 2 === 1 ? 'row-odd' : 'row-even' }}">
                    <td class="center data-cell">{{ $rowIndex }}</td>
                    <td class="data-cell">{{ (string) ($sr['jenis'] ?? '') }}</td>
                    <td class="center data-cell">{{ $fmtInt($sr['pcs'] ?? 0) }}</td>
                    <td class="number data-cell">{{ $fmt4($sr['ton'] ?? 0) }}</td>
                    <td class="number data-cell">{{ $fmt4($sr['kubik'] ?? 0) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="center">Tidak ada data.</td>
                </tr>
            @endforelse
            <tr class="totals-row">
                <td colspan="2" class="center">Grand Total</td>
                <td class="center">{{ $fmtInt($grand['pcs'] ?? 0) }}</td>
                <td class="number">{{ $fmt4($grand['ton'] ?? 0) }}</td>
                <td class="number">{{ $fmt4($grand['kubik'] ?? 0) }}</td>
            </tr>
        </tbody>
    </table>

    @include('reports.partials.pdf-footer-table')
</body>

</html>
