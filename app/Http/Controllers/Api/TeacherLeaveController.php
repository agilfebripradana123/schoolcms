<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreTeacherLeaveRequest;
use App\Http\Requests\Api\UpdateTeacherLeaveRequest;
use App\Http\Resources\TeacherLeaveResource;
use App\Models\TeacherLeave;
use Illuminate\Http\JsonResponse;

class TeacherLeaveController extends Controller
{
    public function index(\Illuminate\Http\Request $request): JsonResponse
    {
        $query = TeacherLeave::query()->with(['teacher', 'approvedBy']);

        if ($request->filled('teacher_id')) {
            $query->where('teacher_id', $request->input('teacher_id'));
        }

        if ($request->filled('leave_type')) {
            $query->where('leave_type', $request->input('leave_type'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $leaves = $query->orderBy('id', 'desc')->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => 'Teacher leaves retrieved successfully',
            'data' => TeacherLeaveResource::collection($leaves),
            'meta' => [
                'current_page' => $leaves->currentPage(),
                'per_page' => $leaves->perPage(),
                'total' => $leaves->total(),
                'last_page' => $leaves->lastPage(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $leave = TeacherLeave::with(['teacher', 'approvedBy'])->find($id);

        if (!$leave) {
            return response()->json([
                'success' => false,
                'message' => 'Teacher leave not found',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Teacher leave retrieved successfully',
            'data' => new TeacherLeaveResource($leave),
        ]);
    }

    public function store(StoreTeacherLeaveRequest $request): JsonResponse
    {
        $leave = TeacherLeave::create($request->validated());
        $leave->load(['teacher', 'approvedBy']);

        return response()->json([
            'success' => true,
            'message' => 'Teacher leave created successfully',
            'data' => new TeacherLeaveResource($leave),
        ], 201);
    }

    public function update(UpdateTeacherLeaveRequest $request, int $id): JsonResponse
    {
        $leave = TeacherLeave::find($id);

        if (!$leave) {
            return response()->json([
                'success' => false,
                'message' => 'Teacher leave not found',
                'data' => null,
            ], 404);
        }

        $leave->update($request->validated());
        $leave->load(['teacher', 'approvedBy']);

        return response()->json([
            'success' => true,
            'message' => 'Teacher leave updated successfully',
            'data' => new TeacherLeaveResource($leave),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $leave = TeacherLeave::find($id);

        if (!$leave) {
            return response()->json([
                'success' => false,
                'message' => 'Teacher leave not found',
                'data' => null,
            ], 404);
        }

        $leave->delete();

        return response()->json([
            'success' => true,
            'message' => 'Teacher leave deleted successfully',
            'data' => null,
        ]);
    }
}
