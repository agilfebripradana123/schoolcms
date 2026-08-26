<?php

namespace App\Http\Controllers\Api\Staff;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Staff\StoreTeacherRequest;
use App\Http\Requests\Api\Staff\TeacherImportRequest;
use App\Http\Requests\Api\Staff\TeacherIndexRequest;
use App\Http\Requests\Api\Staff\TeacherExportRequest;
use App\Http\Requests\Api\Staff\UpdateTeacherRequest;
use App\Http\Resources\Staff\TeacherResource;
use App\Imports\TeachersImport;
use App\Exports\TeachersExport;
use App\Models\Staff\Teacher;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TeacherController extends Controller
{
    public function index(TeacherIndexRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $query = Teacher::with(['user']);

        if (!empty($validated['search'])) {
            $search = $validated['search'];
            $query->where(function ($q) use ($search) {
                $q->where('teacher_code', 'like', "%{$search}%")
                    ->orWhere('nip', 'like', "%{$search}%")
                    ->orWhere('full_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if (!empty($validated['gender'])) {
            $query->where('gender', $validated['gender']);
        }

        if (!empty($validated['employment_status'])) {
            $query->where('employment_status', $validated['employment_status']);
        }

        if (array_key_exists('is_active', $validated)) {
            $query->where('is_active', $validated['is_active']);
        }

        $perPage = $validated['per_page'] ?? 10;
        $teachers = $query->orderBy('id', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Teachers retrieved successfully',
            'data' => TeacherResource::collection($teachers),
            'meta' => [
                'current_page' => $teachers->currentPage(),
                'per_page' => $teachers->perPage(),
                'total' => $teachers->total(),
                'last_page' => $teachers->lastPage(),
            ],
        ]);
    }

    public function store(StoreTeacherRequest $request): JsonResponse
    {
        $teacher = Teacher::create($request->validated());

        $teacher->load(['user']);

        return response()->json([
            'success' => true,
            'message' => 'Teacher created successfully',
            'data' => new TeacherResource($teacher),
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $teacher = Teacher::with(['user', 'classes', 'classSubjects'])
            ->whereNull('deleted_at')
            ->find($id);

        if (!$teacher) {
            return response()->json([
                'success' => false,
                'message' => 'Teacher not found',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Teacher retrieved successfully',
            'data' => new TeacherResource($teacher),
        ]);
    }

    public function update(UpdateTeacherRequest $request, int $id): JsonResponse
    {
        $teacher = Teacher::whereNull('deleted_at')->find($id);

        if (!$teacher) {
            return response()->json([
                'success' => false,
                'message' => 'Teacher not found',
                'data' => null,
            ], 404);
        }

        $teacher->update($request->validated());

        $teacher->load(['user']);

        return response()->json([
            'success' => true,
            'message' => 'Teacher updated successfully',
            'data' => new TeacherResource($teacher),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $teacher = Teacher::whereNull('deleted_at')->find($id);

        if (!$teacher) {
            return response()->json([
                'success' => false,
                'message' => 'Teacher not found',
                'data' => null,
            ], 404);
        }

        $teacher->delete();

        return response()->json([
            'success' => true,
            'message' => 'Teacher deleted successfully',
            'data' => null,
        ]);
    }

    public function export(TeacherExportRequest $request): BinaryFileResponse
    {
        $validated = $request->validated();

        $filters = array_filter($validated, fn ($v) => $v !== null);

        return Excel::download(new TeachersExport($filters), 'teachers.xlsx');
    }

    public function import(TeacherImportRequest $request): JsonResponse
    {
        $import = new TeachersImport();

        try {
            Excel::import($import, $request->file('file'));
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to process Excel file',
                'data' => null,
            ], 422);
        }

        if (!$import->isHeaderValid()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid Excel header',
                'data' => null,
            ], 422);
        }

        if ($import->getTotalRows() === 0 && !$import->hasErrors()) {
            return response()->json([
                'success' => true,
                'message' => 'Teachers imported successfully',
                'data' => [
                    'total_rows' => 0,
                    'imported' => 0,
                    'failed' => 0,
                    'errors' => [],
                ],
            ]);
        }

        if ($import->hasErrors() && $import->getImportedCount() === 0) {
            return response()->json([
                'success' => false,
                'message' => 'Teacher import completed with errors',
                'data' => [
                    'total_rows' => $import->getTotalRows(),
                    'imported' => 0,
                    'failed' => $import->getFailedCount(),
                    'errors' => $import->getErrors(),
                ],
            ], 422);
        }

        if ($import->hasErrors() && $import->getImportedCount() > 0) {
            DB::connection('mysql')->transaction(function () use ($import) {
                foreach ($import->getValidData() as $row) {
                    Teacher::create($row);
                }
            });

            return response()->json([
                'success' => false,
                'message' => 'Teacher import completed with errors',
                'data' => [
                    'total_rows' => $import->getTotalRows(),
                    'imported' => $import->getImportedCount(),
                    'failed' => $import->getFailedCount(),
                    'errors' => $import->getErrors(),
                ],
            ], 422);
        }

        DB::connection('mysql')->transaction(function () use ($import) {
            foreach ($import->getValidData() as $row) {
                Teacher::create($row);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Teachers imported successfully',
            'data' => [
                'total_rows' => $import->getTotalRows(),
                'imported' => $import->getImportedCount(),
                'failed' => $import->getFailedCount(),
                'errors' => $import->getErrors(),
            ],
        ]);
    }
}
