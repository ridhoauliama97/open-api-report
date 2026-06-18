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

        .customer-title {
            margin: 10px 0 6px 0;
            font-weight: bold;
            font-size: 11px;
        }

        table.data-table {
            width: 100%;
            border-collapse: collapse;
            border-spacing: 0;
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
            font-size: 11px;
            border-bottom: 1px solid #000;
            background: #fff;
        }

        table.data-table tbody td {
            border-top: 0;
            border-bottom: 0;
        }

        table.data-table tfoot td {
            border-top: 1px solid #000;
            font-weight: bold;
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

        .summary-table {
            width: 46%;
            margin-top: 14px;
            border-collapse: collapse;
        }

        .summary-table td {
            border: 0;
            padding: 2px 4px;
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
        $groups = is_array($data['customer_groups'] ?? null) ? $data['customer_groups'] : [];
        $summary = is_array($data['summary'] ?? null) ? $data['summary'] : [];

        $generatedByName = $generatedBy?->name ?? 'sistem';
        $generatedAtText = $generatedAt->copy()->locale('id')->translatedFormat('d-M-y H:i');

        $fmtM3 = static function ($v): string {
            $n = (float) ($v ?? 0.0);
            return number_format($n, 4, '.', ',');
        };

        $fmtDate = static function ($v): string {
            $t = is_string($v) ? trim($v) : '';
            if ($t === '') {
                return '';
            }

            try {
                return \Carbon\Carbon::parse($t)->locale('id')->translatedFormat('d-M-y');
            } catch (\Throwable $e) {
                return $t;
            }
        };
    @endphp

    <h1 class="report-title">Laporan KD Upah Per-Customer</h1>
    <p class="report-subtitle"></p>

    @forelse ($groups as $group)
        @php
            $rows = is_array($group['rows'] ?? null) ? $group['rows'] : [];
        @endphp

        <div class="customer-title">Customer : {{ $group['customer'] ?? '-' }}</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 5%;">No</th>
                    <th style="width: 13%;">No Proc KD</th>
                    <th style="width: 9%;">Ruang KD</th>
                    <th style="width: 13%;">Tgl Masuk</th>
                    <th style="width: 13%;">Tgl Keluar</th>
                    <th style="width: 31%;">Jenis Kayu</th>
                    <th style="width: 16%;">M3</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr class="{{ $loop->iteration % 2 === 1 ? 'row-odd' : 'row-even' }}">
                        <td class="center">{{ $loop->iteration }}</td>
                        <td class="center">{{ $row['NoProcKD'] ?? '' }}</td>
                        <td class="center">{{ $row['NoRuangKD'] ?? '' }}</td>
                        <td class="center">{{ $fmtDate($row['TglMasuk'] ?? '') }}</td>
                        <td class="center">{{ $fmtDate($row['TglKeluar'] ?? '') }}</td>
                        <td>{{ $row['Jenis'] ?? '' }}</td>
                        <td class="number">{{ $fmtM3($row['m3'] ?? 0) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="center">Tidak ada data.</td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td colspan="6" class="center">Total {{ $group['customer'] ?? '-' }}</td>
                    <td class="number">{{ $fmtM3($group['total_m3'] ?? 0) }}</td>
                </tr>
            </tfoot>
        </table>
    @empty
        <div class="center">Tidak ada data.</div>
    @endforelse

    @include('reports.partials.pdf-footer-table')
</body>

</html>
