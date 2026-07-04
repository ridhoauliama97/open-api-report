<?php

return [
    'label' => 'Associate',
    'xml_source' => 'RU/Associate/AnlReports.Associate.CustomerList.xml',
    'record_tag' => 'tabel',
    'sub_reports' => [
        'customer_modifikasi' => [
            'label' => 'Laporan Customer (Periode 1 Tahun)',
            'columns' => [
                'Customer_x0020_Code' => 'Kode',
                'Customer_x0020_Name' => 'Nama Customer',
                'Sales_x0020_Person_x0020_Name' => 'Salesman',
                'Customer_x0020_Type' => 'Tipe',
                'Birth_x0020_Date' => 'Tanggal',
                'Credit_x0020_Limit' => 'Credit Limit',
                'Billing_x0020_City' => 'Kota',
                'Last_x0020_Modified_x0020_Date_x002F_Time' => 'Tanggal Modifikasi',
                'Last_x0020_Modified_x0020_By' => 'Dimodifikasi Oleh',
            ],
            'filter' => null,
        ],

        'customer_baru_per_tahun' => [
            'label' => 'Laporan Penambahan Customer Baru (Periode 1 Tahun)',
            'columns' => [
                'Customer_x0020_Code' => 'Kode',
                'Customer_x0020_Name' => 'Nama Customer',
                'Sales_x0020_Person_x0020_Name' => 'Salesman',
                'Customer_x0020_Type' => 'Tipe',
                'Birth_x0020_Date' => 'Tanggal',
                'Credit_x0020_Limit' => 'Credit Limit',
                'Billing_x0020_City' => 'Kota',
                'Created_x0020_Date_x002F_Time' => 'Created Date',
            ],
            'filter' => null,
        ],

        'customer_baru' => [
            'label' => 'Laporan Customer Baru',
            'columns' => [
                'Customer_x0020_Code' => 'Kode Customer',
                'Customer_x0020_Name' => 'Nama Customer',
                'Billing_x0020_Address_x0020_1' => 'Alamat',
                'Billing_x0020_City' => 'Kota',
                'Owner_x0020_Name' => 'Nama Pemilik',
                'Phone' => 'Telepon',
                'Payment_x0020_Term' => 'Syarat (Term)',
                'Credit_x0020_Limit' => 'Credit Limit',
            ],
            'filter' => null,
        ],

        'list_customer' => [
            'label' => 'Laporan Data Customer Per Kota',
            'columns' => [
                'Customer_x0020_Code' => 'Kode Customer',
                'Customer_x0020_Name' => 'Nama Customer',
                'Billing_x0020_Address_x0020_1' => 'Alamat',
                'Billing_x0020_City' => 'Kota',
            ],
            'filter' => null,
        ],
    ],
];
