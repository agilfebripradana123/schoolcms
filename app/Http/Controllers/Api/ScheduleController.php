<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreScheduleRequest;
use App\Http\Requests\Api\UpdateScheduleRequest;
use App\Http\Resources\ScheduleResource;
use App\Models\Schedule;
use Illuminate\Http\JsonResponse;

class ScheduleController extends Controller
{
    public function index(\Illuminate\Http\Request $request): JsonResponse
    {
        $query = Schedule::query()->with(['schoolClass', 'subject', 'teacher', 'period', 'academicYear', 'semester']);

        if ($request->filled('class_id')) {
            $query->where('class_id', $request->input('class_id'));
        }

        if ($request->filled('day')) {
            $query->where('day', $request->input('day'));
        }

        if ($request->filled('teacher_id')) {
            $query->where('teacher_id', $request->input('teacher_id'));
        }

        if ($request->filled('academic_year_id')) {
            $query->where('academic_year_id', $request->input('academic_year_id'));
        }

        if ($request->filled('semester_id')) {
            $query->where('semester_id', $request->input('semester_id'));
        }

        $schedules = $query->orderBy('id', 'desc')->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => 'Schedules retrieved successfully',
            'data' => ScheduleResource::collection($schedules),
            'meta' => [
                'current_page' => $schedules->currentPage(),
                'per_page' => $schedules->perPage(),
                'total' => $schedules->total(),
                'last_page' => $schedules->lastPage(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $schedule = Schedule::with(['schoolClass', 'subject', 'teacher', 'period', 'academicYear', 'semester'])->find($id);

        if (!$schedule) {
            return response()->json([
                'success' => false,
                'message' => 'Schedule not found',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Schedule retrieved successfully',
            'data' => new ScheduleResource($schedule),
        ]);
    }

    public function store(StoreScheduleRequest $request): JsonResponse
    {
        $schedule = Schedule::create($request->validated());
        $schedule->load(['schoolClass', 'subject', 'teacher', 'period', 'academicYear', 'semester']);

        return response()->json([
            'success' => true,
            'message' => 'Schedule created successfully',
            'data' => new ScheduleResource($schedule),
        ], 201);
    }

    public function update(UpdateScheduleRequest $request, int $id): JsonResponse
    {
        $schedule = Schedule::find($id);

        if (!$schedule) {
            return response()->json([
                'success' => false,
                'message' => 'Schedule not found',
                'data' => null,
            ], 404);
        }

        $schedule->update($request->validated());
        $schedule->load(['schoolClass', 'subject', 'teacher', 'period', 'academicYear', 'semester']);

        return response()->json([
            'success' => true,
            'message' => 'Schedule updated successfully',
            'data' => new ScheduleResource($schedule),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $schedule = Schedule::find($id);

        if (!$schedule) {
            return response()->json([
                'success' => false,
                'message' => 'Schedule not found',
                'data' => null,
            ], 404);
        }

        $schedule->delete();

        return response()->json([
            'success' => true,
            'message' => 'Schedule deleted successfully',
            'data' => null,
        ]);
    }
}
