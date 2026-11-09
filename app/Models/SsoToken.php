<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SsoToken extends Model
{
    protected $table = 'sso_tokens';
    protected $primaryKey = 'token';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'token',
        'nip',
        'role',
        'expired_at',
    ];

    protected $casts = [
        'expired_at' => 'datetime',
    ];
}
