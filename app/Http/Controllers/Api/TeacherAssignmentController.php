<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreTeacherAssignmentRequest;
use App\Http\Requests\Api\UpdateTeacherAssignmentRequest;
use App\Http\Resources\TeacherAssignmentResource;
use App\Models\TeacherAssignment;
use Illuminate\Http\JsonResponse;

class TeacherAssignmentController extends Controller
{
    public function index(\Illuminate\Http\Request $request): JsonResponse
    {
        $query = TeacherAssignment::query()->with(['teacher', 'schoolClass', 'subject', 'academicYear']);

        if ($request->filled('teacher_id')) {
            $query->where('teacher_id', $request->input('teacher_id'));
        }

        if ($request->filled('class_id')) {
            $query->where('class_id', $request->input('class_id'));
        }

        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->input('subject_id'));
        }

        if ($request->filled('academic_year_id')) {
            $query->where('academic_year_id', $request->input('academic_year_id'));
        }

        $assignments = $query->orderBy('id', 'desc')->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => 'Teacher assignments retrieved successfully',
            'data' => TeacherAssignmentResource::collection($assignments),
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
        $assignment = TeacherAssignment::with(['teacher', 'schoolClass', 'subject', 'academicYear'])->find($id);

        if (!$assignment) {
            return response()->json([
                'success' => false,
                'message' => 'Teacher assignment not found',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Teacher assignment retrieved successfully',
            'data' => new TeacherAssignmentResource($assignment),
        ]);
    }

    public function store(StoreTeacherAssignmentRequest $request): JsonResponse
    {
        $assignment = TeacherAssignment::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Teacher assignment created successfully',
            'data' => new TeacherAssignmentResource($assignment->load(['teacher', 'schoolClass', 'subject', 'academicYear'])),
        ], 201);
    }

    public function update(UpdateTeacherAssignmentRequest $request, int $id): JsonResponse
    {
        $assignment = TeacherAssignment::find($id);

        if (!$assignment) {
            return response()->json([
                'success' => false,
                'message' => 'Teacher assignment not found',
                'data' => null,
            ], 404);
        }

        $assignment->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Teacher assignment updated successfully',
            'data' => new TeacherAssignmentResource($assignment->load(['teacher', 'schoolClass', 'subject', 'academicYear'])),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $assignment = TeacherAssignment::find($id);

        if (!$assignment) {
            return response()->json([
                'success' => false,
                'message' => 'Teacher assignment not found',
                'data' => null,
            ], 404);
        }

        $assignment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Teacher assignment deleted successfully',
            'data' => null,
        ]);
    }
}
