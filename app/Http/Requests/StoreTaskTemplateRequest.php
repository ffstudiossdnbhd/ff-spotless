<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\SanitizesPlainText;
use App\Models\TaskSession;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreTaskTemplateRequest extends FormRequest
{
    use SanitizesPlainText;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $finishTime = $this->input('finish_time');
        if (is_string($finishTime) && strlen(trim($finishTime)) === 5) {
            $finishTime = trim($finishTime) . ':00';
        }

        $days = $this->input('days_of_week');
        if (is_array($days)) {
            $days = array_values(array_unique(array_map('intval', array_filter($days, static fn ($d) => $d !== null && $d !== ''))));
            sort($days);
        }

        $this->merge([
            'task_name' => $this->sanitizePlainText($this->input('task_name')),
            'description' => $this->input('description') ? $this->sanitizePlainText($this->input('description')) : null,
            'finish_time' => $finishTime,
            'days_of_week' => $days,
            'applies_to_all_collections' => $this->boolean('applies_to_all_collections'),
            'task_collection_ids' => array_values(array_filter((array) $this->input('task_collection_ids', []), static fn ($value) => $value !== null && $value !== '')),
        ]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'task_name' => ['bail', 'required', 'string', 'max:255'],
            'description' => ['bail', 'nullable', 'string', 'max:2000'],
            'task_session_id' => ['bail', 'required', 'integer', 'exists:task_sessions,id'],
            'days_of_week' => ['bail', 'required', 'array', 'min:1'],
            'days_of_week.*' => ['bail', 'integer', 'distinct', 'in:1,2,3,4,5'],
            'applies_to_all_collections' => ['bail', 'required', 'boolean'],
            'task_collection_ids' => ['bail', 'array'],
            'task_collection_ids.*' => ['bail', 'integer', 'distinct', 'exists:task_collections,id'],
            'finish_time' => ['bail', 'required', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! $this->boolean('applies_to_all_collections')) {
                if (count((array) $this->input('task_collection_ids', [])) === 0) {
                    $validator->errors()->add('task_collection_ids', 'Choose at least one task collection, or select all collections.');
                }
            }

            $sessionId = $this->input('task_session_id');
            $finishTime = $this->input('finish_time');

            if ($sessionId && $finishTime) {
                $session = TaskSession::query()->find($sessionId);
                if ($session instanceof TaskSession) {
                    $sessionStart = strlen($session->start_time) === 5 ? $session->start_time . ':00' : substr($session->start_time, 0, 8);
                    $sessionEnd = strlen($session->end_time) === 5 ? $session->end_time . ':00' : substr($session->end_time, 0, 8);
                    $checkFinish = strlen($finishTime) === 5 ? $finishTime . ':00' : substr($finishTime, 0, 8);

                    if ($checkFinish <= $sessionStart || $checkFinish > $sessionEnd) {
                        $startFormatted = \Carbon\CarbonImmutable::parse($sessionStart)->format('g:i A');
                        $endFormatted = \Carbon\CarbonImmutable::parse($sessionEnd)->format('g:i A');
                        $validator->errors()->add('finish_time', "Finish time must be after session start ({$startFormatted}) and up to session end ({$endFormatted}).");
                    }
                }
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'task_name.required' => 'Task name is required.',
            'task_name.string' => 'Task name must be text.',
            'task_name.max' => 'Task name must not exceed 255 characters.',
            'task_session_id.required' => 'Task session is required.',
            'task_session_id.integer' => 'Task session is invalid.',
            'task_session_id.exists' => 'Task session was not found.',
            'days_of_week.required' => 'Please select at least one day.',
            'days_of_week.array' => 'Days must be provided as a list.',
            'days_of_week.min' => 'Please select at least one day.',
            'days_of_week.*.in' => 'Selected day is invalid.',
            'task_collection_ids.array' => 'Task collections are invalid.',
            'task_collection_ids.*.integer' => 'A task collection is invalid.',
            'task_collection_ids.*.exists' => 'A task collection was not found.',
            'finish_time.required' => 'Finish time is required.',
            'finish_time.regex' => 'Finish time format must be HH:MM.',
        ];
    }
}
