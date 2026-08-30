<?php

namespace App\Http\Controllers\Api\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Finance\StorePaymentRequest;
use App\Http\Requests\Api\Finance\UpdatePaymentRequest;
use App\Http\Resources\Finance\PaymentResource;
use App\Models\Finance\Billing;
use App\Models\Finance\Payment;
use App\Services\Finance\BillingService;
use App\Services\Finance\PaymentTransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentController extends Controller
{
    public function index(Request $request): JsonResponse
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

        if (! $payment) {
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

    public function store(StorePaymentRequest $request, BillingService $billingService): JsonResponse
    {
        $data = $request->validated();

        $payment = DB::transaction(function () use ($data, $billingService) {
            $billing = Billing::whereKey($data['billing_id'])->lockForUpdate()->first();

            if ($billing === null) {
                throw ValidationException::withMessages([
                    'billing_id' => 'The selected billing is not available.',
                ]);
            }

            $payment = Payment::create($data);
            $billingService->reconcile($billing);

            return $payment;
        });

        $payment->load(['billing', 'student', 'receivedBy']);

        return response()->json([
            'success' => true,
            'message' => 'Payment created successfully',
            'data' => new PaymentResource($payment),
        ], 201);
    }

    public function update(UpdatePaymentRequest $request, int $id, BillingService $billingService): JsonResponse
    {
        $payment = Payment::find($id);

        if (! $payment) {
            return response()->json([
                'success' => false,
                'message' => 'Payment not found',
                'data' => null,
            ], 404);
        }

        DB::transaction(function () use ($request, $payment, $billingService) {
            $payment = Payment::whereKey($payment->id)->lockForUpdate()->first();

            $oldBillingId = $payment->billing_id;

            $payment->update($request->validated());

            $newBillingId = $payment->billing_id;

            $billingService->reconcileMany([$oldBillingId, $newBillingId]);
        });

        $payment = Payment::with(['billing', 'student', 'receivedBy'])->find($id);

        return response()->json([
            'success' => true,
            'message' => 'Payment updated successfully',
            'data' => new PaymentResource($payment),
        ]);
    }

    public function destroy(int $id, BillingService $billingService, PaymentTransactionService $transactionService): JsonResponse
    {
        $payment = Payment::find($id);

        if (! $payment) {
            return response()->json([
                'success' => false,
                'message' => 'Payment not found',
                'data' => null,
            ], 404);
        }

        if ($transactionService->hasLedgerHistory($payment)) {
            return response()->json([
                'success' => false,
                'message' => 'Payment cannot be deleted because it has ledger transaction history.',
                'data' => null,
            ], 409);
        }

        DB::transaction(function () use ($payment, $billingService) {
            $payment = Payment::whereKey($payment->id)->lockForUpdate()->first();

            $billingId = $payment->billing_id;

            $payment->delete();

            $billingService->reconcileMany([$billingId]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Payment deleted successfully',
            'data' => null,
        ]);
    }
}
