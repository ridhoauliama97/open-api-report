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
            margin: 2px 0 16px 0;
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
            padding: 2px 3px;
            vertical-align: middle;
            word-wrap: break-word;
        }

        .data-table th {
            font-weight: bold;
            font-size: 11px;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            text-align: center;
            vertical-align: middle;
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

        .section-title-row td {
            font-weight: bold;
            text-align: center;
            font-size: 11px;
            font-style: italic;
            padding: 4px 5px;
            color: #9c111d;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
        }

        .empty-row td {
            text-align: center;
            font-style: italic;
            background: #c9d1df;
            font-weight: bold;
            font-size: 11px;
            border-bottom: 1px solid #000;
        }

        .section-break {
            page-break-before: always;
        }
    </style>
</head>

<body>
    @php
        $sections = $reportData['sections'] ?? [];
        $generatedAtText = \Carbon\Carbon::parse($generatedAt ?? now())
            ->locale('id')
            ->translatedFormat('d-M-y H:i');
        $generatedByName = trim((string) ($reportData['printed_by'] ?? ''));
        $yearLabel = $reportData['year_label'] ?? \Carbon\Carbon::now()->format('Y');
        $company = $reportData['company'] ?? $company ?? '';
    @endphp

    @foreach ($sections as $sectionIndex => $section)
        @php
            $rows = $section['rows'] ?? [];
            $sectionTitle = $section['title'] ?? '';
        @endphp

        {{-- Gunakan page-break-before: always untuk section > 1 --}}
        <div class="{{ $sectionIndex > 0 ? 'section-break' : '' }}">
            @php
                $title = $sectionTitle;
            @endphp
            @include('ascends.shared.partials.report-header', ['subtitle' => $yearLabel])

            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 5%;">No</th>
                        <th style="width: 22%;">Nama</th>
                        <th style="width: 10%;">NIK</th>
                        <th style="width: 23%;">Jabatan</th>
                        <th style="width: 12%;">Tanggal Masuk</th>
                        <th style="width: 12%;">Lama Bekerja</th>
                        <th style="width: 16%;">Gaji Pokok</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $index => $row)
                        <tr class="{{ $index % 2 === 0 ? 'row-odd' : 'row-even' }}">
                            <td class="center">{{ $index + 1 }}</td>
                            <td>{{ (string) ($row['Nama'] ?? '') }}</td>
                            <td class="center">{{ (string) ($row['NIK'] ?? '') }}</td>
                            <td>{{ (string) ($row['Jabatan'] ?? '') }}</td>
                            <td class="center">{{ (string) ($row['Tanggal Masuk'] ?? '') }}</td>
                            <td class="center">{{ (string) ($row['Lama Bekerja'] ?? '') }}</td>
                            <td class="right">{{ (string) ($row['Gaji Pokok'] ?? '0') }}</td>
                        </tr>
                    @empty
                        <tr class="empty-row">
                            <td colspan="7">Tidak Ada Data</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            {{-- Tanda Tangan --}}
            <table style="width: 100%; margin-top: 40px;">
                <tr>
                    <td style="width: 50%; text-align: center;">Pujimulio, {{ \Carbon\Carbon::now()->locale('id')->translatedFormat('j F Y') }}</td>
                    <td style="width: 50%; text-align: center;"></td>
                </tr>
                <tr>
                    <td style="width: 50%; text-align: center;">Dibuat Oleh,</td>
                    <td style="width: 50%; text-align: center;">Diketahui Oleh,</td>
                </tr>
                <tr>
                    <td colspan="2" style="height: 50px;"></td>
                </tr>
                <tr>
                    <td style="text-align: center; font-weight: bold;">{{ $generatedByName }}</td>
                    <td style="text-align: center; font-weight: bold;">DINA</td>
                </tr>
                <tr>
                    <td style="text-align: center;"></td>
                    <td style="text-align: center;">KA. Dept Korporate HRGA</td>
                </tr>
            </table>
        </div>
    @endforeach

    <htmlpagefooter name="reportFooter">
        @include('reports.partials.pdf-footer-table', [
            'generatedByName' => $generatedByName,
            'generatedAtText' => $generatedAtText,
        ])
    </htmlpagefooter>
</body>

</html>
