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
            margin: 10mm 6mm 12mm 6mm;
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

        .section-title {
            margin: 10px 0 4px 0;
            font-size: 11px;
            font-weight: bold;
        }

        table {
            width: 100%;
            border-collapse: collapse;
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

        .summary-table {
            width: 70%;
            margin-bottom: 12px;
        }

        .total-row td {
            font-weight: bold;
            border-top: 1px solid #000 !important;
            background: #fff !important;
        }

        @include('reports.partials.pdf-footer-table-style')
    </style>
</head>

<body>
    @php
        $data = is_array($reportData ?? null) ? $reportData : [];
        $categories = is_array($data['categories'] ?? null) ? $data['categories'] : [];
        $summaryRows = is_array($data['summary_rows'] ?? null) ? $data['summary_rows'] : [];
        $grandTotals = is_array($data['grand_totals'] ?? null) ? $data['grand_totals'] : [];
        $start = \Carbon\Carbon::parse($startDate)->locale('id')->translatedFormat('d-M-y');
        $end = \Carbon\Carbon::parse($endDate)->locale('id')->translatedFormat('d-M-y');

        $fmtNumber = static function ($value, int $decimals = 4, bool $blankWhenZero = true): string {
            if ($value === null || !is_numeric($value)) {
                return '';
            }

            $float = (float) $value;
            if ($blankWhenZero && abs($float) < 0.0000001) {
                return '';
            }

            return number_format($float, $decimals, '.', ',');
        };

        $fmtInt = static function ($value): string {
            if ($value === null || !is_numeric($value)) {
                return '';
            }

            return number_format((float) $value, 0, '.', ',');
        };
    @endphp

    <h1 class="report-title">Laporan Label Perhari</h1>
    <div class="report-subtitle">Periode {{ $start }} s/d {{ $end }}</div>

    @foreach ($categories as $category)
        @php
            $chunks = array_chunk($category['rows'] ?? [], 300);
        @endphp
        @foreach ($chunks as $chunkIndex => $chunkRows)
            <div class="section-title">
                {{ $category['no'] ?? '' }}. {{ $category['name'] ?? '-' }}
                @if (count($chunks) > 1)
                    (Bagian {{ $chunkIndex + 1 }}/{{ count($chunks) }})
                @endif
            </div>
            <table class="report-table">
                <thead>
                    <tr>
                        <th style="width: 36px;">No</th>
                        <th style="width: 88px;">No Label</th>
                        <th style="width: 56px;">Urut</th>
                        <th style="width: 82px;">No SPK</th>
                        <th style="width: 82px;">SPK Asal</th>
                        <th style="width: 96px;">Mesin</th>
                        <th>Jenis</th>
                        <th style="width: 54px;">Tebal</th>
                        <th style="width: 54px;">Lebar</th>
                        <th style="width: 58px;">Panjang</th>
                        <th style="width: 58px;">Pcs</th>
                        <th style="width: 84px;">Berat</th>
                    </tr>
                </thead>
                <tfoot>
                    <tr class="table-end-line">
                        <td colspan="12"></td>
                    </tr>
                </tfoot>
                <tbody>
                    @foreach ($chunkRows as $index => $row)
                        <tr class="{{ ($index + 1) % 2 === 1 ? 'row-odd' : 'row-even' }}">
                            <td class="center">{{ $chunkIndex * 300 + $index + 1 }}</td>
                            <td>{{ ($row['NoLabel'] ?? '') !== '' ? $row['NoLabel'] : '-' }}</td>
                            <td class="center">{{ ($row['NoUrut'] ?? '') !== '' ? $row['NoUrut'] : '-' }}</td>
                            <td class="center">{{ $row['NoSPK'] ?: '-' }}</td>
                            <td class="center">{{ $row['NoSPKAsal'] ?: '-' }}</td>
                            <td>{{ $row['Mesin'] ?: '-' }}</td>
                            <td>{{ $row['Jenis'] ?? '-' }}</td>
                            <td class="center">{{ ($row['Tebal'] ?? '') !== '' ? $row['Tebal'] : '-' }}</td>
                            <td class="center">{{ ($row['Lebar'] ?? '') !== '' ? $row['Lebar'] : '-' }}</td>
                            <td class="center">{{ ($row['Panjang'] ?? '') !== '' ? $row['Panjang'] : '-' }}</td>
                            <td class="number">{{ $fmtInt($row['JmlhBatang'] ?? null) }}</td>
                            <td class="number">{{ $fmtNumber($row['Berat'] ?? null) }}</td>
                        </tr>
                    @endforeach
                    @if ($chunkIndex === count($chunks) - 1)
                        <tr class="total-row">
                            <td colspan="10" class="center">Total {{ $category['name'] ?? '-' }}</td>
                            <td class="number">{{ $fmtInt($category['total_pcs'] ?? null) }}</td>
                            <td class="number">{{ $fmtNumber($category['total_berat'] ?? null) }}</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        @endforeach
    @endforeach

    <div class="section-title">Rangkuman</div>
    <table class="report-table summary-table">
        <thead>
            <tr>
                <th>Kategori</th>
                <th style="width: 84px;">Jumlah</th>
                <th style="width: 90px;">Total Pcs</th>
                <th style="width: 90px;">Total Berat</th>
            </tr>
        </thead>
        <tfoot>
            <tr class="table-end-line">
                <td colspan="4"></td>
            </tr>
        </tfoot>
        <tbody>
            @foreach ($summaryRows as $index => $row)
                <tr class="{{ ($index + 1) % 2 === 1 ? 'row-odd' : 'row-even' }}">
                    <td>{{ $row['Kategori'] ?? '-' }}</td>
                    <td class="number">{{ $fmtInt($row['LabelCount'] ?? null) }}</td>
                    <td class="number">{{ $fmtInt($row['TotalPcs'] ?? null) }}</td>
                    <td class="number">{{ $fmtNumber($row['TotalBerat'] ?? null) }}</td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td class="center">Total</td>
                <td class="number">{{ $fmtInt($grandTotals['label_count'] ?? null) }}</td>
                <td class="number">{{ $fmtInt($grandTotals['pcs'] ?? null) }}</td>
                <td class="number">{{ $fmtNumber($grandTotals['berat'] ?? null) }}</td>
            </tr>
        </tbody>
    </table>

    @include('reports.partials.pdf-footer-table')
</body>

</html>
