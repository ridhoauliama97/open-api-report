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
    </style>


</head>

<body>
    @php
        $data = is_array($reportData ?? null) ? $reportData : [];
        $rows = is_array($data['rows'] ?? null) ? $data['rows'] : [];
        $totals = is_array($data['totals'] ?? null) ? $data['totals'] : [];

        $fmtDate = static fn(?string $v): string => $v === null || trim($v) === ''
            ? ''
            : \Carbon\Carbon::parse($v)->locale('id')->isoFormat('DD-MMM-YY');
        $fmtInt = static fn(mixed $v): string => $v === null || $v === '' ? '' : number_format((int) $v, 0, '.', ',');
        $fmtDim = static fn(mixed $v): string => $v === null || $v === '' ? '' : number_format((float) $v, 0, '.', ',');
        $fmtM3 = static fn(mixed $v): string => $v === null || $v === '' ? '' : number_format((float) $v, 4, '.', ',');

        $generatedDate = \Carbon\Carbon::now()->locale('id')->isoFormat('DD-MMM-YY');
    @endphp

    <h1 class="report-title">Laporan Barang Jadi (Hidup) Detail</h1>
    <p class="report-subtitle"></p>

    <table>
        <thead>
            <tr>
                <th style="width: 32px;">No</th>
                <th style="width: 86px;">No BJ</th>
                <th style="width: 76px;">Tanggal</th>
                <th style="width: 74px;">No SPK</th>
                <th>Jenis</th>
                <th style="width: 44px;">Tebal</th>
                <th style="width: 56px;">Lebar</th>
                <th style="width: 58px;">Panjang</th>
                <th style="width: 70px;">Jumlah Batang</th>
                <th style="width: 54px;">Lokasi</th>
                <th style="width: 56px;">M3</th>
            </tr>
        </thead>
        <tbody>
            @php $rowIndex = 0; @endphp
            @forelse ($rows as $row)
                @php
                    $rowIndex++;
                    $row = is_array($row) ? $row : (array) $row;
                @endphp
                <tr class="{{ $rowIndex % 2 === 1 ? 'row-odd' : 'row-even' }}">
                    <td class="center">{{ $rowIndex }}</td>
                    <td class="center">{{ (string) ($row['NoBJ'] ?? '') }}</td>
                    <td class="center">{{ $fmtDate($row['Tanggal'] ?? null) }}</td>
                    <td class="center">{{ (string) ($row['NoSPK'] ?? '') }}</td>
                    <td>{{ (string) ($row['Jenis'] ?? '') }}</td>
                    <td class="center">{{ $fmtDim($row['Tebal'] ?? null) }}</td>
                    <td class="center">{{ $fmtDim($row['Lebar'] ?? null) }}</td>
                    <td class="center">{{ $fmtDim($row['Panjang'] ?? null) }}</td>
                    <td class="center">{{ $fmtInt($row['JmlhBatang'] ?? null) }}</td>
                    <td class="center">{{ (string) ($row['Lokasi'] ?? '') }}</td>
                    <td class="number" style="font-weight: bold;">{{ $fmtM3($row['M3'] ?? null) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="11" class="center">Tidak ada data.</td>
                </tr>
            @endforelse

            @if ($rows !== [] && is_array($totals))
                <tr class="totals-row">
                    <td colspan="10" class="center">Total</td>
                    <td class="number">{{ $fmtM3($totals['M3'] ?? null) }}</td>
                </tr>
            @endif
        </tbody>
    </table>
</body>

</html>