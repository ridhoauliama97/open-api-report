<?php

return [
    'sales' => [
        'database_connection' => env('SALES_REPORT_DB_CONNECTION', env('DB_CONNECTION')),
        'stored_procedure' => env('SALES_REPORT_PROCEDURE', 'sp_sales_report'),
        'call_syntax' => env('SALES_REPORT_CALL_SYNTAX', 'auto'),
        'query' => env('SALES_REPORT_QUERY'),
    ],
];
