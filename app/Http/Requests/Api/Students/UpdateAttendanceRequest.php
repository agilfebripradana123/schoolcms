<?php

namespace App\Http\Requests\Api\Students;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'class_id' => ['required', 'integer', 'exists:classes,id'],
            'date' => ['required', 'date'],
            'status' => [
                'required',
                Rule::in(['hadir', 'sakit', 'izin', 'alpa']),
            ],
            'note' => ['nullable', 'string'],
        ];
    }
}