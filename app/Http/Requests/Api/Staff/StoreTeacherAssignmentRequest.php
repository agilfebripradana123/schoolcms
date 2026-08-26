<?php

namespace App\Http\Requests\Api\Staff;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class StoreTeacherAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'teacher_id' => [
                'required',
                'integer',
                Rule::exists('teachers', 'id'),
                Rule::unique('teacher_assignments')->where(
                    fn ($q) => $q->where('teacher_id', $this->input('teacher_id'))
                        ->where('class_id', $this->input('class_id'))
                        ->where('subject_id', $this->input('subject_id'))
                        ->where('academic_year_id', $this->input('academic_year_id'))
                ),
            ],
            'class_id' => [
                'required',
                'integer',
                Rule::exists('classes', 'id'),
            ],
            'subject_id' => [
                'required',
                'integer',
                Rule::exists('subjects', 'id'),
            ],
            'academic_year_id' => [
                'required',
                'integer',
                Rule::exists('academic_years', 'id'),
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
