<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KaihActivity extends Model
{
    protected $fillable = [
        'key',
        'label',
        'description',
        'group',
        'sort_order',
    ];

    public function entries()
    {
        return $this->hasMany(KaihEntry::class, 'activity_key', 'key');
    }
}
