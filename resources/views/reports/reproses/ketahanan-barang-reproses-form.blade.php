<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Laporan Ketahanan Barang Dagang Reproses</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-light">
    <main class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4 p-md-5">
                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
                            <div>
                                <h1 class="h3 mb-1">Laporan Ketahanan Barang Dagang Reproses</h1>
                                <p class="text-secondary mb-0">
                                    Generate laporan PDF, preview PDF, atau preview JSON dari SP_LapKetahananBarangReproses.
                                </p>
                            </div>
                            <a href="{{ url('/') }}" class="btn btn-outline-secondary">Kembali</a>
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

                        @php
                            $defaultStart = old('TglAwal', old('start_date', now()->startOfMonth()->toDateString()));
                            $defaultEnd = old('TglAkhir', old('end_date', now()->endOfMonth()->toDateString()));
                        @endphp

                        <form method="POST" action="{{ route('reports.reproses.ketahanan-barang-reproses.download') }}"
                            class="row g-3">
                            @csrf

                            <div class="col-md-6">
                                <label for="TglAwal" class="form-label">Tanggal Awal</label>
                                <input type="date" class="form-control" id="TglAwal" name="TglAwal"
                                    value="{{ $defaultStart }}" required>
                            </div>

                            <div class="col-md-6">
                                <label for="TglAkhir" class="form-label">Tanggal Akhir</label>
                                <input type="date" class="form-control" id="TglAkhir" name="TglAkhir"
                                    value="{{ $defaultEnd }}" required>
                            </div>

                            <div class="col-12">
                                <div class="d-flex flex-wrap gap-2 pt-2">
                                    <button type="submit" class="btn btn-primary">Download PDF</button>
                                    <button type="submit" class="btn btn-outline-primary"
                                        formaction="{{ route('reports.reproses.ketahanan-barang-reproses.preview-pdf') }}"
                                        formtarget="_blank">
                                        Preview PDF
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" id="preview-json-btn">
                                        Preview JSON
                                    </button>
                                </div>
                            </div>
                        </form>

                        <div class="mt-4">
                            <label for="preview-json" class="form-label fw-semibold">Hasil Preview JSON</label>
                            <textarea id="preview-json" class="form-control font-monospace" rows="16" readonly
                                placeholder="Klik &quot;Preview JSON&quot; untuk melihat hasil data..."></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const previewButton = document.getElementById('preview-json-btn');
            const previewArea = document.getElementById('preview-json');
            const form = previewButton?.closest('form');

            if (!previewButton || !previewArea || !form) {
                return;
            }

            previewButton.addEventListener('click', async () => {
                previewButton.disabled = true;
                previewArea.value = 'Memuat preview...';

                try {
                    const formData = new FormData(form);
                    const response = await fetch(
                        '{{ route('reports.reproses.ketahanan-barang-reproses.preview') }}', {
                            method: 'POST',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json',
                            },
                            body: formData,
                        });

                    const payload = await response.json();
                    previewArea.value = JSON.stringify(payload, null, 2);
                } catch (error) {
                    previewArea.value = JSON.stringify({
                        message: 'Gagal mengambil preview.',
                        error: error instanceof Error ? error.message : String(error),
                    }, null, 2);
                } finally {
                    previewButton.disabled = false;
                }
            });
        });
    </script>
</body>

</html>
