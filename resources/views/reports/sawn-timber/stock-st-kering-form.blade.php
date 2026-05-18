<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif:ital,wght@0,100..900;1,100..900&display=swap"
        rel="stylesheet">
    <title>Generate Laporan Stock ST kering</title>
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
                <h1 class="h3 mb-3">Generate Laporan Stock ST kering (PDF)</h1>
                <p class="text-secondary mb-4">
                    Laporan ini mengambil data langsung dari stored procedure <code>SP_LapStockSTKering</code>.
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

                <form method="POST" action="{{ route('reports.sawn-timber.stock-st-kering.download') }}" class="row g-3">
                    @csrf
                    <input type="hidden" name="job_id" id="completedPdfJobId">
                    <div class="col-md-4">
                        <label for="end_date" class="form-label">End Date</label>
                        <input type="date" id="end_date" name="end_date" class="form-control"
                            value="{{ old('end_date', old('TglAkhir')) }}" required>
                    </div>
                    <div class="col-12">
                        <div class="d-flex gap-2 flex-wrap">
                            <button type="button" id="asyncPdfBtn" class="btn btn-primary">Generate PDF di
                                Background</button>
                            <button type="submit" class="btn btn-primary">Generate & Download PDF</button>
                            <button type="submit" class="btn btn-outline-primary"
                                formaction="{{ route('reports.sawn-timber.stock-st-kering.preview-pdf-wait') }}"
                                formtarget="_blank">Preview PDF</button>
                            <button type="button" id="previewJsonBtn" class="btn btn-outline-secondary">Preview Raw SP
                                (JSON)</button>
                        </div>
                    </div>
                </form>

                <div id="asyncStatusBox" class="alert alert-info d-none mt-4 mb-0">
                    <div id="asyncStatusText" class="fw-semibold">Menyiapkan job PDF...</div>
                    <div class="small text-secondary mt-1">Halaman ini akan mengecek status otomatis. PDF bisa
                        diunduh setelah proses selesai.</div>
                    <div class="mt-3 d-none" id="asyncDownloadWrap">
                        <a href="#" id="asyncDownloadLink" class="btn btn-success btn-sm">Download PDF</a>
                    </div>
                </div>

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
            const endDateInput = document.getElementById('end_date');
            const asyncButton = document.getElementById('asyncPdfBtn');
            const statusBox = document.getElementById('asyncStatusBox');
            const statusText = document.getElementById('asyncStatusText');
            const downloadWrap = document.getElementById('asyncDownloadWrap');
            const downloadLink = document.getElementById('asyncDownloadLink');
            const completedPdfJobId = document.getElementById('completedPdfJobId');

            if (!previewButton || !previewWrapper || !previewOutput || !endDateInput) {
                return;
            }

            asyncButton?.addEventListener('click', async function() {
                asyncButton.disabled = true;
                statusBox?.classList.remove('d-none');
                downloadWrap?.classList.add('d-none');
                completedPdfJobId?.setAttribute('value', '');
                if (statusText) {
                    statusText.textContent = 'Mengirim job PDF ke background...';
                }

                try {
                    const response = await fetch(
                        '{{ route('reports.sawn-timber.stock-st-kering.async') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            },
                            body: JSON.stringify({
                                end_date: endDateInput.value,
                            }),
                        });

                    const payload = await response.json();
                    if (!response.ok) {
                        throw new Error(payload.message || 'Gagal membuat job PDF.');
                    }

                    if (payload.status === 'done') {
                        markPdfDone(payload);
                        return;
                    }

                    if (statusText) {
                        statusText.textContent = `Job ${payload.job_id} dibuat. Status: ${payload.status}`;
                    }

                    pollPdfJob(payload.status_url);
                } catch (error) {
                    if (statusText) {
                        statusText.textContent = error.message || 'Gagal membuat job PDF.';
                    }
                    asyncButton.disabled = false;
                }
            });

            async function pollPdfJob(statusUrl) {
                try {
                    const response = await fetch(statusUrl, {
                        headers: {
                            'Accept': 'application/json',
                        },
                    });
                    const payload = await response.json();

                    if (!response.ok) {
                        throw new Error(payload.message || 'Gagal membaca status job.');
                    }

                    if (payload.status === 'done') {
                        markPdfDone(payload);
                        return;
                    }

                    if (payload.status === 'failed') {
                        throw new Error(payload.error || 'Job PDF gagal diproses.');
                    }

                    if (statusText) {
                        statusText.textContent = `PDF sedang diproses. Status: ${payload.status}`;
                    }

                    window.setTimeout(() => pollPdfJob(statusUrl), 5000);
                } catch (error) {
                    if (statusText) {
                        statusText.textContent = error.message || 'Gagal membaca status job.';
                    }
                    asyncButton.disabled = false;
                }
            }

            function markPdfDone(payload) {
                completedPdfJobId?.setAttribute('value', payload.job_id || '');
                if (statusText) {
                    statusText.textContent = 'PDF sudah selesai dibuat.';
                }
                if (downloadLink && payload.download_url) {
                    downloadLink.href = payload.download_url;
                    downloadWrap?.classList.remove('d-none');
                }
                asyncButton.disabled = false;
            }

            previewButton.addEventListener('click', async function() {
                previewWrapper.classList.remove('d-none');
                previewOutput.textContent = 'Loading...';

                try {
                    const response = await fetch(
                        '{{ route('reports.sawn-timber.stock-st-kering.preview') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            },
                            body: JSON.stringify({
                                end_date: endDateInput.value,
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


