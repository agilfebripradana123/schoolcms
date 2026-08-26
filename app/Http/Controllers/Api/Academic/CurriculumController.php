<?php

namespace App\Http\Controllers\Api\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Academic\StoreCurriculumRequest;
use App\Http\Requests\Api\Academic\UpdateCurriculumRequest;
use App\Http\Resources\Academic\CurriculumResource;
use App\Models\Academic\Curriculum;
use Illuminate\Http\JsonResponse;

class CurriculumController extends Controller
{
    public function index(\Illuminate\Http\Request $request): JsonResponse
    {
        $query = Curriculum::query();

        if ($request->filled('q')) {
            $q = $request->input('q');
            $query->where('name', 'LIKE', "%{$q}%");
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active') ? 1 : 0);
        }

        $curriculums = $query->orderBy('id', 'desc')->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => 'Curriculums retrieved successfully',
            'data' => CurriculumResource::collection($curriculums),
            'meta' => [
                'current_page' => $curriculums->currentPage(),
                'per_page' => $curriculums->perPage(),
                'total' => $curriculums->total(),
                'last_page' => $curriculums->lastPage(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $curriculum = Curriculum::find($id);

        if (!$curriculum) {
            return response()->json([
                'success' => false,
                'message' => 'Curriculum not found',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Curriculum retrieved successfully',
            'data' => new CurriculumResource($curriculum),
        ]);
    }

    public function store(StoreCurriculumRequest $request): JsonResponse
    {
        $curriculum = Curriculum::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Curriculum created successfully',
            'data' => new CurriculumResource($curriculum),
        ], 201);
    }

    public function update(UpdateCurriculumRequest $request, int $id): JsonResponse
    {
        $curriculum = Curriculum::find($id);

        if (!$curriculum) {
            return response()->json([
                'success' => false,
                'message' => 'Curriculum not found',
                'data' => null,
            ], 404);
        }

        $curriculum->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Curriculum updated successfully',
            'data' => new CurriculumResource($curriculum),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $curriculum = Curriculum::find($id);

        if (!$curriculum) {
            return response()->json([
                'success' => false,
                'message' => 'Curriculum not found',
                'data' => null,
            ], 404);
        }

        $curriculum->delete();

        return response()->json([
            'success' => true,
            'message' => 'Curriculum deleted successfully',
            'data' => null,
        ]);
    }
}
