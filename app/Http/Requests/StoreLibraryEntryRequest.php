<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLibraryEntryRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255'],
            'alternative_title' => ['nullable', 'string', 'max:255'],
            'content_type' => ['required', Rule::in(['manga', 'manhwa', 'manhua', 'comic', 'novel'])],
            'cover_url' => ['nullable', 'url:http,https', 'max:2048'],
            'description' => ['nullable', 'string', 'max:5000'],
            'source_url' => ['nullable', 'url:http,https', 'max:2048'],
            'source_website' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['plan_to_read', 'reading', 'on_hold', 'completed', 'dropped'])],
            'latest_chapter' => ['nullable', 'string', 'max:100'],
            'last_completed_chapter' => ['nullable', 'string', 'max:100'],
            'last_read_at' => ['nullable', 'date'],
            'monitoring_enabled' => ['required', 'boolean'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'rating' => ['nullable', 'integer', 'between:1,10'],
        ];
    }
}
