<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AuditLogResource;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;

class AuditLogController extends Controller
{
    public function index(\Illuminate\Http\Request $request): JsonResponse
    {
        $query = AuditLog::query()->with('user');

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        if ($request->filled('action')) {
            $query->where('action', $request->input('action'));
        }

        if ($request->filled('model')) {
            $query->where('model', $request->input('model'));
        }

        if ($request->filled('q')) {
            $search = $request->input('q');
            $query->where('description', 'LIKE', "%{$search}%");
        }

        $auditLogs = $query->orderBy('created_at', 'desc')->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => 'Audit logs retrieved successfully',
            'data' => AuditLogResource::collection($auditLogs),
            'meta' => [
                'current_page' => $auditLogs->currentPage(),
                'per_page' => $auditLogs->perPage(),
                'total' => $auditLogs->total(),
                'last_page' => $auditLogs->lastPage(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $auditLog = AuditLog::with('user')->find($id);

        if (!$auditLog) {
            return response()->json([
                'success' => false,
                'message' => 'Audit log not found',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Audit log retrieved successfully',
            'data' => new AuditLogResource($auditLog),
        ]);
    }
}
