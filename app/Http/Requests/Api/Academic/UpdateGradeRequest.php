<?php

namespace App\Http\Requests\Api\Academic;

use App\Models\Academic\ClassSubject;
use App\Models\Academic\Grade;
use App\Models\Students\Student;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UpdateGradeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('students', 'id')->whereNull('deleted_at'),
            ],
            'subject_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('subjects', 'id')->whereNull('deleted_at'),
            ],
            'class_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('classes', 'id')->whereNull('deleted_at'),
            ],
            'type' => [
                'sometimes',
                'required',
                'string',
                Rule::in(['tugas', 'uts', 'uas']),
            ],
            'score' => [
                'sometimes',
                'required',
                'numeric',
                'min:0',
                'max:100',
            ],
            'semester' => [
                'sometimes',
                'required',
                'string',
                Rule::in(['1', '2']),
            ],
            'academic_year' => [
                'sometimes',
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

            $gradeId = $this->route('grade')?->id ?? $this->route('grade');

            $existingGrade = Grade::find($gradeId);

            if (!$existingGrade) {
                return;
            }

            $studentId = $this->input('student_id') ?? $existingGrade->student_id;
            $subjectId = $this->input('subject_id') ?? $existingGrade->subject_id;
            $classId = $this->input('class_id') ?? $existingGrade->class_id;
            $type = $this->input('type') ?? $existingGrade->type;
            $semester = $this->input('semester') ?? $existingGrade->semester;
            $academicYear = $this->input('academic_year') ?? $existingGrade->academic_year;

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

            $exists = Grade::where('student_id', $studentId)
                ->where('subject_id', $subjectId)
                ->where('class_id', $classId)
                ->where('type', $type)
                ->where('semester', $semester)
                ->where('academic_year', $academicYear)
                ->where('id', '!=', $gradeId)
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
