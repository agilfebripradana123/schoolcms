<?php

namespace App\Http\Controllers\Api\Students;

use App\Http\Controllers\Controller;
use App\Models\Development\Violation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentViolationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $student = $request->attributes->get('student_profile');

        $rows = Violation::where('student_id', $student->id)
            ->orderBy('violated_at', 'desc')
            ->get()
            ->map(function ($v) {
                return [
                    'id' => $v->id,
                    'category' => $v->category,
                    'description' => $v->description,
                    'points' => $v->points,
                    'violated_at' => $v->violated_at?->format('Y-m-d'),
                ];
            });

        return response()->json([
            'success' => true,
            'message' => 'Violations retrieved successfully',
            'data' => $rows,
        ]);
    }
}