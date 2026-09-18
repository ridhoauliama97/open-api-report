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
    </style>


</head>

<body>
    @php
        $data = is_array($reportData ?? null) ? $reportData : [];
        $rows = is_array($data['rows'] ?? null) ? $data['rows'] : [];
        $totals = is_array($data['totals'] ?? null) ? $data['totals'] : [];

        $generatedByName = $generatedBy?->name ?? 'sistem';
        $generatedAtText = $generatedAt->copy()->locale('id')->isoFormat('DD-MMM-YY HH:mm');

        $fmtDate = static function (?string $v): string {
            $t = trim((string) $v);
            if ($t === '') {
                return '';
            }
            try {
                return \Carbon\Carbon::parse($t)->locale('id')->isoFormat('DD-MMM-YY');
            } catch (\Throwable) {
                return $t;
            }
        };

        $fmtInt = static fn(?int $v): string => $v === null ? '' : number_format($v, 0, '.', ',');
        $fmtFloat = static fn(?float $v, int $dec = 4): string => $v === null ? '' : number_format($v, $dec, '.', '');
    @endphp

    <h1 class="report-title">Laporan Label S4S (Hidup)</h1>
    <p class="report-subtitle">&nbsp;</p>

    <table>
        <thead>
            <tr>
                <th style="width: 28px;">No</th>
                <th style="width: 70px;">No S4S</th>
                <th style="width: 66px;">Tanggal</th>
                <th style="width: 62px;">No SPK</th>
                <th>Jenis</th>
                <th style="width: 40px;">Tebal (mm)</th>
                <th style="width: 40px;">Lebar (mm)</th>
                <th style="width: 50px;">Panjang (ft)</th>
                <th style="width: 80px;">Jmlh Batang (Pcs)</th>
                <th style="width: 56px;">Kubik (m3)</th>
                <th style="width: 46px;">Lokasi</th>
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
                    <td class="center">{{ (string) ($row['NoS4S'] ?? '') }}</td>
                    <td class="center">{{ $fmtDate($row['DateCreate'] ?? null) }}</td>
                    <td class="center">{{ (string) ($row['NoSPK'] ?? '') }}</td>
                    <td>{{ (string) ($row['Jenis'] ?? '') }}</td>
                    <td class="center">{{ $fmtFloat($row['Tebal'] ?? null, 0) }}</td>
                    <td class="center">{{ $fmtFloat($row['Lebar'] ?? null, 0) }}</td>
                    <td class="center">{{ $fmtFloat($row['Panjang'] ?? null, 0) }}</td>
                    <td class="number" style="font-weight: bold;">
                        {{ $fmtInt(is_numeric($row['JmlhBatang'] ?? null) ? (int) $row['JmlhBatang'] : null) }}
                    </td>
                    <td class="number" style="font-weight: bold;"> {{ $fmtFloat($row['Kubik'] ?? null, 4) }}</td>
                    <td class="center">{{ (string) ($row['Lokasi'] ?? '') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="11" class="center">Tidak ada data.</td>
                </tr>
            @endforelse

            @if ($rows !== [])
                <tr class="totals-row">
                    <td colspan="8" class="center">Total</td>
                    <td class="number">{{ $fmtInt((int) ($totals['JmlhBatang'] ?? 0)) }}</td>
                    <td class="number">{{ $fmtFloat((float) ($totals['Kubik'] ?? 0.0), 3) }}</td>
                    <td></td>
                </tr>
            @endif
        </tbody>
    </table>
</body>

</html>