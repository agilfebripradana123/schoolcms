<?php

namespace App\Http\Controllers\Api\Students\Finance;

use App\Http\Controllers\Controller;
use App\Http\Resources\Student\Finance\StudentBillingResource;
use App\Models\Finance\Billing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StudentBillingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $student = $request->attributes->get('student_profile');

        $validated = $request->validate([
            'student_id' => ['prohibited'],
            'semester_id' => ['nullable', 'integer', Rule::exists('semesters', 'id')],
            'academic_year_id' => ['nullable', 'integer', Rule::exists('academic_years', 'id')],
            'fee_type_id' => ['nullable', 'integer', Rule::exists('fee_types', 'id')],
            'status' => ['nullable', 'string', Rule::in(['unpaid', 'partial', 'paid', 'cancelled'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $query = Billing::with(['feeType', 'academicYear', 'semester', 'payments.transactions'])
            ->where('student_id', $student->id);

        // Cancelled rows stay available through status=cancelled but are not
        // part of the normal (unfiltered) list.
        if (! isset($validated['status'])) {
            $query->where('status', '!=', 'cancelled');
        }

        foreach (['semester_id', 'academic_year_id', 'fee_type_id', 'status'] as $filter) {
            if (isset($validated[$filter])) {
                $query->where($filter, $validated[$filter]);
            }
        }

        $billings = $query->orderBy('id', 'desc')->paginate($validated['per_page'] ?? 15);

        return response()->json([
            'success' => true,
            'message' => 'Billings retrieved successfully',
            'data' => StudentBillingResource::collection($billings),
            'meta' => [
                'current_page' => $billings->currentPage(),
                'per_page' => $billings->perPage(),
                'total' => $billings->total(),
                'last_page' => $billings->lastPage(),
            ],
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $student = $request->attributes->get('student_profile');

        $request->validate(['student_id' => ['prohibited']]);

        $billing = Billing::with(['feeType', 'academicYear', 'semester', 'payments.transactions'])
            ->where('student_id', $student->id)
            ->whereKey($id)
            ->first();

        if ($billing === null) {
            return response()->json([
                'success' => false,
                'message' => 'Billing not found',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Billing retrieved successfully',
            'data' => new StudentBillingResource($billing),
        ]);
    }
}
