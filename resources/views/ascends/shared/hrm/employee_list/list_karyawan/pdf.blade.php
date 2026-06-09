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
            color: #636466;
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
            vertical-align: middle;
            word-wrap: break-word;
        }

        .data-table th {
            font-weight: bold;
            font-size: 11px;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
        }

        .group-row td {
            font-weight: bold;
            text-align: center;
            font-size: 11px;
            font-style: italic;
            padding: 5px 6px;
            color: #9c111d;
            background: #fff;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
        }

        .center {
            text-align: center;
        }

        .nowrap {
            white-space: nowrap;
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
            font-size: 11px;
        }

        htmlpagefooter table.footer-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9px;
            color: #000;
        }

        htmlpagefooter table.footer-table td {
            border: 0;
            padding: 0;
            vertical-align: bottom;
        }

        .footer-print {
            text-align: left;
        }

        .footer-page {
            text-align: right;
            white-space: nowrap;
        }
    </style>
</head>

<body>
    @php
        $groupedRows = $reportData['grouped_rows'] ?? [];
        $rows = $reportData['rows'] ?? [];
        $printedAt = \Carbon\Carbon::parse($reportData['printed_at'] ?? now())
            ->locale('id')
            ->translatedFormat('d-M-y');
        $generatedAtText = \Carbon\Carbon::parse($generatedAt ?? now())
            ->locale('id')
            ->translatedFormat('d-M-y H:i');
        $generatedByName = trim((string) ($reportData['printed_by'] ?? ''));
    @endphp

    @include('ascends.shared.partials.report-header', ['subtitle' => 'Per : ' . $printedAt])

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 4%;">No</th>
                <th style="width: 20%;">Nama</th>
                <th style="width: 8%;">Jenis Kelamin</th>
                <th style="width: 7%;">Usia</th>
                <th style="width: 19%;">Jabatan</th>
                <th style="width: 12%;">Lama Bekerja</th>
                <th style="width: 22%;">Keterangan</th>
                <th style="width: 11%;">Tempat Ibadah</th>
                <th style="width: 7%;">Lemari</th>
            </tr>
        </thead>
        <tbody>
            @if (!empty($groupedRows))
                @foreach ($groupedRows as $department => $departmentRows)
                    <tr class="group-row">
                        <td colspan="9">{{ $department }}</td>
                    </tr>
                    @foreach ($departmentRows as $index => $row)
                        <tr class="data-row {{ $loop->odd ? 'row-odd' : 'row-even' }}">
                            <td class="center">{{ $index + 1 }}</td>
                            <td>{{ (string) ($row['Nama'] ?? '') }}</td>
                            <td class="center">{{ (string) ($row['Jenis Kelamin'] ?? '') }}</td>
                            <td class="center">{{ (string) ($row['Usia'] ?? '') }}</td>
                            <td>{{ (string) ($row['Jabatan'] ?? '') }}</td>
                            <td class="center">{{ (string) ($row['Lama Bekerja'] ?? '') }}</td>
                            <td>{{ (string) ($row['Keterangan'] ?? '') }}</td>
                            <td>{{ (string) ($row['Nama Tempat Ibadah'] ?? '') }}</td>
                            <td>{{ (string) ($row['Lemari'] ?? '') }}</td>
                        </tr>
                    @endforeach
                @endforeach
            @else
                @forelse ($rows as $index => $row)
                    <tr class="data-row {{ $loop->odd ? 'row-odd' : 'row-even' }}">
                        <td class="center">{{ $index + 1 }}</td>
                        <td>{{ (string) ($row['Nama'] ?? '') }}</td>
                        <td class="center">{{ (string) ($row['Jenis Kelamin'] ?? '') }}</td>
                        <td class="center">{{ (string) ($row['Usia'] ?? '') }}</td>
                        <td>{{ (string) ($row['Jabatan'] ?? '') }}</td>
                        <td class="center">{{ (string) ($row['Lama Bekerja'] ?? '') }}</td>
                        <td>{{ (string) ($row['Keterangan'] ?? '') }}</td>
                        <td>{{ (string) ($row['Nama Tempat Ibadah'] ?? '') }}</td>
                        <td>{{ (string) ($row['Lemari'] ?? '') }}</td>
                    </tr>
                @empty
                    <tr class="empty-row">
                        <td colspan="9" class="center">Tidak ada data.</td>
                    </tr>
                @endforelse
            @endif
        </tbody>
    </table>

    @include('reports.partials.pdf-footer-table')
</body>

</html>