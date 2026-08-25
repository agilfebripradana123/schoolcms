<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\RegistrationAdminResource;
use App\Models\AuditLog;
use App\Models\Registrant;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VerificationController extends Controller
{
    public function verify(\Illuminate\Http\Request $request, int $id): JsonResponse
    {
        $registrant = Registrant::find($id);

        if (!$registrant) {
            return response()->json([
                'success' => false,
                'message' => 'Registration not found',
                'data' => null,
            ], 404);
        }

        if ($registrant->status !== 'submitted' || $registrant->verification_status !== 'pending') {
            throw ValidationException::withMessages([
                'status' => ['Registration cannot be verified in its current status.'],
            ]);
        }

        DB::connection('mysql')->transaction(function () use ($registrant, $request) {
            $registrant = Registrant::query()->lockForUpdate()->find($registrant->id);

            if ($registrant->status !== 'submitted' || $registrant->verification_status !== 'pending') {
                throw ValidationException::withMessages([
                    'status' => ['Registration cannot be verified in its current status.'],
                ]);
            }

            $fromStatus = $registrant->status;

            // Explicit attribute assignment (not mass assignment)
            $registrant->verification_status = 'verified';
            $registrant->verified_by = $request->user()->id;
            $registrant->verified_at = now();
            $registrant->status = 'verified';
            $registrant->save();

            // Audit log (atomic)
            AuditLog::create([
                'user_id' => $request->user()->id,
                'action' => 'registration_verified',
                'model' => 'Registrant',
                'model_id' => $registrant->id,
                'description' => json_encode([
                    'registration_number' => $registrant->registration_number,
                    'from_status' => $fromStatus,
                    'to_status' => 'verified',
                ]),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Registration verified successfully',
            'data' => new RegistrationAdminResource($registrant->fresh()),
        ]);
    }

    public function reject(\Illuminate\Http\Request $request, int $id): JsonResponse
    {
        $registrant = Registrant::find($id);

        if (!$registrant) {
            return response()->json([
                'success' => false,
                'message' => 'Registration not found',
                'data' => null,
            ], 404);
        }

        if ($registrant->status !== 'submitted' || $registrant->verification_status !== 'pending') {
            throw ValidationException::withMessages([
                'status' => ['Registration cannot be rejected in its current status.'],
            ]);
        }

        $validated = $request->validate([
            'verification_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::connection('mysql')->transaction(function () use ($registrant, $request, $validated) {
            $registrant = Registrant::query()->lockForUpdate()->find($registrant->id);

            $fromStatus = $registrant->status;

            // Explicit attribute assignment
            $registrant->verification_status = 'rejected';
            $registrant->verified_by = $request->user()->id;
            $registrant->verified_at = now();
            $registrant->verification_notes = $validated['verification_notes'] ?? null;
            $registrant->status = 'cancelled';
            $registrant->save();

            // Audit log (atomic)
            AuditLog::create([
                'user_id' => $request->user()->id,
                'action' => 'registration_rejected',
                'model' => 'Registrant',
                'model_id' => $registrant->id,
                'description' => json_encode([
                    'registration_number' => $registrant->registration_number,
                    'from_status' => $fromStatus,
                    'to_status' => 'cancelled',
                ]),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Registration rejected successfully',
            'data' => new RegistrationAdminResource($registrant->fresh()),
        ]);
    }
}
