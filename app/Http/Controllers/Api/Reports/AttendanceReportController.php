<?php

namespace App\Http\Controllers\Api\Reports;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class AttendanceReportController extends Controller
{
    // attendances table is empty today; these are the assumed status values
    private const STATUSES = ['hadir', 'sakit', 'izin', 'alfa'];

    public function daily(\Illuminate\Http\Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'class_id' => 'nullable|integer',
        ]);

        $date = $validated['date'];

        $rows = DB::table('attendances')
            ->leftJoin('classes', 'classes.id', '=', 'attendances.class_id')
            ->whereDate('attendances.date', $date)
            ->when(!empty($validated['class_id']), fn ($q) => $q->where('attendances.class_id', $validated['class_id']))
            ->groupBy('attendances.class_id', 'classes.name')
            ->get([
                'attendances.class_id',
                DB::raw('classes.name AS class_name'),
                DB::raw("SUM(CASE WHEN attendances.status = 'hadir' THEN 1 ELSE 0 END) AS hadir"),
                DB::raw("SUM(CASE WHEN attendances.status = 'sakit' THEN 1 ELSE 0 END) AS sakit"),
                DB::raw("SUM(CASE WHEN attendances.status = 'izin' THEN 1 ELSE 0 END) AS izin"),
                DB::raw("SUM(CASE WHEN attendances.status = 'alfa' THEN 1 ELSE 0 END) AS alfa"),
                DB::raw('COUNT(*) AS total'),
            ]);

        $totals = array_fill_keys(self::STATUSES, 0);
        foreach ($rows as $row) {
            foreach (self::STATUSES as $status) {
                $totals[$status] += (int) $row->{$status};
            }
        }

        $perClass = $rows->map(function ($row) {
            $item = [
                'class_id' => $row->class_id,
                'class_name' => $row->class_name,
            ];
            foreach (self::STATUSES as $status) {
                $item[$status] = (int) $row->{$status};
            }
            return $item;
        });

        return response()->json([
            'success' => true,
            'message' => 'Daily attendance report retrieved successfully',
            'data' => [
                'date' => $date,
                'totals' => $totals,
                'per_class' => $perClass,
            ],
        ]);
    }

    public function studentSummary(\Illuminate\Http\Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date_from' => 'nullable|date',
            'date_end' => 'nullable|date',
            'class_id' => 'nullable|integer',
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
        ]);

        $statusCounts = collect(self::STATUSES)
            ->map(fn ($s) => "SUM(CASE WHEN attendances.status = '{$s}' THEN 1 ELSE 0 END) AS {$s}")
            ->implode(', ');

        $query = DB::table('attendances')
            ->join('students', 'students.id', '=', 'attendances.student_id')
            ->selectRaw("attendances.student_id, students.name AS student_name, COUNT(*) AS total_days, {$statusCounts}")
            ->when(!empty($validated['date_from']), fn ($q) => $q->whereDate('attendances.date', '>=', $validated['date_from']))
            ->when(!empty($validated['date_end']), fn ($q) => $q->whereDate('attendances.date', '<=', $validated['date_end']))
            ->when(!empty($validated['class_id']), fn ($q) => $q->where('attendances.class_id', $validated['class_id']))
            ->groupBy('attendances.student_id', 'students.name');

        // percentage computed after pagination to avoid wrapping the paginated query
        $summaries = $query->orderBy('students.name')->paginate($validated['per_page'] ?? 15);

        $items = collect($summaries->items())->map(function ($row) {
            $totalDays = max(1, (int) $row->total_days);
            return [
                'student_id' => $row->student_id,
                'student_name' => $row->student_name,
                'total_days' => (int) $row->total_days,
                'hadir' => (int) $row->hadir,
                'sakit' => (int) $row->sakit,
                'izin' => (int) $row->izin,
                'alfa' => (int) $row->alfa,
                'attendance_percentage' => round($row->hadir / $totalDays * 100, 1),
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Student attendance report retrieved successfully',
            'data' => $items,
            'meta' => [
                'current_page' => $summaries->currentPage(),
                'per_page' => $summaries->perPage(),
                'total' => $summaries->total(),
                'last_page' => $summaries->lastPage(),
            ],
        ]);
    }
}
