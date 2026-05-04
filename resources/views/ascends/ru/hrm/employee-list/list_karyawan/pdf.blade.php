<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta charset="utf-8">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif:ital,wght@0,100..900;1,100..900&display=swap"
        rel="stylesheet">
    <style>
        body {
            font-family: "Noto Serif", serif;
            font-size: 10px;
            color: #000000;
        }

        .title {
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 2px;
        }

        .period {
            text-align: center;
            font-size: 12px;
            margin-bottom: 20px;
            color: #636466;
        }

        .detail-table {
            width: 100%;
            border-collapse: collapse;
        }

        .detail-table th,
        .detail-table td {
            border: 1px solid #000000;
            padding: 3px 4px;
            vertical-align: middle;
        }

        .detail-table th {
            text-align: center;
            font-weight: bold;
        }

        .group-row td {
            font-weight: bold;
            text-align: center;
            font-size: 11px;
            padding: 5px 4px;
        }

        .center {
            text-align: center;
        }

        .nowrap {
            white-space: nowrap;
        }

        .row-odd td {
            background: #c9d1df;
        }

        .row-even td {
            background: #eef2f8;
        }
    </style>
</head>

<body>
    @php
        $printedAt = \Carbon\Carbon::parse($reportData['printed_at'] ?? now())
            ->locale('id')
            ->translatedFormat('d-M-y');
    @endphp

    <div class="title">{{ $reportData['title'] }}</div>
    <div class="period">Per : {{ $printedAt }}</div>

    <table class="detail-table">
        <thead>
            <tr>
                <th width="4%">No</th>
                <th width="24%">Nama</th>
                <th width="8%">Jenis<br>kelamin</th>
                <th width="7%">Usia</th>
                <th width="21%">Jabatan</th>
                <th width="12%">Lama<br>Bekerja</th>
                <th width="20%">Keterangan</th>
                <th width="12%">Nama Tempat<br>Ibadah</th>
                <th width="6%">Lemari</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($reportData['grouped_rows'] ?? [] as $department => $departmentRows)
                <tr class="group-row">
                    <td colspan="9">{{ $department }}</td>
                </tr>
                @foreach ($departmentRows as $index => $row)
                    <tr>
                        <td class="center">{{ $index + 1 }}</td>
                        <td>{{ $row['name'] !== '' ? $row['name'] : '-' }}</td>
                        <td class="center">{{ $row['gender'] !== '' ? $row['gender'] : '-' }}</td>
                        <td class="center nowrap">{{ $row['age'] !== '' ? $row['age'] : '-' }}</td>
                        <td>{{ $row['job_title'] !== '' ? $row['job_title'] : '-' }}</td>
                        <td class="center nowrap">{{ $row['working_period'] !== '' ? $row['working_period'] : '-' }}
                        </td>
                        <td>
                            {{-- {{ $row['remarks'] }} --}}
                        </td>
                        <td>
                            {{-- {{ $row['place_of_worship'] }} --}}

                        </td>
                        <td class="center">{{ $row['locker'] }}</td>
                    </tr>
                @endforeach
            @endforeach
        </tbody>
    </table>
</body>

</html>
