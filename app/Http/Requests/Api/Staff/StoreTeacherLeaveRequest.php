<?php

namespace App\Http\Requests\Api\Staff;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class StoreTeacherLeaveRequest extends FormRequest
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
            ],
            'leave_type' => [
                'required',
                'string',
                Rule::in(['cuti', 'izin', 'sakit', 'dinas']),
            ],
            'start_date' => [
                'required',
                'date',
            ],
            'end_date' => [
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
