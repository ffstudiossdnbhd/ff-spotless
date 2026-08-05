<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

class AuditLogger
{
    /**
     * @param  array<string, bool|float|int|string|null>  $metadata
     */
    public function record(
        string $action,
        string $actorType,
        string $actorLabel,
        ?Model $subject = null,
        array $metadata = [],
    ): void {
        AuditLog::query()->create([
            'action' => $action,
            'actor_type' => $actorType,
            'actor_label' => $actorLabel,
            'subject_type' => $subject ? $subject->getMorphClass() : null,
            'subject_id' => $subject?->getKey(),
            'metadata' => $this->safeMetadata($metadata),
            'occurred_at' => app(OperationalDate::class)->nowUtc(),
        ]);
    }

    /**
     * @param  array<string, bool|float|int|string|null>  $metadata
     */
    public function admin(string $action, ?Model $subject = null, array $metadata = []): void
    {
        $this->record($action, 'admin', 'Master admin', $subject, $metadata);
    }

    /**
     * @param  array<string, bool|float|int|string|null>  $metadata
     */
    public function cleaner(string $action, ?Model $subject = null, array $metadata = []): void
    {
        $this->record($action, 'cleaner', 'Cleaner (anonymous)', $subject, $metadata);
    }

    /**
     * @param  array<string, bool|float|int|string|null>  $metadata
     * @return array<string, bool|float|int|string|null>
     */
    private function safeMetadata(array $metadata): array
    {
        return collect($metadata)
            ->reject(static fn ($value, $key): bool => in_array(strtolower((string) $key), [
                'password', 'token', 'secret', 'cookie', 'session', 'path', 'url',
            ], true))
            ->map(static function ($value) {
                if (is_string($value)) {
                    return mb_substr(trim(strip_tags($value)), 0, 1000);
                }

                return is_bool($value) || is_float($value) || is_int($value) || $value === null
                    ? $value
                    : null;
            })
            ->all();
    }
}
