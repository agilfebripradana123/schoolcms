<?php

namespace App\Http\Requests\Api\Academic;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UpdateClassStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'class_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('classes', 'id'),
            ],
            'student_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('students', 'id')->whereNull('deleted_at'),
                Rule::unique('class_students')->where(
                    fn ($q) => $q->where('class_id', $this->input('class_id'))
                        ->where('student_id', $this->input('student_id'))
                        ->where('academic_year_id', $this->input('academic_year_id'))
                )->ignore($this->route('class_student')),
            ],
            'academic_year_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('academic_years', 'id'),
            ],
            'status' => [
                'sometimes',
                'required',
                'string',
                Rule::in(['active', 'moved', 'graduated']),
            ],
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
