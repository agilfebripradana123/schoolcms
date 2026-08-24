<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UpdateExamResultRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'participant_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('exam_participants', 'id'),
                Rule::unique('exam_results', 'participant_id')
                    ->ignore($this->route('exam_result')),
            ],
            'total_score' => [
                'sometimes',
                'numeric',
                'min:0',
            ],
            'correct_count' => [
                'sometimes',
                'integer',
                'min:0',
            ],
            'wrong_count' => [
                'sometimes',
                'integer',
                'min:0',
            ],
            'unanswered_count' => [
                'sometimes',
                'integer',
                'min:0',
            ],
            'grade' => [
                'nullable',
                'string',
                'max:5',
            ],
            'status' => [
                'sometimes',
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
