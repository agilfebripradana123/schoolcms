<?php

namespace App\Http\Controllers\Api\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Academic\StoreClassSubjectRequest;
use App\Http\Requests\Api\Academic\UpdateClassSubjectRequest;
use App\Http\Resources\Academic\ClassSubjectResource;
use App\Models\Academic\ClassSubject;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ClassSubjectController extends Controller
{
    public function index(\Illuminate\Http\Request $request): JsonResponse
    {
        $validated = $request->validate([
            'class_id' => 'nullable|integer',
            'subject_id' => 'nullable|integer',
            'teacher_id' => 'nullable|integer',
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
        ]);

        $query = ClassSubject::with(['schoolClass', 'subject', 'teacher']);

        if (!empty($validated['class_id'])) {
            $query->where('class_id', $validated['class_id']);
        }

        if (!empty($validated['subject_id'])) {
            $query->where('subject_id', $validated['subject_id']);
        }

        if (!empty($validated['teacher_id'])) {
            $query->where('teacher_id', $validated['teacher_id']);
        }

        $perPage = $validated['per_page'] ?? 10;
        $classSubjects = $query->orderBy('id', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Class subjects retrieved successfully',
            'data' => ClassSubjectResource::collection($classSubjects),
            'meta' => [
                'current_page' => $classSubjects->currentPage(),
                'per_page' => $classSubjects->perPage(),
                'total' => $classSubjects->total(),
                'last_page' => $classSubjects->lastPage(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $classSubject = ClassSubject::with(['schoolClass', 'subject', 'teacher'])
            ->find($id);

        if (!$classSubject) {
            return response()->json([
                'success' => false,
                'message' => 'Class subject not found',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Class subject retrieved successfully',
            'data' => new ClassSubjectResource($classSubject),
        ]);
    }

    public function store(StoreClassSubjectRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $classSubject = DB::connection('mysql')->transaction(function () use ($validated) {
            $exists = ClassSubject::where('class_id', $validated['class_id'])
                ->where('subject_id', $validated['subject_id'])
                ->exists();

            if ($exists) {
                throw ValidationException::withMessages([
                    'class_id' => ['The combination of class and subject already exists.'],
                ]);
            }

            return ClassSubject::create($validated);
        });

        $classSubject->load(['schoolClass', 'subject', 'teacher']);

        return response()->json([
            'success' => true,
            'message' => 'Class subject created successfully',
            'data' => new ClassSubjectResource($classSubject),
        ], 201);
    }

    public function update(UpdateClassSubjectRequest $request, int $id): JsonResponse
    {
        $classSubject = ClassSubject::find($id);

        if (!$classSubject) {
            return response()->json([
                'success' => false,
                'message' => 'Class subject not found',
                'data' => null,
            ], 404);
        }

        $validated = $request->validated();

        DB::connection('mysql')->transaction(function () use ($classSubject, $validated) {
            $classId = $validated['class_id'] ?? $classSubject->class_id;
            $subjectId = $validated['subject_id'] ?? $classSubject->subject_id;

            $exists = ClassSubject::where('class_id', $classId)
                ->where('subject_id', $subjectId)
                ->where('id', '!=', $classSubject->id)
                ->exists();

            if ($exists) {
                throw ValidationException::withMessages([
                    'class_id' => ['The combination of class and subject already exists.'],
                ]);
            }

            $classSubject->update($validated);
        });

        $classSubject->load(['schoolClass', 'subject', 'teacher']);

        return response()->json([
            'success' => true,
            'message' => 'Class subject updated successfully',
            'data' => new ClassSubjectResource($classSubject),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $classSubject = ClassSubject::find($id);

        if (!$classSubject) {
            return response()->json([
                'success' => false,
                'message' => 'Class subject not found',
                'data' => null,
            ], 404);
        }

        $classSubject->delete();

        return response()->json([
            'success' => true,
            'message' => 'Class subject deleted successfully',
            'data' => null,
        ]);
    }
}
