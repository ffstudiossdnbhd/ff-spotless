<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\InteractsWithOperationalDate;
use App\Services\OperationalDate;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;

class AdminHistoryRequest extends FormRequest
{
    use InteractsWithOperationalDate;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'date' => ['nullable', 'string', 'date_format:Y-m-d'],
            'stats_from' => ['nullable', 'string', 'date_format:Y-m-d'],
            'stats_to' => ['nullable', 'string', 'date_format:Y-m-d', 'after_or_equal:stats_from'],
            'rotation_month' => ['nullable', 'string', 'date_format:Y-m'],
        ];
    }

    public function rotationCalendarMonth(OperationalDate $dates): CarbonImmutable
    {
        $month = $this->validated('rotation_month');

        if (! is_string($month) || $month === '') {
            return $dates->today()->startOfMonth();
        }

        return CarbonImmutable::parse($month.'-01', $dates->timezone())->startOfMonth();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'date.string' => 'Date is invalid.',
            'date.date_format' => 'Date must use the YYYY-MM-DD format.',
            'stats_from.date_format' => 'Statistics start date must use the YYYY-MM-DD format.',
            'stats_to.date_format' => 'Statistics end date must use the YYYY-MM-DD format.',
            'stats_to.after_or_equal' => 'Statistics end date must be after the start date.',
            'rotation_month.date_format' => 'Rotation calendar month must use the YYYY-MM format.',
        ];
    }
}
