<?php

namespace App\Http\Requests\Api\Facilities;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class StoreInventoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('inventories', 'code')->whereNull('deleted_at'),
            ],
            'name' => [
                'required',
                'string',
                'max:150',
            ],
            'description' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'category' => [
                'required',
                'string',
                Rule::in([
                    'stationery',
                    'electronics_supplies',
                    'cleaning',
                    'lab_supplies',
                    'office_supplies',
                    'other',
                ]),
            ],
            'unit' => [
                'required',
                'string',
                'max:20',
            ],
            'quantity' => [
                'required',
                'integer',
                'min:0',
                'max:99999',
            ],
            'minimum_stock' => [
                'required',
                'integer',
                'min:0',
                'max:99999',
            ],
            'location' => [
                'nullable',
                'string',
                'max:150',
            ],
            'room_id' => [
                'nullable',
                'integer',
                Rule::exists('rooms', 'id')->whereNull('deleted_at'),
            ],
            'status' => [
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
