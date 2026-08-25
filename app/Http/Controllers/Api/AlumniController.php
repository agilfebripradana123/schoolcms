<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreAlumniRequest;
use App\Http\Requests\Api\UpdateAlumniRequest;
use App\Http\Resources\AlumniResource;
use App\Models\Alumni;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AlumniController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'graduation_year' => 'nullable|integer',
            'q' => 'nullable|string|max:100',
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
        ]);

        $query = Alumni::query()->with('student');

        if (!empty($validated['graduation_year'])) {
            $query->where('graduation_year', $validated['graduation_year']);
        }

        if (!empty($validated['q'])) {
            $query->where('name', 'LIKE', "%{$validated['q']}%");
        }

        $alumni = $query->orderBy('id', 'desc')->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => 'Alumni retrieved successfully',
            'data' => AlumniResource::collection($alumni),
            'meta' => [
                'current_page' => $alumni->currentPage(),
                'per_page' => $alumni->perPage(),
                'total' => $alumni->total(),
                'last_page' => $alumni->lastPage(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $alumni = Alumni::with('student')->find($id);

        if (!$alumni) {
            return response()->json([
                'success' => false,
                'message' => 'Alumni not found',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Alumni retrieved successfully',
            'data' => new AlumniResource($alumni),
        ]);
    }

    public function store(StoreAlumniRequest $request): JsonResponse
    {
        $alumni = Alumni::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Alumni created successfully',
            'data' => new AlumniResource($alumni->load('student')),
        ], 201);
    }

    public function update(UpdateAlumniRequest $request, int $id): JsonResponse
    {
        $alumni = Alumni::find($id);

        if (!$alumni) {
            return response()->json([
                'success' => false,
                'message' => 'Alumni not found',
                'data' => null,
            ], 404);
        }

        $alumni->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Alumni updated successfully',
            'data' => new AlumniResource($alumni->load('student')),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $alumni = Alumni::find($id);

        if (!$alumni) {
            return response()->json([
                'success' => false,
                'message' => 'Alumni not found',
                'data' => null,
            ], 404);
        }

        $alumni->delete();

        return response()->json([
            'success' => true,
            'message' => 'Alumni deleted successfully',
            'data' => null,
        ]);
    }
}
