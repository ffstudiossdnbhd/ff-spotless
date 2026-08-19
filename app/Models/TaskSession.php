<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaskSession extends Model
{
    protected $fillable = ['name', 'start_time', 'end_time', 'sort_order', 'is_active'];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public static function formatSessionName(string $startTime, string $endTime): string
    {
        $start = \Carbon\CarbonImmutable::parse($startTime)->format('g:i A');
        $end = \Carbon\CarbonImmutable::parse($endTime)->format('g:i A');

        return "{$start} - {$end}";
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function taskTemplates(): HasMany
    {
        return $this->hasMany(TaskTemplate::class);
    }

    public function weeklyTaskTemplates(): HasMany
    {
        return $this->hasMany(WeeklyTaskTemplate::class);
    }

    public function monthlyTaskTemplates(): HasMany
    {
        return $this->hasMany(MonthlyTaskTemplate::class);
    }
}
