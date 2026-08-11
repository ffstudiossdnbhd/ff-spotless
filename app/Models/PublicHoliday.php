<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;

class PublicHoliday extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'date',
        'name',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'immutable_date',
        ];
    }

    protected function setDateAttribute(mixed $value): void
    {
        $this->attributes['date'] = $value instanceof DateTimeInterface
            ? $value->format('Y-m-d')
            : $value;
    }
}
