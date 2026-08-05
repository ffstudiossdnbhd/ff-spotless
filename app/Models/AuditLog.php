<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'action',
        'actor_type',
        'actor_label',
        'subject_type',
        'subject_id',
        'metadata',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'subject_id' => 'integer',
            'metadata' => 'array',
            'occurred_at' => 'immutable_datetime',
        ];
    }
}
