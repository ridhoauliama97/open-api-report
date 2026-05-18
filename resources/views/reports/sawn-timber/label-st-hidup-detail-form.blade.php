<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Generate Laporan Label ST (Hidup) Detail</title>
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
                <h1 class="h3 mb-3">Generate Laporan Label ST (Hidup) Detail</h1>
                <p class="text-secondary mb-4">
                    Laporan ini mengambil data dari stored procedure <code>SP_LapLabelSTHidupDetail</code>.
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

                <form method="POST" action="{{ route('reports.sawn-timber.label-st-hidup-detail.download') }}"
                    class="row g-3">
                    @csrf
                    <input type="hidden" name="job_id" id="completedPdfJobId">
                    <div class="col-12">
                        <div class="d-flex gap-2 flex-wrap">
                            <button type="button" id="asyncPdfBtn" class="btn btn-primary">Generate PDF di
                                Background</button>
                            <button type="submit" class="btn btn-outline-primary"
                                formaction="{{ route('reports.sawn-timber.label-st-hidup-detail.preview-pdf-wait') }}"
                                formtarget="_blank">Preview PDF</button>
                            <button type="submit" class="btn btn-outline-secondary">Download PDF Langsung</button>
                            <button type="button" id="previewJsonBtn" class="btn btn-outline-secondary">Preview (JSON)
                            </button>
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

                <script>
                    const asyncButton = document.getElementById('asyncPdfBtn');
                    const statusBox = document.getElementById('asyncStatusBox');
                    const statusText = document.getElementById('asyncStatusText');
                    const downloadWrap = document.getElementById('asyncDownloadWrap');
                    const downloadLink = document.getElementById('asyncDownloadLink');

                    asyncButton?.addEventListener('click', async () => {
                        asyncButton.disabled = true;
                        statusBox?.classList.remove('d-none');
                        downloadWrap?.classList.add('d-none');
                        if (statusText) {
                            statusText.textContent = 'Mengirim job PDF ke queue...';
                        }

                        try {
                            const response = await fetch(
                                '{{ route('reports.sawn-timber.label-st-hidup-detail.async') }}', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'Accept': 'application/json',
                                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                    },
                                    body: JSON.stringify({}),
                                }
                            );

                            const data = await response.json();
                            if (!response.ok) {
                                throw new Error(data.message || 'Gagal membuat job PDF.');
                            }

                            if (statusText) {
                                statusText.textContent = `Job ${data.job_id} dibuat. Status: ${data.status}`;
                            }

                            pollPdfJob(data.status_url);
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
                            const data = await response.json();

                            if (!response.ok) {
                                throw new Error(data.message || 'Gagal membaca status job.');
                            }

                            if (data.status === 'done') {
                                if (statusText) {
                                    statusText.textContent = 'PDF sudah selesai dibuat.';
                                }
                                document.getElementById('completedPdfJobId')?.setAttribute('value', data.job_id);
                                if (downloadLink && data.download_url) {
                                    downloadLink.href = data.download_url;
                                    downloadWrap?.classList.remove('d-none');
                                }
                                asyncButton.disabled = false;
                                return;
                            }

                            if (data.status === 'failed') {
                                throw new Error(data.error || 'Job PDF gagal diproses.');
                            }

                            if (statusText) {
                                statusText.textContent = `PDF sedang diproses. Status: ${data.status}`;
                            }

                            window.setTimeout(() => pollPdfJob(statusUrl), 5000);
                        } catch (error) {
                            if (statusText) {
                                statusText.textContent = error.message || 'Gagal membaca status job.';
                            }
                            asyncButton.disabled = false;
                        }
                    }

                    document.getElementById('previewJsonBtn')?.addEventListener('click', async () => {
                        const response = await fetch(
                            '{{ route('reports.sawn-timber.label-st-hidup-detail.preview') }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                },
                                body: JSON.stringify({}),
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
