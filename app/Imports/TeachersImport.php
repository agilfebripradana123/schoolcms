<?php

namespace App\Imports;

use App\Models\Staff\Teacher;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use PhpOffice\PhpSpreadsheet\Shared\Date as SpreadsheetDate;

class TeachersImport implements ToCollection
{
    private const EXPECTED_HEADERS = [
        'Kode Guru',
        'NIP',
        'Nama Lengkap',
        'Jenis Kelamin',
        'Tempat Lahir',
        'Tanggal Lahir',
        'No. HP',
        'Email',
        'Agama',
        'Alamat',
        'Pendidikan Terakhir',
        'Jurusan',
        'Status Kepegawaian',
        'Tanggal Bergabung',
    ];

    private bool $headerValid = false;
    private array $errors = [];
    private array $validData = [];
    private array $seenCodes = [];
    private array $seenNips = [];
    private int $totalRows = 0;

    private array $existingCodes = [];
    private array $existingNips = [];

    public function __construct()
    {
        $this->existingCodes = Teacher::whereNull('deleted_at')
            ->pluck('teacher_code')
            ->filter()
            ->map(fn ($c) => strtoupper(trim($c)))
            ->values()
            ->toArray();

        $this->existingNips = Teacher::whereNull('deleted_at')
            ->pluck('nip')
            ->filter()
            ->map(fn ($n) => strtoupper(trim($n)))
            ->values()
            ->toArray();
    }

    public function collection(Collection $rows): void
    {
        if ($rows->isEmpty()) {
            $this->errors[] = [
                'row' => 1,
                'field' => 'file',
                'message' => 'File is empty',
            ];
            return;
        }

        $headerRow = $rows->first();
        $headers = is_array($headerRow) ? array_values($headerRow) : array_values($headerRow->toArray());

        $normalizedHeaders = array_map(fn ($h) => trim((string) $h), $headers);

        if ($normalizedHeaders !== self::EXPECTED_HEADERS) {
            $this->headerValid = false;
            return;
        }

        $this->headerValid = true;

        $dataRows = $rows->slice(1);
        $this->totalRows = $dataRows->count();

        foreach ($dataRows->values() as $index => $row) {
            $excelRow = $index + 2;
            $rowData = is_array($row) ? array_values($row) : array_values($row->toArray());
            $this->processRow($rowData, $excelRow);
        }
    }

