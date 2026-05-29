<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ascend XML Test</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-light">
    @php
        $reportModules = [
            'hrm_analysis_reports' => [
                'label' => 'HRM Analysis Reports',
                'reports' => [
                    'list_karyawan' => 'List Karyawan RU',
                    'karyawan_per_masa_kerja' => 'Laporan Karyawan Per Masa Kerja (RU)',
                    'data_karyawan_status_kerja' => 'Laporan Data Karyawan (RU) - Status Kerja',
                    'daftar_karyawan_berdasarkan_abjad' => 'Laporan Daftar Karyawan (RU) - Berdasarkan Abjad',
                    'daftar_karyawan' => 'Laporan Daftar Karyawan (RU)',
                    'karyawan_aktif_per_departemen' => 'Laporan Karyawan Aktif Per Departemen (RU)',
                    'karyawan_per_agama' => 'Laporan Karyawan Per Agama (RU)',
                    'karyawan_per_etnis' => 'Laporan Karyawan Per Etnis (RU)',
                    'karyawan_per_level' => 'Laporan Karyawan Per Level (RU)',
                    'karyawan_per_umur' => 'Laporan Karyawan Per Umur (RU)',
                    'karyawan_per_departemen_per_jabatan' => 'Laporan Karyawan Per Departemen Per Jabatan (RU)',
                ],
            ],
            'sales' => [
                'label' => 'Sales',
                'reports' => [
                    'sales_invoice' => 'Sales Invoice (RU)',
                ],
            ],
        ];
        $selectedModule = old('report_module', 'hrm_analysis_reports');
        $selectedReportType = old('report_type', 'list_karyawan');
    @endphp

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-7 col-xl-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4 p-md-5">
                        <div class="mb-4">
                            <h1 class="h4 fw-bold mb-2">Ascend XML Test</h1>
                            <p class="text-secondary mb-0">Upload file XML Employee List dari Ascend untuk preview PDF.
                            </p>
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

                        <form action="{{ route('ascend-test.pdf') }}" method="POST" enctype="multipart/form-data"
                            target="_blank">
                            @csrf

                            <div class="mb-4">
                                <label for="report_module" class="form-label fw-semibold">Nama Modules</label>
                                <select class="form-select" id="report_module" name="report_module" required>
                                    @foreach ($reportModules as $moduleKey => $module)
                                        <option value="{{ $moduleKey }}" @selected($selectedModule === $moduleKey)>
                                            {{ $module['label'] }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-4">
                                <label for="report_type" class="form-label fw-semibold" id="report_type_label">
                                    {{ $reportModules[$selectedModule]['label'] ?? 'Laporan' }}
                                </label>
                                <select class="form-select @error('report_type') is-invalid @enderror" id="report_type"
                                    name="report_type" required>
                                    @foreach ($reportModules as $moduleKey => $module)
                                        @foreach ($module['reports'] as $reportType => $reportLabel)
                                            <option value="{{ $reportType }}" data-module="{{ $moduleKey }}"
                                                @selected($selectedReportType === $reportType)>
                                                {{ $reportLabel }}
                                            </option>
                                        @endforeach
                                    @endforeach
                                </select>
                                @error('report_type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-4">
                                <label for="xml_file" class="form-label fw-semibold">File XML</label>
                                <input type="file" class="form-control @error('xml_file') is-invalid @enderror"
                                    id="xml_file" name="xml_file" accept=".xml,text/xml,application/xml" required>
                                <div class="form-text">Maksimal 20 MB.</div>
                            </div>

                            <div class="d-flex justify-content-end gap-2">
                                <button type="reset" class="btn btn-outline-secondary">Reset</button>
                                <button type="submit" class="btn btn-primary">Preview PDF</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        (() => {
            const modules = @json($reportModules);
            const moduleSelect = document.getElementById('report_module');
            const reportSelect = document.getElementById('report_type');
            const reportLabel = document.getElementById('report_type_label');
            const allReportOptions = Array.from(reportSelect.options).map((option) => option.cloneNode(true));

            const refreshReportOptions = () => {
                const selectedModule = moduleSelect.value;
                const currentReport = reportSelect.value;
                const moduleReports = allReportOptions.filter((option) => option.dataset.module === selectedModule);

                reportSelect.replaceChildren(...moduleReports.map((option) => option.cloneNode(true)));
                reportLabel.textContent = modules[selectedModule]?.label || 'Laporan';

                const hasCurrentReport = Array.from(reportSelect.options).some((option) => option.value === currentReport);
                if (hasCurrentReport) {
                    reportSelect.value = currentReport;
                }
            };

            moduleSelect.addEventListener('change', refreshReportOptions);
            refreshReportOptions();
        })();
    </script>
</body>

</html>