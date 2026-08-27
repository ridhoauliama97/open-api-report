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
            margin: 0;
            padding: 0;
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

        .section-title {
            margin: 14px 0 6px 0;
            font-size: 12px;
            font-weight: bold;
        }

        table {
            width: calc(100% - 2px);
            line-height: inherit;
            border-collapse: collapse;
            border-spacing: 0;
            border: 1px solid #000;
        }

        th,
        td {
            border: 1px solid #000;
            word-wrap: break-word;
            padding: 2px 2px;
        }

        td.center {
            text-align: center;
        }

        td.label {
            white-space: nowrap;
        }

        td.number {
            text-align: right;
            white-space: nowrap;
            font-family: "Calibri", "DejaVu Sans", sans-serif;
        }

        .row-odd td {
            background: #c9d1df;
        }

        .row-even td {
            background: #eef2f8;
        }

        .totals-row td {
            font-weight: bold;
        }

        .headers-row th {
            font-weight: bold;
        }

        /* --- Gotenberg wave3 extras --- */
        .meta-layout,
        .meta-table,
        .note-table,
        .ratio-table {
            width: auto;
        }

        table.meta-layout,
        table.meta-table,
        table.note-table,
        table.ratio-table,
        table.meta-layout td,
        table.meta-table td,
        table.note-table td,
        table.ratio-table td,
        table.meta-layout th,
        table.meta-table th,
        .meta-label,
        .meta-separator,
        .meta-value {
            border: 0;
        }

        .group-title,
        .customer-title,
        .grade-output,
        .date-separator,
        .grade-title {
            font-style: italic;
            font-weight: bold;
            color: #9c111d;
        }

        .total-row td,
        .grand-total-row td,
        .row-last td,
        .before-total td,
        .totals-label {
            font-weight: bold;
        }

        .page-break {
            page-break-after: always;
        }
    </style>
</head>

