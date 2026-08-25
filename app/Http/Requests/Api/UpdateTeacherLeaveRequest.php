<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UpdateTeacherLeaveRequest extends FormRequest
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
            'leave_type' => [
                'sometimes',
                'required',
                'string',
                Rule::in(['cuti', 'izin', 'sakit', 'dinas']),
            ],
            'start_date' => [
                'sometimes',
                'required',
                'date',
            ],
            'end_date' => [
                'sometimes',
                'required',
                'date',
                'after_or_equal:start_date',
            ],
            'reason' => [
                'nullable',
                'string',
            ],
            'status' => [
                'sometimes',
                'string',
                Rule::in(['menunggu', 'disetujui', 'ditolak']),
            ],
            'approved_by' => [
                'nullable',
                'integer',
                Rule::exists('teachers', 'id'),
            ],
            'approved_at' => [
                'nullable',
                'date',
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
