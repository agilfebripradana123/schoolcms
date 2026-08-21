<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UpdateClassSubjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $classSubjectId = $this->route('class_subject')?->id ?? $this->route('class_subject');

        return [
            'class_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('classes', 'id')->whereNull('deleted_at'),
            ],
            'subject_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('subjects', 'id')->whereNull('deleted_at'),
            ],
            'teacher_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('teachers', 'id')->whereNull('deleted_at'),
            ],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->any()) {
                return;
            }

            $classId = $this->input('class_id');
            $subjectId = $this->input('subject_id');

            if ($classId && $subjectId) {
                $existingClassSubject = \App\Models\ClassSubject::where('class_id', $classId)
                    ->where('subject_id', $subjectId)
                    ->where('id', '!=', $this->route('class_subject')?->id ?? 0)
                    ->first();

                if ($existingClassSubject) {
                    $validator->errors()->add(
                        'class_id',
                        'The combination of class and subject already exists.'
                    );
                }
            }
        });
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
