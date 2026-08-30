<?php

namespace App\Http\Controllers\Api\Students\Finance;

use App\Http\Controllers\Controller;
use App\Http\Resources\Student\Finance\StudentScholarshipResource;
use App\Models\Finance\Scholarship;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StudentScholarshipController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $student = $request->attributes->get('student_profile');

        $validated = $request->validate([
            'student_id' => ['prohibited'],
            'status' => ['nullable', 'string', Rule::in(['aktif', 'selesai', 'dibatalkan'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $query = Scholarship::where('student_id', $student->id);

        if (isset($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        // Active scholarships first, then deterministic ordering.
        $scholarships = $query
            ->orderByRaw("CASE WHEN status = 'aktif' THEN 0 ELSE 1 END")
            ->orderBy('id')
            ->paginate($validated['per_page'] ?? 15);

        return response()->json([
            'success' => true,
            'message' => 'Scholarships retrieved successfully',
            'data' => StudentScholarshipResource::collection($scholarships),
            'meta' => [
                'current_page' => $scholarships->currentPage(),
                'per_page' => $scholarships->perPage(),
                'total' => $scholarships->total(),
                'last_page' => $scholarships->lastPage(),
            ],
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $student = $request->attributes->get('student_profile');

        $request->validate(['student_id' => ['prohibited']]);

        $scholarship = Scholarship::where('student_id', $student->id)
            ->whereKey($id)
            ->first();

        if ($scholarship === null) {
            return response()->json([
                'success' => false,
                'message' => 'Scholarship not found',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Scholarship retrieved successfully',
            'data' => new StudentScholarshipResource($scholarship),
        ]);
    }
}
