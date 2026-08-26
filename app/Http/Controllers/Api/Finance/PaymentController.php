<?php

namespace App\Http\Controllers\Api\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Finance\StorePaymentRequest;
use App\Http\Requests\Api\Finance\UpdatePaymentRequest;
use App\Http\Resources\Finance\PaymentResource;
use App\Models\Finance\Payment;
use Illuminate\Http\JsonResponse;

class PaymentController extends Controller
{
    public function index(\Illuminate\Http\Request $request): JsonResponse
    {
        $query = Payment::query()->with(['billing', 'student', 'receivedBy']);

        if ($request->filled('billing_id')) {
            $query->where('billing_id', $request->input('billing_id'));
        }

        if ($request->filled('student_id')) {
            $query->where('student_id', $request->input('student_id'));
        }

        if ($request->filled('method')) {
            $query->where('method', $request->input('method'));
        }

        if ($request->filled('payment_date')) {
            $query->whereDate('payment_date', $request->input('payment_date'));
        }

        $payments = $query->orderBy('id', 'desc')->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => 'Payments retrieved successfully',
            'data' => PaymentResource::collection($payments),
            'meta' => [
                'current_page' => $payments->currentPage(),
                'per_page' => $payments->perPage(),
                'total' => $payments->total(),
                'last_page' => $payments->lastPage(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $payment = Payment::with(['billing', 'student', 'receivedBy'])->find($id);

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'Payment not found',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Payment retrieved successfully',
            'data' => new PaymentResource($payment),
        ]);
    }

    public function store(StorePaymentRequest $request): JsonResponse
    {
        $payment = Payment::create($request->validated());
        $payment->load(['billing', 'student', 'receivedBy']);

        return response()->json([
            'success' => true,
            'message' => 'Payment created successfully',
            'data' => new PaymentResource($payment),
        ], 201);
    }

    public function update(UpdatePaymentRequest $request, int $id): JsonResponse
    {
        $payment = Payment::find($id);

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'Payment not found',
                'data' => null,
            ], 404);
        }

        $payment->update($request->validated());
        $payment->load(['billing', 'student', 'receivedBy']);

        return response()->json([
            'success' => true,
            'message' => 'Payment updated successfully',
            'data' => new PaymentResource($payment),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $payment = Payment::find($id);

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'Payment not found',
                'data' => null,
            ], 404);
        }

        $payment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Payment deleted successfully',
            'data' => null,
        ]);
    }
}
