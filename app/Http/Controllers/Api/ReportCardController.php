<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreReportCardRequest;
use App\Http\Requests\Api\UpdateReportCardRequest;
use App\Http\Resources\ReportCardResource;
use App\Models\ReportCard;
use Illuminate\Http\JsonResponse;

class ReportCardController extends Controller
{
    public function index(\Illuminate\Http\Request $request): JsonResponse
    {
        $query = ReportCard::query()->with(['student', 'schoolClass', 'academicYear', 'semester']);

        if ($request->filled('student_id')) {
            $query->where('student_id', $request->input('student_id'));
        }

        if ($request->filled('class_id')) {
            $query->where('class_id', $request->input('class_id'));
        }

        if ($request->filled('academic_year_id')) {
            $query->where('academic_year_id', $request->input('academic_year_id'));
        }

        if ($request->filled('semester_id')) {
            $query->where('semester_id', $request->input('semester_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $reportCards = $query->orderBy('id', 'desc')->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => 'Report cards retrieved successfully',
            'data' => ReportCardResource::collection($reportCards),
            'meta' => [
                'current_page' => $reportCards->currentPage(),
                'per_page' => $reportCards->perPage(),
                'total' => $reportCards->total(),
                'last_page' => $reportCards->lastPage(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $reportCard = ReportCard::with(['student', 'schoolClass', 'academicYear', 'semester'])->find($id);

        if (!$reportCard) {
            return response()->json([
                'success' => false,
                'message' => 'Report card not found',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Report card retrieved successfully',
            'data' => new ReportCardResource($reportCard),
        ]);
    }

    public function store(StoreReportCardRequest $request): JsonResponse
    {
        $reportCard = ReportCard::create($request->validated());
        $reportCard->load(['student', 'schoolClass', 'academicYear', 'semester']);

        return response()->json([
            'success' => true,
            'message' => 'Report card created successfully',
            'data' => new ReportCardResource($reportCard),
        ], 201);
    }

    public function update(UpdateReportCardRequest $request, int $id): JsonResponse
    {
        $reportCard = ReportCard::find($id);

        if (!$reportCard) {
            return response()->json([
                'success' => false,
                'message' => 'Report card not found',
                'data' => null,
            ], 404);
        }

        $reportCard->update($request->validated());
        $reportCard->load(['student', 'schoolClass', 'academicYear', 'semester']);

        return response()->json([
            'success' => true,
            'message' => 'Report card updated successfully',
            'data' => new ReportCardResource($reportCard),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $reportCard = ReportCard::find($id);

        if (!$reportCard) {
            return response()->json([
                'success' => false,
                'message' => 'Report card not found',
                'data' => null,
            ], 404);
        }

        $reportCard->delete();

        return response()->json([
            'success' => true,
            'message' => 'Report card deleted successfully',
            'data' => null,
        ]);
    }
}
