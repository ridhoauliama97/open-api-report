<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Generate Laporan ST Hidup Kering</title>
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
                <h1 class="h3 mb-3">Generate Laporan ST Hidup Kering</h1>
                <p class="text-secondary mb-4">
                    Laporan ini mengambil data dari stored procedure <code>SP_LapSTHidupKering</code>.
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

                <form method="POST" action="{{ route('reports.sawn-timber.st-hidup-kering.download') }}"
                    class="row g-3">
                    @csrf
                    <div class="col-md-6">
                        <label for="hari" class="form-label">Hari (>=)</label>
                        <input type="number" id="hari" name="hari" class="form-control" min="0"
                            value="{{ old('hari', old('Hari', 90)) }}" required>
                    </div>
                    <div class="col-md-6">
                        <label for="mode" class="form-label">Mode</label>
                        @php $oldMode = strtoupper((string) old('mode', old('Mode', 'INCLUDE'))); @endphp
                        <select id="mode" name="mode" class="form-select" required>
                            <option value="INCLUDE" {{ $oldMode === 'INCLUDE' ? 'selected' : '' }}>INCLUDE</option>
                            <option value="EXCLUDE" {{ $oldMode === 'EXCLUDE' ? 'selected' : '' }}>EXCLUDE</option>
                        </select>
                        <div class="form-text">INCLUDE: semua data. EXCLUDE: data yang tidak ada di BongkarSusunOutputST.</div>
                    </div>

                    <div class="col-12">
                        <div class="d-flex gap-2 flex-wrap">
                            <button type="submit" class="btn btn-primary">Generate & Download PDF</button>
                            <button type="submit" class="btn btn-outline-primary"
                                formaction="{{ route('reports.sawn-timber.st-hidup-kering.preview-pdf') }}"
                                formtarget="_blank">Preview PDF</button>
                            <button type="button" id="previewJsonBtn" class="btn btn-outline-secondary">Preview (JSON)
                            </button>
                        </div>
                    </div>
                </form>

                <script>
                    document.getElementById('previewJsonBtn')?.addEventListener('click', async () => {
                        const hari = document.getElementById('hari')?.value;
                        const mode = document.getElementById('mode')?.value;

                        const response = await fetch(
                            '{{ route('reports.sawn-timber.st-hidup-kering.preview') }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                },
                                body: JSON.stringify({
                                    hari,
                                    mode,
                                }),
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
