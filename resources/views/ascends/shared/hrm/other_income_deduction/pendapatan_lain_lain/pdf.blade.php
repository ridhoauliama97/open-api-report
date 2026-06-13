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
            line-height: 1.2;
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
            margin: 0;
            font-size: 16px;
            font-weight: bold;
        }

        .report-subtitle {
            text-align: center;
            margin: 2px 0 20px 0;
            font-size: 12px;
            color: #000;
        }

        .section-title {
            margin-bottom: 4px;
            font-size: 11px;
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
            padding: 3px 4px;
            vertical-align: top;
            word-wrap: break-word;
        }

        .data-table th {
            font-weight: bold;
            font-size: 10px;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            text-align: center;
        }

        .center {
            text-align: center;
        }

        .right {
            text-align: right;
        }

        .row-odd td {
            background: #c9d1df;
        }

        .row-even td {
            background: #eef2f8;
        }

        .total-row td {
            font-weight: bold;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
        }

        .empty-row td {
            text-align: center;
            font-style: italic;
            background: #c9d1df;
            font-weight: bold;
            font-size: 11px;
        }
    </style>
</head>

<body>
    @php
        $rows = $reportData['rows'] ?? [];
        $periodLabel = (string) ($reportData['period']['label'] ?? '');
        $generatedAtText = \Carbon\Carbon::parse($generatedAt ?? now())
            ->locale('id')
            ->translatedFormat('d-M-y H:i');
        $generatedByName = trim((string) ($reportData['printed_by'] ?? ''));
        $title = trim((string) ($reportData['title'] ?? 'Laporan Pendapatan Lain-Lain'));
        $sectionTitle = trim((string) ($reportData['section_title'] ?? 'Penambahan'));
    @endphp

    @include('ascends.shared.partials.report-header', ['subtitle' => $periodLabel, 'title' => $title])

    <div class="section-title">{{ $sectionTitle }}</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 23%;">Nama Lengkap</th>
                <th style="width: 11%;">Tanggal</th>
                <th style="width: 45%;">Keterangan</th>
                <th style="width: 13%;">Disetujui Oleh</th>
                <th style="width: 8%;">Jumlah</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr class="{{ $loop->odd ? 'row-odd' : 'row-even' }}">
                    <td>{{ (string) ($row['Nama Lengkap'] ?? '') }}</td>
                    <td class="center">{{ (string) ($row['Tanggal'] ?? '') }}</td>
                    <td>{{ (string) ($row['Keterangan'] ?? '') }}</td>
                    <td class="center">{{ (string) ($row['Disetujui Oleh'] ?? '') }}</td>
                    <td class="right">{{ (string) ($row['Jumlah'] ?? '0') }}</td>
                </tr>
            @empty
                <tr class="empty-row">
                    <td colspan="5">Tidak ada data pendapatan lain-lain yang dapat ditampilkan.</td>
                </tr>
            @endforelse

            <tr class="total-row">
                <td colspan="4" class="center">Total :</td>
                <td class="right">{{ (string) ($reportData['total_amount'] ?? '0') }}</td>
            </tr>
        </tbody>
    </table>

    <htmlpagefooter name="reportFooter">
        @include('reports.partials.pdf-footer-table', [
            'generatedByName' => $generatedByName,
            'generatedAtText' => $generatedAtText,
        ])
    </htmlpagefooter>
</body>

</html>
