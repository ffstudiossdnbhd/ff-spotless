<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MonthlyTaskTemplate extends Model
{
    protected $fillable = [
        'task_name',
        'description',
        'task_session_id',
        'task_collection_id',
        'applies_to_all_collections',
        'finish_time',
        'sort_order',
        'starts_on',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'task_session_id' => 'integer',
            'task_collection_id' => 'integer',
            'applies_to_all_collections' => 'boolean',
            'sort_order' => 'integer',
            'starts_on' => 'immutable_date',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function taskSession(): BelongsTo
    {
        return $this->belongsTo(TaskSession::class);
    }

    public function taskCollection(): BelongsTo
    {
        return $this->belongsTo(TaskCollection::class);
    }

    public function taskCollections(): BelongsToMany
    {
        return $this->belongsToMany(TaskCollection::class, 'monthly_task_template_task_collection');
    }

    public function occurrences(): HasMany
    {
        return $this->hasMany(MonthlyTaskOccurrence::class);
    }
}
