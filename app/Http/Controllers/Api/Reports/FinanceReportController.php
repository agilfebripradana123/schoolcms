<?php

namespace App\Http\Controllers\Api\Reports;

use App\Http\Controllers\Controller;
use App\Services\Finance\FinancialReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FinanceReportController extends Controller
{
    public function summary(Request $request, FinancialReportService $reportService): JsonResponse
    {
        $validated = $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'academic_year_id' => 'nullable|integer',
            'semester_id' => 'nullable|integer',
            'fee_type_id' => 'nullable|integer',
        ]);

        $data = $reportService->summary($validated);

        return response()->json([
            'success' => true,
            'message' => 'Finance report retrieved successfully',
            'data' => $data,
        ]);
    }
}
