<?php

namespace App\Services;

class PublicHolidayReconciler
{
    public function __construct(
        private readonly OperationalDate $dates,
        private readonly ChecklistMaterializer $daily,
        private readonly WeeklyTaskScheduler $weekly,
    ) {}

    /**
     * Rebuild only future, incomplete snapshots after an admin changes the
     * office-closure calendar. The caller owns any surrounding transaction.
     */
    public function reconcile(): void
    {
        $from = $this->dates->today()->addDay();

        $this->daily->refreshMaterializedDatesFrom($from);
        $this->weekly->reconcilePublicHolidaySchedulesFrom($from);
    }
}
