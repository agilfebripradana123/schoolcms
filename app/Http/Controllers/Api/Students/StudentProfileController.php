<?php

namespace App\Http\Controllers\Api\Students;

use App\Http\Controllers\Controller;
use App\Http\Resources\Students\StudentResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Phase 8 — Student Portal identity foundation.
 *
 * Minimal endpoint that surfaces the authenticated student identity resolved
 * by EnsureStudentProfile. No Phase 9 Finance endpoints are implemented here.
 */
class StudentProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $student = $request->attributes->get('student_profile');

        return response()->json([
            'success' => true,
            'message' => 'Student profile retrieved successfully',
            'data' => new StudentResource($student),
        ]);
    }
}
