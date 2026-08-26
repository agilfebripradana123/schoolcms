<?php

namespace App\Http\Controllers\Api\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Finance\StoreFinancialReportRequest;
use App\Http\Requests\Api\Finance\UpdateFinancialReportRequest;
use App\Http\Resources\Finance\FinancialReportResource;
use App\Models\Finance\FinancialReport;
use Illuminate\Http\JsonResponse;

class FinancialReportController extends Controller
{
    public function index(\Illuminate\Http\Request $request): JsonResponse
    {
        $query = FinancialReport::query()->with('generator');

        if ($request->filled('report_type')) {
            $query->where('report_type', $request->input('report_type'));
        }

        $reports = $query->orderBy('period_start', 'desc')->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => 'Financial reports retrieved successfully',
            'data' => FinancialReportResource::collection($reports),
            'meta' => [
                'current_page' => $reports->currentPage(),
                'per_page' => $reports->perPage(),
                'total' => $reports->total(),
                'last_page' => $reports->lastPage(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $report = FinancialReport::with('generator')->find($id);

        if (!$report) {
            return response()->json([
                'success' => false,
                'message' => 'Financial report not found',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Financial report retrieved successfully',
            'data' => new FinancialReportResource($report),
        ]);
    }

    public function store(StoreFinancialReportRequest $request): JsonResponse
    {
        $report = FinancialReport::create($request->validated());
        $report->load('generator');

        return response()->json([
            'success' => true,
            'message' => 'Financial report created successfully',
            'data' => new FinancialReportResource($report),
        ], 201);
    }

    public function update(UpdateFinancialReportRequest $request, int $id): JsonResponse
    {
        $report = FinancialReport::find($id);

        if (!$report) {
            return response()->json([
                'success' => false,
                'message' => 'Financial report not found',
                'data' => null,
            ], 404);
        }

        $report->update($request->validated());
        $report->load('generator');

        return response()->json([
            'success' => true,
            'message' => 'Financial report updated successfully',
            'data' => new FinancialReportResource($report),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $report = FinancialReport::find($id);

        if (!$report) {
            return response()->json([
                'success' => false,
                'message' => 'Financial report not found',
                'data' => null,
            ], 404);
        }

        $report->delete();

        return response()->json([
            'success' => true,
            'message' => 'Financial report deleted successfully',
            'data' => null,
        ]);
    }
}
