<?php

namespace App\Http\Controllers\Api\Students;

use App\Http\Controllers\Controller;
use App\Models\Development\Extracurricular;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class StudentExtracurricularController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        // Check if participant table exists, otherwise return all extracurriculars
        if (Schema::hasTable('extracurricular_participants')) {
            $student = $request->attributes->get('student_profile');
            $ids = \DB::table('extracurricular_participants')
                ->where('student_id', $student->id)
                ->pluck('extracurricular_id');
            
            $rows = Extracurricular::whereIn('id', $ids)->orderBy('name')->get();
        } else {
            $rows = Extracurricular::orderBy('name')->get();
        }

        $formatted = $rows->map(fn ($e) => [
            'id' => $e->id,
            'name' => $e->name,
            'description' => $e->description,
            'supervisor_id' => $e->supervisor_id,
            'schedule_day' => $e->schedule_day,
            'is_active' => $e->is_active,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Extracurriculars retrieved successfully',
            'data' => $formatted,
        ]);
    }
}