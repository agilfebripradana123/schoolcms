<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreExamParticipantRequest;
use App\Http\Requests\Api\UpdateExamParticipantRequest;
use App\Http\Resources\ExamParticipantResource;
use App\Models\ExamParticipant;
use Illuminate\Http\JsonResponse;

class ExamParticipantController extends Controller
{
    public function index(\Illuminate\Http\Request $request): JsonResponse
    {
        $validated = $request->validate([
            'exam_id' => 'nullable|integer',
            'student_id' => 'nullable|integer',
            'status' => 'nullable|string|in:registered,started,completed,blocked',
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
        ]);

        $query = ExamParticipant::query();

        if ($request->filled('exam_id')) {
            $query->where('exam_id', $validated['exam_id']);
        }

        if ($request->filled('student_id')) {
            $query->where('student_id', $validated['student_id']);
        }

        if (!empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        $participants = $query->orderBy('id', 'desc')->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => 'Exam participants retrieved successfully',
            'data' => ExamParticipantResource::collection($participants),
            'meta' => [
                'current_page' => $participants->currentPage(),
                'per_page' => $participants->perPage(),
                'total' => $participants->total(),
                'last_page' => $participants->lastPage(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $participant = ExamParticipant::find($id);

        if (!$participant) {
            return response()->json([
                'success' => false,
                'message' => 'Exam participant not found',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Exam participant retrieved successfully',
            'data' => new ExamParticipantResource($participant),
        ]);
    }

    public function store(StoreExamParticipantRequest $request): JsonResponse
    {
        $participant = ExamParticipant::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Exam participant created successfully',
            'data' => new ExamParticipantResource($participant),
        ], 201);
    }

    public function update(UpdateExamParticipantRequest $request, int $id): JsonResponse
    {
        $participant = ExamParticipant::find($id);

        if (!$participant) {
            return response()->json([
                'success' => false,
                'message' => 'Exam participant not found',
                'data' => null,
            ], 404);
        }

        $participant->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Exam participant updated successfully',
            'data' => new ExamParticipantResource($participant),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $participant = ExamParticipant::find($id);

        if (!$participant) {
            return response()->json([
                'success' => false,
                'message' => 'Exam participant not found',
                'data' => null,
            ], 404);
        }

        $participant->delete();

        return response()->json([
            'success' => true,
            'message' => 'Exam participant deleted successfully',
            'data' => null,
        ]);
    }
}
