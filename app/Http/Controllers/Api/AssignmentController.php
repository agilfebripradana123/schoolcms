<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreAssignmentRequest;
use App\Http\Requests\Api\UpdateAssignmentRequest;
use App\Http\Resources\AssignmentResource;
use App\Models\Assignment;
use Illuminate\Http\JsonResponse;

class AssignmentController extends Controller
{
    public function index(\Illuminate\Http\Request $request): JsonResponse
    {
        $query = Assignment::query()->with(['subject', 'schoolClass', 'teacher', 'academicYear']);

        if ($request->filled('class_id')) {
            $query->where('class_id', $request->input('class_id'));
        }

        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->input('subject_id'));
        }

        if ($request->filled('teacher_id')) {
            $query->where('teacher_id', $request->input('teacher_id'));
        }

        if ($request->filled('academic_year_id')) {
            $query->where('academic_year_id', $request->input('academic_year_id'));
        }

        if ($request->filled('q')) {
            $search = $request->input('q');
            $query->where('title', 'LIKE', "%{$search}%");
        }

        $assignments = $query->orderBy('id', 'desc')->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => 'Assignments retrieved successfully',
            'data' => AssignmentResource::collection($assignments),
            'meta' => [
                'current_page' => $assignments->currentPage(),
                'per_page' => $assignments->perPage(),
                'total' => $assignments->total(),
                'last_page' => $assignments->lastPage(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $assignment = Assignment::with(['subject', 'schoolClass', 'teacher', 'academicYear'])->find($id);

        if (!$assignment) {
            return response()->json([
                'success' => false,
                'message' => 'Assignment not found',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Assignment retrieved successfully',
            'data' => new AssignmentResource($assignment),
        ]);
    }

    public function store(StoreAssignmentRequest $request): JsonResponse
    {
        $assignment = Assignment::create($request->validated());
        $assignment->load(['subject', 'schoolClass', 'teacher', 'academicYear']);

        return response()->json([
            'success' => true,
            'message' => 'Assignment created successfully',
            'data' => new AssignmentResource($assignment),
        ], 201);
    }

    public function update(UpdateAssignmentRequest $request, int $id): JsonResponse
    {
        $assignment = Assignment::find($id);

        if (!$assignment) {
            return response()->json([
                'success' => false,
                'message' => 'Assignment not found',
                'data' => null,
            ], 404);
        }

        $assignment->update($request->validated());
        $assignment->load(['subject', 'schoolClass', 'teacher', 'academicYear']);

        return response()->json([
            'success' => true,
            'message' => 'Assignment updated successfully',
            'data' => new AssignmentResource($assignment),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $assignment = Assignment::find($id);

        if (!$assignment) {
            return response()->json([
                'success' => false,
                'message' => 'Assignment not found',
                'data' => null,
            ], 404);
        }

        $assignment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Assignment deleted successfully',
            'data' => null,
        ]);
    }
}
