<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreClassStudentRequest;
use App\Http\Requests\Api\UpdateClassStudentRequest;
use App\Http\Resources\ClassStudentResource;
use App\Models\ClassStudent;
use Illuminate\Http\JsonResponse;

class ClassStudentController extends Controller
{
    public function index(\Illuminate\Http\Request $request): JsonResponse
    {
        $query = ClassStudent::query()->with(['schoolClass', 'student', 'academicYear']);

        if ($request->filled('class_id')) {
            $query->where('class_id', $request->input('class_id'));
        }

        if ($request->filled('student_id')) {
            $query->where('student_id', $request->input('student_id'));
        }

        if ($request->filled('academic_year_id')) {
            $query->where('academic_year_id', $request->input('academic_year_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $classStudents = $query->orderBy('id', 'desc')->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => 'Class students retrieved successfully',
            'data' => ClassStudentResource::collection($classStudents),
            'meta' => [
                'current_page' => $classStudents->currentPage(),
                'per_page' => $classStudents->perPage(),
                'total' => $classStudents->total(),
                'last_page' => $classStudents->lastPage(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $classStudent = ClassStudent::with(['schoolClass', 'student', 'academicYear'])->find($id);

        if (!$classStudent) {
            return response()->json([
                'success' => false,
                'message' => 'Class student not found',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Class student retrieved successfully',
            'data' => new ClassStudentResource($classStudent),
        ]);
    }

    public function store(StoreClassStudentRequest $request): JsonResponse
    {
        $classStudent = ClassStudent::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Class student created successfully',
            'data' => new ClassStudentResource($classStudent->load(['schoolClass', 'student', 'academicYear'])),
        ], 201);
    }

    public function update(UpdateClassStudentRequest $request, int $id): JsonResponse
    {
        $classStudent = ClassStudent::find($id);

        if (!$classStudent) {
            return response()->json([
                'success' => false,
                'message' => 'Class student not found',
                'data' => null,
            ], 404);
        }

        $classStudent->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Class student updated successfully',
            'data' => new ClassStudentResource($classStudent->load(['schoolClass', 'student', 'academicYear'])),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $classStudent = ClassStudent::find($id);

        if (!$classStudent) {
            return response()->json([
                'success' => false,
                'message' => 'Class student not found',
                'data' => null,
            ], 404);
        }

        $classStudent->delete();

        return response()->json([
            'success' => true,
            'message' => 'Class student deleted successfully',
            'data' => null,
        ]);
    }
}
