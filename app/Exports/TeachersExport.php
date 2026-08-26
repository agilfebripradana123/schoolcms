<?php

namespace App\Exports;

use App\Models\Staff\Teacher;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class TeachersExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    private Builder $query;

    public function __construct(array $filters = [])
    {
        $query = Teacher::query()->whereNull('deleted_at');

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('teacher_code', 'like', "%{$search}%")
                    ->orWhere('nip', 'like', "%{$search}%")
                    ->orWhere('full_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['gender'])) {
            $query->where('gender', $filters['gender']);
        }

        if (!empty($filters['employment_status'])) {
            $query->where('employment_status', $filters['employment_status']);
        }

        if (array_key_exists('is_active', $filters)) {
            $query->where('is_active', $filters['is_active']);
        }

        $this->query = $query->orderBy('id', 'asc');
    }

    public function query(): Builder
    {
        return $this->query;
    }

    public function headings(): array
    {
        return [
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
    }

    public function map(mixed $row): array
    {
        $teacher = $row instanceof Teacher ? $row : (array) $row;

        return [
            $teacher->teacher_code,
            $teacher->nip,
            $teacher->full_name,
            $teacher->gender,
            $teacher->birth_place,
            $teacher->birth_date?->format('Y-m-d'),
            $teacher->phone,
            $teacher->email,
            $teacher->religion,
            $teacher->address,
            $teacher->last_education,
            $teacher->major,
            $teacher->employment_status,
            $teacher->join_date?->format('Y-m-d'),
        ];
    }
}
