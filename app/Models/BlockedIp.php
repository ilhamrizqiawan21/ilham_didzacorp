<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
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

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('blocked_until', '>', now());
    }
}
