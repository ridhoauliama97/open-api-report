<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
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
            line-height: 1.15;
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

        .group-header td {
            font-weight: bold;
            font-size: 11px;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding: 3px 5px;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            border-spacing: 0;
        }

        .data-table th,
        .data-table td {
            border-left: 1px solid #000;
            border-right: 1px solid #000;
            padding: 2px 2px;
            vertical-align: middle;
            word-wrap: break-word;
        }

        .data-table th {
            font-weight: bold;
            font-size: 10px;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            text-align: center;
        }

        .subtotal-row td {
            text-align: right;
            font-weight: bold;
            font-size: 10px;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding: 3px 5px;
        }

        .grand-row td {
            text-align: right;
            font-weight: bold;
            font-size: 10px;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding: 4px 5px;
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
    </style>
</head>

<body>
    @php
        $sections = $reportData['sections'] ?? [];
        $totalRows = (int) ($reportData['total_rows'] ?? 0);
        $generatedByName = trim((string) ($reportData['printed_by'] ?? 'sistem'));
    @endphp

    @include('ascends.shared.partials.report-header', [
        'subtitle' => ''
    ])

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 5%;">No</th>
                <th style="width: 28%;">Kode Customer</th>
                <th style="width: 32%;">Nama Customer</th>
                <th style="width: 35%;">Alamat</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($sections as $section)
                @php $sectionRows = $section['rows'] ?? []; @endphp
                @if (count($sectionRows) > 0)
                    <tr class="group-header">
                        <td colspan="4">{{ $section['group_label'] ?? '-' }}</td>
                    </tr>
                    @foreach ($sectionRows as $row)
                        <tr class="{{ $loop->odd ? 'row-odd' : 'row-even' }}">
                            <td class="center">{{ $loop->iteration }}</td>
                            <td>{{ (string) ($row['Kode Customer'] ?? '') }}</td>
                            <td>{{ (string) ($row['Nama Customer'] ?? '') }}</td>
                            <td>{{ (string) ($row['Alamat'] ?? '') }}</td>
                        </tr>
                    @endforeach
                    <tr class="subtotal-row">
                        <td colspan="4">Subtotal {{ $section['group_label'] ?? '-' }} : {{ $section['subtotal'] ?? 0 }}</td>
                    </tr>
                @endif
            @empty
                <tr>
                    <td colspan="4" class="center" style="font-style: italic; padding: 10px 2px;">
                        Tidak ada data.
                    </td>
                </tr>
            @endforelse
            <tr class="grand-row">
                <td colspan="4">Grand Total : {{ $totalRows }}</td>
            </tr>
        </tbody>
    </table>

    @include('reports.partials.pdf-footer-table', [
        'generatedByName' => $generatedByName,
    ])
</body>

</html>
