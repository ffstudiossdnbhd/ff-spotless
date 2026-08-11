<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\SanitizesPlainText;
use App\Models\PublicHoliday;
use App\Services\OperationalDate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StorePublicHolidayRequest extends FormRequest
{
    use SanitizesPlainText;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => $this->sanitizePlainText($this->input('name')),
        ]);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'date' => ['bail', 'required', 'string', 'date_format:Y-m-d'],
            'name' => ['bail', 'required', 'string', 'max:100'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->has('date')) {
                return;
            }

            $dates = app(OperationalDate::class);
            $date = $dates->fromDateString((string) $this->input('date'));

            if (! $dates->isWorkingDay($date)) {
                $validator->errors()->add('date', 'Public holidays must fall on a weekday.');

                return;
            }

            if (! $date->greaterThan($dates->today())) {
                $validator->errors()->add('date', 'Public holidays must be after today.');

                return;
            }

            $existingHoliday = PublicHoliday::query()
                ->whereDate('date', $date->toDateString());
            $ignoredHolidayId = $this->ignoredPublicHolidayId();

            if ($ignoredHolidayId !== null) {
                $existingHoliday->where('id', '!=', $ignoredHolidayId);
            }

            if ($existingHoliday->exists()) {
                $validator->errors()->add('date', 'A public holiday already exists for this date.');
            }
        });
    }

    protected function ignoredPublicHolidayId(): ?int
    {
        return null;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'date.required' => 'Public holiday date is required.',
            'date.string' => 'Public holiday date is invalid.',
            'date.date_format' => 'Public holiday date must use the YYYY-MM-DD format.',
            'date.unique' => 'A public holiday already exists for this date.',
            'name.required' => 'Public holiday name is required.',
            'name.string' => 'Public holiday name must be text.',
            'name.max' => 'Public holiday name must not exceed 100 characters.',
        ];
    }
}
