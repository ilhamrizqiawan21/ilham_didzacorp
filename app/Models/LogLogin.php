<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogLogin extends Model
{
    protected $table = 'log_login';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'username',
        'nama_lengkap',
        'role',
        'ip_address',
        'user_agent',
        'login_time',
    ];

    protected $casts = [
        'login_time' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
