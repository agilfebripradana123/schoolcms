<?php

namespace App\Http\Controllers\Api\Students;

use App\Http\Controllers\Controller;
use App\Models\Development\Achievement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentAchievementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $student = $request->attributes->get('student_profile');

        $rows = Achievement::where('student_id', $student->id)
            ->orderBy('achievement_date', 'desc')
            ->get()
            ->map(function ($a) {
                return [
                    'id' => $a->id,
                    'title' => $a->title,
                    'level' => $a->level,
                    'organizer' => $a->organizer,
                    'achievement_date' => $a->achievement_date?->format('Y-m-d'),
                    'description' => $a->description,
                ];
            });

        return response()->json([
            'success' => true,
            'message' => 'Achievements retrieved successfully',
            'data' => $rows,
        ]);
    }
}