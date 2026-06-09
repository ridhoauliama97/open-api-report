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
                            'list_karyawan_habis_kontrak' => 'Laporan List Karyawan Habis Kontrak (RU)',
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
                    'hrm_attendance_full' => [
                        'label' => 'HRM Attendance Full',
                        'reports' => [
                            'absensi_briefing_harian' => 'Laporan Absensi Briefing Harian (RU)',
                            'rekapitulasi_absensi_briefing_harian' => 'Laporan Rekapitulasi Absensi Briefing Harian (RU)',
                            'absensi_individu' => 'Laporan Absensi Individu (RU)',
                            'kehadiran_kru_stick' => 'Laporan Kehadiran Kru Stick (RU)',
                            'kehadiran_kru_racip' => 'Laporan Kehadiran Kru Racip Dorong Dan Kru Racip Sambut (RU)',
                            'persentase_kehadiran_mingguan_per_departemen' => 'Laporan Persentase Kehadiran Mingguan Per Departemen (RU)',
                            'persentase_kehadiran_bulanan' => 'Laporan Persentase Kehadiran Bulanan (RU)',
                            'pengabaian_keterlambatan_kehadiran_manual' => 'Laporan Pengabaian Keterlambatan & Kehadiran Manual (RU) Per Departemen',
                        ],
                    ],
                    'hrm_absence' => [
                        'label' => 'HRM Absence',
                        'reports' => [
                            'ketidakhadiran_bulanan' => 'Laporan Ketidakhadiran Bulanan (RU) - KK/KT',
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
                            'list_karyawan_habis_kontrak' => 'Laporan List Karyawan Habis Kontrak (GSU)',
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
                    'hrm_attendance_full' => [
                        'label' => 'HRM Attendance Full',
                        'reports' => [
                            'absensi_briefing_harian' => 'Laporan Absensi Briefing Harian (GSU)',
                            'rekapitulasi_absensi_briefing_harian' => 'Laporan Rekapitulasi Absensi Briefing Harian (GSU)',
                            'absensi_individu' => 'Laporan Absensi Individu (GSU)',
                            'kehadiran_kru_stick' => 'Laporan Kehadiran Kru Stick (GSU)',
                            'kehadiran_kru_racip' => 'Laporan Kehadiran Kru Racip Dorong Dan Kru Racip Sambut (GSU)',
                            'persentase_kehadiran_mingguan_per_departemen' => 'Laporan Persentase Kehadiran Mingguan Per Departemen (GSU)',
                            'persentase_kehadiran_bulanan' => 'Laporan Persentase Kehadiran Bulanan (GSU)',
                            'pengabaian_keterlambatan_kehadiran_manual' => 'Laporan Pengabaian Keterlambatan & Kehadiran Manual (GSU) Per Departemen',
                        ],
                    ],
                    'hrm_absence' => [
                        'label' => 'HRM Absence',
                        'reports' => [
                            'ketidakhadiran_bulanan' => 'Laporan Ketidakhadiran Bulanan (GSU) - KK/KT',
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
                            'list_karyawan_habis_kontrak' => 'Laporan List Karyawan Habis Kontrak (UC)',
                            'perbandingan_jumlah_karyawan_tahunan_per_bulan' => 'Laporan Perbandingan Jumlah Karyawan Tahunan Per Bulan (UC)',
                        ],
                    ],
                    'hrm_attendance_full' => [
                        'label' => 'HRM Attendance Full',
                        'reports' => [
                            'absensi_briefing_harian' => 'Laporan Absensi Briefing Harian (UC)',
                            'rekapitulasi_absensi_briefing_harian' => 'Laporan Rekapitulasi Absensi Briefing Harian (UC)',
                            'absensi_individu' => 'Laporan Absensi Individu (UC)',
                            'kehadiran_kru_stick' => 'Laporan Kehadiran Kru Stick (UC)',
                            'kehadiran_kru_racip' => 'Laporan Kehadiran Kru Racip Dorong Dan Kru Racip Sambut (UC)',
                            'persentase_kehadiran_mingguan_per_departemen' => 'Laporan Persentase Kehadiran Mingguan Per Departemen (UC)',
                            'persentase_kehadiran_bulanan' => 'Laporan Persentase Kehadiran Bulanan (UC)',
                            'pengabaian_keterlambatan_kehadiran_manual' => 'Laporan Pengabaian Keterlambatan & Kehadiran Manual (UC) Per Departemen',
                        ],
                    ],
                    'hrm_absence' => [
                        'label' => 'HRM Absence',
                        'reports' => [
                            'ketidakhadiran_bulanan' => 'Laporan Ketidakhadiran Bulanan (UC) - KK/KT',
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
                                <input type="hidden" id="DB_CompanyName" name="DB_CompanyName"
                                    value="{{ old('DB_CompanyName', $selectedCompany) }}">
                                <div class="form-text">Parameter Crystal: <code>DB_CompanyName</code>.</div>
                            </div>

                            <div class="mb-4">
                                <label for="Sys_Username" class="form-label fw-semibold">Sys Username</label>
                                <input type="text" class="form-control @error('Sys_Username') is-invalid @enderror"
                                    id="Sys_Username" name="Sys_Username" value="{{ old('Sys_Username') }}"
                                    placeholder="Nama user yang mencetak">
                                @error('Sys_Username')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Parameter Crystal: <code>Sys_Username</code>, dipakai untuk footer Dicetak oleh.</div>
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

                            <div class="mb-4" id="attendance_briefing_fields">
                                <label class="form-label fw-semibold">Parameter Attendance Full</label>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <input type="text" class="form-control @error('group') is-invalid @enderror"
                                            name="group" value="{{ old('group', 'VKD') }}" placeholder="Group/Divisi">
                                        @error('group')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small mb-1">Tanggal Awal</label>
                                        <input type="date"
                                            class="form-control @error('start_date') is-invalid @enderror"
                                            name="start_date" value="{{ old('start_date') }}">
                                        @error('start_date')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small mb-1">Tanggal Akhir</label>
                                        <input type="date"
                                            class="form-control @error('end_date') is-invalid @enderror"
                                            name="end_date" value="{{ old('end_date') }}">
                                        @error('end_date')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-6">
                                        <input type="text"
                                            class="form-control @error('penanggung_jawab') is-invalid @enderror"
                                            name="penanggung_jawab" value="{{ old('penanggung_jawab') }}"
                                            placeholder="Penanggung Jawab">
                                        @error('penanggung_jawab')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-6">
                                        <input type="text" class="form-control @error('tema') is-invalid @enderror"
                                            name="tema" value="{{ old('tema') }}" placeholder="Tema">
                                        @error('tema')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small mb-1">Kategori/Tipe</label>
                                        <input type="text"
                                            class="form-control @error('kategori') is-invalid @enderror"
                                            name="kategori" value="{{ old('kategori', 'ST') }}" placeholder="ST">
                                        @error('kategori')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small mb-1">Pilih Type</label>
                                        <input type="text"
                                            class="form-control @error('Pilih Type') is-invalid @enderror"
                                            name="Pilih Type" value="{{ old('Pilih Type', 'KK/KT') }}"
                                            placeholder="KK/KT atau Staff">
                                        @error('Pilih Type')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small mb-1">Pilih Status</label>
                                        <input type="text"
                                            class="form-control @error('Pilih Status') is-invalid @enderror"
                                            name="Pilih Status" value="{{ old('Pilih Status', 'KK/KT') }}"
                                            placeholder="KK/KT atau Staff">
                                        @error('Pilih Status')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="form-text">Gunakan tanggal awal dan tanggal akhir untuk periode Attendance Full. Field group dipakai untuk Absensi Briefing Harian. Field Pilih Type dipakai untuk Persentase Kehadiran Bulanan. Field Pilih Status dipakai untuk Pengabaian Keterlambatan & Kehadiran Manual.</div>
                            </div>

                            <div class="mb-4" id="contract_period_fields">
                                <label class="form-label fw-semibold">Periode Habis Kontrak</label>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <input type="number" min="1" max="12"
                                            class="form-control @error('month') is-invalid @enderror" name="month"
                                            value="{{ old('month', now()->format('n')) }}" placeholder="Bulan">
                                        @error('month')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-6">
                                        <input type="number" min="1900" max="2100"
                                            class="form-control @error('year') is-invalid @enderror" name="year"
                                            value="{{ old('year', now()->format('Y')) }}" placeholder="Tahun">
                                        @error('year')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="form-text">Digunakan untuk filter Expiry Date pada Laporan List Karyawan Habis Kontrak.</div>
                            </div>

                            <div class="mb-4" id="attendance_individu_fields">
                                <label class="form-label fw-semibold">Parameter Absensi Individu</label>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <input type="text"
                                            class="form-control @error('employee_code') is-invalid @enderror"
                                            name="employee_code" value="{{ old('employee_code') }}"
                                            placeholder="Kode Karyawan">
                                        @error('employee_code')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-6">
                                        <input type="text"
                                            class="form-control @error('employee_name') is-invalid @enderror"
                                            name="employee_name" value="{{ old('employee_name') }}"
                                            placeholder="Nama Karyawan">
                                        @error('employee_name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small mb-1">Tanggal Awal</label>
                                        <input type="date"
                                            class="form-control @error('start_date') is-invalid @enderror"
                                            name="start_date" value="{{ old('start_date') }}">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small mb-1">Tanggal Akhir</label>
                                        <input type="date"
                                            class="form-control @error('end_date') is-invalid @enderror"
                                            name="end_date" value="{{ old('end_date') }}">
                                    </div>
                                </div>
                                <div class="form-text">Opsional. Kosongkan untuk menampilkan seluruh karyawan.</div>
                            </div>

                            <div class="mb-4" id="absence_period_fields">
                                <label class="form-label fw-semibold">Parameter Absence</label>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <label class="form-label small mb-1">Tanggal Awal</label>
                                        <input type="date"
                                            class="form-control @error('start_date') is-invalid @enderror"
                                            name="start_date" value="{{ old('start_date') }}">
                                        @error('start_date')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small mb-1">Tanggal Akhir</label>
                                        <input type="date"
                                            class="form-control @error('end_date') is-invalid @enderror"
                                            name="end_date" value="{{ old('end_date') }}">
                                        @error('end_date')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label small mb-1">Tipe</label>
                                        <input type="text"
                                            class="form-control @error('tipe') is-invalid @enderror"
                                            name="tipe" value="{{ old('tipe', 'KK/KT') }}" placeholder="KK/KT">
                                        @error('tipe')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="form-text">Jika periode tidak diisi, sistem memakai tanggal yang tersedia di XML Absence.</div>
                            </div>

                            <div class="mb-4">
                                <label for="xml_file" class="form-label fw-semibold">File XML</label>
                                <input type="file" class="form-control @error('xml_file') is-invalid @enderror"
                                    id="xml_file" name="xml_file" accept=".xml,text/xml,application/xml" required>
                                <div class="form-text">Maksimal 200 MB.</div>
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
            const dbCompanyNameInput = document.getElementById('DB_CompanyName');
            const moduleSelect = document.getElementById('report_module');
            const reportSelect = document.getElementById('report_type');
            const reportLabel = document.getElementById('report_type_label');
            const emptyMessage = document.getElementById('empty_report_message');
            const submitButton = document.getElementById('submit_button');
            const contractPeriodFields = document.getElementById('contract_period_fields');
            const attendanceBriefingFields = document.getElementById('attendance_briefing_fields');
            const attendanceIndividuFields = document.getElementById('attendance_individu_fields');
            const absencePeriodFields = document.getElementById('absence_period_fields');

            const option = (value, label) => {
                const option = document.createElement('option');
                option.value = value;
                option.textContent = label;

                return option;
            };

            const toggleSection = (section, visible) => {
                section.classList.toggle('d-none', !visible);
                section.querySelectorAll('input, select, textarea').forEach((field) => {
                    field.disabled = !visible;
                });
            };

            const refreshModuleOptions = () => {
                const selectedCompany = companySelect.value;
                dbCompanyNameInput.value = selectedCompany;
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
                toggleSection(contractPeriodFields, reportSelect.value === 'list_karyawan_habis_kontrak');
                toggleSection(attendanceBriefingFields, ['absensi_briefing_harian', 'rekapitulasi_absensi_briefing_harian', 'kehadiran_kru_stick', 'kehadiran_kru_racip', 'persentase_kehadiran_mingguan_per_departemen', 'persentase_kehadiran_bulanan', 'pengabaian_keterlambatan_kehadiran_manual'].includes(reportSelect.value));
                toggleSection(attendanceIndividuFields, reportSelect.value === 'absensi_individu');
                toggleSection(absencePeriodFields, reportSelect.value === 'ketidakhadiran_bulanan');
            };

            companySelect.addEventListener('change', refreshModuleOptions);
            moduleSelect.addEventListener('change', refreshReportOptions);
            reportSelect.addEventListener('change', refreshReportOptions);
            refreshModuleOptions();
        })();
    </script>
</body>

</html>
