<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Presensi extends Model
{
    protected $fillable = [
        'user_id',
        'periode_id',
        'tanggal',
        'check_in',
        'check_out',
        'foto_check_in',
        'foto_check_out',
        'keterangan',
        'lat_check_in',
        'lng_check_in',
        'gps_accuracy_in',
        'lat_check_out',
        'lng_check_out',
        'gps_accuracy_out',
        'exif_lat_in',
        'exif_lng_in',
        'exif_lat_out',
        'exif_lng_out',
        'ip_address',
        'ip_lat',
        'ip_lng',
        'lokasi_valid',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'check_in' => 'datetime',
            'check_out' => 'datetime',
            'lokasi_valid' => 'boolean',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function periode()
    {
        return $this->belongsTo(Periode::class);
    }
}
