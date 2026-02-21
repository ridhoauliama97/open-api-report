<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'MstUsername';

    protected $primaryKey = 'Username';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'Username',
        'Password',
        'Nama',
        'Email',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'Password',
    ];

    public function getAuthPasswordName(): string
    {
        return 'Password';
    }

    public function getRememberTokenName(): ?string
    {
        return null;
    }

    public function getNameAttribute(): string
    {
        return (string) ($this->attributes['Nama'] ?? $this->attributes['Username'] ?? '');
    }

    public function getEmailAttribute(): ?string
    {
        $email = $this->attributes['Email'] ?? null;

        return is_string($email) && $email !== '' ? $email : null;
    }

}
