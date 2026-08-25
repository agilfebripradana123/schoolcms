<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreAchievementRequest;
use App\Http\Requests\Api\UpdateAchievementRequest;
use App\Http\Resources\AchievementResource;
use App\Models\Achievement;
use Illuminate\Http\JsonResponse;

class AchievementController extends Controller
{
    public function index(\Illuminate\Http\Request $request): JsonResponse
    {
        $query = Achievement::query()->with('student');

        if ($request->filled('student_id')) {
            $query->where('student_id', $request->input('student_id'));
        }

        if ($request->filled('level')) {
            $query->where('level', $request->input('level'));
        }

        $achievements = $query->orderBy('achievement_date', 'desc')->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => 'Achievements retrieved successfully',
            'data' => AchievementResource::collection($achievements),
            'meta' => [
                'current_page' => $achievements->currentPage(),
                'per_page' => $achievements->perPage(),
                'total' => $achievements->total(),
                'last_page' => $achievements->lastPage(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $achievement = Achievement::with('student')->find($id);

        if (!$achievement) {
            return response()->json([
                'success' => false,
                'message' => 'Achievement not found',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Achievement retrieved successfully',
            'data' => new AchievementResource($achievement),
        ]);
    }

    public function store(StoreAchievementRequest $request): JsonResponse
    {
        $achievement = Achievement::create($request->validated());
        $achievement->load('student');

        return response()->json([
            'success' => true,
            'message' => 'Achievement created successfully',
            'data' => new AchievementResource($achievement),
        ], 201);
    }

    public function update(UpdateAchievementRequest $request, int $id): JsonResponse
    {
        $achievement = Achievement::find($id);

        if (!$achievement) {
            return response()->json([
                'success' => false,
                'message' => 'Achievement not found',
                'data' => null,
            ], 404);
        }

        $achievement->update($request->validated());
        $achievement->load('student');

        return response()->json([
            'success' => true,
            'message' => 'Achievement updated successfully',
            'data' => new AchievementResource($achievement),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $achievement = Achievement::find($id);

        if (!$achievement) {
            return response()->json([
                'success' => false,
                'message' => 'Achievement not found',
                'data' => null,
            ], 404);
        }

        $achievement->delete();

        return response()->json([
            'success' => true,
            'message' => 'Achievement deleted successfully',
            'data' => null,
        ]);
    }
}
