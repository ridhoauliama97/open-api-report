<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Generate Laporan Harian Hasil Packing Produksi</title>
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
                <h1 class="h3 mb-3">Generate Laporan Harian Hasil Packing Produksi (PPS)</h1>
                <p class="text-secondary mb-4">Sumber data utama: stored procedure <code>SP_LapHasilProduksiHarianPacking</code> pada database PPS.</p>

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('reports.pps.inject.packing.packing-produksi.download') }}" class="row g-3">
                    @csrf
                    <div class="col-md-7">
                        <label for="no_packing" class="form-label">No Packing</label>
                        <input type="text" id="no_packing" name="no_packing" class="form-control" required list="recentNoPackingList" value="{{ old('no_packing') }}" placeholder="Contoh: BD.0000000224">
                        <datalist id="recentNoPackingList">
                            @foreach (($recentNoPacking ?? []) as $item)
                                <option value="{{ $item['no_packing'] }}">{{ $item['tanggal'] }} | Shift {{ $item['shift'] }}</option>
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

                @if (!empty($recentNoPacking))
                    <div class="mt-4">
                        <h2 class="h6 mb-2">No Packing Terbaru</h2>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered align-middle mb-0 bg-white">
                                <thead class="table-light">
                                    <tr>
                                        <th>No Packing</th>
                                        <th>Tanggal</th>
                                        <th>Shift</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($recentNoPacking as $item)
                                        <tr>
                                            <td>{{ $item['no_packing'] }}</td>
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
            const noPackingInput = document.getElementById('no_packing');

            if (!previewButton || !previewWrapper || !previewOutput || !noPackingInput) {
                return;
            }

            previewButton.addEventListener('click', async function () {
                previewWrapper.classList.remove('d-none');
                previewOutput.textContent = 'Loading...';

                try {
                    const response = await fetch('{{ route('reports.pps.inject.packing.packing-produksi.preview') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ no_packing: noPackingInput.value })
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
