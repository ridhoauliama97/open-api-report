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
        $groups = is_iterable($groupedRows ?? null)
            ? (is_array($groupedRows)
                ? $groupedRows
                : collect($groupedRows)->values()->all())
            : [];
        $rowsData = [];
        foreach ($groups as $group) {
            $rowsInGroup = is_array($group['rows'] ?? null) ? $group['rows'] : [];
            foreach ($rowsInGroup as $row) {
                $rowsData[] = $row;
            }
        }
        $summaryData = is_array($summary ?? null) ? $summary : [];
        $generatedByName = $generatedBy?->name ?? 'sistem';
        $generatedAtText = $generatedAt->copy()->locale('id')->isoFormat('DD-MMM-YY HH:mm');
    @endphp

    <h1 class="report-title">Laporan Stock Opname Kayu Bulat</h1>
    <p class="report-subtitle"></p>

    <table class="report-table">
        <thead>
            <tr class="headers-row">
                <th style="width: 30px;">No</th>
                <th style="width: 82px;">No KB</th>
                <th style="width: 72px;">Tanggal</th>
                <th style="width: 72px;">Jenis Kayu</th>
                <th style="width: 95px;">Supplier</th>
                <th style="width: 170px;">No Suket</th>
                <th style="width: 80px;">No Plat</th>
                <th style="width: 52px;">No Truk</th>
                <th style="width: 42px;">Tebal</th>
                <th style="width: 42px;">Lebar</th>
                <th style="width: 52px;">Panjang</th>
                <th style="width: 38px;">Pcs</th>
                <th style="width: 66px;">Jmlh Ton</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rowsData as $row)
                <tr class="data-row {{ $loop->odd ? 'row-odd' : 'row-even' }}">
                    <td class="center data-cell">{{ $loop->iteration }}</td>
                    <td class="center data-cell">{{ (string) ($row['NoKayuBulat'] ?? '') }}</td>
                    <td class="center data-cell">
                        @php
                            $tanggal = $row['Tanggal'] ?? null;
                            $tanggalText = '';
                            if ($tanggal) {
                                try {
                                    $tanggalText = \Carbon\Carbon::parse((string) $tanggal)
                                        ->locale('id')
                                        ->locale('id')->isoFormat('DD-MMM-YY');
                                } catch (\Throwable $exception) {
                                    $tanggalText = (string) $tanggal;
                                }
                            }
                        @endphp
                        {{ $tanggalText }}
                    </td>
                    <td class="data-cell">{{ strtoupper((string) ($row['JenisKayu'] ?? '')) }}</td>
                    <td class="data-cell">{{ (string) ($row['Supplier'] ?? '') }}</td>
                    <td class="data-cell">{{ (string) ($row['NoSuket'] ?? '') }}</td>
                    <td class="data-cell">{{ (string) ($row['NoPlat'] ?? '') }}</td>
                    <td class="center data-cell">{{ (string) ($row['NoTruk'] ?? '') }}</td>
                    <td class="number data-cell">{{ number_format((float) ($row['Tebal'] ?? 0), 0, '.', ',') }}</td>
                    <td class="number data-cell">{{ number_format((float) ($row['Lebar'] ?? 0), 0, '.', ',') }}</td>
                    <td class="number data-cell">{{ number_format((float) ($row['Panjang'] ?? 0), 0, '.', ',') }}</td>
                    <td class="number data-cell" style="font-weight: bold;">
                        {{ number_format((float) ($row['Pcs'] ?? 0), 0, '.', ',') }}
                    </td>
                    <td class="number data-cell" style="font-weight: bold;">
                        {{ number_format((float) ($row['JmlhTon'] ?? 0), 4, '.', ',') }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="13" class="center">Tidak ada data.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="section-title">Rangkuman</div>
    <ul>
        <li>Total No Kayu Bulat:<strong> {{ (int) ($summaryData['total_no_kayu_bulat'] ?? 0) }}</strong></li>
        <li>Total Pcs: <strong>{{ number_format((float) ($summaryData['total_pcs'] ?? 0), 0, '.', ',') }} Pcs </strong>
        </li>
        <li>Total Ton: <strong>{{ number_format((float) ($summaryData['total_ton'] ?? 0), 4, '.', ',') }} Ton</strong>
        </li>
    </ul>
</body>

</html>