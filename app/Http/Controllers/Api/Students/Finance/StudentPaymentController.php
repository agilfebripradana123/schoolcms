<?php

namespace App\Http\Controllers\Api\Students\Finance;

use App\Http\Controllers\Controller;
use App\Http\Resources\Student\Finance\StudentPaymentResource;
use App\Models\Finance\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StudentPaymentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $student = $request->attributes->get('student_profile');

        $validated = $request->validate([
            'student_id' => ['prohibited'],
            'method' => ['nullable', 'string', Rule::in(['cash', 'transfer', 'qris', 'lainnya'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $query = Payment::with(['billing.feeType'])
            ->where('student_id', $student->id);

        if (isset($validated['method'])) {
            $query->where('method', $validated['method']);
        }

        $payments = $query->orderBy('id', 'desc')->paginate($validated['per_page'] ?? 15);

        return response()->json([
            'success' => true,
            'message' => 'Payments retrieved successfully',
            'data' => StudentPaymentResource::collection($payments),
            'meta' => [
                'current_page' => $payments->currentPage(),
                'per_page' => $payments->perPage(),
                'total' => $payments->total(),
                'last_page' => $payments->lastPage(),
            ],
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $student = $request->attributes->get('student_profile');

        $request->validate(['student_id' => ['prohibited']]);

        $payment = Payment::with(['billing.feeType'])
            ->where('student_id', $student->id)
            ->whereKey($id)
            ->first();

        if ($payment === null) {
            return response()->json([
                'success' => false,
                'message' => 'Payment not found',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Payment retrieved successfully',
            'data' => new StudentPaymentResource($payment),
        ]);
    }
}
