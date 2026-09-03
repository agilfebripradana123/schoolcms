<?php

namespace App\Http\Controllers\Api\Students;

use App\Http\Controllers\Controller;
use App\Models\Academic\SchoolClass;
use App\Models\Students\Attendance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentAttendanceController extends Controller
{
    /**
     * Summary of attendance for the authenticated student.
     */
    public function summary(Request $request): JsonResponse
    {
        $student = $request->attributes->get('student_profile');

        $total = Attendance::where('student_id', $student->id)->count();
        $present = Attendance::where('student_id', $student->id)->where('status', 'hadir')->count();
        $sick = Attendance::where('student_id', $student->id)->where('status', 'sakit')->count();
        $permission = Attendance::where('student_id', $student->id)->where('status', 'izin')->count();
        $absent = Attendance::where('student_id', $student->id)->where('status', 'alpa')->count();

        $percentage = $total > 0 ? round(($present / $total) * 100, 2) : 0;

        return response()->json([
            'success' => true,
            'message' => 'Attendance summary retrieved successfully',
            'data' => [
                'total_days' => $total,
                'present' => $present,
                'sick' => $sick,
                'permission' => $permission,
                'absent' => $absent,
                'percentage' => $percentage,
            ],
        ]);
    }

    /**
     * List attendance records for the authenticated student.
     */
    public function index(Request $request): JsonResponse
    {
        $student = $request->attributes->get('student_profile');

        $validated = $request->validate([
            'status' => 'nullable|string|in:hadir,sakit,izin,alpa',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = Attendance::where('student_id', $student->id)
            ->with(['schoolClass'])
            ->latest('date');

        if (!empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        $perPage = $validated['per_page'] ?? 20;
        $attendances = $query->paginate($perPage, ['*'], 'page', $validated['page'] ?? 1);

        $formatted = $attendances->map(function ($item) {
            return [
                'id' => $item->id,
                'date' => $item->date->format('Y-m-d'),
                'status' => $item->status,
                'note' => $item->note,
                'class_name' => $item->schoolClass->name ?? '-',
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Attendance retrieved successfully',
            'data' => $formatted,
            'meta' => [
                'current_page' => $attendances->currentPage(),
                'per_page' => $attendances->perPage(),
                'total' => $attendances->total(),
                'last_page' => $attendances->lastPage(),
            ],
        ]);
    }
}