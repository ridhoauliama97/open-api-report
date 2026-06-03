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
            text-align: center;
            font-size: 11px;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
        }

        .center {
            text-align: center;
        }

        .nowrap {
            white-space: nowrap;
        }

        .empty-row td {
            text-align: center;
            font-style: italic;
            background: #c9d1df;
            font-weight: bold;
            font-size: 11px;
        }

        .row-odd td {
            background: #c9d1df;
        }

        .row-even td {
            background: #eef2f8;
        }
    </style>
</head>

<body>
    @php
        $rows = $reportData['rows'] ?? [];
        $printedAt = \Carbon\Carbon::parse($reportData['printed_at'] ?? now())
            ->locale('id')
            ->translatedFormat('d-M-y');
        $generatedAtText = \Carbon\Carbon::parse($generatedAt ?? now())
            ->locale('id')
            ->translatedFormat('d-M-y H:i');
        $generatedByName = trim((string) ($reportData['printed_by'] ?? ''));
    @endphp

    <h1 class="report-title">{!! nl2br(e($reportData['title'])) !!}</h1>
    <p class="report-subtitle"></p>
    {{-- <p class="report-subtitle">Per : {{ $printedAt }}</p> --}}

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 5%;">No</th>
                <th style="width: 8%;">NIK</th>
                <th style="width: 20%;">Nama</th>
                <th style="width: 10%;">Tempat Lahir</th>
                <th style="width: 10%;">Tanggal Lahir</th>
                <th style="width: 7%;">Umur</th>
                <th style="width: 15%;">Pendidikan</th>
                <th style="width: 15%;">Jabatan</th>
                <th style="width: 10%;">HK</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $index => $row)
                <tr class="{{ $loop->odd ? 'row-odd' : 'row-even' }}">
                    <td class="center">{{ $index + 1 }}</td>
                    <td class="center nowrap">{{ (string) ($row['NIK'] ?? '') }}</td>
                    <td>{{ (string) ($row['Nama'] ?? '') }}</td>
                    <td>{{ (string) ($row['Tempat'] ?? '') }}</td>
                    <td class="center nowrap">{{ (string) ($row['Tgl Lahir'] ?? '') }}</td>
                    <td class="center">{{ (string) ($row['Umur'] . ' Thn' ?? '') }}</td>
                    <td>{{ (string) ($row['Pendidikan'] ?? '') }}</td>
                    <td>{{ (string) ($row['Jabatan'] ?? '') }}</td>
                    <td class="nowrap center">{{ (string) ($row['HK'] ?? '') }}</td>
                </tr>
            @empty
                <tr class="empty-row">
                    <td colspan="9">Tidak ada data.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @include('reports.partials.pdf-footer-table')
</body>

</html>
