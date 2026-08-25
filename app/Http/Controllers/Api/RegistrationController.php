<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreRegistrationRequest;
use App\Http\Requests\Api\UpdateRegistrationRequest;
use App\Http\Resources\RegistrationResource;
use App\Http\Resources\RegistrationAdminResource;
use App\Http\Resources\RegistrationStudentResource;
use App\Models\AuditLog;
use App\Models\Registrant;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RegistrationController extends Controller
{
    public function index(\Illuminate\Http\Request $request): JsonResponse
    {
        $user = $request->user();

        // Guru cannot access PPDB
        if ($user->role->name === 'Guru') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
                'data' => null,
            ], 403);
        }

        $validated = $request->validate([
            'search' => 'nullable|string|max:100',
            'academic_year_id' => 'nullable|integer|exists:academic_years,id',
            'registration_path' => 'nullable|string|in:prestasi,reguler,afirmasi,mutasi',
            'program_choice' => 'nullable|string|in:ipa,ips,bahasa,lainnya',
            'status' => 'nullable|string|in:draft,submitted,verified,selected,not_selected,re_registered,cancelled',
            'verification_status' => 'nullable|string|in:pending,verified,rejected',
            'selection_status' => 'nullable|string|in:pending,selected,not_selected',
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
        ]);

        $query = Registrant::query();

        // Ownership filtering for Siswa
        $user = $request->user();
        $isSiswa = $user->role->name === 'Siswa';

        if ($isSiswa) {
            $query->whereHas('student', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        }

        // Search (within ownership scope)
        if (!empty($validated['search'])) {
            $search = $validated['search'];
            $query->where(function ($q) use ($search) {
                $q->where('registration_number', 'LIKE', "%{$search}%")
                    ->orWhere('full_name', 'LIKE', "%{$search}%");
            });
        }

        // Filters (within ownership scope)
        if (!empty($validated['academic_year_id'])) {
            $query->where('academic_year_id', $validated['academic_year_id']);
        }

        if (!empty($validated['registration_path'])) {
            $query->where('registration_path', $validated['registration_path']);
        }

        if (!empty($validated['program_choice'])) {
            $query->where('program_choice', $validated['program_choice']);
        }

        if (!empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (!empty($validated['verification_status'])) {
            $query->where('verification_status', $validated['verification_status']);
        }

        if (!empty($validated['selection_status'])) {
            $query->where('selection_status', $validated['selection_status']);
        }

        $perPage = $validated['per_page'] ?? 10;
        $registrants = $query->orderBy('id', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Registrations retrieved successfully',
            'data' => RegistrationResource::collection($registrants),
            'meta' => [
                'current_page' => $registrants->currentPage(),
                'per_page' => $registrants->perPage(),
                'total' => $registrants->total(),
                'last_page' => $registrants->lastPage(),
            ],
        ]);
    }

    public function show(\Illuminate\Http\Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        // Guru cannot access PPDB
        if ($user->role->name === 'Guru') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
                'data' => null,
            ], 403);
        }

        $registrant = Registrant::find($id);

        if (!$registrant) {
            return response()->json([
                'success' => false,
                'message' => 'Registration not found',
                'data' => null,
            ], 404);
        }

        $user = $request->user();
        $isSiswa = $user->role->name === 'Siswa';

        // Ownership check for Siswa
        if ($isSiswa) {
            if (!$registrant->student || $registrant->student->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Registration not found',
                    'data' => null,
                ], 404);
            }
        }

        // Admin/Administrator get full details
        $isAdmin = in_array($user->role->name ?? '', ['Admin', 'Administrator']);

        if ($isAdmin) {
            return response()->json([
                'success' => true,
                'message' => 'Registration retrieved successfully',
                'data' => new RegistrationAdminResource($registrant),
            ]);
        }

        // Siswa gets student-specific resource
        if ($isSiswa) {
            return response()->json([
                'success' => true,
                'message' => 'Registration retrieved successfully',
                'data' => new RegistrationStudentResource($registrant),
            ]);
        }

        // Other roles get safe resource
        return response()->json([
            'success' => true,
            'message' => 'Registration retrieved successfully',
            'data' => new RegistrationResource($registrant),
        ]);
    }

    public function store(StoreRegistrationRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // Generate registration number server-side (race-condition safe)
        $validated['registration_number'] = $this->generateRegistrationNumberSafe();
        $validated['registration_date'] = $validated['registration_date'] ?? now()->format('Y-m-d');

        // Server-controlled fields only
        $validated['status'] = 'draft';
        $validated['verification_status'] = 'pending';
        $validated['selection_status'] = 'pending';
        $validated['re_registration_status'] = 'pending';

        // Normalize input
        $validated = $this->normalizeInput($validated);

        try {
            $registrant = DB::connection('mysql')->transaction(function () use ($validated, $request) {
                if (!empty($validated['nik'])) {
                    $nikExists = Registrant::withTrashed()->where('nik', $validated['nik'])->exists();
                    if ($nikExists) {
                        throw ValidationException::withMessages([
                            'nik' => ['The NIK has already been registered.'],
                        ]);
                    }
                }

                if (!empty($validated['nisn'])) {
                    $nisnExists = Registrant::withTrashed()->where('nisn', $validated['nisn'])->exists();
                    if ($nisnExists) {
                        throw ValidationException::withMessages([
                            'nisn' => ['The NISN has already been registered.'],
                        ]);
                    }
                }

                $registrant = Registrant::create($validated)->fresh();

                // Audit log (atomic with registration creation)
                AuditLog::create([
                    'user_id' => $request->user()->id,
                    'action' => 'registration_created',
                    'model' => 'Registrant',
                    'model_id' => $registrant->id,
                    'description' => json_encode([
                        'registration_number' => $registrant->registration_number,
                    ]),
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);

                return $registrant;
            });
        } catch (UniqueConstraintViolationException $e) {
            throw ValidationException::withMessages([
                'registration_number' => ['The registration number has already been taken.'],
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Registration created successfully',
            'data' => new RegistrationAdminResource($registrant),
        ], 201);
    }

    public function update(UpdateRegistrationRequest $request, int $id): JsonResponse
    {
        $registrant = Registrant::find($id);

        if (!$registrant) {
            return response()->json([
                'success' => false,
                'message' => 'Registration not found',
                'data' => null,
            ], 404);
        }

        $validated = $request->validated();

        // Normalize input
        $validated = $this->normalizeInput($validated);

        try {
            DB::connection('mysql')->transaction(function () use ($registrant, $validated, $request) {
                if (!empty($validated['nik']) && $validated['nik'] !== $registrant->nik) {
                    $nikExists = Registrant::withTrashed()->where('nik', $validated['nik'])
                        ->where('id', '!=', $registrant->id)->exists();
                    if ($nikExists) {
                        throw ValidationException::withMessages([
                            'nik' => ['The NIK has already been registered.'],
                        ]);
                    }
                }

                if (!empty($validated['nisn']) && $validated['nisn'] !== $registrant->nisn) {
                    $nisnExists = Registrant::withTrashed()->where('nisn', $validated['nisn'])
                        ->where('id', '!=', $registrant->id)->exists();
                    if ($nisnExists) {
                        throw ValidationException::withMessages([
                            'nisn' => ['The NISN has already been registered.'],
                        ]);
                    }
                }

                $registrant->update($validated);

                // Audit log (atomic with registration update)
                AuditLog::create([
                    'user_id' => $request->user()->id,
                    'action' => 'registration_updated',
                    'model' => 'Registrant',
                    'model_id' => $registrant->id,
                    'description' => json_encode([
                        'registration_number' => $registrant->registration_number,
                    ]),
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);
            });
        } catch (UniqueConstraintViolationException $e) {
            throw ValidationException::withMessages([
                'email' => ['The email has already been taken.'],
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Registration updated successfully',
            'data' => new RegistrationAdminResource($registrant->fresh()),
        ]);
    }

    public function destroy(\Illuminate\Http\Request $request, int $id): JsonResponse
    {
        $registrant = Registrant::find($id);

        if (!$registrant) {
            return response()->json([
                'success' => false,
                'message' => 'Registration not found',
                'data' => null,
            ], 404);
        }

        $registrantId = $registrant->id;
        $registrationNumber = $registrant->registration_number;

        DB::connection('mysql')->transaction(function () use ($registrant, $registrantId, $registrationNumber, $request) {
            $registrant->delete();

            // Audit log (atomic with registration deletion)
            AuditLog::create([
                'user_id' => $request->user()->id,
                'action' => 'registration_deleted',
                'model' => 'Registrant',
                'model_id' => $registrantId,
                'description' => json_encode([
                    'registration_number' => $registrationNumber,
                ]),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Registration deleted successfully',
            'data' => null,
        ]);
    }

    private function generateRegistrationNumberSafe(): string
    {
        $year = date('Y');
        $prefix = "PPDB-{$year}-";

        return DB::connection('mysql')->transaction(function () use ($prefix) {
            $lastRegistration = Registrant::where('registration_number', 'LIKE', "{$prefix}%")
                ->orderBy('registration_number', 'desc')
                ->lockForUpdate()
                ->first();

            if ($lastRegistration) {
                $lastNumber = (int) substr($lastRegistration->registration_number, -6);
                $nextNumber = $lastNumber + 1;
            } else {
                $nextNumber = 1;
            }

            return $prefix . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
        });
    }

    private function normalizeInput(array $data): array
    {
        if (!empty($data['email'])) {
            $data['email'] = strtolower(trim($data['email']));
        }

        $textFields = [
            'nik', 'nisn', 'full_name', 'nickname', 'phone', 'address',
            'rt', 'rw', 'village', 'district', 'city', 'province', 'postal_code',
            'previous_school', 'previous_school_npsn', 'previous_school_address',
            'diploma_number',
            'father_name', 'father_nik', 'father_birth_place', 'father_occupation', 'father_phone', 'father_address',
            'mother_name', 'mother_nik', 'mother_birth_place', 'mother_occupation', 'mother_phone', 'mother_address',
            'guardian_name', 'guardian_nik', 'guardian_occupation', 'guardian_phone', 'guardian_address',
            'special_needs', 'achievements', 'organizations', 'scholarship_info',
        ];

        foreach ($textFields as $field) {
            if (!empty($data[$field]) && is_string($data[$field])) {
                $data[$field] = trim($data[$field]);
            }
        }

        return $data;
    }
}
