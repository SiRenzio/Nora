<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateReadingProgressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->is($this->route('libraryEntry')->user) ?? false;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'progress_action' => ['required', Rule::in(['manual', 'next', 'latest'])],
            'chapter' => ['nullable', 'required_if:progress_action,manual', 'string', 'max:100'],
        ];
    }
}
