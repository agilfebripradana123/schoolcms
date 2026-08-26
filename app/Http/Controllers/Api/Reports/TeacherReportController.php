<?php

namespace App\Http\Controllers\Api\Reports;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class TeacherReportController extends Controller
{
    public function summary(): JsonResponse
    {
        $totalTeachers = DB::table('teachers')->count();

        $activeTeachers = DB::table('teachers')
            ->where('is_active', 1)
            ->count();

        $employmentBreakdown = DB::table('teachers')
            ->select('employment_status')
            ->addSelect(DB::raw('COUNT(*) AS total'))
            ->groupBy('employment_status')
            ->orderBy('employment_status')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Teacher report retrieved successfully',
            'data' => [
                'total_teachers' => $totalTeachers,
                'active_teachers' => $activeTeachers,
                'employment_breakdown' => $employmentBreakdown,
            ],
        ]);
    }

    public function attendanceSummary(\Illuminate\Http\Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date_from' => 'nullable|date',
            'date_end' => 'nullable|date',
        ]);

        $query = DB::table('teacher_attendance')
            ->join('teachers', 'teacher_attendance.teacher_id', '=', 'teachers.id')
            ->select(
                'teacher_attendance.teacher_id AS teacher_id',
                'teachers.full_name AS teacher_name'
            )
            ->addSelect(DB::raw("SUM(CASE WHEN teacher_attendance.status = 'hadir' THEN 1 ELSE 0 END) AS hadir"))
            ->addSelect(DB::raw("SUM(CASE WHEN teacher_attendance.status = 'sakit' THEN 1 ELSE 0 END) AS sakit"))
            ->addSelect(DB::raw("SUM(CASE WHEN teacher_attendance.status = 'izin' THEN 1 ELSE 0 END) AS izin"))
            ->addSelect(DB::raw("SUM(CASE WHEN teacher_attendance.status = 'alfa' THEN 1 ELSE 0 END) AS alfa"))
            ->addSelect(DB::raw("SUM(CASE WHEN teacher_attendance.status = 'terlambat' THEN 1 ELSE 0 END) AS terlambat"))
            ->addSelect(DB::raw('COUNT(*) AS total_days'));

        if (!empty($validated['date_from'])) {
            $query->where('teacher_attendance.date', '>=', $validated['date_from']);
        }

        if (!empty($validated['date_end'])) {
            $query->where('teacher_attendance.date', '<=', $validated['date_end']);
        }

        $rows = $query->groupBy('teacher_attendance.teacher_id', 'teachers.full_name')
            ->orderBy('teachers.full_name')
            ->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => 'Teacher attendance report retrieved successfully',
            'data' => $rows->items(),
            'meta' => [
                'current_page' => $rows->currentPage(),
                'per_page' => $rows->perPage(),
                'total' => $rows->total(),
                'last_page' => $rows->lastPage(),
            ],
        ]);
    }
}
