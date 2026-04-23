<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Generate Laporan Produksi Per Nomor Produksi</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-light">
    <nav class="navbar navbar-expand-lg bg-primary navbar-dark">
        <div class="container">
            <a class="navbar-brand fw-semibold" href="{{ url('/') }}">{{ config('app.name', 'PDF Generator (Open API)') }}</a>
        </div>
    </nav>

    <main class="container py-5">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4 p-md-5">
                <h1 class="h3 mb-3">Generate Laporan Produksi Per Nomor Produksi</h1>
                <p class="text-secondary mb-4">
                    Masukkan nomor produksi, lalu sistem akan mengambil data dari
                    <strong>SPWps_LapProduksiCCAkhir</strong>.
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

                <form method="POST"
                    action="{{ route('reports.proses-produksi.produksi-per-nomor-produksi.download') }}"
                    class="row g-3">
                    @csrf

                    <div class="col-md-7">
                        <label for="no_produksi" class="form-label">No Produksi</label>
                        <input type="text" id="no_produksi" name="no_produksi" class="form-control" required
                            value="{{ old('no_produksi', old('NoProduksi')) }}" placeholder="Contoh: VA.002701">
                    </div>

                    <div class="col-12">
                        <div class="d-flex gap-2 flex-wrap">
                            <button type="submit" class="btn btn-primary">Generate & Download PDF</button>
                            <button type="submit" class="btn btn-outline-primary" name="preview_pdf" value="1"
                                formtarget="_blank">Preview PDF</button>
                            <button type="button" id="previewJsonBtn" class="btn btn-outline-secondary">Preview Raw SP
                                (JSON)</button>
                            <button type="button" id="healthBtn" class="btn btn-outline-dark">Check Health</button>
                        </div>
                    </div>
                </form>

                <div id="previewJsonWrapper" class="mt-4 d-none">
                    <h2 class="h6 mb-2">Preview Raw SP (JSON)</h2>
                    <pre id="previewJsonOutput" class="bg-white border rounded p-3 mb-0"
                        style="max-height: 360px; overflow: auto;"></pre>
                </div>

                <div id="healthWrapper" class="mt-4 d-none">
                    <h2 class="h6 mb-2">Health Check</h2>
                    <pre id="healthOutput" class="bg-white border rounded p-3 mb-0"
                        style="max-height: 360px; overflow: auto;"></pre>
                </div>
            </div>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const noProduksiInput = document.getElementById('no_produksi');
            const previewButton = document.getElementById('previewJsonBtn');
            const previewWrapper = document.getElementById('previewJsonWrapper');
            const previewOutput = document.getElementById('previewJsonOutput');
            const healthButton = document.getElementById('healthBtn');
            const healthWrapper = document.getElementById('healthWrapper');
            const healthOutput = document.getElementById('healthOutput');

            async function postJson(url, outputElement, wrapperElement) {
                wrapperElement.classList.remove('d-none');
                outputElement.textContent = 'Loading...';

                try {
                    const response = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        },
                        body: JSON.stringify({
                            no_produksi: noProduksiInput.value,
                        }),
                    });

                    const payload = await response.json();
                    outputElement.textContent = JSON.stringify(payload, null, 2);
                } catch (error) {
                    outputElement.textContent = JSON.stringify({
                        message: 'Gagal mengambil data.',
                        error: String(error),
                    }, null, 2);
                }
            }

            previewButton?.addEventListener('click', function() {
                postJson('{{ route('reports.proses-produksi.produksi-per-nomor-produksi.preview') }}', previewOutput,
                    previewWrapper);
            });

            healthButton?.addEventListener('click', function() {
                postJson('{{ route('reports.proses-produksi.produksi-per-nomor-produksi.health') }}', healthOutput,
                    healthWrapper);
            });
        });
    </script>
</body>

</html>
