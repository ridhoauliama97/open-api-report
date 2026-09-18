<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif:ital,wght@0,100..900;1,100..900&display=swap"
        rel="stylesheet">
    <meta charset="utf-8">
    @include('reports.partials.pdf-reference-style')
    <style>
        .data-table th, .data-table td {
            border: 1px solid #000;
        }
        .meta, .step {
            border-collapse: collapse;
        }
        .meta td, .step td {
            padding: 1px 2px;
            vertical-align: top;
        }
        .meta {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
        }
        .meta td {
            padding: 1px 0;
            vertical-align: top;
        }
        .step {
            width: 100%;
            border-collapse: collapse;
            margin: 0 0 3px 0;
        }
        .step td {
            vertical-align: top;
            padding: 1px 0;
        }
        .step-name {
            width: 31mm;
            font-weight: bold;
        }
        .step-date {
            width: 20mm;
            text-align: right;
        }
    </style>
    
    
</head>

<body>
    @php
        $data = is_array($reportData ?? null) ? $reportData : [];
        $rows = is_array($data['rows'] ?? null) ? $data['rows'] : [];
        $generatedByName = $generatedBy?->name ?? 'sistem';
        $generatedAtText = $generatedAt->copy()->locale('id')->isoFormat('DD-MMM-YY HH:mm');

        $fmtDay = static function (?int $value, string $label): string {
            if ($value === null) {
                return '';
            }

            return $label . ': ' . number_format($value, 0, ',', '.') . ' hari';
        };
    @endphp

    @forelse ($rows as $row)
        <div class="title">Laporan Tracing ST</div>

        <table class="meta">
            <tr>
                <td class="label">No ST</td>
                <td class="value">{{ $row['NoST'] ?: '-' }}</td>
            </tr>
            <tr>
                <td class="label">No Kayu Bulat</td>
                <td class="value">{{ $row['NoKayuBulat'] ?: '-' }}</td>
            </tr>
            <tr>
                <td class="label">Supplier</td>
                <td>{{ $row['NmSupplier'] ?: '-' }}</td>
            </tr>
            <tr>
                <td class="label">No Truk</td>
                <td>{{ $row['NoTruk'] ?? '-' }}</td>
            </tr>
        </table>

        <div class="section">
            <table class="step">
                <tr>
                    <td class="step-name">Tanggal Masuk Balok</td>
                    <td class="step-date">{{ $row['TglMasuk'] ?: '-' }}</td>
                </tr>
            </table>
            <table class="step">
                <tr>
                    <td class="step-name">Tanggal Mulai Racip</td>
                    <td class="step-date">{{ $row['TglMulai'] ?: '-' }}</td>
                </tr>
                <tr>
                    <td colspan="2" class="day">{{ $fmtDay($row['UT'], 'Umur tunggu') }}</td>
                </tr>
            </table>
            <table class="step">
                <tr>
                    <td class="step-name">Tanggal Selesai Racip</td>
                    <td class="step-date">{{ $row['TglSelesai'] ?: '-' }}</td>
                </tr>
                <tr>
                    <td colspan="2" class="day">{{ $fmtDay($row['UR'], 'Umur racip') }}</td>
                </tr>
            </table>
            <table class="step">
                <tr>
                    <td class="step-name">Tanggal Stick</td>
                    <td class="step-date">{{ $row['TglStick'] ?: '-' }}</td>
                </tr>
                <tr>
                    <td colspan="2" class="day">
                        {{ $fmtDay($row['U-Stick'], 'Umur stick') }}
                        @if ($row['BalokToStick'] !== null)
                            | Balok ke Stick {{ number_format($row['BalokToStick'], 0, ',', '.') }} hari
                        @endif
                    </td>
                </tr>
            </table>
            <table class="step">
                <tr>
                    <td class="step-name">Tanggal Masuk KD</td>
                    <td class="step-date">{{ $row['TglMasukKD'] ?: '-' }}</td>
                </tr>
                <tr>
                    <td colspan="2" class="day">{{ $fmtDay($row['UT-KD'], 'Umur tunggu KD') }}</td>
                </tr>
            </table>
            <table class="step">
                <tr>
                    <td class="step-name">Tanggal Keluar KD</td>
                    <td class="step-date">{{ $row['TglKeluar'] ?: '-' }}</td>
                </tr>
                <tr>
                    <td colspan="2" class="day">{{ $fmtDay($row['LamaKD'], 'Lama KD') }}</td>
                </tr>
            </table>
        </div>

        @if (!$loop->last)
            <div class="page-break"></div>
        @endif
    @empty
        <div class="title">Laporan Tracing ST</div>
        <p>Tidak ada data.</p>
    @endforelse

    <htmlpagefooter name="tracingStFooter">
        <table style="width: 100%; border-collapse: collapse; border: 0;">
            <tr>
                <td class="summary" style="width: 72%; border: 0; text-align: left;">
                    Dicetak oleh {{ $generatedByName }} pada {{ $generatedAtText }}
                </td>
                <td class="summary" style="width: 28%; border: 0; text-align: right;">
                    Halaman {PAGENO} dari {nbpg}
                </td>
            </tr>
        </table>
    </htmlpagefooter>
    <sethtmlpagefooter name="tracingStFooter" value="on" />
</body>

</html>