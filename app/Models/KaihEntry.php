<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KaihEntry extends Model
{
    protected $fillable = [
        'user_id',
        'activity_key',
        'tanggal',
        'status',
        'reason',
        'checked_at',
        'periode_id',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'checked_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function activity()
    {
        return $this->belongsTo(KaihActivity::class, 'activity_key', 'key');
    }

    public function periode()
    {
        return $this->belongsTo(Periode::class);
    }
}
