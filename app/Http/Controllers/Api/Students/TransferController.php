<?php

namespace App\Http\Controllers\Api\Students;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Students\StoreTransferRequest;
use App\Http\Requests\Api\Students\UpdateTransferRequest;
use App\Http\Resources\Students\TransferResource;
use App\Models\Students\Transfer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransferController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'student_id' => 'nullable|integer',
            'type' => 'nullable|string|in:masuk,keluar',
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
        ]);

        $query = Transfer::query()->with('student');

        if (!empty($validated['student_id'])) {
            $query->where('student_id', $validated['student_id']);
        }

        if (!empty($validated['type'])) {
            $query->where('type', $validated['type']);
        }

        $transfers = $query->orderBy('id', 'desc')->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => 'Transfers retrieved successfully',
            'data' => TransferResource::collection($transfers),
            'meta' => [
                'current_page' => $transfers->currentPage(),
                'per_page' => $transfers->perPage(),
                'total' => $transfers->total(),
                'last_page' => $transfers->lastPage(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $transfer = Transfer::with('student')->find($id);

        if (!$transfer) {
            return response()->json([
                'success' => false,
                'message' => 'Transfer not found',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Transfer retrieved successfully',
            'data' => new TransferResource($transfer),
        ]);
    }

    public function store(StoreTransferRequest $request): JsonResponse
    {
        $transfer = Transfer::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Transfer created successfully',
            'data' => new TransferResource($transfer->load('student')),
        ], 201);
    }

    public function update(UpdateTransferRequest $request, int $id): JsonResponse
    {
        $transfer = Transfer::find($id);

        if (!$transfer) {
            return response()->json([
                'success' => false,
                'message' => 'Transfer not found',
                'data' => null,
            ], 404);
        }

        $transfer->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Transfer updated successfully',
            'data' => new TransferResource($transfer->load('student')),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $transfer = Transfer::find($id);

        if (!$transfer) {
            return response()->json([
                'success' => false,
                'message' => 'Transfer not found',
                'data' => null,
            ], 404);
        }

        $transfer->delete();

        return response()->json([
            'success' => true,
            'message' => 'Transfer deleted successfully',
            'data' => null,
        ]);
    }
}