    private function processRow(array $row, int $excelRow): void
    {
        $teacherCode = trim((string) ($row[0] ?? ''));
        $nip = trim((string) ($row[1] ?? ''));
        $fullName = $this->cleanNullable($row[2] ?? null);
        $gender = strtoupper(trim((string) ($row[3] ?? '')));
        $birthPlace = $this->cleanNullable($row[4] ?? null);
        $birthDateRaw = $row[5] ?? null;
        $phone = $this->cleanNullable($row[6] ?? null);
        $email = $this->cleanNullable($row[7] ?? null);
        $religion = $this->cleanNullable($row[8] ?? null);
        $address = $this->cleanNullable($row[9] ?? null);
        $lastEducation = $this->cleanNullable($row[10] ?? null);
        $major = $this->cleanNullable($row[11] ?? null);
        $employmentStatus = $this->cleanNullable($row[12] ?? null);
        $joinDateRaw = $row[13] ?? null;

        if (empty($teacherCode)) {
            $this->errors[] = ['row' => $excelRow, 'field' => 'teacher_code', 'message' => 'Kode Guru is required'];
            return;
        }

        if (strlen($teacherCode) > 20) {
            $this->errors[] = ['row' => $excelRow, 'field' => 'teacher_code', 'message' => 'Kode Guru must not exceed 20 characters'];
            return;
        }

        if (empty($nip)) {
            $this->errors[] = ['row' => $excelRow, 'field' => 'nip', 'message' => 'NIP is required'];
            return;
        }

        if (strlen($nip) > 30) {
            $this->errors[] = ['row' => $excelRow, 'field' => 'nip', 'message' => 'NIP must not exceed 30 characters'];
            return;
        }

        if (!in_array($gender, ['L', 'P'], true)) {
            $this->errors[] = ['row' => $excelRow, 'field' => 'gender', 'message' => 'Jenis Kelamin must be L or P'];
            return;
        }

        $birthDate = $this->parseDate($birthDateRaw);
        if ($birthDateRaw !== null && $birthDateRaw !== '' && $birthDate === false) {
            $this->errors[] = ['row' => $excelRow, 'field' => 'birth_date', 'message' => 'Tanggal Lahir is not a valid date'];
            return;
        }

        $joinDate = $this->parseDate($joinDateRaw);
        if ($joinDateRaw !== null && $joinDateRaw !== '' && $joinDate === false) {
            $this->errors[] = ['row' => $excelRow, 'field' => 'join_date', 'message' => 'Tanggal Bergabung is not a valid date'];
            return;
        }

        if ($email !== null && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->errors[] = ['row' => $excelRow, 'field' => 'email', 'message' => 'Email is not valid'];
            return;
        }

        if (strlen((string) ($fullName ?? '')) > 150) {
            $this->errors[] = ['row' => $excelRow, 'field' => 'full_name', 'message' => 'Nama Lengkap must not exceed 150 characters'];
            return;
        }

        if (strlen((string) ($phone ?? '')) > 20) {
            $this->errors[] = ['row' => $excelRow, 'field' => 'phone', 'message' => 'No. HP must not exceed 20 characters'];
            return;
        }

        if (strlen((string) ($email ?? '')) > 150) {
            $this->errors[] = ['row' => $excelRow, 'field' => 'email', 'message' => 'Email must not exceed 150 characters'];
            return;
        }

        if (strlen((string) ($religion ?? '')) > 30) {
            $this->errors[] = ['row' => $excelRow, 'field' => 'religion', 'message' => 'Agama must not exceed 30 characters'];
            return;
        }

        if (strlen((string) ($lastEducation ?? '')) > 50) {
            $this->errors[] = ['row' => $excelRow, 'field' => 'last_education', 'message' => 'Pendidikan Terakhir must not exceed 50 characters'];
            return;
        }

        if (strlen((string) ($major ?? '')) > 100) {
            $this->errors[] = ['row' => $excelRow, 'field' => 'major', 'message' => 'Jurusan must not exceed 100 characters'];
            return;
        }

        if (strlen((string) ($employmentStatus ?? '')) > 50) {
            $this->errors[] = ['row' => $excelRow, 'field' => 'employment_status', 'message' => 'Status Kepegawaian must not exceed 50 characters'];
            return;
        }

        if (strlen((string) ($birthPlace ?? '')) > 100) {
            $this->errors[] = ['row' => $excelRow, 'field' => 'birth_place', 'message' => 'Tempat Lahir must not exceed 100 characters'];
            return;
        }

        $upperCode = strtoupper($teacherCode);
        if (in_array($upperCode, $this->existingCodes, true)) {
            $this->errors[] = ['row' => $excelRow, 'field' => 'teacher_code', 'message' => 'Kode Guru already exists in database'];
            return;
        }

        $upperNip = strtoupper($nip);
        if (in_array($upperNip, $this->existingNips, true)) {
            $this->errors[] = ['row' => $excelRow, 'field' => 'nip', 'message' => 'NIP already exists in database'];
            return;
        }

        if (in_array($upperCode, $this->seenCodes, true)) {
            $this->errors[] = ['row' => $excelRow, 'field' => 'teacher_code', 'message' => 'Kode Guru duplicate in file'];
            return;
        }

        if (in_array($upperNip, $this->seenNips, true)) {
            $this->errors[] = ['row' => $excelRow, 'field' => 'nip', 'message' => 'NIP duplicate in file'];
            return;
        }

        $this->seenCodes[] = $upperCode;
        $this->seenNips[] = $upperNip;

        $this->validData[] = [
            'teacher_code' => $teacherCode,
            'nip' => $nip,
            'full_name' => $fullName,
            'gender' => $gender,
            'birth_place' => $birthPlace,
            'birth_date' => $birthDate,
            'phone' => $phone,
            'email' => $email,
            'religion' => $religion,
            'address' => $address,
            'last_education' => $lastEducation,
            'major' => $major,
            'employment_status' => $employmentStatus,
            'join_date' => $joinDate,
            'user_id' => null,
            'prefix_title' => null,
            'suffix_title' => null,
            'photo' => null,
            'is_active' => true,
        ];
    }

    private function cleanNullable($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }

    private function parseDate($value)
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof \DateTime) {
            return $value->format('Y-m-d');
        }

        if (is_numeric($value)) {
            try {
                $date = SpreadsheetDate::excelToDateTimeObject((float) $value);

                return $date->format('Y-m-d');
            } catch (\Exception $e) {
                return false;
            }
        }

        $str = trim((string) $value);

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $str)) {
            $parts = explode('-', $str);

            return checkdate((int) $parts[1], (int) $parts[2], (int) $parts[0]) ? $str : false;
        }

        if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $str)) {
            $parts = explode('/', $str);
            $dateStr = "{$parts[2]}-{$parts[1]}-{$parts[0]}";

            return checkdate((int) $parts[1], (int) $parts[0], (int) $parts[2]) ? $dateStr : false;
        }

        try {
            $date = \Carbon\Carbon::parse($str);

            if ($date->year >= 1900 && $date->year <= 2100) {
                return $date->format('Y-m-d');
            }

            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function isHeaderValid(): bool
    {
        return $this->headerValid;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getValidData(): array
    {
        return $this->validData;
    }

    public function getTotalRows(): int
    {
        return $this->totalRows;
    }

    public function getImportedCount(): int
    {
        return count($this->validData);
    }

    public function getFailedCount(): int
    {
        return count($this->errors);
    }

    public function hasErrors(): bool
    {
        return count($this->errors) > 0;
    }
}
