<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $reportData['title'] }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-light">
    <div class="container py-5">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4 p-md-5">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
                    <div>
                        <h1 class="h3 fw-bold mb-2">{{ $reportData['title'] }}</h1>
                        <p class="text-secondary mb-1">Sumber data: {{ $reportData['source_file'] }}</p>
                        <p class="text-secondary mb-0">Per: {{ $reportData['printed_at'] }}</p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('reports.ascends.ru.hrm.employee-list.list-karyawan.preview') }}"
                            target="_blank" class="btn btn-outline-secondary">
                            Preview JSON
                        </a>
                        <a href="{{ route('reports.ascends.ru.hrm.employee-list.list-karyawan.download', ['preview_pdf' => 1]) }}"
                            target="_blank" class="btn btn-primary">
                            Preview PDF
                        </a>
                        <a href="{{ route('reports.ascends.ru.hrm.employee-list.list-karyawan.download') }}"
                            class="btn btn-success">
                            Download PDF
                        </a>
                    </div>
                </div>

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0 ps-3">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="border rounded p-3 h-100">
                            <div class="text-secondary small">Total Karyawan Aktif</div>
                            <div class="fs-3 fw-bold">{{ number_format($reportData['total_rows'] ?? 0) }}</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded p-3 h-100">
                            <div class="text-secondary small">Total Departemen</div>
                            <div class="fs-3 fw-bold">{{ number_format($reportData['summary']['department_count'] ?? 0) }}</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded p-3 h-100">
                            <div class="text-secondary small">Komposisi JK</div>
                            <div class="fw-semibold">
                                @foreach (($reportData['summary']['gender_summary'] ?? []) as $gender => $count)
                                    <div>{{ $gender }}: {{ number_format($count) }}</div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-sm table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>No</th>
                                <th>Nama</th>
                                <th>Jenis Kelamin</th>
                                <th>Usia</th>
                                <th>Jabatan</th>
                                <th>Lama Bekerja</th>
                                <th>Keterangan</th>
                                <th>Nama Tempat Ibadah</th>
                                <th>Lemari</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $shownGroups = 0; @endphp
                            @foreach (($reportData['grouped_rows'] ?? []) as $department => $departmentRows)
                                @break($shownGroups >= 3)
                                <tr class="table-secondary">
                                    <td colspan="9" class="fw-bold text-center">{{ $department }}</td>
                                </tr>
                                @foreach (array_slice($departmentRows, 0, 5) as $index => $row)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $row['name'] !== '' ? $row['name'] : '-' }}</td>
                                        <td>{{ $row['gender'] !== '' ? $row['gender'] : '-' }}</td>
                                        <td>{{ $row['age'] !== '' ? $row['age'] : '-' }}</td>
                                        <td>{{ $row['job_title'] !== '' ? $row['job_title'] : '-' }}</td>
                                        <td>{{ $row['working_period'] !== '' ? $row['working_period'] : '-' }}</td>
                                        <td>{{ $row['remarks'] }}</td>
                                        <td>{{ $row['place_of_worship'] }}</td>
                                        <td>{{ $row['locker'] }}</td>
                                    </tr>
                                @endforeach
                                @php $shownGroups++; @endphp
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <p class="text-secondary small mb-0">Halaman ini menampilkan beberapa grup awal. PDF memuat seluruh data dengan layout per departemen.</p>
            </div>
        </div>
    </div>
</body>

</html>
