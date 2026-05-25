<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ascend XML Test</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-7 col-xl-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4 p-md-5">
                        <div class="mb-4">
                            <h1 class="h4 fw-bold mb-2">Ascend XML Test</h1>
                            <p class="text-secondary mb-0">Upload file XML Employee List dari Ascend untuk preview PDF.
                            </p>
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

                        <form action="{{ route('ascend-test.pdf') }}" method="POST" enctype="multipart/form-data"
                            target="_blank">
                            @csrf

                            <div class="mb-4">
                                <label for="report_type" class="form-label fw-semibold">Laporan</label>
                                <select class="form-select @error('report_type') is-invalid @enderror" id="report_type"
                                    name="report_type" required>
                                    <option value="list_karyawan" @selected(old('report_type', 'list_karyawan') === 'list_karyawan')>
                                        List Karyawan RU
                                    </option>
                                    <option value="karyawan_per_masa_kerja" @selected(old('report_type') === 'karyawan_per_masa_kerja')>
                                        Laporan Karyawan Per Masa Kerja (RU)
                                    </option>
                                    <option value="data_karyawan_status_kerja" @selected(old('report_type') === 'data_karyawan_status_kerja')>
                                        Laporan Data Karyawan (RU) - Status Kerja
                                    </option>
                                </select>
                                @error('report_type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-4">
                                <label for="xml_file" class="form-label fw-semibold">File XML</label>
                                <input type="file" class="form-control @error('xml_file') is-invalid @enderror"
                                    id="xml_file" name="xml_file" accept=".xml,text/xml,application/xml" required>
                                <div class="form-text">Maksimal 20 MB.</div>
                            </div>

                            <div class="d-flex justify-content-end gap-2">
                                <button type="reset" class="btn btn-outline-secondary">Reset</button>
                                <button type="submit" class="btn btn-primary">Preview PDF</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>
