<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const DEFAULT_PASSWORD = '123456';

    protected $table = 'users';

    // Tabel users asli tidak punya kolom timestamps created_at/updated_at
    // Tapi ada kolom created_at (datetime type)
    public $timestamps = false;
    const CREATED_AT = 'created_at';
    const UPDATED_AT = null;

    protected $fillable = [
        'username',
        'email',
        'password',
        'is_password_default',
        'nama_lengkap',
        'nip_nis',
        'jenis_kelamin',
        'foto',
        'role_id',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
            'is_password_default' => 'boolean',
        ];
    }

    // Tabel users asli tidak punya kolom name, tapi Laravel kadang butuh
    // Kita mapping nama_lengkap ke kolom name untuk auth scaffolding
    public function getNameAttribute()
    {
        return $this->nama_lengkap;
    }

    // Relasi ke tabel siswa (untuk user dengan role siswa)
    public function siswa(): HasOne
    {
        return $this->hasOne(Siswa::class, 'user_id');
    }

    // Relasi ke role
    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    // Cek role helper
    public function hasRole(string $roleName): bool
    {
        return $this->role && $this->role->nama_role === $roleName;
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    public function isGuru(): bool
    {
        return $this->hasRole('guru');
    }

    public function isSiswa(): bool
    {
        return $this->hasRole('siswa');
    }

    public function isKepalaSekolah(): bool
    {
        return $this->hasRole('kepala_sekolah');
    }

    // Relasi: guru mengajar banyak kelas_mapel
    public function kelasMapel(): HasMany
    {
        return $this->hasMany(KelasMapel::class, 'guru_id');
    }

    public function jadwalMengajar(): HasMany
    {
        return $this->hasMany(JadwalMengajar::class, 'guru_id');
    }

    public function kelasDaring(): HasMany
    {
        return $this->hasMany(KelasDaring::class, 'guru_id');
    }

    public function waliKelas(): HasMany
    {
        return $this->hasMany(WaliKelas::class, 'guru_id');
    }

    // Chat messages
    public function chatMessages(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'user_id');
    }

    // Log login
    public function logLogin(): HasMany
    {
        return $this->hasMany(LogLogin::class, 'user_id');
    }

    // Notifikasi
    public function notifikasi(): HasMany
    {
        return $this->hasMany(Notifikasi::class, 'user_id');
    }

    // Dashboard widgets
    public function dashboardWidgets(): HasMany
    {
        return $this->hasMany(DashboardWidget::class, 'user_id');
    }

    // Calendar events
    public function calendarEvents(): HasMany
    {
        return $this->hasMany(CalendarEvent::class, 'user_id');
    }
}
