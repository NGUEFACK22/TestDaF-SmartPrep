<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSpeakingSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $maxKb = (int) config('testdaf.media.audio.max_kb', 20480);

        return [
            'exercise_id' => ['required', 'integer', 'exists:exercises,id'],
            'audio' => ['required', 'file', 'max:'.$maxKb],
            'duration_seconds' => ['nullable', 'integer', 'min:0', 'max:600'],
            'submit' => ['sometimes', 'boolean'],
        ];
    }
}
