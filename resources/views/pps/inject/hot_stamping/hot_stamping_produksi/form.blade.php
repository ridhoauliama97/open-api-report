<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Laporan Harian Hasil Hot Stamping Produksi</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-light">
    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-12 col-xl-8">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h1 class="h3 mb-1">Generate Laporan Harian Hasil Hot Stamping Produksi</h1>
                        <p class="text-muted mb-0">
                            Sumber data: stored procedure <code>SP_LapHasilProduksiHarianHotStamping</code> pada database
                            PPS.
                        </p>
                    </div>
                    <a href="{{ url('/') }}" class="btn btn-outline-secondary">Kembali</a>
                </div>

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <div class="fw-semibold mb-1">Generate laporan gagal.</div>
                        <ul class="mb-0 ps-3">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="card shadow-sm border-0">
                    <div class="card-body p-4">
                        <form method="POST" action="{{ route('reports.pps.inject.hot-stamping.hot-stamping-produksi.download') }}"
                            class="row g-3">
                            @csrf
                            <div class="col-12">
                                <label for="no_produksi" class="form-label fw-semibold">No Produksi</label>
                                <input type="text" name="no_produksi" id="no_produksi"
                                    class="form-control @error('no_produksi') is-invalid @enderror"
                                    value="{{ old('no_produksi') }}" list="recent-no-produksi"
                                    placeholder="Contoh: BH.0000000350" required>
                                <datalist id="recent-no-produksi">
                                    @foreach (($recentNoProduksi ?? []) as $item)
                                        <option value="{{ $item['no_produksi'] }}">
                                            {{ $item['no_produksi'] }} | {{ $item['tanggal'] }} | Shift
                                            {{ $item['shift'] }}
                                        </option>
                                    @endforeach
                                </datalist>
                                @error('no_produksi')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12 d-flex gap-2 flex-wrap">
                                <button type="submit" class="btn btn-primary">
                                    Download PDF
                                </button>
                                <button type="submit" name="preview_pdf" value="1" class="btn btn-outline-primary">
                                    Preview PDF
                                </button>
                                <button type="button" class="btn btn-outline-secondary" id="preview-json-button">
                                    Preview JSON
                                </button>
                                <button type="button" class="btn btn-outline-secondary" id="health-check-button">
                                    Health Check
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card shadow-sm border-0 mt-3">
                    <div class="card-body">
                        <h2 class="h6">Output Preview</h2>
                        <pre id="output-panel" class="bg-dark text-white rounded p-3 mb-0"
                            style="min-height: 240px;">Klik "Preview JSON" atau "Health Check" untuk melihat output.</pre>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const noProduksiInput = document.getElementById('no_produksi');
        const outputPanel = document.getElementById('output-panel');
        const csrfToken = document.querySelector('input[name="_token"]').value;

        const postJson = async (url) => {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({
                    no_produksi: noProduksiInput.value,
                }),
            });

            const payload = await response.json();
            outputPanel.textContent = JSON.stringify(payload, null, 2);
        };

        document.getElementById('preview-json-button').addEventListener('click', () => {
            postJson('{{ route('reports.pps.inject.hot-stamping.hot-stamping-produksi.preview') }}');
        });

        document.getElementById('health-check-button').addEventListener('click', () => {
            postJson('{{ route('reports.pps.inject.hot-stamping.hot-stamping-produksi.health') }}');
        });
    </script>
</body>

</html>
