<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_identifier',
        'user_name',
        'user_email',
        'auth_guard',
        'route_name',
        'http_method',
        'path',
        'full_url',
        'status_code',
        'ip_address',
        'user_agent',
        'request_meta',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'request_meta' => 'array',
    ];
}
