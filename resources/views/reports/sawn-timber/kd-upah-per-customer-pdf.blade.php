<!DOCTYPE html>
<html lang="id">

<head>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif:ital,wght@0,100..900;1,100..900&display=swap"
        rel="stylesheet">
    <meta charset="utf-8">
    @include('reports.partials.pdf-reference-style')
    <style>
        table {
            width: 100%;
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
        }

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
        $data = is_array($reportData ?? null) ? $reportData : [];
        $groups = is_array($data['customer_groups'] ?? null) ? $data['customer_groups'] : [];
        $summary = is_array($data['summary'] ?? null) ? $data['summary'] : [];

        $generatedByName = $generatedBy?->name ?? 'sistem';
        $generatedAtText = $generatedAt->copy()->locale('id')->isoFormat('DD-MMM-YY HH:mm');

        $fmtM3 = static function ($v): string {
            $n = (float) ($v ?? 0.0);
            return number_format($n, 4, '.', ',');
        };

        $fmtDate = static function ($v): string {
            $t = is_string($v) ? trim($v) : '';
            if ($t === '') {
                return '';
            }

            try {
                return \Carbon\Carbon::parse($t)->locale('id')->isoFormat('DD-MMM-YY');
            } catch (\Throwable $e) {
                return $t;
            }
        };
    @endphp

    <h1 class="report-title">Laporan KD Upah Per-Customer</h1>
    <p class="report-subtitle"></p>

    @forelse ($groups as $group)
        @php
            $rows = is_array($group['rows'] ?? null) ? $group['rows'] : [];
        @endphp

        <div class="customer-title">Customer : {{ $group['customer'] ?? '-' }}</div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 5%;">No</th>
                    <th style="width: 13%;">No Proc KD</th>
                    <th style="width: 9%;">Ruang KD</th>
                    <th style="width: 13%;">Tgl Masuk</th>
                    <th style="width: 13%;">Tgl Keluar</th>
                    <th style="width: 31%;">Jenis Kayu</th>
                    <th style="width: 16%;">M3</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr class="{{ $loop->iteration % 2 === 1 ? 'row-odd' : 'row-even' }}">
                        <td class="center">{{ $loop->iteration }}</td>
                        <td class="center">{{ $row['NoProcKD'] ?? '' }}</td>
                        <td class="center">{{ $row['NoRuangKD'] ?? '' }}</td>
                        <td class="center">{{ $fmtDate($row['TglMasuk'] ?? '') }}</td>
                        <td class="center">{{ $fmtDate($row['TglKeluar'] ?? '') }}</td>
                        <td>{{ $row['Jenis'] ?? '' }}</td>
                        <td class="number">{{ $fmtM3($row['m3'] ?? 0) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="center">Tidak ada data.</td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td colspan="6" class="center">Total {{ $group['customer'] ?? '-' }}</td>
                    <td class="number">{{ $fmtM3($group['total_m3'] ?? 0) }}</td>
                </tr>
            </tfoot>
        </table>
    @empty
        <div class="center">Tidak ada data.</div>
    @endforelse

</body>

</html>