<?php

return [
    'sales' => [
        'database_connection' => env('SALES_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('SALES_REPORT_PROCEDURE', 'sp_sales_report'),
        'call_syntax' => env('SALES_REPORT_CALL_SYNTAX', 'auto'),
        'query' => env('SALES_REPORT_QUERY'),
    ],
    'mutasi_cross_cut' => [
        'database_connection' => env('MUTASI_CROSS_CUT_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('MUTASI_CROSS_CUT_REPORT_PROCEDURE', 'SP_Mutasi_CCAkhir'),
        'call_syntax' => env('MUTASI_CROSS_CUT_REPORT_CALL_SYNTAX', 'auto'),
        'query' => env('MUTASI_CROSS_CUT_REPORT_QUERY'),
    ],
    'mutasi_barang_jadi' => [
        'database_connection' => env('MUTASI_BARANG_JADI_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('MUTASI_BARANG_JADI_REPORT_PROCEDURE', 'SP_Mutasi_BarangJadi'),
        'sub_stored_procedure' => env('MUTASI_BARANG_JADI_SUB_REPORT_PROCEDURE', 'SP_SubMutasi_BarangJadi'),
        'call_syntax' => env('MUTASI_BARANG_JADI_REPORT_CALL_SYNTAX', 'exec'),
        'query' => env('MUTASI_BARANG_JADI_REPORT_QUERY'),
        'sub_query' => env('MUTASI_BARANG_JADI_SUB_REPORT_QUERY'),
    ],
];
