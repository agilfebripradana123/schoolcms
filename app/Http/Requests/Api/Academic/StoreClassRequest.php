<?php

namespace App\Http\Requests\Api\Academic;

use Illuminate\Foundation\Http\FormRequest;

class StoreClassRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:50'],
            'teacher_id' => ['nullable', 'integer', 'exists:teachers,id'],
            'level' => ['nullable', 'string', 'max:10'],
            'academic_year' => ['nullable', 'string', 'max:20'],
        ];
    }
}