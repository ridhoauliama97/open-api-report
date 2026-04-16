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
            margin: 12mm 8mm 14mm 8mm;
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
            margin: 8px 0 4px 0;
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

        .category-table {
            width: 100%;
            margin-bottom: 8px;
        }

        .summary-title {
            margin: 10px 0 4px 0;
            font-weight: bold;
            font-size: 11px;
        }

        .summary-table {
            width: 48%;
            border-collapse: collapse;
            border-spacing: 0;
        }

        .summary-table td {
            border: 0 !important;
            padding: 1px 4px;
        }

        .empty-state {
            text-align: center;
            padding: 12px;
            font-style: italic;
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
        $summary = is_array($data['summary'] ?? null)
            ? $data['summary']
            : ['total_rows' => 0, 'total_categories' => 0, 'total_spk' => 0, 'grand_total' => 0];
        $tanggalText = \Carbon\Carbon::parse($tanggalAkhir)->locale('id')->translatedFormat('d-M-y');

        $categoryLabels = [
            'ST' => 'ST',
            'BJADI' => 'Barang Jadi',
            'CCAKHIR' => 'CC Akhir',
            'FJ' => 'Finger Joint',
            'LMT' => 'Laminating',
            'S4S' => 'S4S',
            'SAND' => 'Sanding',
            'MLD' => 'Moulding',
        ];

        $fmtNumber = static fn($value): string => $value === null
            ? ''
            : rtrim(rtrim(number_format((float) $value, 2, '.', ','), '0'), '.');
        $fmtTotal = static fn($value): string => $value === null ? '' : number_format((float) $value, 4, '.', ',');
    @endphp

    <h1 class="report-title">Laporan Stock Hidup Per No SPK</h1>
    <div class="report-subtitle">Per Tanggal : {{ $tanggalText }}</div>

    @forelse ($categories as $category)
        @php
            $displayCategory =
                $categoryLabels[(string) ($category['name'] ?? '')] ?? (string) ($category['name'] ?? '-');
            $spks = is_array($category['spks'] ?? null) ? $category['spks'] : [];
            $categoryTotal = (float) ($category['total'] ?? 0);
            $rowNo = 1;
        @endphp

        <div class="section-title">Kategori : {{ $displayCategory }}</div>
        <table class="report-table category-table">
            <thead>
                <tr>
                    <th style="width: 40px;">No</th>
                    <th>Jenis</th>
                    <th style="width: 86px;">No SPK</th>
                    <th style="width: 110px;">Buyer</th>
                    <th style="width: 50px;">Umur</th>
                    <th style="width: 50px;">Tebal</th>
                    <th style="width: 56px;">Lebar</th>
                    <th style="width: 60px;">Panjang</th>
                    <th style="width: 52px;">Pcs</th>
                    <th style="width: 90px;">Total (m3)</th>
                </tr>
            </thead>
            <tfoot>
                <tr class="table-end-line">
                    <td colspan="10"></td>
                </tr>
            </tfoot>
            <tbody>
                @forelse ($spks as $spk)
                    @php
                        $rows = is_array($spk['rows'] ?? null) ? $spk['rows'] : [];
                        $spkLabel = (string) (($spk['no_spk'] ?? '-') !== '-' ? $spk['no_spk'] ?? '-' : 'Tanpa No SPK');
                        $buyer = trim((string) ($spk['buyer'] ?? ''));
                    @endphp

                    @foreach ($rows as $index => $row)
                        <tr class="{{ $rowNo % 2 === 1 ? 'row-odd' : 'row-even' }}">
                            <td class="center">{{ $rowNo }}</td>
                            <td>{{ (string) ($row['Jenis'] ?? '') }}</td>
                            <td class="center">{{ $spkLabel }}</td>
                            <td class="center">{{ $buyer }}</td>
                            <td class="number">{{ $fmtNumber($row['Umur'] ?? null) }}</td>
                            <td class="number">{{ $fmtNumber($row['Tebal'] ?? null) }}</td>
                            <td class="number">{{ $fmtNumber($row['Lebar'] ?? null) }}</td>
                            <td class="number">{{ $fmtNumber($row['Panjang'] ?? null) }}</td>
                            <td class="number">{{ $fmtNumber($row['Pcs'] ?? null) }}</td>
                            <td class="number">{{ $fmtTotal($row['Total'] ?? null) }}</td>
                        </tr>
                        @php $rowNo++; @endphp
                    @endforeach
                @empty
                    <tr>
                        <td colspan="10" class="empty-state">Tidak ada data untuk kategori ini.</td>
                    </tr>
                @endforelse

                <tr class="total-row">
                    <td colspan="9" class="center">Total Per Kategori : {{ $displayCategory }}</td>
                    <td class="number">{{ $fmtTotal($categoryTotal) }}</td>
                </tr>
            </tbody>
        </table>
    @empty
        <table class="report-table">
            <tbody>
                <tr>
                    <td class="empty-state">Tidak ada data untuk tanggal ini.</td>
                </tr>
            </tbody>
        </table>
    @endforelse

    @if ($categories !== [])
        <div class="summary-title">Rangkuman Hasil :</div>
        <table class="summary-table">
            <tbody>
                <tr>
                    <td>Total No SPK</td>
                    <td class="number">{{ number_format((float) ($summary['total_spk'] ?? 0), 0, '.', ',') }}</td>
                </tr>
                <tr>
                    <td>Total Kategori</td>
                    <td class="number">{{ number_format((float) ($summary['total_categories'] ?? 0), 0, '.', ',') }}
                    </td>
                </tr>
                @foreach ($categories as $category)
                    @php
                        $displayCategory =
                            $categoryLabels[(string) ($category['name'] ?? '')] ?? (string) ($category['name'] ?? '-');
                    @endphp
                    <tr>
                        <td>Total {{ $displayCategory }}</td>
                        <td class="number">{{ $fmtTotal($category['total'] ?? null) }}</td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td>Grand Total (m3)</td>
                    <td class="number">{{ $fmtTotal($summary['grand_total'] ?? null) }}</td>
                </tr>
            </tbody>
        </table>
    @endif

    @include('reports.partials.pdf-footer-table')
</body>

</html>
