<?php

return [

    // Laporan Mutasi
    'mutasi_barang_jadi' => [
        'database_connection' => env('MUTASI_BARANG_JADI_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('MUTASI_BARANG_JADI_REPORT_PROCEDURE', 'SP_Mutasi_BarangJadi'),
        'sub_stored_procedure' => env('MUTASI_BARANG_JADI_SUB_REPORT_PROCEDURE', 'SP_SubMutasi_BarangJadi'),
        'call_syntax' => env('MUTASI_BARANG_JADI_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('MUTASI_BARANG_JADI_REPORT_QUERY'),
        'sub_query' => env('MUTASI_BARANG_JADI_SUB_REPORT_QUERY'),
    ],
    'mutasi_finger_joint' => [
        'database_connection' => env('MUTASI_FINGER_JOINT_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('MUTASI_FINGER_JOINT_REPORT_PROCEDURE', 'SP_Mutasi_FingerJoint'),
        'sub_stored_procedure' => env('MUTASI_FINGER_JOINT_SUB_REPORT_PROCEDURE', 'SP_SubMutasi_FingerJoint'),
        'call_syntax' => env('MUTASI_FINGER_JOINT_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('MUTASI_FINGER_JOINT_REPORT_QUERY'),
        'sub_query' => env('MUTASI_FINGER_JOINT_SUB_REPORT_QUERY'),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env('MUTASI_FINGER_JOINT_REPORT_EXPECTED_COLUMNS', '')))),
        'expected_sub_columns' => array_filter(array_map('trim', explode(',', (string) env('MUTASI_FINGER_JOINT_SUB_REPORT_EXPECTED_COLUMNS', '')))),
    ],
    'mutasi_moulding' => [
        'database_connection' => env('MUTASI_MOULDING_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('MUTASI_MOULDING_REPORT_PROCEDURE', 'SP_Mutasi_Moulding'),
        'sub_stored_procedure' => env('MUTASI_MOULDING_SUB_REPORT_PROCEDURE', 'SP_SubMutasi_Moulding'),
        'call_syntax' => env('MUTASI_MOULDING_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('MUTASI_MOULDING_REPORT_QUERY'),
        'sub_query' => env('MUTASI_MOULDING_SUB_REPORT_QUERY'),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env('MUTASI_MOULDING_REPORT_EXPECTED_COLUMNS', '')))),
        'expected_sub_columns' => array_filter(array_map('trim', explode(',', (string) env('MUTASI_MOULDING_SUB_REPORT_EXPECTED_COLUMNS', '')))),
    ],
    'mutasi_s4s' => [
        'database_connection' => env('MUTASI_S4S_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('MUTASI_S4S_REPORT_PROCEDURE', 'SP_Mutasi_S4S'),
        'sub_stored_procedure' => env('MUTASI_S4S_SUB_REPORT_PROCEDURE', 'SP_SubMutasi_S4S'),
        'call_syntax' => env('MUTASI_S4S_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('MUTASI_S4S_REPORT_QUERY'),
        'sub_query' => env('MUTASI_S4S_SUB_REPORT_QUERY'),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env('MUTASI_S4S_REPORT_EXPECTED_COLUMNS', '')))),
        'expected_sub_columns' => array_filter(array_map('trim', explode(',', (string) env('MUTASI_S4S_SUB_REPORT_EXPECTED_COLUMNS', '')))),
    ],
    'rangkuman_jlh_label_input' => [
        'database_connection' => env('RANGKUMAN_LABEL_INPUT_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('RANGKUMAN_LABEL_INPUT_REPORT_PROCEDURE', 'SPWps_LapRangkumanJlhLabelInput'),
        'call_syntax' => env('RANGKUMAN_LABEL_INPUT_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('RANGKUMAN_LABEL_INPUT_REPORT_QUERY'),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env('RANGKUMAN_LABEL_INPUT_REPORT_EXPECTED_COLUMNS', '')))),
    ],
    'bahan_terpakai' => [
        'database_connection' => env('BAHAN_TERPAKAI_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('BAHAN_TERPAKAI_REPORT_PROCEDURE', 'SPWps_LapBahanTerpakai'),
        'sub_stored_procedure' => env('BAHAN_TERPAKAI_SUB_REPORT_PROCEDURE', 'SPWps_LapSubBahanTerpakai'),
        'call_syntax' => env('BAHAN_TERPAKAI_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('BAHAN_TERPAKAI_REPORT_QUERY'),
        'sub_query' => env('BAHAN_TERPAKAI_SUB_REPORT_QUERY'),
        'ton_to_m3_factor' => (float) env('BAHAN_TERPAKAI_TON_TO_M3_FACTOR', '1.416'),
        'expected_columns' => array_filter(array_map('trim', explode(',', (string) env('BAHAN_TERPAKAI_REPORT_EXPECTED_COLUMNS', '')))),
        'expected_sub_columns' => array_filter(array_map('trim', explode(',', (string) env('BAHAN_TERPAKAI_SUB_REPORT_EXPECTED_COLUMNS', '')))),
    ],

    //Laporan Verifikasi :


    // Pengaturan otentikasi JWT untuk endpoint laporan
    'report_auth' => [
        'issuers' => array_filter(array_map('trim', explode(',', (string) env('REPORT_JWT_TRUSTED_ISSUERS', env('REPORT_JWT_TRUSTED_ISSUER', ''))))),
        'audiences' => array_filter(array_map('trim', explode(',', (string) env('REPORT_JWT_TRUSTED_AUDIENCES', env('REPORT_JWT_TRUSTED_AUDIENCE', ''))))),
        'required_scope' => env('REPORT_JWT_REQUIRED_SCOPE'),
        'scope_claim' => env('REPORT_JWT_SCOPE_CLAIM', 'scope'),
        'issued_audience' => env('REPORT_JWT_ISSUED_AUDIENCE'),
        'issued_scope' => env('REPORT_JWT_ISSUED_SCOPE'),
        'subject_claim' => env('REPORT_JWT_SUBJECT_CLAIM', 'sub'),
        'name_claim' => env('REPORT_JWT_NAME_CLAIM', 'name'),
        'email_claim' => env('REPORT_JWT_EMAIL_CLAIM', 'email'),
    ],
];
