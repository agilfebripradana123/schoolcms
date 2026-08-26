<?php

namespace App\Http\Controllers\Api\Reports;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class StudentReportController extends Controller
{
    public function summary(): JsonResponse
    {
        $totalStudents = DB::table('students')
            ->whereNull('deleted_at')
            ->count();

        $totalClasses = DB::table('students')
            ->whereNull('deleted_at')
            ->distinct()
            ->count('class_id');

        $perClass = DB::table('students')
            ->join('classes', 'students.class_id', '=', 'classes.id')
            ->whereNull('students.deleted_at')
            ->whereNull('classes.deleted_at')
            ->select(
                'classes.id AS class_id',
                'classes.name AS class_name'
            )
            ->addSelect(DB::raw('COUNT(*) AS total_students'))
            ->groupBy('classes.id', 'classes.name')
            ->orderBy('classes.name')
            ->get();

        $genderRows = DB::table('students')
            ->whereNull('deleted_at')
            ->select('gender')
            ->addSelect(DB::raw('COUNT(*) AS total'))
            ->groupBy('gender')
            ->get()
            ->pluck('total', 'gender');

        return response()->json([
            'success' => true,
            'message' => 'Student report retrieved successfully',
            'data' => [
                'totals' => [
                    'total_students' => $totalStudents,
                    'total_classes' => $totalClasses,
                ],
                'per_class' => $perClass,
                'gender_distribution' => [
                    'L' => (int) ($genderRows['L'] ?? 0),
                    'P' => (int) ($genderRows['P'] ?? 0),
                ],
            ],
        ]);
    }
}
