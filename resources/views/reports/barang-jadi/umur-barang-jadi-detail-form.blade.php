<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Generate Laporan Umur Barang Jadi Detail</title>
    @vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg bg-primary navbar-dark">
        <div class="container">
            <a class="navbar-brand fw-semibold" href="{{ url('/') }}">{{ config('app.name','PDF Generator (Open API)') }}</a>
        </div>
    </nav>

    <main class="container py-5">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4 p-md-5">
                <h1 class="h3 mb-3">Generate Laporan Umur Barang Jadi Detail</h1>
                <p class="text-secondary mb-4">Masukkan rentang umur untuk membentuk bucket laporan umur barang jadi.</p>

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('reports.barang-jadi.umur-barang-jadi-detail.download') }}" class="row g-3">
                    @csrf
                    <div class="col-6 col-md-3">
                        <label for="Umur1" class="form-label">Umur 1</label>
                        <input type="number" min="0" class="form-control" id="Umur1" name="Umur1" value="{{ old('Umur1', 15) }}" required>
                    </div>
                    <div class="col-6 col-md-3">
                        <label for="Umur2" class="form-label">Umur 2</label>
                        <input type="number" min="0" class="form-control" id="Umur2" name="Umur2" value="{{ old('Umur2', 30) }}" required>
                    </div>
                    <div class="col-6 col-md-3">
                        <label for="Umur3" class="form-label">Umur 3</label>
                        <input type="number" min="0" class="form-control" id="Umur3" name="Umur3" value="{{ old('Umur3', 60) }}" required>
                    </div>
                    <div class="col-6 col-md-3">
                        <label for="Umur4" class="form-label">Umur 4</label>
                        <input type="number" min="0" class="form-control" id="Umur4" name="Umur4" value="{{ old('Umur4', 90) }}" required>
                    </div>
                    <div class="col-12">
                        <div class="d-flex gap-2 flex-wrap">
                            <button type="submit" class="btn btn-primary">Generate & Download PDF</button>
                            <button type="submit" class="btn btn-outline-primary" name="preview_pdf" value="1" formtarget="_blank">Preview PDF</button>
                            <button type="button" id="previewJsonBtn" class="btn btn-outline-secondary">Preview Raw SP (JSON)</button>
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

            if (!previewButton || !previewWrapper || !previewOutput) return;

            previewButton.addEventListener('click', async function() {
                previewWrapper.classList.remove('d-none');
                previewOutput.textContent = 'Loading...';
                const payload = {
                    Umur1: document.getElementById('Umur1').value,
                    Umur2: document.getElementById('Umur2').value,
                    Umur3: document.getElementById('Umur3').value,
                    Umur4: document.getElementById('Umur4').value,
                };

                try {
                    const response = await fetch('{{ route('reports.barang-jadi.umur-barang-jadi-detail.preview') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        },
                        body: JSON.stringify(payload),
                    });
                    previewOutput.textContent = JSON.stringify(await response.json(), null, 2);
                } catch (error) {
                    previewOutput.textContent = JSON.stringify({ message: 'Gagal mengambil preview.', error: String(error) }, null, 2);
                }
            });
        });
    </script>
</body>
</html>
