<?php

namespace App\Http\Controllers\Api\Students\Finance;

use App\Http\Controllers\Controller;
use App\Http\Resources\Student\Finance\StudentPaymentTransactionResource;
use App\Models\Finance\PaymentTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StudentTransactionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $student = $request->attributes->get('student_profile');

        $validated = $request->validate([
            'student_id' => ['prohibited'],
            'type' => ['nullable', 'string', Rule::in(['payment', 'refund', 'adjustment'])],
            'status' => ['nullable', 'string', Rule::in(['success', 'pending', 'failed'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $query = PaymentTransaction::with(['payment.billing.feeType'])
            ->whereHas('payment', fn ($q) => $q->where('student_id', $student->id));

        foreach (['type', 'status'] as $filter) {
            if (isset($validated[$filter])) {
                $query->where($filter, $validated[$filter]);
            }
        }

        $transactions = $query->orderBy('id', 'desc')->paginate($validated['per_page'] ?? 15);

        return response()->json([
            'success' => true,
            'message' => 'Payment transactions retrieved successfully',
            'data' => StudentPaymentTransactionResource::collection($transactions),
            'meta' => [
                'current_page' => $transactions->currentPage(),
                'per_page' => $transactions->perPage(),
                'total' => $transactions->total(),
                'last_page' => $transactions->lastPage(),
            ],
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $student = $request->attributes->get('student_profile');

        $request->validate(['student_id' => ['prohibited']]);

        $transaction = PaymentTransaction::with(['payment.billing.feeType'])
            ->whereHas('payment', fn ($q) => $q->where('student_id', $student->id))
            ->whereKey($id)
            ->first();

        if ($transaction === null) {
            return response()->json([
                'success' => false,
                'message' => 'Payment transaction not found',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Payment transaction retrieved successfully',
            'data' => new StudentPaymentTransactionResource($transaction),
        ]);
    }
}
