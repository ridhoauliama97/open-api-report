<?php

return [
    'mutasi_barang_jadi' => [
        'database_connection' => env('MUTASI_BARANG_JADI_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('MUTASI_BARANG_JADI_REPORT_PROCEDURE', 'SP_Mutasi_BarangJadi'),
        'sub_stored_procedure' => env('MUTASI_BARANG_JADI_SUB_REPORT_PROCEDURE', 'SP_SubMutasi_BarangJadi'),
        'call_syntax' => env('MUTASI_BARANG_JADI_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('MUTASI_BARANG_JADI_REPORT_QUERY'),
        'sub_query' => env('MUTASI_BARANG_JADI_SUB_REPORT_QUERY'),
    ],
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
