<?php

namespace App\Http\Requests\Api\Staff;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UpdateTeacherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $teacherId = $this->route('teacher');

        return [
            'user_id' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
            'teacher_code' => [
                'sometimes', 'required', 'string', 'max:20',
                Rule::unique('teachers', 'teacher_code')->ignore($teacherId),
            ],
            'nip' => [
                'sometimes', 'required', 'string', 'max:30',
                Rule::unique('teachers', 'nip')->ignore($teacherId),
            ],
            'full_name' => ['sometimes', 'nullable', 'string', 'max:150'],
            'prefix_title' => ['sometimes', 'nullable', 'string', 'max:50'],
            'suffix_title' => ['sometimes', 'nullable', 'string', 'max:50'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:20'],
            'email' => ['sometimes', 'nullable', 'email', 'max:150'],
            'last_education' => ['sometimes', 'nullable', 'string', 'max:50'],
            'major' => ['sometimes', 'nullable', 'string', 'max:100'],
            'employment_status' => ['sometimes', 'nullable', 'string', 'max:50'],
            'join_date' => ['sometimes', 'nullable', 'date'],
            'photo' => ['sometimes', 'nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'required', 'boolean'],
            'address' => ['sometimes', 'nullable', 'string'],
            'gender' => ['sometimes', 'required', 'in:L,P'],
            'birth_place' => ['sometimes', 'nullable', 'string', 'max:100'],
            'birth_date' => ['sometimes', 'nullable', 'date'],
            'religion' => ['sometimes', 'nullable', 'string', 'max:30'],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $validator->errors(),
        ], 422));
    }
}
