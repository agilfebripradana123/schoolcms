<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreDispositionRequest;
use App\Http\Requests\Api\UpdateDispositionRequest;
use App\Http\Resources\DispositionResource;
use App\Models\Disposition;
use Illuminate\Http\JsonResponse;

class DispositionController extends Controller
{
    public function index(\Illuminate\Http\Request $request): JsonResponse
    {
        $query = Disposition::query()->with('incomingLetter');

        if ($request->filled('incoming_letter_id')) {
            $query->where('incoming_letter_id', $request->input('incoming_letter_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $dispositions = $query->orderBy('id', 'desc')->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => 'Dispositions retrieved successfully',
            'data' => DispositionResource::collection($dispositions),
            'meta' => [
                'current_page' => $dispositions->currentPage(),
                'per_page' => $dispositions->perPage(),
                'total' => $dispositions->total(),
                'last_page' => $dispositions->lastPage(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $disposition = Disposition::with('incomingLetter')->find($id);

        if (!$disposition) {
            return response()->json([
                'success' => false,
                'message' => 'Disposition not found',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Disposition retrieved successfully',
            'data' => new DispositionResource($disposition),
        ]);
    }

    public function store(StoreDispositionRequest $request): JsonResponse
    {
        $disposition = Disposition::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Disposition created successfully',
            'data' => new DispositionResource($disposition->load('incomingLetter')),
        ], 201);
    }

    public function update(UpdateDispositionRequest $request, int $id): JsonResponse
    {
        $disposition = Disposition::find($id);

        if (!$disposition) {
            return response()->json([
                'success' => false,
                'message' => 'Disposition not found',
                'data' => null,
            ], 404);
        }

        $disposition->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Disposition updated successfully',
            'data' => new DispositionResource($disposition->load('incomingLetter')),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $disposition = Disposition::find($id);

        if (!$disposition) {
            return response()->json([
                'success' => false,
                'message' => 'Disposition not found',
                'data' => null,
            ], 404);
        }

        $disposition->delete();

        return response()->json([
            'success' => true,
            'message' => 'Disposition deleted successfully',
            'data' => null,
        ]);
    }
}
