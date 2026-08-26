<?php

namespace App\Http\Controllers\Api\PPDB;

use App\Http\Controllers\Controller;
use App\Http\Resources\PPDB\RegistrationAdminResource;
use App\Models\System\AuditLog;
use App\Models\PPDB\Registrant;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SelectionController extends Controller
{
    public function select(\Illuminate\Http\Request $request, int $id): JsonResponse
    {
        $registrant = Registrant::find($id);

        if (!$registrant) {
            return response()->json([
                'success' => false,
                'message' => 'Registration not found',
                'data' => null,
            ], 404);
        }

        if ($registrant->verification_status !== 'verified' || $registrant->selection_status !== 'pending') {
            throw ValidationException::withMessages([
                'status' => ['Registration cannot be selected in its current status.'],
            ]);
        }

        $validated = $request->validate([
            'selection_score' => ['required', 'numeric', 'min:0', 'max:100'],
            'selection_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::connection('mysql')->transaction(function () use ($registrant, $request, $validated) {
            $registrant = Registrant::query()->lockForUpdate()->find($registrant->id);

            if ($registrant->verification_status !== 'verified' || $registrant->selection_status !== 'pending') {
                throw ValidationException::withMessages([
                    'status' => ['Registration cannot be selected in its current status.'],
                ]);
            }

            $fromStatus = $registrant->status;

            // Explicit attribute assignment
            $registrant->selection_status = 'selected';
            $registrant->selection_score = $validated['selection_score'];
            $registrant->selection_notes = $validated['selection_notes'] ?? null;
            $registrant->selected_at = now();
            $registrant->status = 'selected';
            $registrant->save();

            // Audit log (atomic)
            AuditLog::create([
                'user_id' => $request->user()->id,
                'action' => 'registration_selected',
                'model' => 'Registrant',
                'model_id' => $registrant->id,
                'description' => json_encode([
                    'registration_number' => $registrant->registration_number,
                    'from_status' => $fromStatus,
                    'to_status' => 'selected',
                ]),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Registration selected successfully',
            'data' => new RegistrationAdminResource($registrant->fresh()),
        ]);
    }

    public function notSelect(\Illuminate\Http\Request $request, int $id): JsonResponse
    {
        $registrant = Registrant::find($id);

        if (!$registrant) {
            return response()->json([
                'success' => false,
                'message' => 'Registration not found',
                'data' => null,
            ], 404);
        }

        if ($registrant->verification_status !== 'verified' || $registrant->selection_status !== 'pending') {
            throw ValidationException::withMessages([
                'status' => ['Registration cannot be processed in its current status.'],
            ]);
        }

        $validated = $request->validate([
            'selection_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'selection_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::connection('mysql')->transaction(function () use ($registrant, $request, $validated) {
            $registrant = Registrant::query()->lockForUpdate()->find($registrant->id);

            $fromStatus = $registrant->status;

            // Explicit attribute assignment
            $registrant->selection_status = 'not_selected';
            $registrant->selection_score = $validated['selection_score'] ?? null;
            $registrant->selection_notes = $validated['selection_notes'] ?? null;
            $registrant->selected_at = now();
            $registrant->status = 'not_selected';
            $registrant->save();

            // Audit log (atomic)
            AuditLog::create([
                'user_id' => $request->user()->id,
                'action' => 'registration_not_selected',
                'model' => 'Registrant',
                'model_id' => $registrant->id,
                'description' => json_encode([
                    'registration_number' => $registrant->registration_number,
                    'from_status' => $fromStatus,
                    'to_status' => 'not_selected',
                ]),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Registration not selected',
            'data' => new RegistrationAdminResource($registrant->fresh()),
        ]);
    }
}
