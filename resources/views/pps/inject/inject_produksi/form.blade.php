<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Generate Laporan Harian Hasil Inject Produksi</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-light">
    <nav class="navbar navbar-expand-lg bg-primary navbar-dark">
        <div class="container">
            <a class="navbar-brand fw-semibold" href="{{ url('/') }}">{{ config('app.name', 'Reporting Tools') }}</a>
        </div>
    </nav>
    <main class="container py-5">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4 p-md-5">
                <h1 class="h3 mb-3">Generate Laporan Harian Hasil Inject Produksi (PPS)</h1>
                <p class="text-secondary mb-4">Sumber data utama: stored procedure <code>SP_LapHasilProduksiHarianInject</code> pada database PPS.</p>

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('reports.pps.inject.inject-produksi.download') }}" class="row g-3">
                    @csrf
                    <div class="col-md-7">
                        <label for="no_produksi" class="form-label">No Produksi</label>
                        <input type="text" id="no_produksi" name="no_produksi" class="form-control" required list="recentNoProduksiList" value="{{ old('no_produksi') }}" placeholder="Contoh: S.0000033902">
                        <datalist id="recentNoProduksiList">
                            @foreach (($recentNoProduksi ?? []) as $item)
                                <option value="{{ $item['no_produksi'] }}">{{ $item['tanggal'] }} | Shift {{ $item['shift'] }}</option>
                            @endforeach
                        </datalist>
                    </div>
                    <div class="col-12">
                        <div class="d-flex gap-2 flex-wrap">
                            <button type="submit" class="btn btn-primary">Generate & Download PDF</button>
                            <button type="submit" class="btn btn-outline-primary" name="preview_pdf" value="1" formtarget="_blank">Preview PDF</button>
                            <button type="button" id="previewJsonBtn" class="btn btn-outline-secondary">Preview Raw SP (JSON)</button>
                        </div>
                    </div>
                </form>

                @if (!empty($recentNoProduksi))
                    <div class="mt-4">
                        <h2 class="h6 mb-2">No Produksi Terbaru</h2>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered align-middle mb-0 bg-white">
                                <thead class="table-light">
                                    <tr>
                                        <th>No Produksi</th>
                                        <th>Tanggal</th>
                                        <th>Shift</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($recentNoProduksi as $item)
                                        <tr>
                                            <td>{{ $item['no_produksi'] }}</td>
                                            <td>{{ $item['tanggal'] }}</td>
                                            <td>{{ $item['shift'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                <div id="previewJsonWrapper" class="mt-4 d-none">
                    <h2 class="h6 mb-2">Preview Raw SP (JSON)</h2>
                    <pre id="previewJsonOutput" class="bg-white border rounded p-3 mb-0" style="max-height: 360px; overflow: auto;"></pre>
                </div>
            </div>
        </div>
    </main>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const previewButton = document.getElementById('previewJsonBtn');
            const previewWrapper = document.getElementById('previewJsonWrapper');
            const previewOutput = document.getElementById('previewJsonOutput');
            const noProduksiInput = document.getElementById('no_produksi');

            if (!previewButton || !previewWrapper || !previewOutput || !noProduksiInput) {
                return;
            }

            previewButton.addEventListener('click', async function () {
                previewWrapper.classList.remove('d-none');
                previewOutput.textContent = 'Loading...';

                try {
                    const response = await fetch('{{ route('reports.pps.inject.inject-produksi.preview') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ no_produksi: noProduksiInput.value })
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
