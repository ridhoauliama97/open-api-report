<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif:ital,wght@0,100..900;1,100..900&display=swap"
        rel="stylesheet">
    @include('reports.partials.pdf-reference-style', [
        'pageMargin' => '24mm 10mm 18mm 10mm',
        'subtitleMargin' => '2px 0 20px 0',
    ])
</head>

<body>
    @php
        $rowsData = is_iterable($rows ?? null) ? (is_array($rows) ? $rows : collect($rows)->values()->all()) : [];
        $summaryData = is_array($summary ?? null) ? $summary : [];
        $start = \Carbon\Carbon::parse($startDate)->locale('id')->translatedFormat('d-M-y');
        $end = \Carbon\Carbon::parse($endDate)->locale('id')->translatedFormat('d-M-y');
        $generatedByName = $generatedBy?->name ?? 'sistem';
        $generatedAtText = $generatedAt->copy()->locale('id')->translatedFormat('d-M-y H:i');
    @endphp

    <h1 class="report-title">Laporan Kayu Bulat Hidup</h1>
    <p class="report-subtitle">Periode {{ $start }} s/d {{ $end }}</p>

    <table class="report-table">

        <thead>
            <tr class="header-line-1">
                <th>No</th>
                <th>Tanggal</th>
                <th>Supplier</th>
                <th>Nomor<br>Truk</th>
                <th>Jenis</th>
                <th>Batang Balok Masuk</th>
                <th>Batang Balok Terpakai</th>
                <th>Fisik Batang Balok<br>Di Lapangan</th>
            </tr>
        </thead>
        
        <tfoot>
            <tr class="table-end-line">
                <td colspan="8"></td>
            </tr>
        </tfoot>
        <tbody>
            @forelse ($rowsData as $row)
                <tr class="data-row {{ $loop->odd ? 'row-odd' : 'row-even' }} {{ $loop->last ? 'row-last' : '' }}">
                    <td class="center data-cell">{{ $loop->iteration }}</td>
                    <td class="center data-cell">
                        @php
                            $tanggal = $row['Tanggal'] ?? null;
                            $tanggalText = '';
                            if ($tanggal) {
                                try {
                                    $tanggalText = \Carbon\Carbon::parse((string) $tanggal)
                                        ->locale('id')
                                        ->translatedFormat('d-M-y');
                                } catch (\Throwable $exception) {
                                    $tanggalText = (string) $tanggal;
                                }
                            }
                        @endphp
                        {{ $tanggalText }}
                    </td>
                    <td class="data-cell">{{ (string) ($row['Supplier'] ?? '') }}</td>
                    <td class="number data-cell" style="text-align: center;">
                        @php
                            $noTrukRaw = (string) ($row['NoTruk'] ?? '');
                            $noTrukNumeric = str_replace(',', '', $noTrukRaw);
                            $noTrukText = is_numeric($noTrukNumeric)
                                ? number_format((float) $noTrukNumeric, 0, '', '')
                                : $noTrukRaw;
                        @endphp
                        {{ $noTrukText }}
                    </td>
                    <td class="data-cell">{{ (string) ($row['Jenis'] ?? '') }}</td>
                    <td class="number data-cell">{{ number_format((float) ($row['BatangBalokMasuk'] ?? 0), 0, '.', ',') }}</td>
                    <td class="number data-cell">{{ number_format((float) ($row['BatangBalokTerpakai'] ?? 0), 0, '.', ',') }}
                    </td>
                    <td class="number data-cell">
                        @php
                            $fisik = (float) ($row['FisikBatangBalokDiLapangan'] ?? 0);
                        @endphp
                        {{ $fisik > 0 ? number_format($fisik, 0, '.', ',') : '' }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="center">Tidak ada data.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <section class="summary-page" style="page-break-before: auto; margin-top: 4px;">
        <h2 class="summary-title">Keterangan:</h2>
        <ul class="summary-list">
            <li>
                Total Seluruh Data: {{ (int) ($summaryData['total_rows'] ?? 0) }}
            </li>
            <li>
                Total Balok Masuk: {{ number_format((float) ($summaryData['total_pcs'] ?? 0), 0, '.', ',') }}
            </li>
            <li>
                Total Balok Terpakai:
                {{ number_format((float) ($summaryData['total_blk_terpakai'] ?? 0), 0, '.', ',') }}
            </li>
            <li>
                Total Fisik Di Lapangan:
                {{ number_format((float) ($summaryData['total_fisik_lapangan'] ?? 0), 0, '.', ',') }}
            </li>
        </ul>
    </section>

    @include('reports.partials.pdf-reference-footer')
</body>

</html>
