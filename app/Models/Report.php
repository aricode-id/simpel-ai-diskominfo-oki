<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 
        'jenis_dokumen', 
        'judul', 
        'tanggal', 
        'bidang',
        'status',          // 'draft', 'pending', 'approved', 'rejected'
        'form_data', 
        'html_hasil',      // Nama file .docx di storage/app/public/reports/
        'catatan_revisi',
        'approved_by',
        'approved_at',
        'is_manual_edit',
    ];

    protected $casts = [
        'form_data' => 'array',
        'approved_at' => 'datetime',
        'is_manual_edit' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
