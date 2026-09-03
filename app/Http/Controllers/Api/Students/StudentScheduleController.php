<?php

namespace App\Http\Controllers\Api\Students;

use App\Http\Controllers\Controller;
use App\Models\Academic\Schedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentScheduleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $student = $request->attributes->get('student_profile');

        if (!$student->class_id) {
            return response()->json([
                'success' => true,
                'message' => 'Student has no class assigned',
                'data' => [],
            ]);
        }

        $validated = $request->validate([
            'day' => 'nullable|string',
        ]);

        $query = Schedule::with(['schoolClass', 'subject', 'teacher', 'period'])
            ->where('class_id', $student->class_id);

        if (!empty($validated['day'])) {
            $query->where('day', $validated['day']);
        }

        $schedules = $query->orderBy('day', 'asc')->get();

        $daysOrder = ['senin' => 1, 'selasa' => 2, 'rabu' => 3, 'kamis' => 4, 'jumat' => 5, 'sabtu' => 6];

        $formatted = [];
        foreach ($schedules as $schedule) {
            $period = $schedule->period;
            $formatted[] = [
                'id' => $schedule->id,
                'day' => $schedule->day,
                'start_time' => $period->start_time ?? null,
                'end_time' => $period->end_time ?? null,
                'subject_name' => $schedule->subject->name ?? '-',
                'teacher_name' => $schedule->teacher->full_name ?? null,
                'room_name' => $schedule->schoolClass->name ?? '-',
            ];
        }

        usort($formatted, function ($a, $b) use ($daysOrder) {
            $dayCmp = ($daysOrder[$a['day']] ?? 99) <=> ($daysOrder[$b['day']] ?? 99);
            if ($dayCmp !== 0) {
                return $dayCmp;
            }
            $startA = $a['start_time'] ?? '00:00';
            $startB = $b['start_time'] ?? '00:00';
            return strcmp($startA, $startB);
        });

        return response()->json([
            'success' => true,
            'message' => 'Schedules retrieved successfully',
            'data' => $formatted,
        ]);
    }
}