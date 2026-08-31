<?php

namespace App\Http\Controllers\Api\PPDB;

use App\Http\Controllers\Controller;
use App\Http\Resources\PPDB\RegistrationAdminResource;
use App\Models\PPDB\Registrant;
use App\Models\Students\Guardian;
use App\Models\Students\Student;
use App\Models\Students\StudentParent;
use App\Models\System\AuditLog;
use App\Models\System\Role;
use App\Models\System\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ReRegistrationController extends Controller
{
    public function reRegister(Request $request, int $id): JsonResponse
    {
        $registrant = Registrant::find($id);

        if (! $registrant) {
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

    public function verifyReRegistration(Request $request, int $id): JsonResponse
    {
        $registrant = Registrant::find($id);

        if (! $registrant) {
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

            // Integrasi ke sistem siswa: buat Student + User(role Siswa) +
            // Parents/Guardians, lalu tautkan registrants.student_id (idempoten).
            $this->materializeStudent($registrant, $request->user()->id);

            // Audit log (atomic)
            AuditLog::create([
                'user_id' => $request->user()->id,
                'action' => 'registration_re_registration_verified',
                'model' => 'Registrant',
                'model_id' => $registrant->id,
                'description' => json_encode([
                    'registration_number' => $registrant->registration_number,
                    'status' => 're_registered',
                    'student_id' => $registrant->student_id,
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

    /**
     * Materialisasi registrant menjadi Student + User(role Siswa) + Parents/Guardians.
     * Idempoten: kalau registrants.student_id sudah terisi, tidak membuat duplikat.
     * Dipanggil di dalam transaksi milik verifyReRegistration().
     */
    private function materializeStudent(Registrant $registrant, int $verifierId): void
    {
        if ($registrant->student_id !== null) {
            return;
        }

        // User login role 'Siswa' (password default = NISN, or NIS/email fallback).
        $siswaRoleId = Role::where('name', 'Siswa')->value('id');
        if (! $siswaRoleId) {
            throw ValidationException::withMessages([
                'status' => ['Cannot materialize student: role "Siswa" is missing.'],
            ]);
        }

        $username = $registrant->nisn ?: ($registrant->nisn ?: $registrant->email);
        $user = User::create([
            'role_id' => $siswaRoleId,
            'name' => $registrant->full_name,
            'username' => $username,
            'email' => $registrant->email,
            'password' => $registrant->nisn ?: Hash::make(Str::random(12)),
            'is_active' => true,
        ]);

        // NIS: PPDB-<tahun>-<urutan global> agar unik di tabel students.
        $nis = 'NIS-'.now()->format('Y').'-'.str_pad((string) $registrant->id, 6, '0', STR_PAD_LEFT);

        $student = Student::create([
            'user_id' => $user->id,
            'class_id' => null,
            'nisn' => $registrant->nisn,
            'nis' => $nis,
            'name' => $registrant->full_name,
            'nik' => $registrant->nik,
            'religion' => $registrant->religion,
            'gender' => $registrant->gender,
            'birth_place' => $registrant->birth_place,
            'birth_date' => $registrant->birth_date,
            'address' => $registrant->address,
            'previous_school' => $registrant->previous_school,
            'email' => $registrant->email,
            'phone' => $registrant->phone,
            'photo' => $registrant->photo,
        ]);

        // Tautkan registrant -> student (penanda integrasi & guard idempoten).
        $registrant->student_id = $student->id;
        $registrant->save();

        // Parents (satu baris per siswa).
        StudentParent::create([
            'student_id' => $student->id,
            'father_name' => $registrant->father_name,
            'father_birth_year' => $registrant->father_birth_year,
            'father_education' => $registrant->father_education,
            'father_occupation' => $registrant->father_occupation,
            'father_income' => $registrant->father_income,
            'father_nik' => $registrant->father_nik,
            'mother_name' => $registrant->mother_name,
            'mother_birth_year' => $registrant->mother_birth_year,
            'mother_education' => $registrant->mother_education,
            'mother_occupation' => $registrant->mother_occupation,
            'mother_income' => $registrant->mother_income,
            'mother_nik' => $registrant->mother_nik,
            'phone' => $registrant->phone,
            'address' => $registrant->address,
        ]);

        // Guardians (hanya bila nama wali ada; satu baris).
        if (! empty($registrant->guardian_name)) {
            Guardian::create([
                'student_id' => $student->id,
                'name' => $registrant->guardian_name,
                'nik' => $registrant->guardian_nik,
                'birth_year' => $registrant->guardian_birth_year,
                'education' => $registrant->guardian_education,
                'relation' => 'lainnya',
                'phone' => $registrant->guardian_phone,
                'occupation' => $registrant->guardian_occupation,
                'income' => $registrant->guardian_income ? (string) $registrant->guardian_income : null,
                'address' => $registrant->guardian_address,
            ]);
        }

        AuditLog::create([
            'user_id' => $verifierId,
            'action' => 'student_created_from_ppdb',
            'model' => 'Student',
            'model_id' => $student->id,
            'description' => json_encode([
                'registration_number' => $registrant->registration_number,
                'student_id' => $student->id,
            ]),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
