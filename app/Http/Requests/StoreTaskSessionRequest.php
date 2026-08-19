<?php

namespace App\Http\Requests;

use App\Models\TaskSession;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreTaskSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $startTime = $this->input('start_time');
        $endTime = $this->input('end_time');

        if (is_string($startTime) && strlen(trim($startTime)) === 5) {
            $startTime = trim($startTime) . ':00';
        }
        if (is_string($endTime) && strlen(trim($endTime)) === 5) {
            $endTime = trim($endTime) . ':00';
        }

        $this->merge([
            'start_time' => $startTime,
            'end_time' => $endTime,
        ]);
    }

    public function rules(): array
    {
        return [
            'start_time' => ['bail', 'required', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
            'end_time' => ['bail', 'required', 'regex:/^\d{2}:\d{2}(:\d{2})?$/', 'after:start_time'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $startTime = $this->input('start_time');
            $endTime = $this->input('end_time');

            if (! $startTime || ! $endTime || $startTime >= $endTime) {
                return;
            }

            $sessionId = $this->route('taskSession')?->getKey();
            $duplicate = TaskSession::query()
                ->active()
                ->when($sessionId, fn ($q) => $q->where('id', '!=', $sessionId))
                ->where('start_time', $startTime)
                ->where('end_time', $endTime)
                ->exists();

            if ($duplicate) {
                $validator->errors()->add('start_time', 'A work session with this exact time range already exists.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'start_time.required' => 'Start time is required.',
            'start_time.regex' => 'Start time format must be HH:MM.',
            'end_time.required' => 'End time is required.',
            'end_time.regex' => 'End time format must be HH:MM.',
            'end_time.after' => 'End time must be after start time.',
        ];
    }
}
