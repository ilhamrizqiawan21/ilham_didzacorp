<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlockedIp extends Model
{
    protected $table = 'blocked_ips';
    public $timestamps = false;

    protected $fillable = ['ip_address', 'blocked_until', 'reason', 'created_at'];

    protected $casts = [
        'blocked_until' => 'datetime',
        'created_at' => 'datetime',
    ];
}
