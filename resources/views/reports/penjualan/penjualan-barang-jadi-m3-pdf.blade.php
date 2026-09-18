<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif:ital,wght@0,100..900;1,100..900&display=swap"
        rel="stylesheet">
    @include('reports.partials.pdf-reference-style')
    <style>
        .data-table th, .data-table td {
            border: 1px solid #000;
        }
        .report-table th, .report-table td {
            border: 1px solid #000;
            word-wrap: break-word;
            padding: 2px;
            vertical-align: top;
        }
        .report-table th {
            font-weight: bold;
            background-color: #eef2f8;
        }
        .meta-grid, .total-line {
            border-collapse: collapse;
        }
        .meta-grid td, .total-line td {
            padding: 1px 2px;
            vertical-align: top;
        }
        .meta-grid {
            width: 100%;
            margin-bottom: 10px;
        }
        .meta-grid td {
            border: 0 !important;
            padding: 2px 6px 2px 0;
            vertical-align: top;
        }
        .total-line {
            width: 100%;
            margin: 4px 0 0;
            border-collapse: collapse;
        }
        .total-line td {
            border: 0;
            padding: 1px 4px;
        }
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
                return \Carbon\Carbon::parse((string) $value)->locale('id')->isoFormat('DD-MMM-YY');
            } catch (\Throwable) {
                return (string) $value;
            }
        };

        $fmtInt = static fn($value): string => $value === null ? '' : number_format((float) $value, 0, '.', ',');
        $fmtM3 = static fn($value): string => number_format((float) $value, 4, '.', ',');
    @endphp

    <h1 class="report-title" style="margin-bottom: 20px;">Laporan Penjualan Barang Jadi (M3)</h1>

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
