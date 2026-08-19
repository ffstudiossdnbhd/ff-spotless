<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaskCollection extends Model
{
    protected $fillable = [
        'name',
        'is_default',
        'rotation_order',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'rotation_order' => 'integer',
        ];
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(TaskCollectionSchedule::class);
    }

    public function dailyTemplates(): HasMany
    {
        return $this->hasMany(TaskTemplate::class);
    }

    public function weeklyTemplates(): HasMany
    {
        return $this->hasMany(WeeklyTaskTemplate::class);
    }

    public function monthlyTemplates(): HasMany
    {
        return $this->hasMany(MonthlyTaskTemplate::class);
    }
}
