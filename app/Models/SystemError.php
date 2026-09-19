<?php

namespace App\Models;

use App\Support\SensitiveDataRedactor;
use Illuminate\Database\Eloquent\Model;

class SystemError extends Model
{
    protected $table = 'system_errors';
    public $timestamps = false;

    protected $fillable = [
        'error_level', 'error_code', 'message', 'file', 'line',
        'trace', 'url', 'ip_address', 'user_agent', 'user_id', 'created_at', 'is_resolved',
    ];

    protected $casts = [
        'is_resolved' => 'boolean',
        'created_at' => 'datetime',
    ];

    public function setMessageAttribute(?string $value): void
    {
        $this->attributes['message'] = mb_substr(SensitiveDataRedactor::text($value) ?? '', 0, 5000);
    }

    public function setTraceAttribute(?string $value): void
    {
        $this->attributes['trace'] = $value === null
            ? null
            : mb_substr(SensitiveDataRedactor::text($value) ?? '', 0, 10000);
    }

    public function setUrlAttribute(?string $value): void
    {
        $this->attributes['url'] = $value === null
            ? null
            : mb_substr(SensitiveDataRedactor::url($value) ?? '', 0, 255);
    }

    public function setUserAgentAttribute(?string $value): void
    {
        $this->attributes['user_agent'] = $value === null
            ? null
            : mb_substr(SensitiveDataRedactor::text($value) ?? '', 0, 255);
    }
}
