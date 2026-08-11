<?php

namespace App\Database;

use Illuminate\Database\Connectors\SqlServerConnector;
use PDO;

class CustomSqlServerConnector extends SqlServerConnector
{
    /**
     * The PDO connection options.
     *
     * PDO::ATTR_STRINGIFY_FETCHES sengaja dihapus dari daftar default
     * karena driver pdo_sqlsrv versi terbaru (5.13.0+) di PHP 8.5
     * tidak mendukung attribute ini dan akan melempar:
     * SQLSTATE[IMSSP]: An invalid attribute was designated on the PDO object.
     *
     * @var array
     */
    protected $options = [
        PDO::ATTR_CASE => PDO::CASE_NATURAL,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_ORACLE_NULLS => PDO::NULL_NATURAL,
    ];
}
