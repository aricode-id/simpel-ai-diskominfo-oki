<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'nip',
        'jabatan',
        'role',
        'bidang',
        'status_pegawai',
        'google_id',
        'google_token',
        'google_refresh_token',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'google_token',
        'google_refresh_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function reports()
    {
        return $this->hasMany(Report::class);
    }

    public function approvedReports()
    {
        return $this->hasMany(Report::class, 'approved_by');
    }
}
