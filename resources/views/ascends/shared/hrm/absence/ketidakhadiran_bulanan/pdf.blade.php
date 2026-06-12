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
            padding: 2px 2px;
            vertical-align: middle;
            word-wrap: break-word;
        }

        .data-table th {
            font-weight: bold;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            text-align: center;
        }

        .data-table tbody tr:last-child td {
            border-bottom: 1px solid #000;
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

        .nowrap {
            white-space: nowrap;
        }

        .empty-row td {
            text-align: center;
            font-style: italic;
            background: #c9d1df;
            font-weight: bold;
            font-size: 11px;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
        }

        .notes-section {
            margin-top: 5px;
            page-break-inside: auto;
            font-size: 10px !important;
        }

        .notes-title {
            font-size: 10px;
            font-weight: bold;
            text-decoration: underline;
            margin-bottom: 2px;
        }

        .notes-row {
            width: 100%;
            margin-bottom: 6px;
            page-break-inside: auto;
        }

        .notes-row:after {
            content: "";
            display: block;
            clear: both;
        }

        .note-column {
            float: left;
            width: 24%;
            margin-right: 1.333%;
            font-size: 10px !important;
            line-height: 1.15;
            page-break-inside: auto;
        }

        .note-column:nth-child(4n) {
            margin-right: 0;
        }

        .note-heading {
            font-size: 10px;
            font-weight: bold;
            text-align: center;
            border-bottom: 1px solid #000;
            padding: 2px 2px;
            margin-bottom: 2px;
        }

        .note-remark {
            font-weight: bold;
            text-decoration: underline;
            margin-bottom: 2px;
            font-size: 10px !important;
        }

        .note-item {
            margin-bottom: 2px;
            font-size: 10px !important;
        }

        .note-date {
            margin-left: 0;
            margin-bottom: 3px;
            font-size: 10px !important;
        }
    </style>
</head>

<body>
    @php
        $dateColumns = $reportData['date_columns'] ?? [];
        $rows = $reportData['rows'] ?? [];
        $periodLabel = (string) ($reportData['period']['label'] ?? '');
        $generatedAtText = \Carbon\Carbon::parse($generatedAt ?? now())
            ->locale('id')
            ->translatedFormat('d-M-y H:i');
        $generatedByName = trim((string) ($reportData['printed_by'] ?? ''));
        $footerCenterText = '';
        $title = (string) ($reportData['title'] ?? 'Laporan Ketidakhadiran Bulanan');
        $additionalNotes = $reportData['additional_notes'] ?? [];
        $additionalNoteChunks = array_chunk($additionalNotes, 4);
    @endphp

    @include('ascends.shared.partials.report-header', [
        'title' => $title,
        'subtitle' => $periodLabel,
    ])

    <table class="data-table">
        <thead>
            <tr>
                <th rowspan="2" style="width: 3%;">No</th>
                <th rowspan="2" style="width: 17%;">Nama</th>
                <th rowspan="2" style="width: 19%;">Jabatan</th>
                <th colspan="{{ count($dateColumns) }}">Tanggal</th>
                <th rowspan="2" style="width: 5%;">Total</th>
            </tr>
            <tr>
                @foreach ($dateColumns as $column)
                    <th style="width: 2.3%;">{{ (string) ($column['label'] ?? '') }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr class="{{ $loop->odd ? 'row-odd' : 'row-even' }}">
                    <td class="center">{{ $loop->iteration }}</td>
                    <td>{{ (string) ($row['Nama'] ?? '') }}</td>
                    <td>{{ (string) ($row['Jabatan'] ?? '') }}</td>
                    @foreach ($dateColumns as $column)
                        @php
                            $dateKey = (string) ($column['date'] ?? '');
                            $cell = (string) (($row['dates'] ?? [])[$dateKey] ?? '');
                        @endphp
                        <td class="center nowrap">{{ $cell }}</td>
                    @endforeach
                    <td class="center nowrap">{{ (string) ($row['Total'] ?? '') }}</td>
                </tr>
            @empty
                <tr class="empty-row">
                    <td colspan="{{ 4 + count($dateColumns) }}">Tidak ada data ketidakhadiran untuk periode ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if (!empty($additionalNotes))
        <section class="notes-section">
            <div class="notes-title">Keterangan Tambahan:</div>
            @foreach ($additionalNoteChunks as $chunk)
                <div class="notes-row">
                    @foreach ($chunk as $note)
                        <div class="note-column">
                            <div class="note-heading">{{ (string) ($note['label'] ?? '') }}</div>
                            @foreach (($note['groups'] ?? []) as $group)
                                <div class="note-remark">{{ (string) ($group['remark'] ?? '') }}</div>
                                @foreach (($group['items'] ?? []) as $item)
                                    <div class="note-item">
                                        {{ (string) ($item['name'] ?? '') }} ({{ (string) ($item['days'] ?? '0') }}) hari
                                    </div>
                                    @if (trim((string) ($item['dates'] ?? '')) !== '')
                                        <div class="note-date">{{ (string) ($item['dates'] ?? '') }},</div>
                                    @endif
                                @endforeach
                            @endforeach
                        </div>
                    @endforeach
                </div>
            @endforeach
        </section>
    @endif

    @include('reports.partials.pdf-footer-table')
</body>

</html>
