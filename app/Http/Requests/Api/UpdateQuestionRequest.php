<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UpdateQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $type = $this->input('type');
        $questionId = $this->route('question');

        $rules = [
            'subject_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('subjects', 'id')->whereNull('deleted_at'),
            ],
            'instruction_id' => [
                'nullable',
                'integer',
                Rule::exists('exam_instructions', 'id'),
            ],
            'question_text' => [
                'sometimes',
                'required',
                'string',
                'max:10000',
            ],
            'question_image' => [
                'nullable',
                'string',
                'max:255',
            ],
            'type' => [
                'sometimes',
                'required',
                'string',
                Rule::in(['multiple_choice', 'true_false', 'essay']),
            ],
            'difficulty' => [
                'sometimes',
                'required',
                'string',
                Rule::in(['easy', 'medium', 'hard']),
            ],
            'explanation' => [
                'nullable',
                'string',
                'max:10000',
            ],
            'points' => [
                'sometimes',
                'required',
                'integer',
                'min:1',
                'max:1000',
            ],
            'options' => [
                'nullable',
                'array',
                'min:0',
            ],
            'options.*.option_text' => [
                'required_with:options',
                'string',
                'max:5000',
            ],
            'options.*.option_image' => [
                'nullable',
                'string',
                'max:255',
            ],
            'options.*.is_correct' => [
                'required_with:options',
                'boolean',
            ],
        ];

        if ($type === 'multiple_choice') {
            $rules['options'] = ['required', 'array', 'min:2'];
        } elseif ($type === 'true_false') {
            $rules['options'] = ['required', 'array', 'size:2'];
        } elseif ($type === 'essay') {
            $rules['options'] = ['nullable', 'array', 'size:0'];
        }

        return $rules;
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
