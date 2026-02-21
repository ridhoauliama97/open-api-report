<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif:ital,wght@0,100..900;1,100..900&display=swap"
        rel="stylesheet">
    <title>Generate Laporan Mutasi Finger Joint</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-light">
    @if (session('success'))
        <div class="toast-container position-fixed top-0 end-0 p-3">
            <div id="authSuccessToast" class="toast align-items-center text-bg-success border-0" role="alert"
                aria-live="assertive" aria-atomic="true" data-bs-delay="3000">
                <div class="d-flex">
                    <div class="toast-body">{{ session('success') }}</div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"
                        aria-label="Close"></button>
                </div>
            </div>
        </div>
    @endif

    <nav class="navbar navbar-expand-lg bg-primary navbar-dark">
        <div class="container">
            <a class="navbar-brand fw-semibold"
                href="{{ url('/') }}">{{ config('app.name', 'PDF Generator (Open API)') }}</a>
        </div>
    </nav>

    <main class="container py-5">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4 p-md-5">
                <h1 class="h3 mb-3">Generate Laporan Mutasi Finger Joint (PDF)</h1>
                <p class="text-secondary mb-4">
                    Isi tanggal awal dan tanggal akhir, lalu sistem akan mengambil data mutasi finger joint
                    dan langsung mengunduh file PDF.
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

                <form method="POST" action="{{ route('reports.mutasi.finger-joint.download') }}" class="row g-3">
                    @csrf
                    <div class="col-md-6">
                        <label for="TglAwal" class="form-label">Tanggal Awal</label>
                        <input type="date" id="TglAwal" name="TglAwal" class="form-control" required
                            value="{{ old('TglAwal', old('start_date')) }}">
                    </div>

                    <div class="col-md-6">
                        <label for="TglAkhir" class="form-label">Tanggal Akhir</label>
                        <input type="date" id="TglAkhir" name="TglAkhir" class="form-control" required
                            value="{{ old('TglAkhir', old('end_date')) }}">
                    </div>

                    <div class="col-12">
                        <div class="d-flex gap-2 flex-wrap">
                            <button type="submit" class="btn btn-primary">Generate & Download PDF</button>
                            <button type="button" id="previewJsonBtn" class="btn btn-outline-secondary">Preview Raw SP
                                (JSON)</button>
                        </div>
                    </div>
                </form>

                <div id="previewJsonWrapper" class="mt-4 d-none">
                    <h2 class="h6 mb-2">Preview Raw SP (JSON)</h2>
                    <pre id="previewJsonOutput" class="bg-white border rounded p-3 mb-0" style="max-height: 360px; overflow: auto;"></pre>
                </div>
            </div>
        </div>
    </main>

    @if (session('success'))
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const toastEl = document.getElementById('authSuccessToast');
                if (!toastEl || !window.bootstrap) {
                    return;
                }

                const toast = new window.bootstrap.Toast(toastEl);
                toast.show();
            });
        </script>
    @endif

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const previewButton = document.getElementById('previewJsonBtn');
            const previewWrapper = document.getElementById('previewJsonWrapper');
            const previewOutput = document.getElementById('previewJsonOutput');
            const startDateInput = document.getElementById('TglAwal');
            const endDateInput = document.getElementById('TglAkhir');

            if (!previewButton || !previewWrapper || !previewOutput || !startDateInput || !endDateInput) {
                return;
            }

            previewButton.addEventListener('click', async function() {
                const tglAwal = startDateInput.value;
                const tglAkhir = endDateInput.value;

                previewWrapper.classList.remove('d-none');
                previewOutput.textContent = 'Loading...';

                try {
                    const response = await fetch(
                        '{{ route('reports.mutasi.finger-joint.preview') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            },
                            body: JSON.stringify({
                                TglAwal: tglAwal,
                                TglAkhir: tglAkhir,
                            }),
                        });

                    const payload = await response.json();
                    previewOutput.textContent = JSON.stringify(payload, null, 2);
                } catch (error) {
                    previewOutput.textContent = JSON.stringify({
                        message: 'Gagal mengambil preview.',
                        error: String(error),
                    }, null, 2);
                }
            });
        });
    </script>
</body>

</html>



