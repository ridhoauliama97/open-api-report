<?php

namespace App\Models;

class PpsUser extends User
{
    protected $connection = 'sqlsrv_pps';
}
