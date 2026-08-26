<?php

namespace App\Http\Controllers\Api\Examination;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Examination\StoreExamInstructionRequest;
use App\Http\Requests\Api\Examination\UpdateExamInstructionRequest;
use App\Http\Resources\Examination\ExamInstructionResource;
use App\Models\Examination\ExamInstruction;
use Illuminate\Http\JsonResponse;

class ExamInstructionController extends Controller
{
    public function index(\Illuminate\Http\Request $request): JsonResponse
    {
        $instructions = ExamInstruction::query()
            ->when($request->filled('search'), function ($q) use ($request) {
                $q->where('title', 'LIKE', '%' . $request->input('search') . '%');
            })
            ->when($request->filled('is_active'), function ($q) use ($request) {
                $q->where('is_active', (bool) $request->boolean('is_active'));
            })
            ->orderBy('id', 'desc')
            ->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => 'Exam instructions retrieved successfully',
            'data' => ExamInstructionResource::collection($instructions),
            'meta' => [
                'current_page' => $instructions->currentPage(),
                'per_page' => $instructions->perPage(),
                'total' => $instructions->total(),
                'last_page' => $instructions->lastPage(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $instruction = ExamInstruction::find($id);

        if (!$instruction) {
            return response()->json([
                'success' => false,
                'message' => 'Exam instruction not found',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Exam instruction retrieved successfully',
            'data' => new ExamInstructionResource($instruction),
        ]);
    }

    public function store(StoreExamInstructionRequest $request): JsonResponse
    {
        $instruction = ExamInstruction::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Exam instruction created successfully',
            'data' => new ExamInstructionResource($instruction),
        ], 201);
    }

    public function update(UpdateExamInstructionRequest $request, int $id): JsonResponse
    {
        $instruction = ExamInstruction::find($id);

        if (!$instruction) {
            return response()->json([
                'success' => false,
                'message' => 'Exam instruction not found',
                'data' => null,
            ], 404);
        }

        $instruction->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Exam instruction updated successfully',
            'data' => new ExamInstructionResource($instruction),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $instruction = ExamInstruction::find($id);

        if (!$instruction) {
            return response()->json([
                'success' => false,
                'message' => 'Exam instruction not found',
                'data' => null,
            ], 404);
        }

        $instruction->delete();

        return response()->json([
            'success' => true,
            'message' => 'Exam instruction deleted successfully',
            'data' => null,
        ]);
    }
}
