<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PushSubscription extends Model
{
    protected $fillable = [
        'endpoint_hash',
        'endpoint',
        'public_key',
        'auth_token',
        'content_encoding',
        'role',
        'user_agent',
        'last_active_at',
    ];

    protected function casts(): array
    {
        return [
            'last_active_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (PushSubscription $subscription): void {
            if ($subscription->endpoint !== null && $subscription->endpoint !== '') {
                $subscription->endpoint_hash = hash('sha256', (string) $subscription->endpoint);
            }
        });
    }

    public function scopeAdmin(Builder $query): Builder
    {
        return $query->where('role', 'admin');
    }

    public function scopeCleaner(Builder $query): Builder
    {
        return $query->where('role', 'cleaner');
    }

    public function scopeForRole(Builder $query, string $role): Builder
    {
        return $query->where('role', $role);
    }

    /**
     * @return array{endpoint: string, publicKey: string, authToken: string, contentEncoding: string}
     */
    public function toWebPushArray(): array
    {
        return [
            'endpoint' => $this->endpoint,
            'publicKey' => $this->public_key,
            'authToken' => $this->auth_token,
            'contentEncoding' => $this->content_encoding ?: 'aes128gcm',
        ];
    }
}
