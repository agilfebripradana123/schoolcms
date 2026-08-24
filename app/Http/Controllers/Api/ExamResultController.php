<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreExamResultRequest;
use App\Http\Requests\Api\UpdateExamResultRequest;
use App\Http\Resources\ExamResultResource;
use App\Models\ExamResult;
use Illuminate\Http\JsonResponse;

class ExamResultController extends Controller
{
    public function index(\Illuminate\Http\Request $request): JsonResponse
    {
        $query = ExamResult::query()->with('participant');

        if ($request->filled('participant_id')) {
            $query->where('participant_id', $request->input('participant_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $results = $query->orderBy('id', 'desc')->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => 'Exam results retrieved successfully',
            'data' => ExamResultResource::collection($results),
            'meta' => [
                'current_page' => $results->currentPage(),
                'per_page' => $results->perPage(),
                'total' => $results->total(),
                'last_page' => $results->lastPage(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $result = ExamResult::with('participant')->find($id);

        if (!$result) {
            return response()->json([
                'success' => false,
                'message' => 'Exam result not found',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Exam result retrieved successfully',
            'data' => new ExamResultResource($result),
        ]);
    }

    public function store(StoreExamResultRequest $request): JsonResponse
    {
        $result = ExamResult::create($request->validated());
        $result->load('participant');

        return response()->json([
            'success' => true,
            'message' => 'Exam result created successfully',
            'data' => new ExamResultResource($result),
        ], 201);
    }

    public function update(UpdateExamResultRequest $request, int $id): JsonResponse
    {
        $result = ExamResult::find($id);

        if (!$result) {
            return response()->json([
                'success' => false,
                'message' => 'Exam result not found',
                'data' => null,
            ], 404);
        }

        $result->update($request->validated());
        $result->load('participant');

        return response()->json([
            'success' => true,
            'message' => 'Exam result updated successfully',
            'data' => new ExamResultResource($result),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $result = ExamResult::find($id);

        if (!$result) {
            return response()->json([
                'success' => false,
                'message' => 'Exam result not found',
                'data' => null,
            ], 404);
        }

        $result->delete();

        return response()->json([
            'success' => true,
            'message' => 'Exam result deleted successfully',
            'data' => null,
        ]);
    }
}
