<?php

namespace App\Http\Requests\Api\Development;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UpdateAchievementRequest extends FormRequest
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
            'title' => [
                'sometimes',
                'required',
                'string',
                'max:200',
            ],
            'level' => [
                'sometimes',
                'required',
                'string',
                Rule::in(['sekolah', 'kecamatan', 'kota', 'provinsi', 'nasional', 'internasional']),
            ],
            'organizer' => [
                'nullable',
                'string',
                'max:150',
            ],
            'achievement_date' => [
                'nullable',
                'date',
            ],
            'description' => [
                'nullable',
                'string',
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
