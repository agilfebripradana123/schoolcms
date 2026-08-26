<?php

namespace App\Http\Requests\Api\Examination;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class StoreExamResultRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'participant_id' => [
                'required',
                'integer',
                Rule::exists('exam_participants', 'id'),
                Rule::unique('exam_results', 'participant_id'),
            ],
            'total_score' => [
                'numeric',
                'min:0',
            ],
            'correct_count' => [
                'integer',
                'min:0',
            ],
            'wrong_count' => [
                'integer',
                'min:0',
            ],
            'unanswered_count' => [
                'integer',
                'min:0',
            ],
            'grade' => [
                'nullable',
                'string',
                'max:5',
            ],
            'status' => [
                'required',
                'string',
                Rule::in(['pending', 'graded']),
            ],
            'graded_at' => [
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
