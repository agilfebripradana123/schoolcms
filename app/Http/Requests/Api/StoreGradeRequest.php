<?php

namespace App\Http\Requests\Api;

use App\Models\ClassSubject;
use App\Models\Student;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class StoreGradeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student_id' => [
                'required',
                'integer',
                Rule::exists('students', 'id')->whereNull('deleted_at'),
            ],
            'subject_id' => [
                'required',
                'integer',
                Rule::exists('subjects', 'id')->whereNull('deleted_at'),
            ],
            'class_id' => [
                'required',
                'integer',
                Rule::exists('classes', 'id')->whereNull('deleted_at'),
            ],
            'type' => [
                'required',
                'string',
                Rule::in(['tugas', 'uts', 'uas']),
            ],
            'score' => [
                'required',
                'numeric',
                'min:0',
                'max:100',
            ],
            'semester' => [
                'required',
                'string',
                Rule::in(['1', '2']),
            ],
            'academic_year' => [
                'required',
                'string',
                'regex:/^\d{4}\/\d{4}$/',
            ],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->any()) {
                return;
            }

            $studentId = $this->input('student_id');
            $subjectId = $this->input('subject_id');
            $classId = $this->input('class_id');

            $student = Student::where('id', $studentId)->whereNull('deleted_at')->first();

            if (!$student) {
                $validator->errors()->add('student_id', 'The selected student is invalid.');
                return;
            }

            if (is_null($student->class_id)) {
                $validator->errors()->add('student_id', 'The selected student has not been assigned to a class.');
                return;
            }

            if ($student->class_id != $classId) {
                $validator->errors()->add('student_id', 'The selected student does not belong to the specified class.');
                return;
            }

            $classSubjectExists = ClassSubject::where('class_id', $classId)
                ->where('subject_id', $subjectId)
                ->exists();

            if (!$classSubjectExists) {
                $validator->errors()->add('subject_id', 'The selected subject is not assigned to the specified class.');
                return;
            }

            $exists = \App\Models\Grade::where('student_id', $studentId)
                ->where('subject_id', $subjectId)
                ->where('class_id', $classId)
                ->where('type', $this->input('type'))
                ->where('semester', $this->input('semester'))
                ->where('academic_year', $this->input('academic_year'))
                ->exists();

            if ($exists) {
                $validator->errors()->add('student_id', 'A grade for this student, subject, class, type, semester, and academic year already exists.');
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
