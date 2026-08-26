<?php

namespace App\Http\Controllers\Api\Students;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Students\StoreGuardianRequest;
use App\Http\Requests\Api\Students\UpdateGuardianRequest;
use App\Http\Resources\Students\GuardianResource;
use App\Models\Students\Guardian;
use Illuminate\Http\JsonResponse;

class GuardianController extends Controller
{
    public function index(\Illuminate\Http\Request $request): JsonResponse
    {
        $query = Guardian::query()->with('student');

        if ($request->filled('student_id')) {
            $query->where('student_id', $request->input('student_id'));
        }

        $guardians = $query->orderBy('id', 'desc')->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => 'Guardians retrieved successfully',
            'data' => GuardianResource::collection($guardians),
            'meta' => [
                'current_page' => $guardians->currentPage(),
                'per_page' => $guardians->perPage(),
                'total' => $guardians->total(),
                'last_page' => $guardians->lastPage(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $guardian = Guardian::with('student')->find($id);

        if (!$guardian) {
            return response()->json([
                'success' => false,
                'message' => 'Guardian not found',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Guardian retrieved successfully',
            'data' => new GuardianResource($guardian),
        ]);
    }

    public function store(StoreGuardianRequest $request): JsonResponse
    {
        $guardian = Guardian::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Guardian created successfully',
            'data' => new GuardianResource($guardian->load('student')),
        ], 201);
    }

    public function update(UpdateGuardianRequest $request, int $id): JsonResponse
    {
        $guardian = Guardian::find($id);

        if (!$guardian) {
            return response()->json([
                'success' => false,
                'message' => 'Guardian not found',
                'data' => null,
            ], 404);
        }

        $guardian->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Guardian updated successfully',
            'data' => new GuardianResource($guardian->load('student')),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $guardian = Guardian::find($id);

        if (!$guardian) {
            return response()->json([
                'success' => false,
                'message' => 'Guardian not found',
                'data' => null,
            ], 404);
        }

        $guardian->delete();

        return response()->json([
            'success' => true,
            'message' => 'Guardian deleted successfully',
            'data' => null,
        ]);
    }
}
