<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreScholarshipRequest;
use App\Http\Requests\Api\UpdateScholarshipRequest;
use App\Http\Resources\ScholarshipResource;
use App\Models\Scholarship;
use Illuminate\Http\JsonResponse;

class ScholarshipController extends Controller
{
    public function index(\Illuminate\Http\Request $request): JsonResponse
    {
        $query = Scholarship::query()->with('student');

        if ($request->filled('student_id')) {
            $query->where('student_id', $request->input('student_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $scholarships = $query->orderBy('id', 'desc')->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => 'Scholarships retrieved successfully',
            'data' => ScholarshipResource::collection($scholarships),
            'meta' => [
                'current_page' => $scholarships->currentPage(),
                'per_page' => $scholarships->perPage(),
                'total' => $scholarships->total(),
                'last_page' => $scholarships->lastPage(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $scholarship = Scholarship::with('student')->find($id);

        if (!$scholarship) {
            return response()->json([
                'success' => false,
                'message' => 'Scholarship not found',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Scholarship retrieved successfully',
            'data' => new ScholarshipResource($scholarship),
        ]);
    }

    public function store(StoreScholarshipRequest $request): JsonResponse
    {
        $scholarship = Scholarship::create($request->validated());
        $scholarship->load('student');

        return response()->json([
            'success' => true,
            'message' => 'Scholarship created successfully',
            'data' => new ScholarshipResource($scholarship),
        ], 201);
    }

    public function update(UpdateScholarshipRequest $request, int $id): JsonResponse
    {
        $scholarship = Scholarship::find($id);

        if (!$scholarship) {
            return response()->json([
                'success' => false,
                'message' => 'Scholarship not found',
                'data' => null,
            ], 404);
        }

        $scholarship->update($request->validated());
        $scholarship->load('student');

        return response()->json([
            'success' => true,
            'message' => 'Scholarship updated successfully',
            'data' => new ScholarshipResource($scholarship),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $scholarship = Scholarship::find($id);

        if (!$scholarship) {
            return response()->json([
                'success' => false,
                'message' => 'Scholarship not found',
                'data' => null,
            ], 404);
        }

        $scholarship->delete();

        return response()->json([
            'success' => true,
            'message' => 'Scholarship deleted successfully',
            'data' => null,
        ]);
    }
}
