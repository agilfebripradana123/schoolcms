<?php

namespace Database\Seeders;

use App\Models\System\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Idempotent seeder — safe to run multiple times.
     * Uses updateOrCreate to create or update without duplicates.
     */
    public function run(): void
    {
        $permissions = [
            // System
            ['name' => 'manage-users', 'description' => 'Mengelola pengguna sistem.'],
            ['name' => 'manage-roles', 'description' => 'Mengelola peran dan assignment hak akses.'],
            ['name' => 'manage-settings', 'description' => 'Mengelola pengaturan sistem.'],
            ['name' => 'view-audit-logs', 'description' => 'Melihat log aktivitas sistem.'],

            // Academic
            ['name' => 'manage-academic-years', 'description' => 'Mengelola tahun ajaran, semester, dan kurikulum.'],
            ['name' => 'manage-subjects', 'description' => 'Mengelola mata pelajaran.'],
            ['name' => 'manage-classes', 'description' => 'Mengelola kelas, penugasan siswa, dan penugasan mata pelajaran ke kelas.'],
            ['name' => 'manage-schedules', 'description' => 'Mengelola jadwal pelajaran.'],
            ['name' => 'manage-grades', 'description' => 'Mengelola data penilaian, tugas, periode, dan rapor.'],

            // Staff
            ['name' => 'manage-teachers', 'description' => 'Mengelola data guru, penugasan, kehadiran, cuti, dan dokumen.'],
            ['name' => 'manage-staff', 'description' => 'Mengelola tenaga kependidikan.'],

            // Students
            ['name' => 'manage-students', 'description' => 'Mengelola data siswa, kehadiran, orang tua, wali, riwayat, mutasi, alumni, dan kartu pelajar.'],

            // Finance
            ['name' => 'manage-finance', 'description' => 'Mengelola jenis biaya, tagihan, pembayaran, transaksi, dan laporan keuangan.'],
            ['name' => 'manage-scholarships', 'description' => 'Mengelola beasiswa.'],

            // Examination
            ['name' => 'manage-exams', 'description' => 'Mengelola ujian, bank soal, sesi, jadwal, instruksi, peserta, dan hasil.'],

            // Facilities
            ['name' => 'manage-facilities', 'description' => 'Mengelola ruangan, aset, pemeliharaan, dan inventaris.'],

            // Communication
            ['name' => 'manage-announcements', 'description' => 'Mengelola pengumuman.'],
            ['name' => 'manage-notifications', 'description' => 'Mengelola notifikasi.'],
            ['name' => 'manage-calendars', 'description' => 'Mengelola kalender akademik.'],

            // Development
            ['name' => 'manage-development', 'description' => 'Mengelola prestasi, pelanggaran, bimbingan konseling, dan ekstrakurikuler.'],

            // Administration
            ['name' => 'manage-letters', 'description' => 'Mengelola surat masuk, surat keluar, dan disposisi.'],
            ['name' => 'manage-documents', 'description' => 'Mengelola dokumen administrasi.'],

            // PPDB
            ['name' => 'manage-ppdb', 'description' => 'Mengelola pendaftaran dan daftar ulang siswa baru.'],

            // Reports
            ['name' => 'view-reports', 'description' => 'Melihat laporan akademik, siswa, guru, keuangan, kehadiran, dan inventaris.'],
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                ['name' => $permission['name']],
                ['description' => $permission['description']]
            );
        }
    }
}
