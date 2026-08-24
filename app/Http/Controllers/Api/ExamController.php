<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreExamRequest;
use App\Http\Requests\Api\UpdateExamRequest;
use App\Http\Resources\ExamResource;
use App\Models\Exam;
use Illuminate\Http\JsonResponse;

class ExamController extends Controller
{
    public function index(\Illuminate\Http\Request $request): JsonResponse
    {
        $query = Exam::query()->with('subject');

        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->input('subject_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $exams = $query->orderBy('id', 'desc')->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => 'Exams retrieved successfully',
            'data' => ExamResource::collection($exams),
            'meta' => [
                'current_page' => $exams->currentPage(),
                'per_page' => $exams->perPage(),
                'total' => $exams->total(),
                'last_page' => $exams->lastPage(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $exam = Exam::with('subject')->find($id);

        if (!$exam) {
            return response()->json([
                'success' => false,
                'message' => 'Exam not found',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Exam retrieved successfully',
            'data' => new ExamResource($exam),
        ]);
    }

    public function store(StoreExamRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $exam = \Illuminate\Support\Facades\DB::connection('mysql')->transaction(function () use ($validated) {
            return Exam::create($validated);
        });

        $exam->load('subject');

        return response()->json([
            'success' => true,
            'message' => 'Exam created successfully',
            'data' => new ExamResource($exam),
        ], 201);
    }

    public function update(UpdateExamRequest $request, int $id): JsonResponse
    {
        $exam = Exam::find($id);

        if (!$exam) {
            return response()->json([
                'success' => false,
                'message' => 'Exam not found',
                'data' => null,
            ], 404);
        }

        $validated = $request->validated();

        \Illuminate\Support\Facades\DB::connection('mysql')->transaction(function () use ($exam, $validated) {
            $exam->update($validated);
        });

        $exam->refresh()->load('subject');

        return response()->json([
            'success' => true,
            'message' => 'Exam updated successfully',
            'data' => new ExamResource($exam),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $exam = Exam::find($id);

        if (!$exam) {
            return response()->json([
                'success' => false,
                'message' => 'Exam not found',
                'data' => null,
            ], 404);
        }

        $exam->delete();

        return response()->json([
            'success' => true,
            'message' => 'Exam deleted successfully',
            'data' => null,
        ]);
    }
}
