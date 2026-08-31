<?php

namespace App\Http\Controllers\Api\PPDB;

use App\Http\Controllers\Controller;
use App\Models\PPDB\Registrant;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PublicRegistrationController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'full_name'         => ['required', 'string', 'max:150'],
                'email'             => ['required', 'email', 'max:150', Rule::unique('registrants', 'email')],
                'phone'             => ['nullable', 'string', 'max:20'],
                'gender'            => ['required', 'string', Rule::in(['L', 'P'])],
                'birth_place'       => ['nullable', 'string', 'max:100'],
                'birth_date'        => ['nullable', 'date', 'before_or_equal:today'],
                'address'           => ['nullable', 'string', 'max:500'],
                'rt'                => ['nullable', 'string', 'max:5'],
                'rw'                => ['nullable', 'string', 'max:5'],
                'district'          => ['nullable', 'string', 'max:100'],
                'city'              => ['nullable', 'string', 'max:100'],
                'province'          => ['nullable', 'string', 'max:100'],
                'postal_code'       => ['nullable', 'string', 'max:10'],
                'previous_school'   => ['nullable', 'string', 'max:150'],
                'nisn'              => ['nullable', 'string', 'max:20'],
                'nik'               => ['nullable', 'string', 'max:20'],
                'graduation_year'   => ['nullable', 'integer', 'min:2000', 'max:2030'],
                'religion'          => ['nullable', 'string', Rule::in(['islam', 'kristen', 'katolik', 'hindu', 'buddha', 'konghucu'])],
                'sibling_count'     => ['nullable', 'integer', 'min:0', 'max:20'],
                'birth_order'       => ['nullable', 'integer', 'min:1', 'max:20'],

                'father_name'           => ['nullable', 'string', 'max:150'],
                'father_nik'            => ['nullable', 'string', 'max:20'],
                'father_birth_place'    => ['nullable', 'string', 'max:100'],
                'father_birth_date'     => ['nullable', 'date', 'before_or_equal:today'],
                'father_education'      => ['nullable', 'string', Rule::in(['sd', 'smp', 'sma', 'smk', 'd3', 's1', 's2', 's3'])],
                'father_occupation'     => ['nullable', 'string', 'max:100'],
                'father_phone'          => ['nullable', 'string', 'max:20'],

                'mother_name'           => ['nullable', 'string', 'max:150'],
                'mother_nik'            => ['nullable', 'string', 'max:20'],
                'mother_birth_place'    => ['nullable', 'string', 'max:100'],
                'mother_birth_date'     => ['nullable', 'date', 'before_or_equal:today'],
                'mother_education'      => ['nullable', 'string', Rule::in(['sd', 'smp', 'sma', 'smk', 'd3', 's1', 's2', 's3'])],
                'mother_occupation'     => ['nullable', 'string', 'max:100'],
                'mother_phone'          => ['nullable', 'string', 'max:20'],

                'guardian_name'         => ['nullable', 'string', 'max:150'],
                'guardian_nik'          => ['nullable', 'string', 'max:20'],
                'guardian_occupation'   => ['nullable', 'string', 'max:100'],
                'guardian_phone'        => ['nullable', 'string', 'max:20'],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $e->errors(),
            ], 422);
        }

        $validated = $this->normalizeInput($validated);

        try {
            $registrant = DB::connection('mysql')->transaction(function () use ($validated) {
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

                $validated['registration_number']    = $this->generateRegistrationNumberSafe();
                $validated['registration_date']     = $validated['registration_date'] ?? now()->format('Y-m-d');
                $validated['status']                = 'draft';
                $validated['verification_status']   = 'pending';
                $validated['selection_status']      = 'pending';
                $validated['re_registration_status'] = 'pending';

                return Registrant::create($validated)->fresh();
            });
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Duplicate or invalid data',
                'errors'  => $e->errors(),
            ], 409);
        } catch (UniqueConstraintViolationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'The registration number has already been taken.',
                'errors'  => ['registration_number' => ['The registration number has already been taken.']],
            ], 409);
        }

        return response()->json([
            'success' => true,
            'message' => 'Registration created successfully',
            'data'    => [
                'id'                 => $registrant->id,
                'registration_number'=> $registrant->registration_number,
                'full_name'          => $registrant->full_name,
                'email'              => $registrant->email,
            ],
        ], 201);
    }

    private function generateRegistrationNumberSafe(): string
    {
        $year   = date('Y');
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
            'nik', 'nisn', 'full_name', 'phone', 'address',
            'rt', 'rw', 'district', 'city', 'province', 'postal_code',
            'previous_school',
            'father_name', 'father_nik', 'father_birth_place', 'father_occupation', 'father_phone',
            'mother_name', 'mother_nik', 'mother_birth_place', 'mother_occupation', 'mother_phone',
            'guardian_name', 'guardian_nik', 'guardian_occupation', 'guardian_phone',
        ];

        foreach ($textFields as $field) {
            if (!empty($data[$field]) && is_string($data[$field])) {
                $data[$field] = trim($data[$field]);
            }
        }

        return $data;
    }
}
