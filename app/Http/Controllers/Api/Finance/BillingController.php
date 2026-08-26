<?php

namespace App\Http\Controllers\Api\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Finance\StoreBillingRequest;
use App\Http\Requests\Api\Finance\UpdateBillingRequest;
use App\Http\Resources\Finance\BillingResource;
use App\Models\Finance\Billing;
use Illuminate\Http\JsonResponse;

class BillingController extends Controller
{
    public function index(\Illuminate\Http\Request $request): JsonResponse
    {
        $query = Billing::query()->with(['student', 'feeType', 'academicYear', 'semester']);

        if ($request->filled('student_id')) {
            $query->where('student_id', $request->input('student_id'));
        }

        if ($request->filled('fee_type_id')) {
            $query->where('fee_type_id', $request->input('fee_type_id'));
        }

        if ($request->filled('academic_year_id')) {
            $query->where('academic_year_id', $request->input('academic_year_id'));
        }

        if ($request->filled('semester_id')) {
            $query->where('semester_id', $request->input('semester_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $billings = $query->orderBy('id', 'desc')->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => 'Billings retrieved successfully',
            'data' => BillingResource::collection($billings),
            'meta' => [
                'current_page' => $billings->currentPage(),
                'per_page' => $billings->perPage(),
                'total' => $billings->total(),
                'last_page' => $billings->lastPage(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $billing = Billing::with(['student', 'feeType', 'academicYear', 'semester'])->find($id);

        if (!$billing) {
            return response()->json([
                'success' => false,
                'message' => 'Billing not found',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Billing retrieved successfully',
            'data' => new BillingResource($billing),
        ]);
    }

    public function store(StoreBillingRequest $request): JsonResponse
    {
        $billing = Billing::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Billing created successfully',
            'data' => new BillingResource($billing->load(['student', 'feeType', 'academicYear', 'semester'])),
        ], 201);
    }

    public function update(UpdateBillingRequest $request, int $id): JsonResponse
    {
        $billing = Billing::find($id);

        if (!$billing) {
            return response()->json([
                'success' => false,
                'message' => 'Billing not found',
                'data' => null,
            ], 404);
        }

        $billing->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Billing updated successfully',
            'data' => new BillingResource($billing->load(['student', 'feeType', 'academicYear', 'semester'])),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $billing = Billing::find($id);

        if (!$billing) {
            return response()->json([
                'success' => false,
                'message' => 'Billing not found',
                'data' => null,
            ], 404);
        }

        $billing->delete();

        return response()->json([
            'success' => true,
            'message' => 'Billing deleted successfully',
            'data' => null,
        ]);
    }
}
