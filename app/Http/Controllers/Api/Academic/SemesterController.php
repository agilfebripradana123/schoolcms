<?php

namespace App\Http\Controllers\Api\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Academic\StoreSemesterRequest;
use App\Http\Requests\Api\Academic\UpdateSemesterRequest;
use App\Http\Resources\Academic\SemesterResource;
use App\Models\Academic\Semester;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class SemesterController extends Controller
{
    public function index(\Illuminate\Http\Request $request): JsonResponse
    {
        $query = Semester::query()->with('academicYear');

        if ($request->filled('academic_year_id')) {
            $query->where('academic_year_id', $request->input('academic_year_id'));
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active') ? 1 : 0);
        }

        $semesters = $query->orderBy('id', 'desc')->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => 'Semesters retrieved successfully',
            'data' => SemesterResource::collection($semesters),
            'meta' => [
                'current_page' => $semesters->currentPage(),
                'per_page' => $semesters->perPage(),
                'total' => $semesters->total(),
                'last_page' => $semesters->lastPage(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $semester = Semester::with('academicYear')->find($id);

        if (!$semester) {
            return response()->json([
                'success' => false,
                'message' => 'Semester not found',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Semester retrieved successfully',
            'data' => new SemesterResource($semester),
        ]);
    }

    public function store(StoreSemesterRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $semester = DB::connection('mysql')->transaction(function () use ($validated) {
            $activate = (bool) ($validated['is_active'] ?? false);

            if ($activate) {
                Semester::query()
                    ->where('academic_year_id', $validated['academic_year_id'])
                    ->update(['is_active' => false]);
            }

            $record = Semester::create($validated);

            if ($activate) {
                $record->update(['is_active' => true]);
            }

            return $record;
        });

        return response()->json([
            'success' => true,
            'message' => 'Semester created successfully',
            'data' => new SemesterResource($semester->load('academicYear')),
        ], 201);
    }

    public function update(UpdateSemesterRequest $request, int $id): JsonResponse
    {
        $semester = Semester::find($id);

        if (!$semester) {
            return response()->json([
                'success' => false,
                'message' => 'Semester not found',
                'data' => null,
            ], 404);
        }

        $validated = $request->validated();

        DB::connection('mysql')->transaction(function () use ($semester, $validated) {
            $academicYearId = $validated['academic_year_id'] ?? $semester->academic_year_id;
            $activate = array_key_exists('is_active', $validated)
                && (bool) $validated['is_active'];

            if ($activate) {
                Semester::query()
                    ->where('academic_year_id', $academicYearId)
                    ->where('id', '!=', $semester->id)
                    ->update(['is_active' => false]);
            }

            $semester->update($validated);
        });

        return response()->json([
            'success' => true,
            'message' => 'Semester updated successfully',
            'data' => new SemesterResource($semester->load('academicYear')),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $semester = Semester::find($id);

        if (!$semester) {
            return response()->json([
                'success' => false,
                'message' => 'Semester not found',
                'data' => null,
            ], 404);
        }

        $semester->delete();

        return response()->json([
            'success' => true,
            'message' => 'Semester deleted successfully',
            'data' => null,
        ]);
    }
}
