<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreExtracurricularRequest;
use App\Http\Requests\Api\UpdateExtracurricularRequest;
use App\Http\Resources\ExtracurricularResource;
use App\Models\Extracurricular;
use Illuminate\Http\JsonResponse;

class ExtracurricularController extends Controller
{
    public function index(\Illuminate\Http\Request $request): JsonResponse
    {
        $query = Extracurricular::query()->with('supervisor');

        if ($request->filled('q')) {
            $q = $request->input('q');
            $query->where('name', 'LIKE', "%{$q}%");
        }

        if ($request->filled('supervisor_id')) {
            $query->where('supervisor_id', $request->input('supervisor_id'));
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active') ? 1 : 0);
        }

        $extracurriculums = $query->orderBy('id', 'desc')->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => 'Extracurriculums retrieved successfully',
            'data' => ExtracurricularResource::collection($extracurriculums),
            'meta' => [
                'current_page' => $extracurriculums->currentPage(),
                'per_page' => $extracurriculums->perPage(),
                'total' => $extracurriculums->total(),
                'last_page' => $extracurriculums->lastPage(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $extracurricular = Extracurricular::with('supervisor')->find($id);

        if (!$extracurricular) {
            return response()->json([
                'success' => false,
                'message' => 'Extracurricular not found',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Extracurricular retrieved successfully',
            'data' => new ExtracurricularResource($extracurricular),
        ]);
    }

    public function store(StoreExtracurricularRequest $request): JsonResponse
    {
        $extracurricular = Extracurricular::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Extracurricular created successfully',
            'data' => new ExtracurricularResource($extracurricular->load('supervisor')),
        ], 201);
    }

    public function update(UpdateExtracurricularRequest $request, int $id): JsonResponse
    {
        $extracurricular = Extracurricular::find($id);

        if (!$extracurricular) {
            return response()->json([
                'success' => false,
                'message' => 'Extracurricular not found',
                'data' => null,
            ], 404);
        }

        $extracurricular->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Extracurricular updated successfully',
            'data' => new ExtracurricularResource($extracurricular->load('supervisor')),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $extracurricular = Extracurricular::find($id);

        if (!$extracurricular) {
            return response()->json([
                'success' => false,
                'message' => 'Extracurricular not found',
                'data' => null,
            ], 404);
        }

        $extracurricular->delete();

        return response()->json([
            'success' => true,
            'message' => 'Extracurricular deleted successfully',
            'data' => null,
        ]);
    }
}
