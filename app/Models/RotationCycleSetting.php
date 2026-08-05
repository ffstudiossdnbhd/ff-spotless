<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RotationCycleSetting extends Model
{
    protected $fillable = [
        'anchor_week_start',
    ];

    protected function casts(): array
    {
        return [
            'anchor_week_start' => 'immutable_date',
        ];
    }
}
