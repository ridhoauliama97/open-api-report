<!DOCTYPE html>
<html lang="id">

<head>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif:ital,wght@0,100..900;1,100..900&display=swap"
        rel="stylesheet">
    <meta charset="utf-8">
    <style>
        body {
            font-family: "Noto Serif", serif;
            font-size: 10px;
            margin: 0;
            padding: 0;
        }
        .report-companyTitle {
            font-size: 18px;
            font-weight: bold;
            text-align: center;
            margin: 0 0 4px;
        }
        .report-title {
            font-size: 16px;
            font-weight: bold;
            text-align: center;
            margin: 0;
        }
        .report-subtitle {
            font-size: 12px;
            color: #636466;
            text-align: center;
            margin: 2px 0 20px;
        }
        .data-table {
            border: 1px solid #000;
            border-collapse: collapse;
            width: calc(100% - 2px);
            table-layout: fixed;
        }
        .data-table th,
        .data-table td {
            border: 1px solid #000;            padding: 1px 2px;
            vertical-align: top;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
        .data-table th {
            background-color: #eef2f8;
            font-weight: bold;
        }
        .section-header td {
            font-weight: bold;
            font-style: italic;
            color: #9c111d;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding-left: 4px;
        }
        .sub-section-header td {
            font-weight: bold;
            color: #9c111d;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding-left: 4px;
        }
        .item-row td {
            padding-left: 4px;
        }
        .row-odd td {
            background-color: #c9d1df;
        }
        .row-even td {
            background-color: #eef2f8;
        }
        .subtotal-row td {
            font-weight: bold;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
        }
        .grand-total-row td {
            font-weight: bold;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
        }
        .empty-row td {
            font-style: italic;
            font-weight: bold;
            color: #9c111d;
            background-color: #c9d1df;
        }
        .number {
            text-align: right;
        }
        .nowrap {
            white-space: nowrap;
        }
        .number-negative {
            color: #9c111d;
        }
    
        /* standardized table borders */
        .meta-table {
            border-collapse: collapse;
        }
.meta-table td { padding: 1px 2px; vertical-align: top; }
    </style>
</head>

<body>
    @php
        $data = is_array($reportData ?? null) ? $reportData : [];
        $header = is_array($data['header'] ?? null) ? $data['header'] : [];
        $rows = is_array($data['rows'] ?? null) ? $data['rows'] : [];
        $summary = is_array($data['summary'] ?? null) ? $data['summary'] : [];

        $generatedByName = $generatedBy?->name ?? 'sistem';
        $generatedAtText = $generatedAt->copy()->locale('id')->translatedFormat('d-M-y H:i');

        $formatDate = static function ($value): string {
            if ($value === null || trim((string) $value) === '') {
                return '-';
            }

            try {
                return \Carbon\Carbon::parse($value)->locale('id')->translatedFormat('d-M-y');
            } catch (\Throwable) {
                return (string) $value;
            }
        };

        $formatDim = static function ($value): string {
            return number_format((float) ($value ?? 0), 1, '.', ',');
        };

        $formatInt = static function ($value): string {
            return number_format((float) ($value ?? 0), 0, '.', ',');
        };

        $formatTon = static function ($value): string {
            return number_format((float) ($value ?? 0), 4, '.', ',');
        };
    @endphp

    <h1 class="report-title">Laporan Stok Opname ST Detail Pada KD</h1>
    <p class="report-subtitle"></p>

    <table class="meta-table">
        <tbody>
            <tr>
                <td class="meta-label">No KD</td>
                <td class="meta-sep">:</td>
                <td>{{ $header['NoProcKD'] ?? ($noProcKd ?? '-') }}</td>
                <td class="meta-label">Ruang KD</td>
                <td class="meta-sep">:</td>
                <td>{{ $header['NoRuangKD'] ?? '-' }}</td>
            </tr>
            <tr>
                <td class="meta-label">Tanggal Masuk</td>
                <td class="meta-sep">:</td>
                <td>{{ $formatDate($header['TglMasuk'] ?? null) }}</td>
                <td class="meta-label">Tanggal Keluar</td>
                <td class="meta-sep">:</td>
                <td>{{ $formatDate($header['TglKeluar'] ?? null) }}</td>
            </tr>
        </tbody>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 4%;">No</th>
                <th style="width: 10%;">No ST</th>
                <th style="width: 8%;">Tanggal</th>
                <th style="width: 20%;">Jenis</th>
                <th style="width: 10%;">No KB</th>
                <th style="width: 7%;">Tebal</th>
                <th style="width: 7%;">Lebar</th>
                <th style="width: 7%;">Panjang</th>
                <th style="width: 6%;">UOM<br>Lebar & Tebal</th>
                <th style="width: 7%;">UOM<br>Panjang</th>
                <th style="width: 7%;">Pcs</th>
                <th style="width: 7%;">Ton</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                @php $rowIndex = ($loop->index ?? 0) + 1; @endphp
                <tr class="data-row {{ $rowIndex % 2 === 1 ? 'row-odd' : 'row-even' }}">
                    <td class="center">{{ $rowIndex }}</td>
                    <td class="center">{{ $row['NoST'] ?? '' }}</td>
                    <td class="center">{{ $formatDate($row['DateCreate'] ?? null) }}</td>
                    <td>{{ $row['Jenis'] ?? '' }}</td>
                    <td class="center">{{ $row['NoKayuBulat'] ?? '' }}</td>
                    <td class="number">{{ $formatDim($row['Tebal'] ?? 0) }}</td>
                    <td class="number">{{ $formatDim($row['Lebar'] ?? 0) }}</td>
                    <td class="number">{{ $formatDim($row['Panjang'] ?? 0) }}</td>
                    <td class="center">{{ $row['UOMLebar'] ?? '' }}</td>
                    <td class="center">{{ $row['UOMPanjang'] ?? '' }}</td>
                    <td class="number" style="font-weight: bold;">{{ $formatInt($row['JmlhBatang'] ?? 0) }}</td>
                    <td class="number" style="font-weight: bold;">{{ $formatTon($row['Ton'] ?? 0) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="12" class="center">Tidak ada data.</td>
                </tr>
            @endforelse
            <tr class="totals-row">
                <td colspan="10" class="center">Total</td>
                <td class="number">{{ $formatInt($summary['total_pcs'] ?? 0) }}</td>
                <td class="number">{{ $formatTon($summary['total_ton'] ?? 0) }}</td>
            </tr>
        </tbody>
    </table>

    </body>

</html>
