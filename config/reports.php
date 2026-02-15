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
        'stored_procedure' => env('MUTASI_CROSS_CUT_REPORT_PROCEDURE', 'sp_mutasi_cross_cut_report'),
        'call_syntax' => env('MUTASI_CROSS_CUT_REPORT_CALL_SYNTAX', 'auto'),
        'query' => env('MUTASI_CROSS_CUT_REPORT_QUERY'),
        'sqlite_fallback_query' => env(
            'MUTASI_CROSS_CUT_SQLITE_FALLBACK_QUERY',
            'SELECT * FROM mutasi_cross_cut ORDER BY jenis ASC'
        ),
    ],
];
