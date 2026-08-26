<?php

namespace App\Http\Requests\Api\Facilities;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UpdateAssetRequest extends FormRequest
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
                Rule::unique('assets', 'code')
                    ->whereNull('deleted_at')
                    ->ignore($this->route('asset')),
            ],
            'name' => [
                'sometimes',
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
                'sometimes',
                'required',
                'string',
                Rule::in([
                    'electronics',
                    'furniture',
                    'lab_equipment',
                    'sports',
                    'teaching_aids',
                    'office',
                    'other',
                ]),
            ],
            'quantity' => [
                'sometimes',
                'required',
                'integer',
                'min:1',
                'max:10000',
            ],
            'condition' => [
                'sometimes',
                'required',
                'string',
                Rule::in(['good', 'fair', 'poor', 'damaged']),
            ],
            'location' => [
                'nullable',
                'string',
                'max:150',
            ],
            'room_id' => [
                'nullable',
                'integer',
                'exists:rooms,id',
            ],
            'purchase_date' => [
                'nullable',
                'date',
                'before_or_equal:today',
            ],
            'purchase_price' => [
                'nullable',
                'numeric',
                'min:0',
                'max:9999999999.99',
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
