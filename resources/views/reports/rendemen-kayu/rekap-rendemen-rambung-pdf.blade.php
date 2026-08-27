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
                return \Carbon\Carbon::instance($value)->locale('id')->translatedFormat('d-M-y');
            }

            $raw = trim((string) $value);
            if ($raw === '') {
                return '';
            }

            try {
                return \Carbon\Carbon::parse($raw)->locale('id')->translatedFormat('d-M-y');
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

    <h1 class="report-title">Laporan Rekap Rendemen Rambung</h1>
    <div class="report-subtitle">Mulai Periode {{ $reportMonthText }} {{ $reportYear }}</div>

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
                            '%ST/KB', '%WIP/ST', '%BJ/WIP', '%BJ/ST', '%Total' => '56px',
                            default => 'auto',
                        };
                    @endphp
                    <th style="width: {{ $width }};">{{ $label }}</th>
                @endforeach
            </tr>
        </thead>
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

    </body>

</html>
