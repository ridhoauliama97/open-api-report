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

        .buyer-title {
            margin: 10px 0 6px 0;
            font-size: 11px;
            font-weight: bold;
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
            font-size: 9px;
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
        $groups = is_array($data['groups'] ?? null) ? $data['groups'] : [];

        $generatedByName = $generatedBy?->name ?? 'sistem';
        $generatedAtText = $generatedAt->copy()->locale('id')->translatedFormat('d-M-y H:i');
        $start = \Carbon\Carbon::parse((string) ($startDate ?? ''))->locale('id')->translatedFormat('d-M-y');
        $end = \Carbon\Carbon::parse((string) ($endDate ?? ''))->locale('id')->translatedFormat('d-M-y');

        $fmtDate = static function (string $raw): string {
            $t = trim($raw);
            if ($t === '') {
                return '';
            }
            try {
                return \Carbon\Carbon::parse($t)->locale('id')->translatedFormat('d M Y');
            } catch (\Throwable $e) {
                return $t;
            }
        };

        $fmtTon = static function ($v): string {
            return number_format((float) ($v ?? 0), 4, '.', '');
        };

        $fmtPanjang = static function ($v): string {
            return number_format((float) ($v ?? 0), 2, ',', '');
        };

        $fmtIntNoSep = static function ($v): string {
            return (string) ((int) ($v ?? 0));
        };

        $fmtDimInt = static function ($v): string {
            $n = (float) ($v ?? 0);
            return (string) ((int) round($n));
        };
    @endphp

    <h1 class="report-title">Laporan Rekap ST Penjualan</h1>
    <p class="report-subtitle">Periode {{ $start }} s/d {{ $end }}</p>

    @forelse ($groups as $group)
        @php
            $buyer = (string) ($group['pembeli'] ?? '-');
            $rows = is_array($group['rows'] ?? null) ? $group['rows'] : [];
            $totals = is_array($group['totals'] ?? null) ? $group['totals'] : [];
        @endphp

        <div class="buyer-title">Pembeli&nbsp;&nbsp;: {{ $buyer }}</div>

        <table class="data-table">
            <colgroup>
                <col style="width: 10%;"> {{-- NoST --}}
                <col style="width: 13%;"> {{-- Tanggal (ST) --}}
                <col style="width: 30%;"> {{-- Jenis Kayu --}}
                <col style="width: 5%;"> {{-- Tebal --}}
                <col style="width: 5%;"> {{-- Lebar --}}
                <col style="width: 8%;"> {{-- UOM Tbl Lebar --}}
                <col style="width: 7%;"> {{-- Panjang --}}
                <col style="width: 8%;"> {{-- UOM Panjang --}}
                <col style="width: 7%;"> {{-- Jmlh Btg --}}
                <col style="width: 7%;"> {{-- Ton --}}
            </colgroup>
            <thead>
                <tr>
                    <th>NoST</th>
                    <th>Tanggal (ST)</th>
                    <th>Jenis Kayu</th>
                    <th>Tebal</th>
                    <th>Lebar</th>
                    <th>UOMTblLebar</th>
                    <th>Panjang</th>
                    <th>UOMPanjang</th>
                    <th>JmlhBtg</th>
                    <th>Ton</th>
                </tr>
            </thead>
            @if ($rows !== [])
                <tfoot>
                    <tr class="table-end-line">
                        <td colspan="10"></td>
                    </tr>
                </tfoot>
            @endif
            <tbody>
                @forelse ($rows as $r)
                    @php $rowIndex = ($loop->index ?? 0) + 1; @endphp
                    <tr class="data-row {{ $rowIndex % 2 === 1 ? 'row-odd' : 'row-even' }}">
                        <td class="center data-cell">{{ (string) ($r['NoST'] ?? '') }}</td>
                        <td class="center data-cell">
                            {{ $fmtDate((string) ($r['TanggalSTRaw'] ?? ($r['Tanggal (ST)'] ?? ''))) }}
                        </td>
                        <td class="data-cell">{{ (string) ($r['Jenis Kayu'] ?? '') }}</td>
                        <td class="center data-cell">{{ $fmtDimInt($r['Tebal'] ?? 0) }}</td>
                        <td class="center data-cell">{{ $fmtDimInt($r['Lebar'] ?? 0) }}</td>
                        <td class="center data-cell">{{ (string) ($r['UOMTblLebar'] ?? '') }}</td>
                        <td class="number data-cell">{{ $fmtPanjang($r['Panjang'] ?? 0) }}</td>
                        <td class="center data-cell">{{ (string) ($r['UOMPanjang'] ?? '') }}</td>
                        <td class="number data-cell">{{ $fmtIntNoSep($r['JmlhBtg'] ?? 0) }}</td>
                        <td class="number data-cell">{{ $fmtTon($r['Ton'] ?? 0) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="center">Tidak ada data.</td>
                    </tr>
                @endforelse
                @if ($rows !== [])
                    <tr class="totals-row">
                        <td colspan="8" class="number">Jmlh Batang / {{ $buyer }} :</td>
                        <td class="number">{{ $fmtIntNoSep($totals['jmlh_btg'] ?? 0) }}</td>
                        <td class="number">{{ $fmtTon($totals['ton'] ?? 0) }}</td>
                    </tr>
                @endif
            </tbody>
        </table>
    @empty
        <div class="center">Tidak ada data.</div>
    @endforelse

    @include('reports.partials.pdf-footer-table')
</body>

</html>
