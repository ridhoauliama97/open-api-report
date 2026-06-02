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
        $companyReports = [
            'RU' => [
                'label' => 'RU',
                'modules' => [
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
                            'perbandingan_jumlah_karyawan_tahunan_per_bulan' => 'Laporan Perbandingan Jumlah Karyawan Tahunan Per Bulan (RU)',
                        ],
                    ],
                    'sales' => [
                        'label' => 'Sales Analysis Reports',
                        'reports' => [
                            'sales_invoice_panjang' => 'Sales Invoice (RU) - Panjang',
                            'sales_invoice_normal' => 'Sales Invoice (RU) - Normal',
                            'surat_jalan_panjang' => 'Surat Jalan (RU) - Panjang',
                            'surat_jalan_normal' => 'Surat Jalan (RU) - Normal',
                        ],
                    ],
                ],
            ],
            'GSU' => [
                'label' => 'GSU',
                'modules' => [
                    'hrm_analysis_reports' => [
                        'label' => 'HRM Analysis Reports',
                        'reports' => [
                            'gsu_list_karyawan' => 'List Karyawan (GSU)',
                            'perbandingan_jumlah_karyawan_tahunan_per_bulan' => 'Laporan Perbandingan Jumlah Karyawan Tahunan Per Bulan (GSU)',
                        ],
                    ],
                    'sales' => [
                        'label' => 'Sales Analysis Reports',
                        'reports' => [
                            'gsu_sales_invoice_panjang' => 'Sales Invoices (GSU) - Panjang',
                            'gsu_sales_invoice_normal' => 'Sales Invoices (GSU) - Normal',
                            'gsu_surat_jalan_panjang' => 'Surat Jalan (GSU) - Panjang',
                            'gsu_surat_jalan_normal' => 'Surat Jalan (GSU) - Normal',
                        ],
                    ],
                ],
            ],
            'UC' => [
                'label' => 'UC',
                'modules' => [
                    'hrm_analysis_reports' => [
                        'label' => 'HRM Analysis Reports',
                        'reports' => [
                            'uc_list_karyawan' => 'List Karyawan (UC)',
                            'uc_karyawan_aktif_per_departemen' => 'Laporan Karyawan Aktif Per Departemen (UC)',
                            'uc_daftar_karyawan' => 'Laporan Daftar Karyawan (UC)',
                            'uc_daftar_karyawan_berdasarkan_abjad' => 'Laporan Daftar Karyawan (UC) - Berdasarkan Abjad',
                            'uc_data_karyawan_status_kerja' => 'Laporan Data Karyawan (UC) - Status Kerja',
                            'uc_karyawan_masuk_per_departemen_per_tanggal_masuk' => 'Laporan Karyawan Masuk Per Departemen Per Tanggal Masuk (UC)',
                            'perbandingan_jumlah_karyawan_tahunan_per_bulan' => 'Laporan Perbandingan Jumlah Karyawan Tahunan Per Bulan (UC)',
                        ],
                    ],
                ],
            ],
        ];
        $selectedCompany = strtoupper((string) old('company', 'RU'));
        $selectedCompany = array_key_exists($selectedCompany, $companyReports) ? $selectedCompany : 'RU';
        $selectedModule = old('report_module', 'hrm_analysis_reports');
        $selectedReportType = old('report_type', 'list_karyawan');
        $selectedCompanyModules = $companyReports[$selectedCompany]['modules'] ?? [];
        if (!array_key_exists($selectedModule, $selectedCompanyModules)) {
            $selectedModule = array_key_first($selectedCompanyModules) ?? '';
        }
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
                                <label for="company" class="form-label fw-semibold">Perusahaan</label>
                                <select class="form-select @error('company') is-invalid @enderror" id="company"
                                    name="company" required>
                                    @foreach ($companyReports as $companyKey => $company)
                                        <option value="{{ $companyKey }}" @selected($selectedCompany === $companyKey)>
                                            {{ $company['label'] }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('company')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-4">
                                <label for="report_module" class="form-label fw-semibold">Nama Modules</label>
                                <select class="form-select" id="report_module" name="report_module" required>
                                    @foreach ($selectedCompanyModules as $moduleKey => $module)
                                        <option value="{{ $moduleKey }}" @selected($selectedModule === $moduleKey)>
                                            {{ $module['label'] }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-4">
                                <label for="report_type" class="form-label fw-semibold" id="report_type_label">
                                    {{ $selectedCompanyModules[$selectedModule]['label'] ?? 'Laporan' }}
                                </label>
                                <select class="form-select @error('report_type') is-invalid @enderror" id="report_type"
                                    name="report_type" required>
                                    @foreach ($selectedCompanyModules as $moduleKey => $module)
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

                            <div class="alert alert-info d-none" id="empty_report_message">
                                Belum ada laporan yang dikonfigurasi untuk perusahaan ini.
                            </div>

                            <div class="mb-4">
                                <label for="xml_file" class="form-label fw-semibold">File XML</label>
                                <input type="file" class="form-control @error('xml_file') is-invalid @enderror"
                                    id="xml_file" name="xml_file" accept=".xml,text/xml,application/xml" required>
                                <div class="form-text">Maksimal 20 MB.</div>
                            </div>

                            <div class="d-flex justify-content-end gap-2">
                                <button type="reset" class="btn btn-outline-secondary">Reset</button>
                                <button type="submit" class="btn btn-primary" id="submit_button">Preview PDF</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        (() => {
            const companyReports = @json($companyReports);
            const companySelect = document.getElementById('company');
            const moduleSelect = document.getElementById('report_module');
            const reportSelect = document.getElementById('report_type');
            const reportLabel = document.getElementById('report_type_label');
            const emptyMessage = document.getElementById('empty_report_message');
            const submitButton = document.getElementById('submit_button');

            const option = (value, label) => {
                const option = document.createElement('option');
                option.value = value;
                option.textContent = label;

                return option;
            };

            const refreshModuleOptions = () => {
                const selectedCompany = companySelect.value;
                const currentModule = moduleSelect.value;
                const modules = companyReports[selectedCompany]?.modules || {};
                const moduleEntries = Object.entries(modules);

                moduleSelect.replaceChildren(...moduleEntries.map(([moduleKey, module]) => option(moduleKey, module.label)));

                const hasCurrentModule = moduleEntries.some(([moduleKey]) => moduleKey === currentModule);
                if (hasCurrentModule) {
                    moduleSelect.value = currentModule;
                }

                refreshReportOptions();
            };

            const refreshReportOptions = () => {
                const selectedCompany = companySelect.value;
                const selectedModule = moduleSelect.value;
                const currentReport = reportSelect.value;
                const modules = companyReports[selectedCompany]?.modules || {};
                const reports = modules[selectedModule]?.reports || {};
                const reportEntries = Object.entries(reports);

                reportSelect.replaceChildren(...reportEntries.map(([reportType, reportLabel]) => option(reportType, reportLabel)));
                reportLabel.textContent = modules[selectedModule]?.label || 'Laporan';

                const hasCurrentReport = reportEntries.some(([reportType]) => reportType === currentReport);
                if (hasCurrentReport) {
                    reportSelect.value = currentReport;
                }

                const hasReports = reportEntries.length > 0;
                const hasModules = Object.keys(modules).length > 0;
                moduleSelect.disabled = !hasModules;
                reportSelect.disabled = !hasReports;
                submitButton.disabled = !hasReports;
                emptyMessage.classList.toggle('d-none', hasReports);
            };

            companySelect.addEventListener('change', refreshModuleOptions);
            moduleSelect.addEventListener('change', refreshReportOptions);
            refreshModuleOptions();
        })();
    </script>
</body>

</html>
