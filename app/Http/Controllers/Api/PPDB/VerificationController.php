<?php

namespace App\Http\Controllers\Api\PPDB;

use App\Http\Controllers\Controller;
use App\Http\Resources\PPDB\RegistrationAdminResource;
use App\Models\System\AuditLog;
use App\Models\PPDB\Registrant;
use App\Models\Students\Guardian;
use App\Models\Students\Student;
use App\Models\Students\StudentParent;
use App\Models\System\Role;
use App\Models\System\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
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

        // Boleh verifikasi jika status 'draft', 'submitted', 'active' (daftar ulang) atau verification_status 'pending'
        if (!in_array($registrant->status, ['draft', 'submitted', 'active']) || $registrant->verification_status !== 'pending') {
            throw ValidationException::withMessages([
                'status' => ['Registration cannot be verified in its current status.'],
            ]);
        }

        DB::connection('mysql')->transaction(function () use ($registrant, $request) {
            $registrant = Registrant::query()->lockForUpdate()->find($registrant->id);

            if (!in_array($registrant->status, ['draft', 'submitted', 'active']) || $registrant->verification_status !== 'pending') {
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
            $registrant->re_registration_status = 'pending';
            $registrant->save();

            // Salin data ke tabel re_registrants (data yang sudah di konfirmasi oleh admin
            // di page daftar ulang). Hanya buat satu baris; idempoten bila sudah ada.
            $this->syncToReRegistrants($registrant, $request->user()->id);

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

        if ($registrant->status !== 'submitted' && $registrant->status !== 'draft' || $registrant->verification_status !== 'pending') {
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

    /**
     * Salin data registrant ke re_registrants setelah admin verify.
     * Idempoten: bila re_registrant untuk registrant ini sudah ada, update saja.
     */
    private function syncToReRegistrants(Registrant $registrant, int $verifierId): void
    {
        $reRegistrantData = [
            'student_id' => $registrant->student_id,
            'registration_number' => $registrant->registration_number,
            'nik' => $registrant->nik,
            'nisn' => $registrant->nisn,
            'full_name' => $registrant->full_name,
            'nickname' => $registrant->nickname,
            'gender' => $registrant->gender,
            'religion' => $registrant->religion,
            'birth_place' => $registrant->birth_place,
            'birth_date' => $registrant->birth_date,
            'email' => $registrant->email,
            'phone' => $registrant->phone,
            'address' => $registrant->address,
            'rt' => $registrant->rt,
            'rw' => $registrant->rw,
            'village' => $registrant->village,
            'district' => $registrant->district,
            'city' => $registrant->city,
            'province' => $registrant->province,
            'postal_code' => $registrant->postal_code,
            'previous_school' => $registrant->previous_school,
            'previous_school_npsn' => $registrant->previous_school_npsn,
            'graduation_year' => $registrant->graduation_year,
            'father_name' => $registrant->father_name,
            'father_nik' => $registrant->father_nik,
            'father_education' => $registrant->father_education,
            'father_occupation' => $registrant->father_occupation,
            'father_income' => $registrant->father_income,
            'father_phone' => $registrant->father_phone,
            'mother_name' => $registrant->mother_name,
            'mother_nik' => $registrant->mother_nik,
            'mother_education' => $registrant->mother_education,
            'mother_occupation' => $registrant->mother_occupation,
            'mother_income' => $registrant->mother_income,
            'mother_phone' => $registrant->mother_phone,
            'academic_year_id' => $registrant->academic_year_id,
            'registration_path' => $registrant->registration_path,
            'program_choice' => $registrant->program_choice,
            'registration_date' => $registrant->registration_date,
            'document_kk' => $registrant->document_kk,
            'document_birth_certificate' => $registrant->document_birth_certificate,
            'document_diploma' => $registrant->document_diploma,
            'document_parent_ktp' => $registrant->document_parent_ktp,
            'document_photo' => $registrant->document_photo,
            'verification_status' => 'verified',
            'verification_notes' => $registrant->verification_notes,
            'verified_by' => $verifierId,
            'verified_at' => now(),
            'selection_score' => $registrant->selection_score,
            'selection_status' => $registrant->selection_status,
            'selection_notes' => $registrant->selection_notes,
            'selected_at' => $registrant->selected_at,
            're_registration_status' => 'pending',
            're_registration_notes' => null,
            're_registration_verified_by' => null,
            're_registration_verified_at' => null,
            'data_completed' => false,
            'data_completed_at' => null,
            'declaration' => $registrant->declaration ?? false,
            'photo' => $registrant->photo,
            'notes' => $registrant->notes,
            'status' => 'active',
        ];

        // Idempoten: cari re_registrant berdasarkan registration_number
        $existing = \App\Models\PPDB\ReRegistrant::where('registration_number', $registrant->registration_number)->first();

        if ($existing) {
            $existing->update($reRegistrantData);
        } else {
            \App\Models\PPDB\ReRegistrant::create($reRegistrantData);
        }
    }
}