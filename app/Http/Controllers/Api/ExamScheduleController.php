<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreExamScheduleRequest;
use App\Http\Requests\Api\UpdateExamScheduleRequest;
use App\Http\Resources\ExamScheduleResource;
use App\Models\ExamSchedule;
use Illuminate\Http\JsonResponse;

class ExamScheduleController extends Controller
{
    public function index(\Illuminate\Http\Request $request): JsonResponse
    {
        $query = ExamSchedule::query()->with(['exam', 'room', 'session']);

        if ($request->filled('exam_id')) {
            $query->where('exam_id', $request->input('exam_id'));
        }

        if ($request->filled('room_id')) {
            $query->where('room_id', $request->input('room_id'));
        }

        if ($request->filled('session_id')) {
            $query->where('session_id', $request->input('session_id'));
        }

        if ($request->filled('exam_date')) {
            $query->whereDate('exam_date', $request->input('exam_date'));
        }

        $schedules = $query->orderBy('id', 'desc')->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => 'Exam schedules retrieved successfully',
            'data' => ExamScheduleResource::collection($schedules),
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
        $schedule = ExamSchedule::with(['exam', 'room', 'session'])->find($id);

        if (!$schedule) {
            return response()->json([
                'success' => false,
                'message' => 'Exam schedule not found',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Exam schedule retrieved successfully',
            'data' => new ExamScheduleResource($schedule),
        ]);
    }

    public function store(StoreExamScheduleRequest $request): JsonResponse
    {
        $schedule = ExamSchedule::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Exam schedule created successfully',
            'data' => new ExamScheduleResource($schedule->load(['exam', 'room', 'session'])),
        ], 201);
    }

    public function update(UpdateExamScheduleRequest $request, int $id): JsonResponse
    {
        $schedule = ExamSchedule::find($id);

        if (!$schedule) {
            return response()->json([
                'success' => false,
                'message' => 'Exam schedule not found',
                'data' => null,
            ], 404);
        }

        $schedule->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Exam schedule updated successfully',
            'data' => new ExamScheduleResource($schedule->load(['exam', 'room', 'session'])),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $schedule = ExamSchedule::find($id);

        if (!$schedule) {
            return response()->json([
                'success' => false,
                'message' => 'Exam schedule not found',
                'data' => null,
            ], 404);
        }

        $schedule->delete();

        return response()->json([
            'success' => true,
            'message' => 'Exam schedule deleted successfully',
            'data' => null,
        ]);
    }
}
