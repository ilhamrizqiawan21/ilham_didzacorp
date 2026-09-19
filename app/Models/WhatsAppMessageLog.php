<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsAppMessageLog extends Model
{
    // Keep the model aligned with the migration table name ("whatsapp", not "whats_app").
    protected $table = 'whatsapp_message_logs';

    protected $fillable = ['siswa_id', 'guru_id', 'jenis_template', 'tugas_ids', 'total_hari_terlambat', 'prepared_at', 'sent_marked_at'];

    protected $casts = ['tugas_ids' => 'array', 'prepared_at' => 'datetime', 'sent_marked_at' => 'datetime'];

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class);
    }

    public function guru(): BelongsTo
    {
        return $this->belongsTo(User::class, 'guru_id');
    }
}
