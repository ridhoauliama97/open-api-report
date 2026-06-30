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

    'label' => 'HRM — Human Resource Management',
    'xml_source' => 'RU/hrm/AnlReports.HRM.EmployeeList.xml',
    'record_tag' => 'Employees',

    'sub_reports' => [

        // -----------------------------------------------------------------
        // Daftar Karyawan (ringkas)
        // -----------------------------------------------------------------
        'employee_list' => [
            'label' => 'List Karyawan RU',
            'columns' => [
                'Full_x0020_Name' => 'Nama',
                'Sex' => 'Jenis Kelamin',
                'Age' => 'Usia',
                'Job_x0020_Title' => 'Jabatan',
                'Department_x0020_Name' => 'Departemen',
                'Working_x0020_Years' => 'Lama Bekerja Tahun',
                'Working_x0020_Months' => 'Lama Bekerja Bulan',
                'Employee_x0020_Remarks' => 'Keterangan',
                'Nama_x0020_Tempat_x0020_Ibadah' => 'Nama Tempat Ibadah',
                'Lemari' => 'Lemari',
                'Nama_x0020_User' => 'Nama User',
                'User_x0020_Name' => 'User Name',
                'Printed_x0020_By' => 'Printed By',
                'Created_x0020_By' => 'Created By',
            ],
            'filter' => ['Active' => 'Active'],
        ],

        // -----------------------------------------------------------------
        // Laporan Karyawan Per Masa Kerja
        // -----------------------------------------------------------------
        'karyawan_per_masa_kerja' => [
            'label' => 'Laporan Karyawan Per Masa Kerja (RU)',
            'columns' => [
                'Full_x0020_Name' => 'Nama',
                'Sex' => 'L/P',
                'Job_x0020_Title' => 'Jabatan',
                'Job_x0020_Status' => 'Status',
                'Daily_x0020_Worker_x0020_Type_x0020_Code' => 'Status Kode',
                'Salary_x0020_Security_x0020_Code' => 'Salary Security Code',
                'Level_x0020_Name' => 'Level',
                'Join_x0020_Date' => 'Tanggal Masuk',
                'Working_x0020_Years' => 'Masa Kerja Tahun',
                'Working_x0020_Months' => 'Masa Kerja Bulan',
                'Working_x0020_Days' => 'Masa Kerja Hari',
                'Nama_x0020_User' => 'Nama User',
                'User_x0020_Name' => 'User Name',
                'Printed_x0020_By' => 'Printed By',
                'Created_x0020_By' => 'Created By',
            ],
            'filter' => ['Active' => 'Active'],
        ],

        // -----------------------------------------------------------------
        // Laporan Data Karyawan Berdasarkan Status Kerja
        // -----------------------------------------------------------------
        'data_karyawan_status_kerja' => [
            'label' => "Laporan Data Karyawan (RU)\nStaff, Karyawan Tetap & Karyawan Kontrak\nBerdasarkan Status Kerja",
            'columns' => [
                'Employee_x0020_Code' => 'NIK',
                'Full_x0020_Name' => 'Nama',
                'Birth_x0020_Place' => 'Tempat',
                'Birth_x0020_Date' => 'Tgl Lahir',
                'Age' => 'Umur',
                'Last_x0020_Education_x0020_School_x0020_Name' => 'Pendidikan',
                'Last_x0020_Academic_x0020_Level' => 'Jenjang Pendidikan',
                'Job_x0020_Title' => 'Jabatan',
                'Level_x0020_Name' => 'Level',
                'Daily_x0020_Worker_x0020_Type_x0020_Code' => 'HK Kode',
                'Daily_x0020_Worker_x0020_Type_x0020_Name' => 'HK',
                'Salary_x0020_Security_x0020_Code' => 'Salary Security Code',
                'Active' => 'Status Aktif',
                'Nama_x0020_User' => 'Nama User',
                'User_x0020_Name' => 'User Name',
                'Printed_x0020_By' => 'Printed By',
                'Created_x0020_By' => 'Created By',
            ],
            'filter' => null,
        ],

        // -----------------------------------------------------------------
        // Laporan Daftar Karyawan Berdasarkan Abjad
        // -----------------------------------------------------------------
        'daftar_karyawan_berdasarkan_abjad' => [
            'label' => "Laporan Daftar Karyawan (RU)\nBerdasarkan Abjad",
            'columns' => [
                'Employee_x0020_Code' => 'No ID',
                'Full_x0020_Name' => 'Nama',
                'Job_x0020_Title' => 'Posisi',
                'Daily_x0020_Worker_x0020_Type_x0020_Code' => 'HK Kode',
                'Active' => 'Status Aktif',
                'Nama_x0020_User' => 'Nama User',
                'User_x0020_Name' => 'User Name',
                'Printed_x0020_By' => 'Printed By',
                'Created_x0020_By' => 'Created By',
            ],
            'filter' => null,
        ],

        // -----------------------------------------------------------------
        // Laporan Daftar Karyawan
        // -----------------------------------------------------------------
        'daftar_karyawan' => [
            'label' => 'Laporan Daftar Karyawan (RU)',
            'columns' => [
                'Full_x0020_Name' => 'Nama',
                'Job_x0020_Title' => 'Jabatan',
                'Daily_x0020_Worker_x0020_Type_x0020_Code' => 'Tp',
                'Level_x0020_Name' => 'Level',
                'Marital_x0020_Status' => 'Tgn',
                'Employee_x0020_Remarks' => 'Perusahaan Sebelumnya',
                'Last_x0020_Academic_x0020_Level' => 'LastEdu',
                'Join_x0020_Date' => 'Tgl Masuk',
                'Department_x0020_Name' => 'Department',
                'Sex' => 'L/P',
                'IdentityNo' => 'No Identitas',
                'Active' => 'Status Aktif',
                'Nama_x0020_User' => 'Nama User',
                'User_x0020_Name' => 'User Name',
                'Printed_x0020_By' => 'Printed By',
                'Created_x0020_By' => 'Created By',
            ],
            'filter' => null,
        ],

        // -----------------------------------------------------------------
        // Laporan Karyawan Aktif Per Departemen
        // -----------------------------------------------------------------
        'karyawan_aktif_per_departemen' => [
            'label' => 'Laporan Karyawan Aktif Per Departemen (RU)',
            'columns' => [
                'Employee_x0020_Code' => 'Kode Karyawan',
                'Full_x0020_Name' => 'Nama',
                'Daily_x0020_Worker_x0020_Type_x0020_Code' => 'Status',
                'Sex' => 'L/P',
                'IdentityNo' => 'No Identitas',
                'Job_x0020_Title' => 'Jabatan',
                'Level_x0020_Name' => 'Level',
                'Last_x0020_Academic_x0020_Level' => 'Strata Pend',
                'Join_x0020_Date' => 'Tanggal Masuk',
                'Department_x0020_Name' => 'Departemen',
                'Active' => 'Status Aktif',
                'Nama_x0020_User' => 'Nama User',
                'User_x0020_Name' => 'User Name',
                'Printed_x0020_By' => 'Printed By',
                'Created_x0020_By' => 'Created By',
            ],
            'filter' => null,
        ],

        // -----------------------------------------------------------------
        // Laporan Kehadiran KK/KT/ST
        // -----------------------------------------------------------------
        'kehadiran_kk_kt_st' => [
            'label' => 'Laporan Kehadiran KK/KT/ST',
            'columns' => [
                'Employee_x0020_Code' => 'Kode Karyawan',
                'Full_x0020_Name' => 'Nama',
                'Daily_x0020_Worker_x0020_Type_x0020_Code' => 'Status',
                'Department_x0020_Name' => 'Divisi',
                'Active' => 'Status Aktif',
                'Nama_x0020_User' => 'Nama User',
                'User_x0020_Name' => 'User Name',
                'Printed_x0020_By' => 'Printed By',
                'Created_x0020_By' => 'Created By',
            ],
            'filter' => null,
        ],

        // -----------------------------------------------------------------
        // Laporan List Karyawan Habis Kontrak
        // -----------------------------------------------------------------
        'list_karyawan_habis_kontrak' => [
            'label' => 'Laporan List Karyawan Habis Kontrak',
            'columns' => [
                'Employee_x0020_Code' => 'Code',
                'Full_x0020_Name' => 'Full Name',
                'Job_x0020_Title' => 'Job Title',
                'Department_x0020_Name' => 'Department',
                'Join_x0020_Date' => 'Join Date',
                'Expiry_x0020_Date' => 'Expiry Date',
                'Days_x0020_to_x0020_Expiry' => 'Days to Expiry',
                'Daily_x0020_Worker_x0020_Type_x0020_Code' => 'Status',
                'Active' => 'Active',
                'Nama_x0020_User' => 'Nama User',
                'User_x0020_Name' => 'User Name',
                'Printed_x0020_By' => 'Printed By',
                'Created_x0020_By' => 'Created By',
            ],
            'filter' => null,
        ],

        // -----------------------------------------------------------------
        // Laporan Karyawan Per Agama
        // -----------------------------------------------------------------
        'karyawan_per_agama' => [
            'label' => 'Laporan Karyawan Per Agama (RU)',
            'columns' => [
                'Employee_x0020_Code' => 'Kode Karyawan',
                'Full_x0020_Name' => 'Nama',
                'Sex' => 'L/P',
                'IdentityNo' => 'No Identitas',
                'Job_x0020_Title' => 'Jabatan',
                'Age' => 'Umur',
                'THR' => 'THR',
                'Religion' => 'Agama',
                'Active' => 'Status Aktif',
                'Nama_x0020_User' => 'Nama User',
                'User_x0020_Name' => 'User Name',
                'Printed_x0020_By' => 'Printed By',
                'Created_x0020_By' => 'Created By',
            ],
            'filter' => null,
        ],

        // -----------------------------------------------------------------
        // Laporan Karyawan Per Etnis
        // -----------------------------------------------------------------
        'karyawan_per_etnis' => [
            'label' => 'Laporan Karyawan Per Etnis (RU)',
            'columns' => [
                'Employee_x0020_Code' => 'NIK',
                'Full_x0020_Name' => 'Nama',
                'Sex' => 'L/P',
                'IdentityNo' => 'No Identitas',
                'Job_x0020_Title' => 'Jabatan',
                'Age' => 'Umur',
                'Religion' => 'Agama',
                'Race' => 'Etnis',
                'Active' => 'Status Aktif',
                'Nama_x0020_User' => 'Nama User',
                'User_x0020_Name' => 'User Name',
                'Printed_x0020_By' => 'Printed By',
                'Created_x0020_By' => 'Created By',
            ],
            'filter' => null,
        ],

        // -----------------------------------------------------------------
        // Laporan Karyawan Per Level
        // -----------------------------------------------------------------
        'karyawan_per_level' => [
            'label' => 'Laporan Karyawan Per Level (RU)',
            'columns' => [
                'Employee_x0020_Code' => 'Kode Karyawan',
                'Full_x0020_Name' => 'Nama',
                'Sex' => 'L/P',
                'IdentityNo' => 'No Identitas',
                'Job_x0020_Title' => 'Jabatan',
                'Daily_x0020_Worker_x0020_Type_x0020_Code' => 'Status',
                'Join_x0020_Date' => 'Tanggal Masuk',
                'Level_x0020_Name' => 'Level',
                'Active' => 'Status Aktif',
                'Nama_x0020_User' => 'Nama User',
                'User_x0020_Name' => 'User Name',
                'Printed_x0020_By' => 'Printed By',
                'Created_x0020_By' => 'Created By',
            ],
            'filter' => null,
        ],

        // -----------------------------------------------------------------
        // Laporan Karyawan Per Umur
        // -----------------------------------------------------------------
        'karyawan_per_umur' => [
            'label' => 'Laporan Karyawan Per Umur (RU)',
            'columns' => [
                'Employee_x0020_Code' => 'Kode Karyawan',
                'Full_x0020_Name' => 'Nama',
                'Sex' => 'L/P',
                'IdentityNo' => 'No Identitas',
                'Job_x0020_Title' => 'Jabatan',
                'Daily_x0020_Worker_x0020_Type_x0020_Code' => 'Status',
                'Age' => 'Umur',
                'Working_x0020_Years' => 'Masa Kerja Tahun',
                'Working_x0020_Months' => 'Masa Kerja Bulan',
                'Working_x0020_Days' => 'Masa Kerja Hari',
                'Join_x0020_Date' => 'Tanggal Masuk',
                'Level_x0020_Name' => 'Level',
                'Active' => 'Status Aktif',
                'Nama_x0020_User' => 'Nama User',
                'User_x0020_Name' => 'User Name',
                'Printed_x0020_By' => 'Printed By',
                'Created_x0020_By' => 'Created By',
            ],
            'filter' => null,
        ],

        // -----------------------------------------------------------------
        // Laporan Karyawan Per Departemen Per Jabatan
        // -----------------------------------------------------------------
        'karyawan_per_departemen_per_jabatan' => [
            'label' => 'Laporan Karyawan Per Departemen Per Jabatan (RU)',
            'columns' => [
                'Employee_x0020_Code' => 'Kode Karyawan',
                'Full_x0020_Name' => 'Nama',
                'Sex' => 'L/P',
                'IdentityNo' => 'No Identitas',
                'Job_x0020_Title' => 'Jabatan',
                'Daily_x0020_Worker_x0020_Type_x0020_Code' => 'Tipe',
                'Level_x0020_Name' => 'Level',
                'Last_x0020_Academic_x0020_Level' => 'Pendidikan Terakhir',
                'Join_x0020_Date' => 'Tanggal Masuk',
                'Workgroup' => 'Kelompok Kerja',
                'Department_x0020_Name' => 'Departemen',
                'Active' => 'Status Aktif',
                'Nama_x0020_User' => 'Nama User',
                'User_x0020_Name' => 'User Name',
                'Printed_x0020_By' => 'Printed By',
                'Created_x0020_By' => 'Created By',
            ],
            'filter' => null,
        ],

        // -----------------------------------------------------------------
        // Laporan Karyawan Masuk Per Departemen Per Tanggal Masuk
        // -----------------------------------------------------------------
        'karyawan_masuk_per_departemen_per_tanggal_masuk' => [
            'label' => 'Laporan Karyawan Masuk Per Departemen Per Tanggal Masuk (RU)',
            'columns' => [
                'Employee_x0020_Code' => 'Kode Karyawan',
                'Full_x0020_Name' => 'Nama',
                'Sex' => 'L/P',
                'IdentityNo' => 'No Identitas',
                'Job_x0020_Title' => 'Jabatan',
                'Daily_x0020_Worker_x0020_Type_x0020_Code' => 'Status',
                'Level_x0020_Name' => 'Level',
                'Last_x0020_Academic_x0020_Level' => 'Pendidikan Terakhir',
                'Join_x0020_Date' => 'Tanggal Masuk',
                'Department_x0020_Name' => 'Departemen',
                'Active' => 'Status Aktif',
                'Nama_x0020_User' => 'Nama User',
                'User_x0020_Name' => 'User Name',
                'Printed_x0020_By' => 'Printed By',
                'Created_x0020_By' => 'Created By',
            ],
            'filter' => null,
        ],

        // -----------------------------------------------------------------
        // Laporan Usia Generasi Berdasakan Tahun Kelahiran dan Masa Kerja
        // -----------------------------------------------------------------
        'usia_generasi_tahun_kelahiran_masa_kerja' => [
            'label' => 'Laporan Usia Generasi Berdasakan Tahun Kelahiran dan Masa Kerja',
            'columns' => [
                'Employee_x0020_Code' => 'Kode Karyawan',
                'Full_x0020_Name' => 'Nama',
                'Job_x0020_Title' => 'Jabatan',
                'Department_x0020_Name' => 'Departemen',
                'Age' => 'Usia',
                'Birth_x0020_Date' => 'Tanggal Lahir',
                'Birth_x0020_Date_x0020__x0028_Year_x0029_' => 'Tahun Lahir',
                'Working_x0020_Years' => 'Masa Kerja Tahun',
                'Working_x0020_Months' => 'Masa Kerja Bulan',
                'Working_x0020_Days' => 'Masa Kerja Hari',
                'Join_x0020_Date' => 'Tanggal Masuk',
                'Active' => 'Status Aktif',
                'Nama_x0020_User' => 'Nama User',
                'User_x0020_Name' => 'User Name',
                'Printed_x0020_By' => 'Printed By',
                'Created_x0020_By' => 'Created By',
            ],
            'filter' => null,
        ],

        // -----------------------------------------------------------------
        // Biodata Karyawan
        // -----------------------------------------------------------------
        'employee_biodata' => [
            'label' => 'Biodata Karyawan',
            'columns' => [
                'Employee_x0020_Code' => 'Kode',
                'Full_x0020_Name' => 'Nama Lengkap',
                'Sex' => 'JK',
                'Birth_x0020_Place' => 'Tempat Lahir',
                'Birth_x0020_Date' => 'Tgl Lahir',
                'Age' => 'Usia',
                'IdentityNo' => 'No. KTP',
                'NPWP' => 'NPWP',
                'Religion' => 'Agama',
                'Marital_x0020_Status' => 'Status Nikah',
                'Mobile_x0020_Phone' => 'No. HP',
                'Address_x0020_Street_x0020_1' => 'Alamat',
                'Address_x0020_City' => 'Kota',
            ],
            'filter' => ['Active' => 'Active'],
        ],

        // -----------------------------------------------------------------
        // Data Penempatan Karyawan
        // -----------------------------------------------------------------
        'employee_placement' => [
            'label' => 'Data Penempatan Karyawan',
            'columns' => [
                'Employee_x0020_Code' => 'Kode',
                'Full_x0020_Name' => 'Nama Lengkap',
                'Department_x0020_Code' => 'Kode Dept.',
                'Department_x0020_Name' => 'Departemen',
                'Sub-Department_x0020_Name' => 'Sub-Departemen',
                'Job_x0020_Title' => 'Jabatan',
                'Level_x0020_Name' => 'Level',
                'Office_x002F_Location' => 'Lokasi',
                'Workgroup' => 'Workgroup',
                'Join_x0020_Date' => 'Tgl Masuk',
                'Working_x0020_Years' => 'Masa Kerja (Thn)',
                'Working_x0020_Months' => 'Masa Kerja (Bln)',
                'Job_x0020_Status' => 'Status Kerja',
            ],
            'filter' => ['Active' => 'Active'],
        ],

        // -----------------------------------------------------------------
        // Pendidikan Terakhir Karyawan
        // -----------------------------------------------------------------
        'employee_education' => [
            'label' => 'Pendidikan Terakhir Karyawan',
            'columns' => [
                'Employee_x0020_Code' => 'Kode',
                'Full_x0020_Name' => 'Nama Lengkap',
                'Department_x0020_Name' => 'Departemen',
                'Last_x0020_Academic_x0020_Level' => 'Jenjang',
                'Last_x0020_Education_x0020_School_x0020_Name' => 'Nama Sekolah/PT',
                'Last_x0020_Education_x0020_Major' => 'Jurusan',
                'Last_x0020_Education_x0020_Degree' => 'Gelar',
                'Last_x0020_Education_x0020_Graduate_x0020_Year' => 'Thn Lulus',
            ],
            'filter' => ['Active' => 'Active'],
        ],

        // -----------------------------------------------------------------
        // Data Bank Karyawan
        // -----------------------------------------------------------------
        'employee_bank' => [
            'label' => 'Data Bank Karyawan',
            'columns' => [
                'Employee_x0020_Code' => 'Kode',
                'Full_x0020_Name' => 'Nama Lengkap',
                'Department_x0020_Name' => 'Departemen',
                'Bank_x0020_1' => 'Bank',
                'Bank_x0020_Branch_x0020_1' => 'Cabang',
                'Bank_x0020_Account_x0020_No._x0020_1' => 'No. Rekening',
                'Bank_x0020_Account_x0020_Owner_x0020_1' => 'Atas Nama',
                'Salary_x0020_Payment' => 'Metode Bayar',
                'Salary_x0020_Payment_x0020_Interval' => 'Interval',
            ],
            'filter' => ['Active' => 'Active'],
        ],

        // -----------------------------------------------------------------
        // Semua Karyawan (Aktif + Non-Aktif)
        // -----------------------------------------------------------------
        'employee_all' => [
            'label' => 'Daftar Semua Karyawan',
            'columns' => [
                'Employee_x0020_Code' => 'Kode Karyawan',
                'Full_x0020_Name' => 'Nama Lengkap',
                'Department_x0020_Name' => 'Departemen',
                'Job_x0020_Title' => 'Jabatan',
                'Join_x0020_Date' => 'Tgl Masuk',
                'Termination_x0020_Date' => 'Tgl Keluar',
                'Job_x0020_Status' => 'Status Kerja',
                'Active' => 'Status Aktif',
            ],
            'filter' => null,
        ],

        // -----------------------------------------------------------------
        // Laporan Perbandingan Jumlah Karyawan Tahunan Per Bulan
        // -----------------------------------------------------------------
        // -----------------------------------------------------------------
        // Laporan Diagram Karyawan Per Departemen
        // -----------------------------------------------------------------
        'diagram_karyawan_per_departemen' => [
            'label' => 'Laporan Diagram Karyawan Per Departemen',
            'columns' => [
                'Department_x0020_Name' => 'Departemen',
            ],
            'filter' => ['Active' => 'Active'],
        ],

        'perbandingan_jumlah_karyawan_tahunan_per_bulan' => [
            'label' => 'Laporan Perbandingan Jumlah Karyawan Tahunan Per Bulan',
            'columns' => [
                'Employee_x0020_Code' => 'Kode Karyawan',
                'Full_x0020_Name' => 'Nama',
                'Join_x0020_Date' => 'Tanggal Masuk',
                'Join_x0020_Date_x0020__x0028_Year_x0029_' => 'Tahun Masuk',
                'Join_x0020_Date_x0020__x0028_Month_x0029_' => 'Bulan Masuk',
                'Termination_x0020_Date' => 'Tanggal Keluar',
                'Termination_x0020_Date_x0020__x0028_Year_x0029_' => 'Tahun Keluar',
                'Termination_x0020_Date_x0020__x0028_Month_x0029_' => 'Bulan Keluar',
                'Active' => 'Status Aktif',
                'Nama_x0020_User' => 'Nama User',
                'User_x0020_Name' => 'User Name',
                'Printed_x0020_By' => 'Printed By',
                'Created_x0020_By' => 'Created By',
            ],
            'filter' => null,
        ],

    ],
];
