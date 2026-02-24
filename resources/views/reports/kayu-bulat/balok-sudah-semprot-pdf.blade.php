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
            margin: 20mm 10mm 16mm 10mm;
            footer: html_reportFooter;
        }

        body {
            margin: 0;
            font-family:"Noto Serif", serif;
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

        table {
            width: 100%;
            border-collapse: collapse;
            page-break-inside: auto;
        }

        thead {
            display: table-header-group;
        }

        tr {
            page-break-inside: avoid;
            page-break-after: auto;
            border: 1px solid #000;
        }

        th,
        td {
            border: 1px solid #8f8f8f;
            padding: 3px 4px;
            vertical-align: middle;
            word-break: break-word;
            text-align: center;
        }

        th {
            text-align: center;
            font-weight: bold;
            font-size: 11px;
        }

        td.center {
            text-align: center;
        }

        td.number-right {
            text-align: right;
            font-family:"Calibri","DejaVu Sans", sans-serif;
        }

        .row-odd td {
            background: #c9d1df;
        }

        .row-even td {
            background: #eef2f8;
        }

        .footer-wrap {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }

        .footer-left,
        .footer-right {
            font-size: 8px;
            font-style: italic;
        }

        .footer-right {
            text-align: right;
        }
    
        .headers-row th {
            font-weight: bold;
            font-size: 11px;
            border: 1.5px solid #000;
        }
    
        .totals-row td {
            font-weight: bold;
            font-size: 11px;
            border: 1.5px solid #000;
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
        $generatedAtText = $generatedAt->copy()->locale('id')->translatedFormat('d M Y H:i');

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
                return \Carbon\Carbon::parse((string) $value)->locale('id')->translatedFormat('d M Y');
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
            Periode {{ \Carbon\Carbon::parse($startDate)->locale('id')->translatedFormat('d M Y') }} s/d
            {{ \Carbon\Carbon::parse($endDate)->locale('id')->translatedFormat('d M Y') }}
        </p>
    @else
        <p class="report-subtitle">&nbsp;</p>
    @endif

    <table>
        <thead>
            <tr class="headers-row" style="border: 1.5px solid #000">
                <th style="width: 34px;">No</th>
                @foreach ($columns as $column)
                    <th>{{ $resolveHeaderLabel((string) $column) }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($rowsData as $row)
                <tr class="{{ $loop->odd ? 'row-odd' : 'row-even' }}">
                    <td class="center">{{ $loop->iteration }}</td>
                    @foreach ($columns as $column)
                        @php
                            $value = $row[$column] ?? '';
                            $columnKey = $normalize((string) $column);
                            $isBeratColumn = $columnKey === 'berat';
                            $isDateColumn = in_array($columnKey, ['datecreate', 'tglsemprot'], true);
                            $displayValue = (string) $value;

                            if ($isBeratColumn && is_numeric($value)) {
                                $jenisValue =
                                    $jenisColumn !== null ? strtoupper(trim((string) ($row[$jenisColumn] ?? ''))) : '';
                                if ($jenisValue === 'JABON') {
                                    $displayValue = number_format((float) $value, 4, '.', '') . ' Ton';
                                } elseif ($jenisValue === 'RAMBUNG') {
                                    $displayValue = number_format((float) $value, 0, '.', ',') . ' Kg';
                                } else {
                                    $displayValue = number_format((float) $value, 2, '.', '');
                                }
                            } elseif ($isDateColumn) {
                                $displayValue = $formatDateValue($value);
                            }
                        @endphp
                        <td class="{{ $isBeratColumn ? 'number-right' : 'center' }}">{{ $displayValue }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($columns) + 1 }}" class="center">Tidak ada data.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <htmlpagefooter name="reportFooter">
        <div class="footer-wrap">
            <div class="footer-left">Dicetak oleh: {{ $generatedByName }} pada {{ $generatedAtText }}</div>
            <div class="footer-right">Halaman {PAGENO} dari {nbpg}</div>
        </div>
    </htmlpagefooter>
</body>

</html>
