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
    </style>
</head>

<body>
    @php
        $groupedRows = $reportData['grouped_rows'] ?? [];
        $printedAt = \Carbon\Carbon::parse($reportData['printed_at'] ?? now())
            ->locale('id')
            ->translatedFormat('d-M-y');
        $generatedAtText = \Carbon\Carbon::parse($generatedAt ?? now())
            ->locale('id')
            ->translatedFormat('d-M-y H:i');
        $generatedByName = trim((string) ($reportData['printed_by'] ?? ''));
    @endphp

    <h1 class="report-title">Laporan Daftar Karyawan (UC)<br>Berdasarkan Abjad</h1>
    <p class="report-subtitle"></p>
    {{-- <p class="report-subtitle">Per : {{ $printedAt }}</p> --}}

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 6%;">No</th>
                <th style="width: 34%;">Nama</th>
                <th style="width: 14%;">No ID</th>
                <th style="width: 34%;">Posisi</th>
                <th style="width: 12%;">Paraf</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($groupedRows as $group)
                <tr class="group-row">
                    <td colspan="5">{{ $group['label'] ?? '' }}</td>
                </tr>

                @php $rowNumber = 0; @endphp
                @foreach ($group['rows'] ?? [] as $row)
                    @php $rowNumber++; @endphp
                    <tr class="{{ $loop->odd ? 'row-odd' : 'row-even' }}">
                        <td class="center">{{ $rowNumber }}</td>
                        <td>{{ (string) ($row['Nama'] ?? '') }}</td>
                        <td class="center nowrap">{{ (string) ($row['No ID'] ?? '') }}</td>
                        <td>{{ (string) ($row['Posisi'] ?? '') }}</td>
                        <td>{{ (string) ($row['Paraf'] ?? '') }}</td>
                    </tr>
                @endforeach
            @empty
                <tr class="empty-row">
                    <td colspan="5">Tidak ada data.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @include('reports.partials.pdf-footer-table')
</body>

</html>