<body>
    @php
        $groups = is_array($reportData['groups'] ?? null) ? $reportData['groups'] : [];
        $reportSummary = is_array($reportData['summary'] ?? null) ? $reportData['summary'] : [];

        $formatDate = static function (?string $value): string {
            if ($value === null || trim($value) === '') {
                return '-';
            }

            try {
                return \Carbon\Carbon::parse($value)->copy()->locale('id')->translatedFormat('d-M-y');
            } catch (\Throwable $exception) {
                return $value;
            }
        };

        $formatDecimal = static function ($value): string {
            $text = number_format((float) $value, 1, '.', '');

            return str_ends_with($text, '.0') ? substr($text, 0, -2) : $text;
        };

        $formatTon = static function ($value): string {
            return number_format((float) $value, 4, '.', '');
        };
    @endphp

    <h1 class="report-title">Laporan Tally Hasil Sawmill Detail</h1>
    <p class="report-subtitle">Periode : {{ $formatDate(isset($startDate) ? (string) $startDate : null) }} s/d
        {{ $formatDate(isset($endDate) ? (string) $endDate : null) }}
    </p>
    @foreach ($groups as $group)
        @php
            $header = is_array($group['header'] ?? null) ? $group['header'] : [];
            $rows = is_array($group['rows'] ?? null) ? $group['rows'] : [];
            $summary = is_array($group['summary'] ?? null) ? $group['summary'] : [];
        @endphp

        <table class="meta-layout">
            <tbody>
                <tr>
                    <td style="width: 50%; padding-right: 14px;">
                        <table class="meta-block">
                            <tbody>
                                <tr>
                                    <td class="meta-label">No. Meja</td>
                                    <td class="meta-separator">:</td>
                                    <td class="meta-value">{{ $header['no_meja'] ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="meta-label">No. ST</td>
                                    <td class="meta-separator">:</td>
                                    <td class="meta-value">{{ $header['no_st'] ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="meta-label">Tanggal</td>
                                    <td class="meta-separator">:</td>
                                    <td class="meta-value">{{ $formatDate($header['tanggal'] ?? null) }}</td>
                                </tr>
                                <tr>
                                    <td class="meta-label">Operator</td>
                                    <td class="meta-separator">:</td>
                                    <td class="meta-value">{{ $header['operator'] ?? '-' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </td>
                    <td style="width: 50%; padding-left: 14px;">
                        <table class="meta-block">
                            <tbody>
                                <tr>
                                    <td class="meta-label">Supplier</td>
                                    <td class="meta-separator">:</td>
                                    <td class="meta-value">{{ $header['supplier'] ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="meta-label">Jenis Kayu</td>
                                    <td class="meta-separator">:</td>
                                    <td class="meta-value">{{ $header['jenis_kayu'] ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="meta-label">No KB</td>
                                    <td class="meta-separator">:</td>
                                    <td class="meta-value">{{ $header['no_kb'] ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="meta-label">No.Plat</td>
                                    <td class="meta-separator">:</td>
                                    <td class="meta-value">{{ $header['no_plat'] ?? '-' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </td>
                </tr>
            </tbody>
        </table>

        <table class="report-table">
            <thead>
                <tr class="headers-row">
                    <th style="width: 6%;">No</th>
                    <th style="width: 9%;">Tebal</th>
                    <th style="width: 9%;">Lebar</th>
                    <th style="width: 14%;">UOM Tbl Lebar</th>
                    <th style="width: 10%;">Panjang</th>
                    <th style="width: 14%;">UOM Panjang</th>
                    <th style="width: 14%;">Jmlh Batang</th>
                    <th style="width: 24%;">Ton</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    <tr class="data-row {{ $loop->odd ? 'row-odd' : 'row-even' }} {{ $loop->last ? 'row-last' : '' }}">
                        <td class="data-cell">{{ $row['no'] ?? '' }}</td>
                        <td class="data-cell">{{ $formatDecimal($row['tebal'] ?? 0) }}</td>
                        <td class="data-cell">{{ $formatDecimal($row['lebar'] ?? 0) }}</td>
                        <td class="data-cell">{{ $row['uom_tbl_lebar'] ?? '-' }}</td>
                        <td class="number data-cell">{{ $formatDecimal($row['panjang'] ?? 0) }}</td>
                        <td class="data-cell">{{ $row['uom_panjang'] ?? '-' }}</td>
                        <td class="number data-cell">
                            {{ number_format((int) ($row['jumlah_batang'] ?? 0), 0, '.', ',') }}
                        </td>
                        <td class="number data-cell">{{ $formatTon($row['ton'] ?? 0) }}</td>
                    </tr>
                @endforeach
                <tr class="totals-row">
                    <td colspan="6" class="totals-label">Total</td>
                    <td class="number">{{ number_format((int) ($summary['total_batang'] ?? 0), 0, '.', ',') }}</td>
                    <td class="number">{{ $formatTon($summary['total_ton'] ?? 0) }}</td>
                </tr>
            </tbody>
        </table>
    @endforeach

    @if ($groups !== [])
        <div class="page-break"></div>
        <div class="section-title">Rangkuman Grand Total</div>

        <table class="report-table">
            <thead>
                <tr class="headers-row">
                    <th style="width: 8%;">No</th>
                    <th style="width: 12%;">No. Meja</th>
                    <th style="width: 22%;">No. ST</th>
                    <th style="width: 22%;">No KB</th>
                    <th style="width: 16%;">Total Batang</th>
                    <th style="width: 20%;">Total Ton</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($groups as $group)
                    @php
                        $header = is_array($group['header'] ?? null) ? $group['header'] : [];
                        $summary = is_array($group['summary'] ?? null) ? $group['summary'] : [];
                    @endphp
                    <tr class="data-row {{ $loop->odd ? 'row-odd' : 'row-even' }} {{ $loop->last ? 'row-last' : '' }}">
                        <td class="data-cell">{{ $loop->iteration }}</td>
                        <td class="data-cell">{{ $header['no_meja'] ?? '-' }}</td>
                        <td class="data-cell">{{ $header['no_st'] ?? '-' }}</td>
                        <td class="data-cell">{{ $header['no_kb'] ?? '-' }}</td>
                        <td class="number data-cell">
                            {{ number_format((int) ($summary['total_batang'] ?? 0), 0, '.', ',') }}
                        </td>
                        <td class="number data-cell">{{ $formatTon($summary['total_ton'] ?? 0) }}</td>
                    </tr>
                @endforeach
                <tr class="totals-row">
                    <td colspan="4" class="totals-label">Grand Total</td>
                    <td class="number">
                        {{ number_format((int) ($reportSummary['total_batang'] ?? 0), 0, '.', ',') }}
                    </td>
                    <td class="number">{{ $formatTon($reportSummary['total_ton'] ?? 0) }}</td>
                </tr>
            </tbody>
        </table>
    @endif

</body>

</html>