<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UpdateTeacherAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'teacher_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('teachers', 'id'),
            ],
            'date' => [
                'sometimes',
                'required',
                'date',
                Rule::unique('teacher_attendance')->where(fn ($q) => $q
                    ->where('teacher_id', $this->input('teacher_id'))
                    ->where('date', $this->input('date')))
                    ->ignore($this->route('teacher_attendance')),
            ],
            'status' => [
                'sometimes',
                'required',
                'string',
                Rule::in(['hadir', 'sakit', 'izin', 'alfa', 'terlambat']),
            ],
            'check_in' => [
                'nullable',
                'date_format:H:i',
            ],
            'check_out' => [
                'nullable',
                'date_format:H:i',
                'after:check_in',
            ],
            'notes' => [
                'nullable',
                'string',
                'max:255',
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
