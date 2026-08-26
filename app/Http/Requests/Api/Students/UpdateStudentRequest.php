<?php

namespace App\Http\Requests\Api\Students;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $studentId = $this->route('student');

        return [
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'class_id' => ['nullable', 'integer', 'exists:classes,id'],
            'nisn' => [
                'required',
                'string',
                'max:20',
                Rule::unique('students', 'nisn')->ignore($studentId),
            ],
            'nis' => [
                'required',
                'string',
                'max:20',
                Rule::unique('students', 'nis')->ignore($studentId),
            ],
            'name' => ['required', 'string', 'max:100'],
            'gender' => ['required', 'in:L,P'],
            'birth_place' => ['required', 'string', 'max:100'],
            'birth_date' => ['required', 'date'],
            'address' => ['required', 'string'],
            'phone' => ['nullable', 'string', 'max:20'],
            'photo' => ['nullable', 'string', 'max:255'],
        ];
    }
}