<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif:ital,wght@0,100..900;1,100..900&display=swap"
        rel="stylesheet">
    <title>Generate Laporan Umur Laminating Detail</title>
    @vite(['resources/css/app.css','resources/js/app.js'])
</head>

<body class="bg-light">
    <nav class="navbar navbar-expand-lg bg-primary navbar-dark">
        <div class="container">
            <a class="navbar-brand fw-semibold"
                href="{{ url('/') }}">{{ config('app.name','PDF Generator (Open API)') }}</a>
        </div>
    </nav>

    <main class="container py-5">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4 p-md-5">
                <h1 class="h3 mb-3">Generate Laporan Umur Laminating Detail (PDF)</h1>
                <p class="text-secondary mb-4">
                    Isi parameter umur (batas hari) Umur 1 sampai Umur 4, lalu sistem akan mengambil data dari
                    <strong>SP_LapUmurLaminating</strong> dan mengunduh file PDF.
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

                <form method="POST" action="{{ route('reports.laminating.umur-laminating-detail.download') }}" class="row g-3">
                    @csrf
                    @php
                        $defaultUmur1 = 15;
                        $defaultUmur2 = 30;
                        $defaultUmur3 = 60;
                        $defaultUmur4 = 90;
                    @endphp

                    <div class="col-md-3">
                        <label for="Umur1" class="form-label">Umur 1</label>
                        <input type="number" id="Umur1" name="Umur1" class="form-control" min="0" required
                            value="{{ old('Umur1', old('umur1', $defaultUmur1)) }}">
                    </div>

                    <div class="col-md-3">
                        <label for="Umur2" class="form-label">Umur 2</label>
                        <input type="number" id="Umur2" name="Umur2" class="form-control" min="0" required
                            value="{{ old('Umur2', old('umur2', $defaultUmur2)) }}">
                    </div>

                    <div class="col-md-3">
                        <label for="Umur3" class="form-label">Umur 3</label>
                        <input type="number" id="Umur3" name="Umur3" class="form-control" min="0" required
                            value="{{ old('Umur3', old('umur3', $defaultUmur3)) }}">
                    </div>

                    <div class="col-md-3">
                        <label for="Umur4" class="form-label">Umur 4</label>
                        <input type="number" id="Umur4" name="Umur4" class="form-control" min="0" required
                            value="{{ old('Umur4', old('umur4', $defaultUmur4)) }}">
                    </div>

                    <div class="col-12">
                        <div class="d-flex gap-2 flex-wrap">
                            <button type="submit" class="btn btn-primary">Generate & Download PDF</button>
                            <button type="submit" class="btn btn-outline-primary" name="preview_pdf" value="1"
                                formaction="{{ route('reports.laminating.umur-laminating-detail.preview-pdf') }}"
                                formtarget="_blank">Preview PDF</button>
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

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const previewButton = document.getElementById('previewJsonBtn');
            const previewWrapper = document.getElementById('previewJsonWrapper');
            const previewOutput = document.getElementById('previewJsonOutput');
            const umur1Input = document.getElementById('Umur1');
            const umur2Input = document.getElementById('Umur2');
            const umur3Input = document.getElementById('Umur3');
            const umur4Input = document.getElementById('Umur4');

            if (!previewButton || !previewWrapper || !previewOutput) {
                return;
            }

            previewButton.addEventListener('click', async function() {
                previewWrapper.classList.remove('d-none');
                previewOutput.textContent = 'Loading...';

                try {
                    const response = await fetch(
                        '{{ route('reports.laminating.umur-laminating-detail.preview') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            },
                            body: JSON.stringify({
                                Umur1: umur1Input ? umur1Input.value : null,
                                Umur2: umur2Input ? umur2Input.value : null,
                                Umur3: umur3Input ? umur3Input.value : null,
                                Umur4: umur4Input ? umur4Input.value : null,
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
