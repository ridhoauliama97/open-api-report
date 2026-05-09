<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Generate Laporan Tracing ST</title>
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
                <h1 class="h3 mb-3">Generate Laporan Tracing ST (PDF)</h1>
                <p class="text-secondary mb-4">
                    Laporan ini mengambil data dari stored procedure <code>SP_LapTracingST</code>
                    dengan parameter <code>NoProduk</code> dan mencetak PDF A6 portrait.
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

                <form method="POST" action="{{ route('reports.sawn-timber.tracing-st.download') }}" class="row g-3">
                    @csrf
                    <div class="col-12 col-md-6">
                        <label for="no_produk" class="form-label">No Produk / No ST</label>
                        <input type="text" id="no_produk" name="no_produk" value="{{ old('no_produk') }}"
                            maxlength="20" class="form-control" required>
                    </div>
                    <div class="col-12">
                        <div class="d-flex gap-2 flex-wrap">
                            <button type="submit" class="btn btn-primary">Generate & Download PDF</button>
                            <button type="submit" id="previewPdfBtn" class="btn btn-outline-primary"
                                name="preview_pdf" value="1"
                                formaction="{{ route('reports.sawn-timber.tracing-st.preview-pdf') }}"
                                formtarget="_blank">Preview PDF</button>
                            <button type="button" id="previewJsonBtn" class="btn btn-outline-secondary">Preview Raw SP
                                (JSON)</button>
                        </div>
                    </div>
                </form>

                <div id="previewJsonWrapper" class="mt-4 d-none">
                    <h2 class="h6 mb-2">Preview Raw SP (JSON)</h2>
                    <pre id="previewJsonOutput" class="bg-white border rounded p-3 mb-0"
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
            const noProdukInput = document.getElementById('no_produk');
            const previewPdfButton = document.getElementById('previewPdfBtn');

            if (!previewButton || !previewWrapper || !previewOutput || !noProdukInput) {
                return;
            }

            if (previewPdfButton) {
                const previewPdfBaseUrl = '{{ route('reports.sawn-timber.tracing-st.preview-pdf') }}';
                previewPdfButton.addEventListener('click', function() {
                    const safeNoProduk = noProdukInput.value.replace(/[^A-Za-z0-9-]+/g, '-').replace(/^-+|-+$/g, '') || 'tanpa-nomor';
                    const filename = `Laporan-Tracing-ST-${safeNoProduk}.pdf`;
                    previewPdfButton.formAction = `${previewPdfBaseUrl}/${encodeURIComponent(filename)}`;
                });
            }

            previewButton.addEventListener('click', async function() {
                previewWrapper.classList.remove('d-none');
                previewOutput.textContent = 'Loading...';

                try {
                    const response = await fetch('{{ route('reports.sawn-timber.tracing-st.preview') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        },
                        body: JSON.stringify({
                            no_produk: noProdukInput.value,
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
