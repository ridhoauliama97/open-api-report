<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
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
            width: 100%;
            table-layout: fixed;
        }
        .data-table th,
        .data-table td {
            border-left: 1px solid #000;
            border-right: 1px solid #000;
            padding: 1px 2px;
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
    </style>
</head>

<body>
    @php
        $data = is_array($reportData ?? null) ? $reportData : [];
        $rows = is_array($data['rows'] ?? null) ? $data['rows'] : [];
        $generatedByName = $generatedBy?->name ?? 'sistem';
        $generatedAtText = $generatedAt->copy()->locale('id')->translatedFormat('d-M-y H:i');

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