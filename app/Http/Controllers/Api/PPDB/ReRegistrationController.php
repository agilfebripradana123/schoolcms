<?php

namespace App\Http\Controllers\Api\PPDB;

use App\Http\Controllers\Controller;
use App\Http\Resources\PPDB\RegistrationAdminResource;
use App\Models\PPDB\ReRegistrant;
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
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReRegistrationController extends Controller
{
    /**
     * Daftar pendaftar yang sudah di-verify untuk daftar ulang.
     * Hanya menampilkan re_registration_status='pending' + verification_status='verified'.
     */
    public function index(Request $request): JsonResponse
    {
        $query = ReRegistrant::query()
            ->where('verification_status', 'verified')
            ->where('re_registration_status', 'pending');

        $search = $request->query('search');
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('registration_number', 'LIKE', "%{$search}%")
                  ->orWhere('full_name', 'LIKE', "%{$search}%");
            });
        }

        $perPage = (int) ($request->query('per_page') ?? 10);
        $registrants = $query->orderByDesc('created_at')->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Re-registration list retrieved',
            'data' => RegistrationAdminResource::collection($registrants),
            'meta' => [
                'current_page' => $registrants->currentPage(),
                'per_page' => $registrants->perPage(),
                'total' => $registrants->total(),
                'last_page' => $registrants->lastPage(),
            ],
        ]);
    }

    /**
     * Siswa menandai data diri sudah lengkap (dipanggil dari portal siswa).
     */
    public function completeData(Request $request, int $id): JsonResponse
    {
        $registrant = ReRegistrant::find($id);

        if (! $registrant) {
            return response()->json([
                'success' => false,
                'message' => 'Registration not found',
                'data' => null,
            ], 404);
        }

        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::connection('mysql')->transaction(function () use ($registrant, $request, $validated) {
            $registrant = ReRegistrant::query()->lockForUpdate()->find($registrant->id);

            if ($registrant->data_completed) {
                throw ValidationException::withMessages([
                    'status' => ['Data has already been marked as completed.'],
                ]);
            }

            $registrant->data_completed = true;
            $registrant->data_completed_at = now();
            $registrant->re_registration_status = 'completed';
            $registrant->re_registration_date = now();
            if (! empty($validated['notes'])) {
                $registrant->re_registration_notes = $validated['notes'];
            }
            $registrant->save();

            AuditLog::create([
                'user_id' => $request->user()->id,
                'action' => 'registration_data_completed',
                'model' => 'ReRegistrant',
                'model_id' => $registrant->id,
                'description' => json_encode([
                    'registration_number' => $registrant->registration_number,
                ]),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Data marked as completed.',
            'data' => new RegistrationAdminResource($registrant->fresh()),
        ]);
    }

    /**
     * Admin verifikasi daftar ulang - materialize ke Student.
     */
    public function verifyReRegistration(Request $request, int $id): JsonResponse
    {
        $registrant = ReRegistrant::find($id);

        if (! $registrant) {
            return response()->json([
                'success' => false,
                'message' => 'Registration not found',
                'data' => null,
            ], 404);
        }

        if ($registrant->re_registration_status !== 'completed') {
            throw ValidationException::withMessages([
                'status' => ['Registration is not ready for verification.'],
            ]);
        }

        if ($registrant->student_id) {
            throw ValidationException::withMessages([
                'status' => ['This registration has already been materialized as a student.'],
            ]);
        }

        $validated = $request->validate([
            're_registration_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::connection('mysql')->transaction(function () use ($registrant, $request, $validated) {
            $registrant = ReRegistrant::query()->lockForUpdate()->find($registrant->id);

            if ($registrant->student_id || $registrant->re_registration_status !== 'completed') {
                throw ValidationException::withMessages([
                    'status' => ['Registration cannot be verified in its current status.'],
                ]);
            }

            // Materialize ke Student
            $this->materializeStudent($registrant, $request->user()->id, $validated);

            // Update status di re_registrants
            $registrant->re_registration_status = 'completed';
            $registrant->re_registration_verified_by = $request->user()->id;
            $registrant->re_registration_verified_at = now();
            $registrant->re_registration_notes = $validated['re_registration_notes'] ?? null;
            $registrant->save();

            AuditLog::create([
                'user_id' => $request->user()->id,
                'action' => 'registration_re_registration_verified',
                'model' => 'ReRegistrant',
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
     * Materialisasi ReRegistrant menjadi Student + User(role Siswa) + Parents/Guardians.
     */
    private function materializeStudent(ReRegistrant $registrant, int $verifierId, array $validated = []): void
    {
        $siswaRoleId = Role::where('name', 'Siswa')->value('id');
        if (! $siswaRoleId) {
            throw ValidationException::withMessages([
                'status' => ['Cannot materialize student: role "Siswa" is missing.'],
            ]);
        }

        $username = $registrant->nisn ?: $registrant->email;
        $user = User::create([
            'role_id' => $siswaRoleId,
            'name' => $registrant->full_name,
            'username' => $username,
            'email' => $registrant->email,
            'password' => $registrant->nisn ?: Hash::make(Str::random(12)),
            'is_active' => true,
        ]);

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

        // Update re_registrants dengan student_id
        $registrant->student_id = $student->id;
        $registrant->save();

        // Parents (satu baris per siswa).
        StudentParent::create([
            'student_id' => $student->id,
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
            'phone' => $registrant->phone,
            'address' => $registrant->address,
        ]);

        // Guardians (hanya bila nama wali ada)
        if (! empty($registrant->guardian_name)) {
            Guardian::create([
                'student_id' => $student->id,
                'name' => $registrant->guardian_name,
                'nik' => $registrant->guardian_nik,
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
            'action' => 'student_created_from_re_registrant',
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

    /**
     * Export list untuk dapodik: re_registration verified + data_completed + belum ada student_id.
     */
    public function exportList(Request $request): JsonResponse
    {
        $query = ReRegistrant::query()
            ->where('re_registration_status', 'completed')
            ->where('data_completed', true)
            ->whereNull('student_id');

        $perPage = (int) ($request->query('per_page') ?? 10);
        $registrants = $query->orderByDesc('re_registration_verified_at')->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Export list retrieved',
            'data' => RegistrationAdminResource::collection($registrants),
            'meta' => [
                'current_page' => $registrants->currentPage(),
                'per_page' => $registrants->perPage(),
                'total' => $registrants->total(),
                'last_page' => $registrants->lastPage(),
            ],
        ]);
    }

    /**
     * Export Excel (CSV) Dapodik: 1 row (id=) atau semua (ids=).
     * Format kolom sesuai spesifikasi Dapodik.
     */
    public function exportDapodik(Request $request): StreamedResponse
    {
        $id = $request->query('id');
        $ids = $request->query('ids');

        $query = ReRegistrant::query()
            ->where('re_registration_status', 'completed')
            ->where('data_completed', true)
            ->whereNull('student_id');

        if ($id) {
            $query->where('id', (int) $id);
        } elseif ($ids) {
            $idList = array_filter(explode(',', (string) $ids), fn ($v) => $v !== '');
            $idList = array_map('intval', $idList);
            if ($idList !== []) $query->whereIn('id', $idList);
        }

        $rows = $query->orderByDesc('re_registration_date')->get();
        $filename = 'export-dapodik-' . now()->format('Y-m-d-His') . '.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            // BOM untuk Excel agar terbaca dengan baik
            fwrite($out, "\xEF\xBB\xBF");

            // Headings (urut sesuai spesifikasi Dapodik)
            $headings = [
                'No','Nama','NIPD','JK','NISN','Tempat Lahir','Tanggal Lahir','NIK','Agama','Alamat',
                'RT','RW','Dusun','Kelurahan','Kecamatan','Kode Pos','Jenis Tinggal','Alat Transportasi',
                'Telepon','HP','E-Mail','SKHUN','Penerima KPS','No. KPS',
                // Data Ayah (6 kolom)
                'Nama Ayah','Tahun Lahir Ayah','Jenjang Pendidikan Ayah','Pekerjaan Ayah','Penghasilan Ayah','NIK Ayah',
                // Data Ibu (6 kolom)
                'Nama Ibu','Tahun Lahir Ibu','Jenjang Pendidikan Ibu','Pekerjaan Ibu','Penghasilan Ibu','NIK Ibu',
                // Data Wali (6 kolom)
                'Nama Wali','Tahun Lahir Wali','Jenjang Pendidikan Wali','Pekerjaan Wali','Penghasilan Wali','NIK Wali',
                // Lanjutan
                'Rombel Saat Ini','No Peserta Ujian Nasional','No Seri Ijazah','Penerima KIP','Nomor KIP',
                'Nama di KIP','Nomor KKS','No Registrasi Akta Lahir','Bank','Nomor Rekening Bank',
                'Rekening Atas Nama','Layak PIP (usulan dari sekolah)','Alasan Layak PIP','Kebutuhan Khusus',
                'Sekolah Asal','Anak ke-berapa','Lintang','Bujur','No KK','Berat Badan','Tinggi Badan',
                'Lingkar Kepala','Jml. Saudara Kandung','Jarak Rumah ke Sekolah (KM)',
            ];
            fputcsv($out, $headings, ';');

            foreach ($rows as $r) {
                $this->writeDapodikRow($out, $r);
            }
            fclose($out);
        }, 200, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Tulis satu baris data ke CSV output sesuai format Dapodik.
     */
    private function writeDapodikRow($out, $r): void
    {
        // Data Ayah
        $fatherBirthYear = '';
        if (!empty($r->father_birth_date)) {
            $fatherBirthYear = is_string($r->father_birth_date)
                ? substr($r->father_birth_date, 0, 4)
                : $r->father_birth_date->format('Y');
        } elseif (!empty($r->father_birth_year)) {
            $fatherBirthYear = $r->father_birth_year;
        }

        // Data Ibu
        $motherBirthYear = '';
        if (!empty($r->mother_birth_date)) {
            $motherBirthYear = is_string($r->mother_birth_date)
                ? substr($r->mother_birth_date, 0, 4)
                : $r->mother_birth_date->format('Y');
        } elseif (!empty($r->mother_birth_year)) {
            $motherBirthYear = $r->mother_birth_year;
        }

        // Data Wali (guardian)
        $guardianBirthYear = '';
        if (!empty($r->guardian_birth_date)) {
            $guardianBirthYear = is_string($r->guardian_birth_date)
                ? substr($r->guardian_birth_date, 0, 4)
                : $r->guardian_birth_date->format('Y');
        } elseif (!empty($r->guardian_birth_year)) {
            $guardianBirthYear = $r->guardian_birth_year;
        }

        $jk = match ($r->gender ?? null) {
            'L' => 'L',
            'P' => 'P',
            default => $r->gender ?? '',
        };

        $row = [
            '', // No
            $r->full_name ?? '', // Nama
            $r->nis ?? '', // NIPD
            $jk, // JK
            $r->nisn ?? '', // NISN
            $r->birth_place ?? '', // Tempat Lahir
            $r->birth_date ? (is_string($r->birth_date) ? $r->birth_date : $r->birth_date->format('Y-m-d')) : '', // Tanggal Lahir
            $r->nik ?? '', // NIK
            $r->religion ?? '', // Agama
            $r->address ?? '', // Alamat
            $r->rt ?? '', // RT
            $r->rw ?? '', // RW
            $r->village ?? '', // Dusun
            $r->village ?? '', // Kelurahan
            $r->district ?? '', // Kecamatan
            $r->postal_code ?? '', // Kode Pos
            '', // Jenis Tinggal
            '', // Alat Transportasi
            $r->phone ?? '', // Telepon
            $r->phone ?? '', // HP
            $r->email ?? '', // E-Mail
            '', // SKHUN
            '', // Penerima KPS
            '', // No. KPS
            // Data Ayah (6 kolom)
            $r->father_name ?? '', // Nama Ayah
            $fatherBirthYear, // Tahun Lahir Ayah
            $r->father_education ?? '', // Jenjang Pendidikan Ayah
            $r->father_occupation ?? '', // Pekerjaan Ayah
            $r->father_income ?? '', // Penghasilan Ayah
            $r->father_nik ?? '', // NIK Ayah
            // Data Ibu (6 kolom)
            $r->mother_name ?? '', // Nama Ibu
            $motherBirthYear, // Tahun Lahir Ibu
            $r->mother_education ?? '', // Jenjang Pendidikan Ibu
            $r->mother_occupation ?? '', // Pekerjaan Ibu
            $r->mother_income ?? '', // Penghasilan Ibu
            $r->mother_nik ?? '', // NIK Ibu
            // Data Wali (6 kolom)
            $r->guardian_name ?? '', // Nama Wali
            $guardianBirthYear, // Tahun Lahir Wali
            $r->guardian_education ?? '', // Jenjang Pendidikan Wali
            $r->guardian_occupation ?? '', // Pekerjaan Wali
            $r->guardian_income ?? '', // Penghasilan Wali
            $r->guardian_nik ?? '', // NIK Wali
            // Lanjutan
            '', // Rombel Saat Ini
            '', // No Peserta Ujian Nasional
            '', // No Seri Ijazah
            '', // Penerima KIP
            '', // Nomor KIP
            '', // Nama di KIP
            '', // Nomor KKS
            '', // No Registrasi Akta Lahir
            '', // Bank
            '', // Nomor Rekening Bank
            '', // Rekening Atas Nama
            '', // Layak PIP (usulan dari sekolah)
            '', // Alasan Layak PIP
            '', // Kebutuhan Khusus
            '', // Sekolah Asal
            '', // Anak ke-berapa
            '', // Lintang
            '', // Bujur
            '', // No KK
            '', // Berat Badan
            '', // Tinggi Badan
            '', // Lingkar Kepala
            '', // Jml. Saudara Kandung
            '', // Jarak Rumah ke Sekolah (KM)
        ];
        fputcsv($out, $row, ';');
    }
}