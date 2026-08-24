<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreExamInstructionRequest;
use App\Http\Requests\Api\UpdateExamInstructionRequest;
use App\Http\Resources\ExamInstructionResource;
use App\Models\ExamInstruction;
use Illuminate\Http\JsonResponse;

class ExamInstructionController extends Controller
{
    public function index(\Illuminate\Http\Request $request): JsonResponse
    {
        $instructions = ExamInstruction::query()
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
        $validated = $request->validated();

        $instruction = \Illuminate\Support\Facades\DB::connection('mysql')->transaction(function () use ($validated) {
            return ExamInstruction::create($validated);
        });

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

        $validated = $request->validated();

        \Illuminate\Support\Facades\DB::connection('mysql')->transaction(function () use ($instruction, $validated) {
            $instruction->update($validated);
        });

        $instruction->refresh();

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
