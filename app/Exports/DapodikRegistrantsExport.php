<?php

namespace App\Exports;

use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithPredefinedSheetTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class DapodikRegistrantsExport implements FromCollection, WithHeadings, WithMapping, WithPredefinedSheetTitle, ShouldAutoSize
{
    private $registrants;

    public function __construct($registrants)
    {
        $this->registrants = $registrants instanceof Builder ? $registrants->get() : collect($registrants);
    }

    public function collection()
    {
        return $this->registrants;
    }

    public function headings(): array
    {
        return [
            // Row 1 header
            'PNo', 'Nama', 'NIPD', 'JK', 'NISN', 'Tempat Lahir', 'Tanggal Lahir', 'NIK', 'Agama', 'Alamat', 'RT', 'RW', 'Dusun', 'Kelurahan', 'Kecamatan', 'Kode Pos', 'Jenis Tinggal', 'Alat Transportasi', 'Telepon', 'HP', 'E-Mail', 'SKHUN', 'Penerima KPS', 'No. KPS',
            // Data Ayah (6 kolom)
            'Ayah - Nama', 'Ayah - Tahun Lahir', 'Ayah - Jenjang Pendidikan', 'Ayah - Pekerjaan', 'Ayah - Penghasilan', 'Ayah - NIK',
            // Data Ibu (6 kolom)
            'Ibu - Nama', 'Ibu - Tahun Lahir', 'Ibu - Jenjang Pendidikan', 'Ibu - Pekerjaan', 'Ibu - Penghasilan', 'Ibu - NIK',
            // Data Wali (6 kolom)
            'Wali - Nama', 'Wali - Tahun Lahir', 'Wali - Jenjang Pendidikan', 'Wali - Pekerjaan', 'Wali - Penghasilan', 'Wali - NIK',
            // Lanjutan
            'Rombel Saat Ini', 'No Peserta Ujian Nasional', 'No Seri Ijazah', 'Penerima KIP', 'Nomor KIP', 'Nama di KIP', 'Nomor KKS', 'No Registrasi Akta Lahir', 'Bank', 'Nomor Rekening Bank', 'Rekening Atas Nama', 'Layak PIP (usulan dari sekolah)', 'Alasan Layak PIP', 'Kebutuhan Khusus', 'Sekolah Asal', 'Anak ke-berapa', 'Lintang', 'Bujur', 'No KK', 'Berat Badan', 'Tinggi Badan', 'Lingkar Kepala', 'Jml. Saudara Kandung', 'Jarak Rumah ke Sekolah (KM)',
        ];
    }

    public function map(mixed $row): array
    {
        $r = $row;
        $jk = match ($r->gender ?? null) {
            'L' => 'L',
            'P' => 'P',
            default => $r->gender ?? '',
        };
        $dob = isset($r->birth_date) ? (is_string($r->birth_date) ? $r->birth_date : $r->birth_date?->format('Y-m-d')) : '';
        // father/mother year fallback: try birth_year fields if exist else from birth_date
        $fatherYear = $r->father_birth_year ?? ($r->father_birth_date ? (is_string($r->father_birth_date) ? substr($r->father_birth_date, 0, 4) : $r->father_birth_date->format('Y')) : '');
        $motherYear = $r->mother_birth_year ?? ($r->mother_birth_date ? (is_string($r->mother_birth_date) ? substr($r->mother_birth_date, 0, 4) : $r->mother_birth_date->format('Y')) : '');
        $guardianYear = $r->guardian_birth_year ?? ($r->guardian_birth_date ?? '');

        return [
            '', // PNo - nomor urut kosong, isi manual di dapodik
            $r->full_name ?? '',
            $r->nis ?? $r->nisn ?? '', // NIPD
            $jk,
            $r->nisn ?? '',
            $r->birth_place ?? '',
            $dob ?? '',
            $r->nik ?? '',
            $r->religion ?? '',
            $r->address ?? '',
            $r->rt ?? '',
            $r->rw ?? '',
            $r->dusun ?? $r->village ?? '', // Dusun fallback village
            $r->village ?? '',
            $r->district ?? '',
            $r->postal_code ?? '',
            $r->jenis_tinggal ?? '', // Jenis Tinggal - tidak ada di DB
            $r->alat_transportasi ?? '', // Alat Transportasi - tidak ada di DB
            $r->phone ?? '',
            $r->phone ?? '', // HP same as phone
            $r->email ?? '',
            $r->skhun ?? '', // SKHUN tidak ada di DB
            $r->penerima_kps ?? '',
            $r->no_kps ?? $r->document_kip_kks ?? '',
            // Ayah
            $r->father_name ?? '',
            $fatherYear ?? '',
            $r->father_education ?? '',
            $r->father_occupation ?? '',
            $r->father_income ? (string) $r->father_income : '',
            $r->father_nik ?? '',
            // Ibu
            $r->mother_name ?? '',
            $motherYear ?? '',
            $r->mother_education ?? '',
            $r->mother_occupation ?? '',
            $r->mother_income ? (string) $r->mother_income : '',
            $r->mother_nik ?? '',
            // Wali
            $r->guardian_name ?? '',
            $guardianYear ?? '',
            $r->guardian_education ?? '',
            $r->guardian_occupation ?? '',
            $r->guardian_income ? (string) $r->guardian_income : '',
            $r->guardian_nik ?? '',
            // Lanjutan
            $r->rombel ?? '',
            $r->no_peserta_un ?? '',
            $r->no_seri_ijazah ?? $r->diploma_number ?? '',
            $r->penerima_kip ?? '',
            $r->nomor_kip ?? '',
            $r->nama_di_kip ?? '',
            $r->nomor_kks ?? '',
            $r->no_reg_akta_lahir ?? '',
            $r->bank ?? '',
            $r->no_rekening_bank ?? '',
            $r->rekening_atas_nama ?? '',
            $r->layak_pip ?? '',
            $r->alasan_layak_pip ?? '',
            $r->special_needs ?? $r->kebutuhan_khusus ?? '',
            $r->previous_school ?? '',
            $r->birth_order ?? $r->anak_ke ?? '',
            $r->lintang ?? '',
            $r->bujur ?? '',
            $r->no_kk ?? $r->document_kk ?? '',
            $r->berat_badan ?? '',
            $r->tinggi_badan ?? '',
            $r->lingkar_kepala ?? '',
            $r->sibling_count ?? $r->jml_saudara ?? '',
            $r->jarak_rumah ?? '',
        ];
    }

    public function sheetTitle(): string
    {
        return 'Export Dapodik';
    }
}
