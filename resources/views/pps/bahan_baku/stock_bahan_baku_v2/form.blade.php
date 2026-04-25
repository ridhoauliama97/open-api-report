@php
    $warehouses = [
        'ALL',
        'BAHAN BAKU',
        'BARANG BS',
        'BARANG JADI',
        'BAZAR',
        'BROKER',
        'BS',
        'BS ENAMEL',
        'CACAT INJECT',
        'CBD MDN',
        'CRUSHER',
        'CWD JKT',
        'CWD PLB',
        'DISPLAY TOKO',
        'GILINGAN',
        'GUDANG ONLINE',
        'HOT STAMPING',
        'INJECT',
        'INJECT RETUR',
        'MIXER',
        'PACKING',
        'PACKING KURSI & MEJA',
        'PACKING LEMARI',
        'PART LEMARI',
        'PASANG KUNCI',
        'QC',
        'RETUR',
        'SPANNER',
        'SPAREPART',
        'WASHING',
    ];
@endphp

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Generate Laporan Stok Bahan Baku</title>
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
                <h1 class="h3 mb-3">Generate Laporan Stok Bahan Baku (PPS)</h1>
                <p class="text-secondary mb-4">
                    Sumber data: stored procedure <code>SP_LapStokBahanBakuV2</code>.
                    Parameter mengikuti SP: <code>@TglAkhir</code> dan <code>@Warehouse</code>.
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

                <form method="POST" action="{{ route('reports.pps.bahan-baku.stock-bahan-baku-v2.download') }}"
                    class="row g-3">
                    @csrf
                    <div class="col-md-6">
                        <label for="TglAkhir" class="form-label">Tanggal</label>
                        <input type="date" id="TglAkhir" name="TglAkhir" class="form-control" required
                            value="{{ old('TglAkhir', old('end_date', now()->format('Y-m-d'))) }}">
                    </div>

                    <div class="col-md-6">
                        <label for="Warehouse" class="form-label">Gudang</label>
                        <select name="Warehouse" id="Warehouse" class="form-control" required>
                            @foreach ($warehouses as $warehouse)
                                <option value="{{ $warehouse }}"
                                    {{ old('Warehouse', old('warehouse', 'ALL')) === $warehouse ? 'selected' : '' }}>
                                    {{ $warehouse }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12">
                        <div class="d-flex gap-2 flex-wrap">
                            <button type="submit" class="btn btn-primary">Generate & Download PDF</button>
                            <button type="submit" class="btn btn-outline-primary" name="preview_pdf" value="1"
                                formtarget="_blank">Preview PDF</button>
                            <button type="button" id="previewJsonBtn" class="btn btn-outline-secondary">Preview Raw SP
                                (JSON)</button>
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
            const endDateInput = document.getElementById('TglAkhir');
            const warehouseInput = document.getElementById('Warehouse');

            if (!previewButton || !previewWrapper || !previewOutput || !endDateInput || !warehouseInput) {
                return;
            }

            previewButton.addEventListener('click', async function() {
                previewWrapper.classList.remove('d-none');
                previewOutput.textContent = 'Loading...';

                try {
                    const response = await fetch(
                        '{{ route('reports.pps.bahan-baku.stock-bahan-baku-v2.preview') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            },
                            body: JSON.stringify({
                                TglAkhir: endDateInput.value,
                                Warehouse: warehouseInput.value,
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
