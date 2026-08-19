<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MonthlyTaskOccurrence extends Model
{
    protected $fillable = [
        'month_start',
        'monthly_task_template_id',
        'task_session_id',
        'task_name',
        'description',
        'session_name',
        'finish_time',
        'original_due_date',
        'scheduled_date',
        'status',
        'missed_reason',
        'completed_at',
        'completed_on',
        'completed_by_user_id',
        'completion_note',
    ];

    protected function casts(): array
    {
        return [
            'month_start' => 'immutable_date',
            'original_due_date' => 'immutable_date',
            'scheduled_date' => 'immutable_date',
            'completed_at' => 'immutable_datetime',
            'completed_on' => 'immutable_date',
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(MonthlyTaskTemplate::class, 'monthly_task_template_id');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by_user_id');
    }

    public function taskSession(): BelongsTo
    {
        return $this->belongsTo(TaskSession::class);
    }

    public function postponements(): HasMany
    {
        return $this->hasMany(MonthlyTaskPostponement::class);
    }

    public function evidence(): HasMany
    {
        return $this->hasMany(MonthlyTaskEvidence::class)->whereNull('invalidated_at');
    }
}
