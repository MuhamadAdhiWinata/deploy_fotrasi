<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Periode extends Model
{
    protected $fillable = [
        'nama',
        'tanggal_mulai',
        'tanggal_selesai',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_mulai' => 'date',
            'tanggal_selesai' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function presensis()
    {
        return $this->hasMany(Presensi::class);
    }

    public function tugas()
    {
        return $this->hasMany(Tugas::class);
    }

    public function siswa()
    {
        return $this->users()->where('role', 'siswa');
    }
}
