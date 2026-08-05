<?php

namespace App\Services;

use App\Models\TaskCollection;
use App\Models\RotationCycleSetting;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use LogicException;

class TaskCollectionResolver
{
    /**
     * @var array<string, TaskCollection>
     */
    private array $activeByDate = [];

    private ?TaskCollection $defaultCollection = null;

    /** @var Collection<int, TaskCollection>|null */
    private ?Collection $rotations = null;

    private ?CarbonImmutable $anchorWeekStart = null;

    public function __construct(
        private readonly OperationalDate $dates,
    ) {}

    public function default(): TaskCollection
    {
        if ($this->defaultCollection instanceof TaskCollection) {
            return $this->defaultCollection;
        }

        $collection = TaskCollection::query()
            ->where('is_default', true)
            ->first();

        if (! $collection instanceof TaskCollection) {
            throw new LogicException('The default task collection is missing.');
        }

        return $this->defaultCollection = $collection;
    }

    public function forDate(CarbonImmutable $date): TaskCollection
    {
        $dateString = $date->toDateString();

        if (isset($this->activeByDate[$dateString])) {
            return $this->activeByDate[$dateString];
        }

        $rotations = $this->rotations();

        if ($rotations->isEmpty()) {
            return $this->activeByDate[$dateString] = $this->default();
        }

        $weekStart = $date->startOfWeek(CarbonInterface::SUNDAY);
        $daysSinceAnchor = $this->anchorWeekStart()->diffInDays($weekStart, false);
        $weeksSinceAnchor = (int) floor($daysSinceAnchor / 7);
        $index = (($weeksSinceAnchor % $rotations->count()) + $rotations->count()) % $rotations->count();

        return $this->activeByDate[$dateString] = $rotations->values()->get($index);
    }

    /** @return Collection<int, TaskCollection> */
    private function rotations(): Collection
    {
        if ($this->rotations instanceof Collection) {
            return $this->rotations;
        }

        return $this->rotations = TaskCollection::query()
            ->where('is_default', false)
            ->orderBy('rotation_order')
            ->orderBy('id')
            ->get();
    }

    private function anchorWeekStart(): CarbonImmutable
    {
        if ($this->anchorWeekStart instanceof CarbonImmutable) {
            return $this->anchorWeekStart;
        }

        $setting = RotationCycleSetting::query()->find(1);

        if (! $setting instanceof RotationCycleSetting) {
            throw new LogicException('The rotation cycle settings are missing.');
        }

        return $this->anchorWeekStart = CarbonImmutable::parse(
            $setting->anchor_week_start->toDateString(),
            $this->dates->timezone(),
        )->startOfWeek(CarbonInterface::SUNDAY);
    }
}
