<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif:ital,wght@0,100..900;1,100..900&display=swap"
        rel="stylesheet">
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
        .report-table {
            border: 1px solid #000;
            border-collapse: collapse;
            width: calc(100% - 2px);
            line-height: inherit;
        }
.report-table th, .report-table td {
            border: 1px solid #000;
            word-wrap: break-word;
            padding: 2px;
            vertical-align: top;
        }

        .report-table th { font-weight: bold; background-color: #eef2f8; }
.meta-grid, .total-line {
            border-collapse: collapse;
        }
.meta-grid td, .total-line td { padding: 1px 2px; vertical-align: top; }
    </style>
</head>

<body>
    @php
        $data = is_array($reportData ?? null) ? $reportData : [];
        $header = is_array($data['header'] ?? null) ? $data['header'] : [];
        $groups = is_array($data['jenis_groups'] ?? null) ? $data['jenis_groups'] : [];
        $summary = is_array($data['summary'] ?? null) ? $data['summary'] : [];

        $fmtDate = static function ($value): string {
            if ($value === null || trim((string) $value) === '') {
                return '-';
            }

            try {
                return \Carbon\Carbon::parse((string) $value)->locale('id')->translatedFormat('d-M-y');
            } catch (\Throwable) {
                return (string) $value;
            }
        };

        $fmtInt = static fn($value): string => $value === null ? '' : number_format((float) $value, 0, '.', ',');
        $fmtM3 = static fn($value): string => number_format((float) $value, 4, '.', ',');
    @endphp

    <h1 class="report-title">Laporan Penjualan Barang Jadi (M3)</h1>

    <table class="meta-grid">
        <tr>
            <td style="width: 50%;">
                <table>
                    <tr>
                        <td class="meta-label">Tanggal</td>
                        <td class="meta-sep">:</td>
                        <td>{{ $fmtDate($header['tanggal'] ?? null) }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">Buyer</td>
                        <td class="meta-sep">:</td>
                        <td>{{ $header['buyer'] ?? '-' }}</td>
                    </tr>
                </table>
            </td>
            <td style="width: 50%;">
                <table>
                    <tr>
                        <td class="meta-label">No SPK</td>
                        <td class="meta-sep">:</td>
                        <td>{{ $header['no_spk'] ?? '-' }}</td>
                    </tr>
                    {{-- <tr>
                        <td class="meta-label">No Jual</td>
                        <td class="meta-sep">:</td>
                        <td>{{ $header['no_bj_jual'] ?? ($noJual ?? '-') }}</td>
                    </tr> --}}
                </table>
            </td>
        </tr>
    </table>

    @forelse ($groups as $group)
        <div class="section-title">Jenis Kayu : {{ $group['jenis'] ?? '-' }}</div>
        <table class="report-table">
            <thead>
                <tr>
                    <th style="width: 7%;">No</th>
                    <th style="width: 34%;">Nama Barang Jadi</th>
                    <th style="width: 10%;">Tebal</th>
                    <th style="width: 10%;">Lebar</th>
                    <th style="width: 11%;">Panjang</th>
                    <th style="width: 12%;">Pcs</th>
                    <th style="width: 16%;">M3</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($group['rows'] ?? [] as $row)
                    <tr class="{{ $loop->iteration % 2 === 1 ? 'row-odd' : 'row-even' }}">
                        <td class="center">{{ $row['No'] ?? $loop->iteration }}</td>
                        <td>{{ $row['NamaBarangJadi'] ?? '-' }}</td>
                        <td class="number">{{ $fmtInt($row['Tebal'] ?? null) }}</td>
                        <td class="number">{{ $fmtInt($row['Lebar'] ?? null) }}</td>
                        <td class="number">{{ $fmtInt($row['Panjang'] ?? null) }}</td>
                        <td class="number">{{ $fmtInt($row['Pcs'] ?? null) }}</td>
                        <td class="number">{{ $fmtM3($row['M3'] ?? 0) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <table class="total-line">
            <tbody>
                @foreach ($group['product_totals'] ?? [] as $name => $total)
                    <tr>
                        <td class="total-label">Jmlh / {{ $name }} :</td>
                        <td class="total-value">{{ $fmtM3($total) }}</td>
                    </tr>
                @endforeach
                <tr>
                    <td class="total-label">Jmlh / {{ $group['jenis'] ?? '-' }} :</td>
                    <td class="total-value">{{ $fmtM3($group['total_m3'] ?? 0) }}</td>
                </tr>
            </tbody>
        </table>
    @empty
        <table class="report-table">
            <tbody>
                <tr>
                    <td class="empty-state">Tidak ada data.</td>
                </tr>
            </tbody>
        </table>
    @endforelse

    @if ($groups !== [])
        <table class="total-line grand-total">
            <tbody>
                <tr>
                    <td class="total-label">Grand Total :</td>
                    <td class="total-value">{{ $fmtM3($summary['grand_total_m3'] ?? 0) }}</td>
                </tr>
            </tbody>
        </table>
    @endif

    </body>

</html>
