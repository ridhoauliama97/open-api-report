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
        }

        @page {
            margin: 12mm 8mm 14mm 8mm;
            footer: html_reportFooter;
        }

        body {
            margin: 0;
            font-family: "Noto Serif", serif;
            font-size: 10px;
            line-height: 1.15;
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

        table {
            width: 100%;
            border-collapse: collapse;
            page-break-inside: auto;
            border: 1px solid #000;
        }

        thead {
            display: table-header-group;
        }

        tfoot {
            display: table-footer-group;
        }

        tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }

        th,
        td {
            border: 0;
            border-left: 1px solid #000;
            padding: 2px 3px;
            vertical-align: middle;
        }

        th:first-child,
        td:first-child {
            border-left: 0;
        }

        th {
            text-align: center;
            font-weight: bold;
            font-size: 10px;
            border-bottom: 1px solid #000;
        }

        tbody td {
            border-top: 0;
            border-bottom: 0;
        }

        .row-odd td {
            background: #c9d1df;
        }

        .row-even td {
            background: #eef2f8;
        }

        td.center {
            text-align: center;
        }

        td.number {
            text-align: right;
            white-space: nowrap;
            font-family: "Calibri", "DejaVu Sans", sans-serif;
        }

        .empty-state {
            text-align: center;
            padding: 12px;
            font-style: italic;
        }

        .table-end-line td {
            border-top: 1px solid #000 !important;
            border-right: 0 !important;
            border-bottom: 0 !important;
            border-left: 0 !important;
            padding: 0 !important;
            height: 0 !important;
            line-height: 0 !important;
            background: #fff !important;
        }

        @include('reports.partials.pdf-footer-table-style')
    </style>
</head>

<body>
    @php
        $data = is_array($reportData ?? null) ? $reportData : [];
        $rows = is_array($data['rows'] ?? null) ? $data['rows'] : [];
        $schema = is_array($data['column_schema'] ?? null) ? $data['column_schema'] : [];
        $generatedByName = $generatedBy?->name ?? 'sistem';
        $generatedAtText = $generatedAt->copy()->locale('id')->translatedFormat('d-M-y H:i');
        $reportYear = (string) ($year ?? '');
        $reportMonth = (int) ($month ?? 0);
        $monthLabels = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];
        $reportMonthText = $monthLabels[$reportMonth] ?? (string) $reportMonth;

        $toFloat = static function (mixed $value): ?float {
            if ($value === null) {
                return null;
            }

            if (is_numeric($value)) {
                return (float) $value;
            }

            if (!is_string($value)) {
                return null;
            }

            $normalized = trim(str_replace(' ', '', $value));
            if ($normalized === '') {
                return null;
            }

            if (str_contains($normalized, ',') && str_contains($normalized, '.')) {
                if (strrpos($normalized, ',') > strrpos($normalized, '.')) {
                    $normalized = str_replace('.', '', $normalized);
                    $normalized = str_replace(',', '.', $normalized);
                } else {
                    $normalized = str_replace(',', '', $normalized);
                }
            } elseif (str_contains($normalized, ',')) {
                $normalized = str_replace(',', '.', $normalized);
            }

            return is_numeric($normalized) ? (float) $normalized : null;
        };

        $formatDateCell = static function (mixed $value): string {
            if ($value === null || $value === '') {
                return '';
            }

            if ($value instanceof \DateTimeInterface) {
                return \Carbon\Carbon::instance($value)->locale('id')->translatedFormat('d M Y');
            }

            $raw = trim((string) $value);
            if ($raw === '') {
                return '';
            }

            try {
                return \Carbon\Carbon::parse($raw)->locale('id')->translatedFormat('d M Y');
            } catch (\Throwable $exception) {
                return $raw;
            }
        };

        $formatBySpec = static function (mixed $value, array $spec) use ($toFloat, $formatDateCell): string {
            $type = strtolower((string) ($spec['type'] ?? 'text'));
            $decimals = isset($spec['decimals']) ? (int) $spec['decimals'] : 2;

            if ($type === 'date') {
                return $formatDateCell($value);
            }

            if ($type === 'integer') {
                if ($value === null || $value === '') {
                    return '';
                }

                return (string) (int) round((float) $value);
            }

            if ($type === 'number') {
                $number = $toFloat($value);
                return $number === null ? '' : number_format($number, $decimals, '.', ',');
            }

            if ($type === 'percent') {
                $number = $toFloat($value);
                if ($number === null) {
                    return '';
                }

                $percent = $number <= 1.5 ? $number * 100.0 : $number;

                return number_format($percent, $decimals, '.', ',') . '%';
            }

            return trim((string) ($value ?? ''));
        };
    @endphp

    <h1 class="report-title">Laporan Rekap Rendemen Non Rambung</h1>
    <div class="report-subtitle">Periode: {{ $reportMonthText }} {{ $reportYear }}</div>

    <table>
        <thead>
            <tr>
                <th style="width: 26px;">No</th>
                @foreach ($schema as $column)
                    @php
                        $label = (string) ($column['label'] ?? ($column['key'] ?? ''));
                        $width = match ($label) {
                            'Tahun' => '48px',
                            'Bulan' => '34px',
                            'KB Keluar (Ton)', 'ST Masuk (Ton)', 'ST Keluar (M3)' => '78px',
                            'WIP Masuk (M3)', 'WIP Pemakaian Net (M3)', 'BJ Masuk (M3)' => '86px',
                            '% ST/KB', '% WIP/ST', '% BJ/WIP', '% BJ/ST', '% Total' => '56px',
                            default => 'auto',
                        };
                    @endphp
                    <th style="width: {{ $width }};">{{ $label }}</th>
                @endforeach
            </tr>
        </thead>
        <tfoot>
            <tr class="table-end-line">
                <td colspan="{{ count($schema) + 1 }}"></td>
            </tr>
        </tfoot>
        <tbody>
            @forelse ($rows as $index => $row)
                <tr class="{{ ($index + 1) % 2 === 1 ? 'row-odd' : 'row-even' }}">
                    <td class="center">{{ $index + 1 }}</td>
                    @foreach ($schema as $column)
                        @php
                            $key = (string) ($column['key'] ?? '');
                            $type = strtolower((string) ($column['type'] ?? 'text'));
                        @endphp
                        <td class="{{ in_array($type, ['number', 'percent'], true) ? 'number' : 'center' }}">
                            {{ $formatBySpec($row[$key] ?? null, $column) }}
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($schema) + 1 }}" class="empty-state">Tidak ada data untuk periode ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @include('reports.partials.pdf-footer-table')
</body>

</html>
