<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ImportLibraryEntryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'source_url' => ['required', 'url:http,https', 'max:2048'],
            'status' => ['required', Rule::in(['plan_to_read', 'reading', 'on_hold', 'completed', 'dropped'])],
            'last_completed_chapter' => ['nullable', 'string', 'max:100'],
            'monitoring_enabled' => ['required', 'boolean'],
        ];
    }
}
