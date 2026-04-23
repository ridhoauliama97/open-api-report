<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif:ital,wght@0,100..900;1,100..900&display=swap"
        rel="stylesheet">
    <title>Rekap Pembelian Kayu Bulat</title>
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
                <h1 class="h3 mb-3">Rekap Pembelian Kayu Bulat</h1>
                <p class="text-secondary mb-4">
                    Laporan ini mengambil data langsung dari <code>SPWps_LapRekapPembelianKayuBulat</code>
                    tanpa parameter input dan otomatis menampilkan rekap <strong>11 tahun terakhir</strong>.
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

                <form method="POST" action="{{ route('reports.kayu-bulat.rekap-pembelian.download') }}" class="row g-3">
                    @csrf
                    <div class="col-12">
                        <div class="d-flex gap-2 flex-wrap">
                            <button type="submit" class="btn btn-primary">Generate & Download PDF</button>
                            <button type="submit" class="btn btn-outline-primary" name="preview_pdf" value="1"
                                formaction="{{ route('reports.kayu-bulat.rekap-pembelian.preview-pdf') }}"
                                formtarget="_blank">Preview PDF</button>
                            <button type="button" id="previewJsonBtn" class="btn btn-outline-secondary">Preview JSON</button>
                            <button type="button" id="healthBtn" class="btn btn-outline-dark">Check Health</button>
                        </div>
                    </div>
                </form>

                <div id="previewJsonWrapper" class="mt-4 d-none">
                    <h2 class="h6 mb-2">Preview JSON</h2>
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
                        body: JSON.stringify({}),
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
                postJson('{{ route('reports.kayu-bulat.rekap-pembelian.preview') }}', previewOutput, previewWrapper);
            });

            healthButton?.addEventListener('click', function() {
                postJson('{{ route('reports.kayu-bulat.rekap-pembelian.health') }}', healthOutput, healthWrapper);
            });
        });
    </script>
</body>

</html>
