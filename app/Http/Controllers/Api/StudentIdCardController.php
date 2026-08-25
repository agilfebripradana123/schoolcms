<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreStudentIdCardRequest;
use App\Http\Requests\Api\UpdateStudentIdCardRequest;
use App\Http\Resources\StudentIdCardResource;
use App\Models\StudentIdCard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentIdCardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'student_id' => 'nullable|integer',
            'status' => 'nullable|string|in:aktif,hilang,rusak,nonaktif',
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
        ]);

        $query = StudentIdCard::query()->with('student');

        if (!empty($validated['student_id'])) {
            $query->where('student_id', $validated['student_id']);
        }

        if (!empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        $cards = $query->orderBy('id', 'desc')->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => 'Student ID cards retrieved successfully',
            'data' => StudentIdCardResource::collection($cards),
            'meta' => [
                'current_page' => $cards->currentPage(),
                'per_page' => $cards->perPage(),
                'total' => $cards->total(),
                'last_page' => $cards->lastPage(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $card = StudentIdCard::with('student')->find($id);

        if (!$card) {
            return response()->json([
                'success' => false,
                'message' => 'Student ID card not found',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Student ID card retrieved successfully',
            'data' => new StudentIdCardResource($card),
        ]);
    }

    public function store(StoreStudentIdCardRequest $request): JsonResponse
    {
        $card = StudentIdCard::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Student ID card created successfully',
            'data' => new StudentIdCardResource($card->load('student')),
        ], 201);
    }

    public function update(UpdateStudentIdCardRequest $request, int $id): JsonResponse
    {
        $card = StudentIdCard::find($id);

        if (!$card) {
            return response()->json([
                'success' => false,
                'message' => 'Student ID card not found',
                'data' => null,
            ], 404);
        }

        $card->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Student ID card updated successfully',
            'data' => new StudentIdCardResource($card->load('student')),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $card = StudentIdCard::find($id);

        if (!$card) {
            return response()->json([
                'success' => false,
                'message' => 'Student ID card not found',
                'data' => null,
            ], 404);
        }

        $card->delete();

        return response()->json([
            'success' => true,
            'message' => 'Student ID card deleted successfully',
            'data' => null,
        ]);
    }
}
