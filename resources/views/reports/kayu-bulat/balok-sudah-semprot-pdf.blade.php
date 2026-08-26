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

        td.number-right {
            text-align: right;
            white-space: nowrap;
            font-family: "Calibri", "DejaVu Sans", sans-serif;
        }

        td.weight-value {
            font-weight: bold;
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
    </style>
</head>

<body>
    @php
        $rowsData =
            isset($rows) && is_iterable($rows) ? (is_array($rows) ? $rows : collect($rows)->values()->all()) : [];
        $columns = array_keys($rowsData[0] ?? []);
        $hasDateRange = trim((string) $startDate) !== '' && trim((string) $endDate) !== '';
        $generatedByName = $generatedBy?->name ?? 'sistem';
        $generatedAtText = $generatedAt->copy()->locale('id')->translatedFormat('d-M-y H:i');

        $normalize = static fn(string $name): string => preg_replace('/[^a-z0-9]/', '', strtolower($name)) ?? '';
        $resolveHeaderLabel = static function (string $column) use ($normalize): string {
            return match ($normalize($column)) {
                'nokayubulat' => 'No Kayu Bulat',
                'datecreate' => 'Tanggal Masuk Balok',
                'jammasuk' => 'Jam Masuk Mobil',
                'nmsupplier' => 'Nama Supplier',
                'notruk' => 'Nomor Truk',
                'type' => 'Tipe',
                'jamsiapbongkar', 'jamsiapbongkart' => 'Jam Siap Bongkar',
                'tglsemprot' => 'Tanggal Semprot',
                'berat' => 'Berat',
                default => $column,
            };
        };

        $formatDateValue = static function ($value): string {
            if ($value === null || $value === '') {
                return '';
            }

            try {
                return \Carbon\Carbon::parse((string) $value)->locale('id')->translatedFormat('d-M-y');
            } catch (\Throwable $exception) {
                return (string) $value;
            }
        };

        $jenisColumn = null;
        foreach ($columns as $column) {
            if ($normalize((string) $column) === 'jenis') {
                $jenisColumn = (string) $column;
                break;
            }
        }
    @endphp

    <h1 class="report-title">Laporan Balok Sudah Semprot</h1>
    @if ($hasDateRange)
        <p class="report-subtitle">
            Periode {{ \Carbon\Carbon::parse($startDate)->locale('id')->translatedFormat('d-M-y') }} s/d
            {{ \Carbon\Carbon::parse($endDate)->locale('id')->translatedFormat('d-M-y') }}
        </p>
    @else
        <p class="report-subtitle">&nbsp;</p>
    @endif

    <table class="report-table">
        <thead>
            <tr class="headers-row">
                <th style="width: 4%;">No</th>
                @foreach ($columns as $column)
                    <th style="width: 9.5%;">{{ $resolveHeaderLabel((string) $column) }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($rowsData as $row)
                <tr class="data-row {{ $loop->odd ? 'row-odd' : 'row-even' }}">
                    <td class="center data-cell">{{ $loop->iteration }}</td>
                    @foreach ($columns as $column)
                        @php
                            $value = $row[$column] ?? '';
                            $columnKey = $normalize((string) $column);
                            $isBeratColumn = $columnKey === 'berat';
                            $isTglSemprotColumn = $columnKey === 'tglsemprot';
                            $isDateColumn = in_array($columnKey, ['datecreate', 'tglsemprot'], true);
                            $displayValue = (string) $value;

                            if ($isBeratColumn && is_numeric($value)) {
                                $jenisValue =
                                    $jenisColumn !== null ? strtoupper(trim((string) ($row[$jenisColumn] ?? ''))) : '';
                                if ($jenisValue === 'RAMBUNG') {
                                    $displayValue = number_format((float) $value, 0, '.', ',') . ' Kg';
                                } else {
                                    $displayValue = number_format((float) $value, 4, '.', ',') . ' Ton';
                                }
                            } elseif ($isDateColumn) {
                                $displayValue = $formatDateValue($value);
                            }
                        @endphp
                        <td
                            class="data-cell {{ $isBeratColumn ? 'number-right weight-value' : 'center' }} {{ $isTglSemprotColumn ? 'weight-value' : '' }}">
                            {{ $displayValue }}
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($columns) + 1 }}" class="center">Tidak ada data.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>

</html>
