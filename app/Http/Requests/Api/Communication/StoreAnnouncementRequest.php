<?php

namespace App\Http\Requests\Api\Communication;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAnnouncementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'category' => ['required', Rule::in(['umum', 'guru', 'siswa'])],
            'attachment' => ['nullable', 'string', 'max:255'],
            'publish_date' => ['nullable', 'date'],
            'expired_date' => ['nullable', 'date', 'after_or_equal:publish_date'],
        ];
    }
}