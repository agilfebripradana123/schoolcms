<?php

namespace App\Http\Controllers\Api\Students;

use App\Http\Controllers\Controller;
use App\Models\Academic\Assignment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentAssignmentController extends Controller
{
    /**
     * List assignments for the authenticated student (by their class).
     */
    public function index(Request $request): JsonResponse
    {
        $student = $request->attributes->get('student_profile');

        $assignments = Assignment::where('class_id', $student->class_id)
            ->with(['subject', 'teacher'])
            ->orderBy('due_date', 'asc')
            ->get()
            ->map(function ($a) {
                return [
                    'id' => $a->id,
                    'title' => $a->title,
                    'description' => $a->description,
                    'subject_id' => $a->subject_id,
                    'class_id' => $a->class_id,
                    'teacher_id' => $a->teacher_id,
                    'due_date' => $a->due_date?->format('Y-m-d'),
                    'subject' => $a->subject ? ['name' => $a->subject->name] : null,
                    'teacher' => $a->teacher ? ['full_name' => $a->teacher->full_name] : null,
                    'created_at' => $a->created_at?->format('Y-m-d H:i:s'),
                ];
            });

        return response()->json([
            'success' => true,
            'message' => 'Assignments retrieved successfully',
            'data' => $assignments,
        ]);
    }

    /**
     * Show single assignment detail.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $student = $request->attributes->get('student_profile');

        $assignment = Assignment::where('class_id', $student->class_id)
            ->with(['subject', 'teacher'])
            ->find($id);

        if (!$assignment) {
            return response()->json([
                'success' => false,
                'message' => 'Assignment not found',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Assignment retrieved successfully',
            'data' => [
                'id' => $assignment->id,
                'title' => $assignment->title,
                'description' => $assignment->description,
                'subject_id' => $assignment->subject_id,
                'class_id' => $assignment->class_id,
                'teacher_id' => $assignment->teacher_id,
                'due_date' => $assignment->due_date?->format('Y-m-d'),
                'subject' => $assignment->subject ? ['name' => $assignment->subject->name] : null,
                'teacher' => $assignment->teacher ? ['full_name' => $assignment->teacher->full_name] : null,
                'created_at' => $assignment->created_at?->format('Y-m-d H:i:s'),
            ],
        ]);
    }
}