<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Generate Laporan Rekap Hasil Sawmill / Meja</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-light">
    <nav class="navbar navbar-expand-lg bg-primary navbar-dark">
        <div class="container">
            <a class="navbar-brand fw-semibold"
                href="{{ url('/') }}">{{ config('app.name', 'PDF Generator (Open API)') }}</a>
        </div>
    </nav>

    <main class="container py-5">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4 p-md-5">
                <h1 class="h3 mb-3">Generate Laporan Rekap Hasil Sawmill / Meja</h1>
                <p class="text-secondary mb-4">
                    Laporan ini mengambil data dari stored procedure <code>SPWps_LapRekapHasilSawmillPerMeja</code>.
                </p>

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('reports.sawn-timber.rekap-hasil-sawmill-per-meja.download') }}"
                    class="row g-3">
                    @csrf
                    <div class="col-md-4">
                        <label for="start_date" class="form-label">Tanggal Awal</label>
                        <input type="date" id="start_date" name="start_date" class="form-control"
                            value="{{ old('start_date', old('TglAwal')) }}" required>
                    </div>
                    <div class="col-md-4">
                        <label for="end_date" class="form-label">Tanggal Akhir</label>
                        <input type="date" id="end_date" name="end_date" class="form-control"
                            value="{{ old('end_date', old('TglAkhir')) }}" required>
                    </div>
                    <div class="col-12">
                        <div class="d-flex gap-2 flex-wrap">
                            <button type="submit" class="btn btn-primary">Generate & Download PDF</button>
                            <button type="submit" class="btn btn-outline-primary"
                                formaction="{{ route('reports.sawn-timber.rekap-hasil-sawmill-per-meja.preview-pdf') }}"
                                formtarget="_blank">Preview PDF</button>
                            <button type="button" id="previewJsonBtn" class="btn btn-outline-secondary">Preview Pivot
                                (JSON)</button>
                        </div>
                    </div>
                </form>

                <script>
                    document.getElementById('previewJsonBtn')?.addEventListener('click', async () => {
                        const startDate = document.getElementById('start_date')?.value;
                        const endDate = document.getElementById('end_date')?.value;

                        const response = await fetch(
                            '{{ route('reports.sawn-timber.rekap-hasil-sawmill-per-meja.preview') }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                },
                                body: JSON.stringify({
                                    start_date: startDate,
                                    end_date: endDate,
                                }),
                            }
                        );

                        const data = await response.json();
                        const win = window.open('', '_blank');
                        win.document.write('<pre>' + JSON.stringify(data, null, 2) + '</pre>');
                        win.document.close();
                    });
                </script>
            </div>
        </div>
    </main>
</body>

</html>
