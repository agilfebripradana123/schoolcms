<?php

namespace App\Http\Controllers\Api\Examination;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Examination\StoreExamRequest;
use App\Http\Requests\Api\Examination\UpdateExamRequest;
use App\Http\Resources\Examination\ExamResource;
use App\Models\Examination\Exam;
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

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%")
                    ->orWhere('description', 'LIKE', "%{$search}%");
            });
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
        $exam = Exam::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Exam created successfully',
            'data' => new ExamResource($exam->load('subject')),
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

        $exam->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Exam updated successfully',
            'data' => new ExamResource($exam->load('subject')),
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
