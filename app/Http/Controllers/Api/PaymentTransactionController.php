<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StorePaymentTransactionRequest;
use App\Http\Requests\Api\UpdatePaymentTransactionRequest;
use App\Http\Resources\PaymentTransactionResource;
use App\Models\PaymentTransaction;
use Illuminate\Http\JsonResponse;

class PaymentTransactionController extends Controller
{
    public function index(\Illuminate\Http\Request $request): JsonResponse
    {
        $query = PaymentTransaction::query()->with('payment');

        if ($request->filled('payment_id')) {
            $query->where('payment_id', $request->input('payment_id'));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $transactions = $query->orderBy('id', 'desc')->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => 'Payment transactions retrieved successfully',
            'data' => PaymentTransactionResource::collection($transactions),
            'meta' => [
                'current_page' => $transactions->currentPage(),
                'per_page' => $transactions->perPage(),
                'total' => $transactions->total(),
                'last_page' => $transactions->lastPage(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $transaction = PaymentTransaction::with('payment')->find($id);

        if (!$transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Payment transaction not found',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Payment transaction retrieved successfully',
            'data' => new PaymentTransactionResource($transaction),
        ]);
    }

    public function store(StorePaymentTransactionRequest $request): JsonResponse
    {
        $transaction = PaymentTransaction::create($request->validated());
        $transaction->load('payment');

        return response()->json([
            'success' => true,
            'message' => 'Payment transaction created successfully',
            'data' => new PaymentTransactionResource($transaction),
        ], 201);
    }

    public function update(UpdatePaymentTransactionRequest $request, int $id): JsonResponse
    {
        $transaction = PaymentTransaction::find($id);

        if (!$transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Payment transaction not found',
                'data' => null,
            ], 404);
        }

        $transaction->update($request->validated());
        $transaction->load('payment');

        return response()->json([
            'success' => true,
            'message' => 'Payment transaction updated successfully',
            'data' => new PaymentTransactionResource($transaction),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $transaction = PaymentTransaction::find($id);

        if (!$transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Payment transaction not found',
                'data' => null,
            ], 404);
        }

        $transaction->delete();

        return response()->json([
            'success' => true,
            'message' => 'Payment transaction deleted successfully',
            'data' => null,
        ]);
    }
}
