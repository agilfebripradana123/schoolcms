<?php

namespace App\Http\Controllers\Api\Students;

use App\Http\Controllers\Controller;
use App\Models\Academic\Grade;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Student Portal — Grades (read-only, identity scoped).
 *
 * Returns only grades belonging to the authenticated student.
 */
class StudentGradeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $student = $request->attributes->get('student_profile');

        $validated = $request->validate([
            'semester' => 'nullable|string|in:1,2',
            'academic_year' => 'nullable|string',
        ]);

        $grades = Grade::with(['subject', 'schoolClass'])
            ->where('student_id', $student->id)
            ->when(!empty($validated['semester']), fn ($q) => $q->where('semester', $validated['semester']))
            ->when(!empty($validated['academic_year']), fn ($q) => $q->where('academic_year', $validated['academic_year']))
            ->orderBy('subject_id')
            ->get();

        // Group by subject: pivot tugas/uts/uas per subject+semester+year
        $rows = collect();
        $grouped = $grades->groupBy(fn ($g) => $g->subject_id . '|' . $g->semester . '|' . $g->academic_year);

        foreach ($grouped as $group) {
            $first = $group->first();
            $row = [
                'id' => $first->id,
                'subject_id' => $first->subject_id,
                'subject_name' => $first->subject->name ?? '-',
                'class_name' => $first->schoolClass->name ?? '-',
                'semester' => $first->semester,
                'academic_year' => $first->academic_year,
                'tugas' => null,
                'uts' => null,
                'uas' => null,
                'final_score' => null,
            ];

            foreach ($group as $g) {
                $row[$g->type] = (float) $g->score;
            }

            // Final score: average of all present scores
            $scores = array_filter([$row['tugas'], $row['uts'], $row['uas']], fn ($v) => $v !== null);
            $row['final_score'] = count($scores) > 0 ? round(array_sum($scores) / count($scores), 2) : null;

            $rows->push($row);
        }

        return response()->json([
            'success' => true,
            'message' => 'Grades retrieved successfully',
            'data' => $rows,
        ]);
    }

    public function summary(Request $request): JsonResponse
    {
        $student = $request->attributes->get('student_profile');

        $validated = $request->validate([
            'semester' => 'nullable|string|in:1,2',
            'academic_year' => 'nullable|string',
        ]);

        $grades = Grade::where('student_id', $student->id)
            ->when(!empty($validated['semester']), fn ($q) => $q->where('semester', $validated['semester']))
            ->when(!empty($validated['academic_year']), fn ($q) => $q->where('academic_year', $validated['academic_year']))
            ->get();

        // Group by subject to compute per-subject final scores
        $grouped = $grades->groupBy(fn ($g) => $g->subject_id);
        $finalScores = [];

        foreach ($grouped as $group) {
            $scores = $group->pluck('score')->filter()->values();
            if ($scores->isNotEmpty()) {
                $finalScores[] = round($scores->sum() / $scores->count(), 2);
            }
        }

        $totalSubjects = count($finalScores);
        $average = $totalSubjects > 0 ? round(array_sum($finalScores) / $totalSubjects, 2) : 0;
        $highest = $totalSubjects > 0 ? max($finalScores) : 0;

        return response()->json([
            'success' => true,
            'message' => 'Grade summary retrieved successfully',
            'data' => [
                'average' => $average,
                'highest' => $highest,
                'total_subjects' => $totalSubjects,
            ],
        ]);
    }
}
