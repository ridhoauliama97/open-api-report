<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <style>
        * {
            box-sizing: border-box;
        }

        @page {
            margin: 18mm 10mm 18mm 10mm;
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

        table.report-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #000;
            table-layout: fixed;
        }

        thead {
            display: table-header-group;
        }

        tr {
            page-break-inside: avoid;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 4px 6px;
            vertical-align: middle;
        }

        th {
            font-size: 11px;
            text-align: center;
            font-weight: bold;
            background: #fff;
        }

        td.center {
            text-align: center;
        }

        td.number {
            text-align: right;
            white-space: nowrap;
            font-family: "Calibri", "DejaVu Sans", sans-serif;
        }

        .merged-cell {
            vertical-align: middle;
        }

        .row-odd td {
            background: #c9d1df;
        }

        .row-even td {
            background: #eef2f8;
        }

        .footer-left {
            font-size: 8px;
            font-style: italic;
        }

        .footer-right {
            font-size: 8px;
            font-style: italic;
            text-align: right;
        }
    </style>
</head>

<body>
    @php
        $rowsData =
            isset($rows) && is_iterable($rows) ? (is_array($rows) ? $rows : collect($rows)->values()->all()) : [];
        $generatedByName = $generatedBy->name ?? 'sistem';
        $generatedAtText = $generatedAt->copy()->locale('id')->translatedFormat('d-M-y H:i');
        $reportDateText = \Carbon\Carbon::parse($reportDate ?? now()->toDateString())
            ->locale('id')
            ->translatedFormat('d-M-y');
        $formatNumber = static function ($value): string {
            if (!is_numeric($value) || $value === null) {
                return '';
            }

            return number_format((float) $value, 2, '.', ',');
        };
    @endphp

    <h1 class="report-title">Laporaan QC Harian Mixer</h1>
    <p class="report-subtitle">Per Tanggal : {{ $reportDateText }}</p>

    <table class="report-table">
        <thead>
            <tr>
                <th style="width: 4%;">No</th>
                <th style="width: 17%;">No Mixer</th>
                <th style="width: 35%;">Jenis</th>
                <th style="width: 12%;">Moisture</th>
                <th style="width: 12%;">MFI</th>
                <th style="width: 20%;">Melt Temp</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rowsData as $row)
                @php
                    $rowClass = $loop->odd ? 'row-odd' : 'row-even';
                    $moistureValues = collect([
                        $row['Moisture'] ?? null,
                        $row['Moisture2'] ?? null,
                        $row['Moisture3'] ?? null,
                    ])
                        ->filter(static fn($value) => $value !== null && $value !== '')
                        ->values()
                        ->all();
                    $rowspan = max(count($moistureValues), 1);
                @endphp

                <tr class="{{ $rowClass }}">
                    <td class="center merged-cell" rowspan="{{ $rowspan }}">{{ $loop->iteration }}</td>
                    <td class="center merged-cell" rowspan="{{ $rowspan }}">{{ $row['NoMixer'] ?? '' }}</td>
                    <td class="merged-cell" rowspan="{{ $rowspan }}">{{ $row['Jenis'] ?? '' }}</td>
                    <td class="number">{{ $formatNumber($moistureValues[0] ?? null) }}</td>
                    <td class="number merged-cell" rowspan="{{ $rowspan }}">
                        {{ $formatNumber($row['MFI'] ?? null) }}</td>
                    <td class="center merged-cell" rowspan="{{ $rowspan }}">{{ $row['MeltTemp'] ?? '' }}</td>
                </tr>

                @foreach (array_slice($moistureValues, 1) as $moistureValue)
                    <tr class="{{ $rowClass }}">
                        <td class="number">{{ $formatNumber($moistureValue) }}</td>
                    </tr>
                @endforeach
            @empty
                <tr class="row-odd">
                    <td colspan="6" class="center" style="font-size: 11px; font-weight: bold; font-style: italic;">
                        Tidak ada data.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <htmlpagefooter name="reportFooter">
        <div style="width: 100%;">
            <table style="width: 100%; border-collapse: collapse; border: 0;">
                <tr>
                    <td class="footer-left" style="border: 0; padding: 0;">Dicetak oleh {{ $generatedByName }} pada
                        {{ $generatedAtText }}
                    </td>
                    <td class="footer-right" style="border: 0; padding: 0;">Halaman {PAGENO} dari {nbpg}</td>
                </tr>
            </table>
        </div>
    </htmlpagefooter>
    <sethtmlpagefooter name="reportFooter" value="on" />
</body>

</html>
