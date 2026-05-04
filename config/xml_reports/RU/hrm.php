<?php

/**
 * Konfigurasi Sub-Report XML — Company: RU | Module: HRM
 *
 * Diakses via: config('xml_reports.RU.hrm')
 *
 * File XML sumber diletakkan di:
 *   storage/app/xml_sources/RU/hrm/AnlReports.HRM.EmployeeList.xml
 *
 * Cara menambah sub-report baru:
 *   1. Tambahkan entry baru di dalam 'sub_reports' di bawah ini
 *   2. Tentukan 'columns' — key adalah nama field di XML, value adalah label header PDF
 *   3. Tentukan 'filter' untuk menyaring baris, atau set null untuk tampilkan semua
 *
 * Nama field XML menggunakan encoding Microsoft DataSet:
 *   spasi → _x0020_   |   / → _x002F_   |   # → _x0023_   |   . → _x002E_
 */

return [

    'label'      => 'HRM — Human Resource Management',
    'xml_source' => 'RU/hrm/AnlReports.HRM.EmployeeList.xml',
    'record_tag' => 'Employees',

    'sub_reports' => [

        // -----------------------------------------------------------------
        // Daftar Karyawan (ringkas)
        // -----------------------------------------------------------------
        'employee_list' => [
            'label'   => 'List Karyawan RU',
            'columns' => [
                'Employee_x0020_Code'     => 'Kode Karyawan',
                'Full_x0020_Name'         => 'Nama Lengkap',
                'Nick_x0020_Name'         => 'Nama Panggilan',
                'Sex'                     => 'JK',
                'Department_x0020_Name'   => 'Departemen',
                'Job_x0020_Title'         => 'Jabatan',
                'Join_x0020_Date'         => 'Tgl Masuk',
                'Job_x0020_Status'        => 'Status Kerja',
                'Active'                  => 'Status Aktif',
            ],
            'filter' => ['Active' => 'Active'],
        ],

        // -----------------------------------------------------------------
        // Biodata Karyawan
        // -----------------------------------------------------------------
        'employee_biodata' => [
            'label'   => 'Biodata Karyawan',
            'columns' => [
                'Employee_x0020_Code'          => 'Kode',
                'Full_x0020_Name'              => 'Nama Lengkap',
                'Sex'                          => 'JK',
                'Birth_x0020_Place'            => 'Tempat Lahir',
                'Birth_x0020_Date'             => 'Tgl Lahir',
                'Age'                          => 'Usia',
                'IdentityNo'                   => 'No. KTP',
                'NPWP'                         => 'NPWP',
                'Religion'                     => 'Agama',
                'Marital_x0020_Status'         => 'Status Nikah',
                'Mobile_x0020_Phone'           => 'No. HP',
                'Address_x0020_Street_x0020_1' => 'Alamat',
                'Address_x0020_City'           => 'Kota',
            ],
            'filter' => ['Active' => 'Active'],
        ],

        // -----------------------------------------------------------------
        // Data Penempatan Karyawan
        // -----------------------------------------------------------------
        'employee_placement' => [
            'label'   => 'Data Penempatan Karyawan',
            'columns' => [
                'Employee_x0020_Code'       => 'Kode',
                'Full_x0020_Name'           => 'Nama Lengkap',
                'Department_x0020_Code'     => 'Kode Dept.',
                'Department_x0020_Name'     => 'Departemen',
                'Sub-Department_x0020_Name' => 'Sub-Departemen',
                'Job_x0020_Title'           => 'Jabatan',
                'Level_x0020_Name'          => 'Level',
                'Office_x002F_Location'     => 'Lokasi',
                'Workgroup'                 => 'Workgroup',
                'Join_x0020_Date'           => 'Tgl Masuk',
                'Working_x0020_Years'       => 'Masa Kerja (Thn)',
                'Working_x0020_Months'      => 'Masa Kerja (Bln)',
                'Job_x0020_Status'          => 'Status Kerja',
            ],
            'filter' => ['Active' => 'Active'],
        ],

        // -----------------------------------------------------------------
        // Pendidikan Terakhir Karyawan
        // -----------------------------------------------------------------
        'employee_education' => [
            'label'   => 'Pendidikan Terakhir Karyawan',
            'columns' => [
                'Employee_x0020_Code'                            => 'Kode',
                'Full_x0020_Name'                                => 'Nama Lengkap',
                'Department_x0020_Name'                          => 'Departemen',
                'Last_x0020_Academic_x0020_Level'                => 'Jenjang',
                'Last_x0020_Education_x0020_School_x0020_Name'   => 'Nama Sekolah/PT',
                'Last_x0020_Education_x0020_Major'               => 'Jurusan',
                'Last_x0020_Education_x0020_Degree'              => 'Gelar',
                'Last_x0020_Education_x0020_Graduate_x0020_Year' => 'Thn Lulus',
            ],
            'filter' => ['Active' => 'Active'],
        ],

        // -----------------------------------------------------------------
        // Data Bank Karyawan
        // -----------------------------------------------------------------
        'employee_bank' => [
            'label'   => 'Data Bank Karyawan',
            'columns' => [
                'Employee_x0020_Code'                     => 'Kode',
                'Full_x0020_Name'                         => 'Nama Lengkap',
                'Department_x0020_Name'                   => 'Departemen',
                'Bank_x0020_1'                            => 'Bank',
                'Bank_x0020_Branch_x0020_1'               => 'Cabang',
                'Bank_x0020_Account_x0020_No._x0020_1'    => 'No. Rekening',
                'Bank_x0020_Account_x0020_Owner_x0020_1'  => 'Atas Nama',
                'Salary_x0020_Payment'                    => 'Metode Bayar',
                'Salary_x0020_Payment_x0020_Interval'     => 'Interval',
            ],
            'filter' => ['Active' => 'Active'],
        ],

        // -----------------------------------------------------------------
        // Semua Karyawan (Aktif + Non-Aktif)
        // -----------------------------------------------------------------
        'employee_all' => [
            'label'   => 'Daftar Semua Karyawan',
            'columns' => [
                'Employee_x0020_Code'    => 'Kode Karyawan',
                'Full_x0020_Name'        => 'Nama Lengkap',
                'Department_x0020_Name'  => 'Departemen',
                'Job_x0020_Title'        => 'Jabatan',
                'Join_x0020_Date'        => 'Tgl Masuk',
                'Termination_x0020_Date' => 'Tgl Keluar',
                'Job_x0020_Status'       => 'Status Kerja',
                'Active'                 => 'Status Aktif',
            ],
            'filter' => null,
        ],

    ],
];
