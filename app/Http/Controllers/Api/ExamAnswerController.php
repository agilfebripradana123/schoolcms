<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreExamAnswerRequest;
use App\Http\Requests\Api\UpdateExamAnswerRequest;
use App\Http\Resources\ExamAnswerResource;
use App\Models\ExamAnswer;
use Illuminate\Http\JsonResponse;

class ExamAnswerController extends Controller
{
    public function index(\Illuminate\Http\Request $request): JsonResponse
    {
        $validated = $request->validate([
            'participant_id' => 'nullable|integer',
            'question_id' => 'nullable|integer',
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
        ]);

        $query = ExamAnswer::query();

        if ($request->filled('participant_id')) {
            $query->where('participant_id', $validated['participant_id']);
        }

        if ($request->filled('question_id')) {
            $query->where('question_id', $validated['question_id']);
        }

        $answers = $query->orderBy('id', 'desc')->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => 'Exam answers retrieved successfully',
            'data' => ExamAnswerResource::collection($answers),
            'meta' => [
                'current_page' => $answers->currentPage(),
                'per_page' => $answers->perPage(),
                'total' => $answers->total(),
                'last_page' => $answers->lastPage(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $answer = ExamAnswer::find($id);

        if (!$answer) {
            return response()->json([
                'success' => false,
                'message' => 'Exam answer not found',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Exam answer retrieved successfully',
            'data' => new ExamAnswerResource($answer),
        ]);
    }

    public function store(StoreExamAnswerRequest $request): JsonResponse
    {
        $answer = ExamAnswer::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Exam answer created successfully',
            'data' => new ExamAnswerResource($answer),
        ], 201);
    }

    public function update(UpdateExamAnswerRequest $request, int $id): JsonResponse
    {
        $answer = ExamAnswer::find($id);

        if (!$answer) {
            return response()->json([
                'success' => false,
                'message' => 'Exam answer not found',
                'data' => null,
            ], 404);
        }

        $answer->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Exam answer updated successfully',
            'data' => new ExamAnswerResource($answer),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $answer = ExamAnswer::find($id);

        if (!$answer) {
            return response()->json([
                'success' => false,
                'message' => 'Exam answer not found',
                'data' => null,
            ], 404);
        }

        $answer->delete();

        return response()->json([
            'success' => true,
            'message' => 'Exam answer deleted successfully',
            'data' => null,
        ]);
    }
}
