<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\RegistrationAdminResource;
use App\Models\AuditLog;
use App\Models\Registrant;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReRegistrationController extends Controller
{
    public function reRegister(\Illuminate\Http\Request $request, int $id): JsonResponse
    {
        $registrant = Registrant::find($id);

        if (!$registrant) {
            return response()->json([
                'success' => false,
                'message' => 'Registration not found',
                'data' => null,
            ], 404);
        }

        if ($registrant->selection_status !== 'selected') {
            throw ValidationException::withMessages([
                'status' => ['Only selected students can re-register.'],
            ]);
        }

        if ($registrant->re_registration_status === 'completed') {
            throw ValidationException::withMessages([
                'status' => ['Re-registration has already been completed.'],
            ]);
        }

        DB::connection('mysql')->transaction(function () use ($registrant, $request) {
            $registrant = Registrant::query()->lockForUpdate()->find($registrant->id);

            $fromStatus = $registrant->status;

            // Explicit attribute assignment
            $registrant->re_registration_status = 'completed';
            $registrant->re_registration_date = now();
            $registrant->status = 're_registered';
            $registrant->save();

            // Audit log (atomic)
            AuditLog::create([
                'user_id' => $request->user()->id,
                'action' => 'registration_re_registered',
                'model' => 'Registrant',
                'model_id' => $registrant->id,
                'description' => json_encode([
                    'registration_number' => $registrant->registration_number,
                    'from_status' => $fromStatus,
                    'to_status' => 're_registered',
                ]),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Re-registration completed successfully',
            'data' => new RegistrationAdminResource($registrant->fresh()),
        ]);
    }

    public function verifyReRegistration(\Illuminate\Http\Request $request, int $id): JsonResponse
    {
        $registrant = Registrant::find($id);

        if (!$registrant) {
            return response()->json([
                'success' => false,
                'message' => 'Registration not found',
                'data' => null,
            ], 404);
        }

        if ($registrant->re_registration_status !== 'completed') {
            throw ValidationException::withMessages([
                'status' => ['Re-registration has not been completed yet.'],
            ]);
        }

        if ($registrant->re_registration_verified_at !== null) {
            throw ValidationException::withMessages([
                'status' => ['Re-registration has already been verified.'],
            ]);
        }

        $validated = $request->validate([
            're_registration_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::connection('mysql')->transaction(function () use ($registrant, $request, $validated) {
            $registrant = Registrant::query()->lockForUpdate()->find($registrant->id);

            // Explicit attribute assignment
            $registrant->re_registration_verified_by = $request->user()->id;
            $registrant->re_registration_verified_at = now();
            $registrant->re_registration_notes = $validated['re_registration_notes'] ?? null;
            $registrant->save();

            // Audit log (atomic)
            AuditLog::create([
                'user_id' => $request->user()->id,
                'action' => 'registration_re_registration_verified',
                'model' => 'Registrant',
                'model_id' => $registrant->id,
                'description' => json_encode([
                    'registration_number' => $registrant->registration_number,
                    'status' => 're_registered',
                ]),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Re-registration verified successfully',
            'data' => new RegistrationAdminResource($registrant->fresh()),
        ]);
    }
}
