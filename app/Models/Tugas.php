<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tugas extends Model
{
    protected $fillable = [
        'judul',
        'deskripsi',
        'mulai',
        'deadline',
        'created_by',
        'periode_id',
    ];

    protected function casts(): array
    {
        return [
            'mulai' => 'datetime',
            'deadline' => 'datetime',
        ];
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function pengumpulan()
    {
        return $this->hasMany(PengumpulanTugas::class);
    }

    public function periode()
    {
        return $this->belongsTo(Periode::class);
    }
}
