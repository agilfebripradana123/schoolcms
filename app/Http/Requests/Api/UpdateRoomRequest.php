<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UpdateRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:20',
                Rule::unique('rooms', 'code')
                    ->whereNull('deleted_at')
                    ->ignore($this->route('room')),
            ],
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:100',
            ],
            'capacity' => [
                'sometimes',
                'required',
                'integer',
                'min:0',
                'max:10000',
            ],
            'location' => [
                'nullable',
                'string',
                'max:150',
            ],
            'has_computer' => [
                'sometimes',
                'required',
                'boolean',
            ],
            'status' => [
                'sometimes',
                'required',
                'string',
                Rule::in(['active', 'inactive']),
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
