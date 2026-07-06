<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KaihReflection extends Model
{
    protected $fillable = [
        'user_id',
        'tanggal',
        'content',
        'periode_id',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
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
