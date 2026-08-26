<?php

namespace App\Http\Controllers\Api\Staff;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Staff\StoreTeacherAttendanceRequest;
use App\Http\Requests\Api\Staff\UpdateTeacherAttendanceRequest;
use App\Http\Resources\Staff\TeacherAttendanceResource;
use App\Models\Staff\TeacherAttendance;
use Illuminate\Http\JsonResponse;

class TeacherAttendanceController extends Controller
{
    public function index(\Illuminate\Http\Request $request): JsonResponse
    {
        $query = TeacherAttendance::query()->with('teacher');

        if ($request->filled('teacher_id')) {
            $query->where('teacher_id', $request->input('teacher_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('date')) {
            $query->whereDate('date', $request->input('date'));
        }

        $attendances = $query->orderBy('date', 'desc')->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => 'Teacher attendance retrieved successfully',
            'data' => TeacherAttendanceResource::collection($attendances),
            'meta' => [
                'current_page' => $attendances->currentPage(),
                'per_page' => $attendances->perPage(),
                'total' => $attendances->total(),
                'last_page' => $attendances->lastPage(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $attendance = TeacherAttendance::with('teacher')->find($id);

        if (!$attendance) {
            return response()->json([
                'success' => false,
                'message' => 'Teacher attendance not found',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Teacher attendance retrieved successfully',
            'data' => new TeacherAttendanceResource($attendance),
        ]);
    }

    public function store(StoreTeacherAttendanceRequest $request): JsonResponse
    {
        $attendance = TeacherAttendance::create($request->validated());
        $attendance->load('teacher');

        return response()->json([
            'success' => true,
            'message' => 'Teacher attendance created successfully',
            'data' => new TeacherAttendanceResource($attendance),
        ], 201);
    }

    public function update(UpdateTeacherAttendanceRequest $request, int $id): JsonResponse
    {
        $attendance = TeacherAttendance::find($id);

        if (!$attendance) {
            return response()->json([
                'success' => false,
                'message' => 'Teacher attendance not found',
                'data' => null,
            ], 404);
        }

        $attendance->update($request->validated());
        $attendance->load('teacher');

        return response()->json([
            'success' => true,
            'message' => 'Teacher attendance updated successfully',
            'data' => new TeacherAttendanceResource($attendance),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $attendance = TeacherAttendance::find($id);

        if (!$attendance) {
            return response()->json([
                'success' => false,
                'message' => 'Teacher attendance not found',
                'data' => null,
            ], 404);
        }

        $attendance->delete();

        return response()->json([
            'success' => true,
            'message' => 'Teacher attendance deleted successfully',
            'data' => null,
        ]);
    }
}
