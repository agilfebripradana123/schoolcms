<?php

namespace App\Http\Requests\Api\Facilities;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UpdateMaintenanceRequest extends FormRequest
{
    private const VALID_TRANSITIONS = [
        'pending' => ['pending', 'in_progress', 'cancelled'],
        'in_progress' => ['in_progress', 'completed', 'cancelled'],
        'completed' => ['completed'],
        'cancelled' => ['cancelled'],
    ];

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
                Rule::unique('maintenance', 'code')
                    ->whereNull('deleted_at')
                    ->ignore($this->route('maintenance')),
            ],
            'title' => [
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
            'asset_id' => [
                'nullable',
                'integer',
                Rule::exists('assets', 'id')->whereNull('deleted_at'),
            ],
            'room_id' => [
                'nullable',
                'integer',
                Rule::exists('rooms', 'id')->whereNull('deleted_at'),
            ],
            'reported_by' => [
                'nullable',
                'string',
                'max:100',
            ],
            'maintenance_type' => [
                'sometimes',
                'required',
                'string',
                Rule::in(['corrective', 'preventive', 'emergency', 'inspection']),
            ],
            'priority' => [
                'sometimes',
                'required',
                'string',
                Rule::in(['low', 'medium', 'high', 'urgent']),
            ],
            'status' => [
                'sometimes',
                'required',
                'string',
                Rule::in(['pending', 'in_progress', 'completed', 'cancelled']),
            ],
            'scheduled_date' => [
                'nullable',
                'date',
            ],
            'started_date' => [
                'nullable',
                'date',
            ],
            'completed_date' => [
                'nullable',
                'date',
            ],
            'estimated_cost' => [
                'nullable',
                'numeric',
                'min:0',
                'max:9999999999.99',
            ],
            'actual_cost' => [
                'nullable',
                'numeric',
                'min:0',
                'max:9999999999.99',
            ],
            'notes' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'resolution' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $maintenanceParam = $this->route('maintenance');
            $newStatus = $this->input('status');
            $completedDate = $this->input('completed_date');
            $startedDate = $this->input('started_date');
            $scheduledDate = $this->input('scheduled_date');

            if ($maintenanceParam instanceof \App\Models\Facilities\Maintenance) {
                $maintenance = $maintenanceParam;
            } elseif (is_numeric($maintenanceParam)) {
                $maintenance = \App\Models\Facilities\Maintenance::withTrashed()->find($maintenanceParam);
            } else {
                return;
            }

            if (!$maintenance) {
                return;
            }

            $currentStatus = $maintenance->status;
            $effectiveStatus = $newStatus ?? $currentStatus;

            if ($newStatus !== null && $newStatus !== $currentStatus) {
                $allowed = self::VALID_TRANSITIONS[$currentStatus] ?? [];
                if (!in_array($newStatus, $allowed)) {
                    $validator->errors()->add('status', "The status cannot be changed from {$currentStatus} to {$newStatus}.");
                }
            }

            if ($effectiveStatus === 'completed' && empty($completedDate)) {
                if ($newStatus === 'completed' || ($newStatus === null && $currentStatus === 'completed')) {
                    $validator->errors()->add('completed_date', 'The completed date is required when status is completed.');
                }
            }

            if (in_array($effectiveStatus, ['pending', 'in_progress', 'cancelled']) && !empty($completedDate)) {
                $validator->errors()->add('completed_date', 'The completed date must not be present when status is pending, in progress, or cancelled.');
            }

            if (!empty($startedDate) && !empty($scheduledDate) && $startedDate < $scheduledDate) {
                $validator->errors()->add('started_date', 'The started date must be after or equal to the scheduled date.');
            }

            if (!empty($completedDate) && !empty($startedDate) && $completedDate < $startedDate) {
                $validator->errors()->add('completed_date', 'The completed date must be after or equal to the started date.');
            }
        });
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
