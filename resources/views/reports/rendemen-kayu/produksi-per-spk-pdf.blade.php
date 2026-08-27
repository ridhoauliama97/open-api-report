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

        .section-subtitle {
            margin: 4px 0 6px 0;
            font-size: 11px;
            font-weight: bold;
        }

        .meta-grid {
            width: 100%;
            margin-bottom: 10px;
        }

        .meta-grid table {
            border: 0;
        }

        .meta-grid td {
            border: 0;
            padding: 2px 6px 2px 0;
            vertical-align: top;
        }

        .meta-label {
            width: 68px;
            white-space: nowrap;
        }

        .meta-sep {
            width: 10px;
            text-align: center;
        }

        .label-total td,
        .group-total td,
        .grand-total-row td {
            font-weight: bold;
        }

        .empty-state {
            text-align: center;
            padding: 10px;
            font-style: italic;
        }

        .empty-row td {
            font-style: italic;
            font-weight: bold;
            color: #9c111d;
            background: #c9d1df;
        }

        .spacer {
            height: 8px;
        }
    </style>
</head>

<body>
    @php
        $data = is_array($reportData ?? null) ? $reportData : [];
        $header = is_array($data['header'] ?? null) ? $data['header'] : [];
        $dimensions = is_array($data['dimensions'] ?? null) ? $data['dimensions'] : [];
        $rendemenRows = is_array($data['rendemen_rows'] ?? null) ? $data['rendemen_rows'] : [];
        $aliveLabels = is_array($data['alive_labels'] ?? null) ? $data['alive_labels'] : [];
        $missLabels = is_array($data['miss_labels'] ?? null) ? $data['miss_labels'] : [];
        $rendemenGlobal = $data['rendemen_global'] ?? null;
        $generatedByName = $generatedBy?->name ?? 'sistem';
        $generatedAtText = $generatedAt->copy()->locale('id')->translatedFormat('d-M-y H:i');

        $fmtNumber = static fn($value, int $decimals = 4): string => $value === null
            ? ''
            : number_format((float) $value, $decimals, '.', ',');
        $fmtPercent = static fn($value): string => $value === null ? '' : number_format((float) $value, 2, '.', ',');
        $fmtDim = static fn($value): string => $value === null ? '' : number_format((float) $value, 0, '.', ',');
        $fmtDate = static function ($value): string {
            if ($value === null || $value === '') {
                return '';
            }

            try {
                return \Carbon\Carbon::parse($value)->locale('id')->translatedFormat('d-M-y');
            } catch (\Throwable $exception) {
                return (string) $value;
            }
        };
    @endphp

    <h1 class="report-title">Laporan Produksi Per SPK</h1>

    <table class="meta-grid">
        <tr>
            <td style="width: 50%;">
                <table>
                    <tr>
                        <td class="meta-label">No SPK</td>
                        <td class="meta-sep">:</td>
                        <td>{{ $header['NoSPK'] ?? ($noSpk ?? '-') }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">Tanggal</td>
                        <td class="meta-sep">:</td>
                        <td>{{ $fmtDate($header['Tanggal'] ?? null) }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">Tujuan</td>
                        <td class="meta-sep">:</td>
                        <td>{{ $header['Tujuan'] ?? '' }}</td>
                    </tr>
                </table>
            </td>
            <td style="width: 50%;">
                <table>
                    <tr>
                        <td class="meta-label">Buyer</td>
                        <td class="meta-sep">:</td>
                        <td>{{ $header['Buyer'] ?? '' }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">No Contract</td>
                        <td class="meta-sep">:</td>
                        <td>{{ $header['NoContract'] ?? '' }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">Status</td>
                        <td class="meta-sep">:</td>
                        <td>{{ $header['Status'] ?? '' }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="report-table" style="width: 48%; margin-bottom: 10px;">
        <thead>
            <tr>
                <th>Jenis</th>
                <th style="width: 74px;">Tebal</th>
                <th style="width: 74px;">Lebar</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($dimensions as $index => $row)
                <tr class="{{ ($index + 1) % 2 === 1 ? 'row-odd' : 'row-even' }}">
                    <td>{{ $row['Jenis'] ?? '-' }}</td>
                    <td class="center">{{ $fmtDim($row['Tebal'] ?? null) }}</td>
                    <td class="center">{{ $fmtDim($row['Lebar'] ?? null) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="empty-state">Tidak ada dimensi SPK.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <table class="report-table" style="width: 54%; margin-bottom: 6px;">
        <thead>
            <tr>
                <th style="width: 38%;">Group</th>
                <th style="width: 20%;">Input</th>
                <th style="width: 20%;">Output</th>
                <th style="width: 22%;">Rend</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rendemenRows as $index => $row)
                <tr class="{{ ($index + 1) % 2 === 1 ? 'row-odd' : 'row-even' }}">
                    <td>{{ $row['Group'] ?? '' }}</td>
                    <td class="number">{{ $fmtNumber($row['Input'] ?? null) }}</td>
                    <td class="number">{{ $fmtNumber($row['Output'] ?? null) }}</td>
                    <td class="number">{{ $fmtPercent($row['Rend'] ?? null) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="section-subtitle" style="margin-left:245px;">
        Rendemen Global{{ $rendemenGlobal !== null ? ' : ' . $fmtPercent($rendemenGlobal) . '%' : '' }}
    </div>

    <div class="section-title">Label yang masih hidup : </div>
    @forelse ($aliveLabels as $category)
        <div class="section-subtitle">Kategori : {{ $category['name'] }}</div>
        <table class="report-table">
            <thead>
                <tr>
                    <th style="width: 19%;">Jenis</th>
                    <th style="width: 18%;">No Label</th>
                    <th style="width: 10%;">Lokasi</th>
                    <th style="width: 10%;">Tebal</th>
                    <th style="width: 10%;">Lebar</th>
                    <th style="width: 12%;">Panjang</th>
                    <th style="width: 13%;">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($category['rows'] as $index => $row)
                    <tr class="{{ ($index + 1) % 2 === 1 ? 'row-odd' : 'row-even' }}">
                        <td>{{ $row['Jenis'] ?? '-' }}</td>
                        <td>{{ $row['NoLabel'] ?? '-' }}</td>
                        <td class="center">{{ $row['Lokasi'] ?? '-' }}</td>
                        <td class="center">{{ $fmtDim($row['Tebal'] ?? null) }}</td>
                        <td class="center">{{ $fmtDim($row['Lebar'] ?? null) }}</td>
                        <td class="center">{{ $fmtDim($row['Panjang'] ?? null) }}</td>
                        <td class="number">{{ $fmtNumber($row['Total'] ?? null) }}</td>
                    </tr>
                @endforeach
                <tr class="label-total">
                    <td colspan="6" class="center">Total</td>
                    <td class="number">{{ $fmtNumber($category['total'] ?? null) }}</td>
                </tr>
            </tbody>
        </table>
        <div class="spacer"></div>
    @empty
        <table class="report-table">
            <tbody>
                <tr>
                    <td class="empty-state">Tidak ada label hidup untuk SPK ini.</td>
                </tr>
            </tbody>
        </table>
        <div class="spacer"></div>
    @endforelse

    <div class="section-title">Label yang miss/salah prediksi</div>
    @forelse ($missLabels as $category)
        <div class="section-subtitle">Kategori : {{ $category['name'] }}</div>
        <table class="report-table">
            <thead>
                <tr>
                    <th style="width: 19%;">Jenis</th>
                    <th style="width: 18%;">No Label</th>
                    <th style="width: 10%;">Lokasi</th>
                    <th style="width: 10%;">Tebal</th>
                    <th style="width: 10%;">Lebar</th>
                    <th style="width: 12%;">Panjang</th>
                    <th style="width: 13%;">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($category['rows'] as $index => $row)
                    <tr class="{{ ($index + 1) % 2 === 1 ? 'row-odd' : 'row-even' }}">
                        <td>{{ $row['Jenis'] ?? '-' }}</td>
                        <td>{{ $row['NoLabel'] ?? '-' }}</td>
                        <td class="center">{{ $row['Lokasi'] ?? '-' }}</td>
                        <td class="center">{{ $fmtDim($row['Tebal'] ?? null) }}</td>
                        <td class="center">{{ $fmtDim($row['Lebar'] ?? null) }}</td>
                        <td class="center">{{ $fmtDim($row['Panjang'] ?? null) }}</td>
                        <td class="number">{{ $fmtNumber($row['Total'] ?? null) }}</td>
                    </tr>
                @endforeach
                <tr class="label-total">
                    <td colspan="6" class="center">Total</td>
                    <td class="number">{{ $fmtNumber($category['total'] ?? null) }}</td>
                </tr>
            </tbody>
        </table>
        <div class="spacer"></div>
    @empty
        <table class="report-table">
            <tbody>
                <tr>
                    <td class="empty-state">Tidak ada label miss/salah prediksi untuk SPK ini.</td>
                </tr>
            </tbody>
        </table>
    @endforelse
</body>

</html>
