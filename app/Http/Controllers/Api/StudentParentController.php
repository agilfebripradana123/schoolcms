<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreStudentParentRequest;
use App\Http\Requests\Api\UpdateStudentParentRequest;
use App\Http\Resources\StudentParentResource;
use App\Models\StudentParent;
use Illuminate\Http\JsonResponse;

class StudentParentController extends Controller
{
    public function index(\Illuminate\Http\Request $request): JsonResponse
    {
        $query = StudentParent::query()->with('student');

        if ($request->filled('student_id')) {
            $query->where('student_id', $request->input('student_id'));
        }

        $parents = $query->orderBy('id', 'desc')->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => 'Parents retrieved successfully',
            'data' => StudentParentResource::collection($parents),
            'meta' => [
                'current_page' => $parents->currentPage(),
                'per_page' => $parents->perPage(),
                'total' => $parents->total(),
                'last_page' => $parents->lastPage(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $parent = StudentParent::with('student')->find($id);

        if (!$parent) {
            return response()->json([
                'success' => false,
                'message' => 'Parent not found',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Parent retrieved successfully',
            'data' => new StudentParentResource($parent),
        ]);
    }

    public function store(StoreStudentParentRequest $request): JsonResponse
    {
        $parent = StudentParent::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Parent created successfully',
            'data' => new StudentParentResource($parent->load('student')),
        ], 201);
    }

    public function update(UpdateStudentParentRequest $request, int $id): JsonResponse
    {
        $parent = StudentParent::find($id);

        if (!$parent) {
            return response()->json([
                'success' => false,
                'message' => 'Parent not found',
                'data' => null,
            ], 404);
        }

        $parent->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Parent updated successfully',
            'data' => new StudentParentResource($parent->load('student')),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $parent = StudentParent::find($id);

        if (!$parent) {
            return response()->json([
                'success' => false,
                'message' => 'Parent not found',
                'data' => null,
            ], 404);
        }

        $parent->delete();

        return response()->json([
            'success' => true,
            'message' => 'Parent deleted successfully',
            'data' => null,
        ]);
    }
}
