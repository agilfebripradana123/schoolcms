<?php

namespace Database\Seeders;

use App\Models\Facilities\Asset;
use App\Models\Facilities\Inventory;
use App\Models\Facilities\Maintenance;
use App\Models\Facilities\Room;
use App\Models\Facilities\StockMovement;
use Database\Factories\Facilities\AssetFactory;
use Database\Factories\Facilities\InventoryFactory;
use Database\Factories\Facilities\MaintenanceFactory;
use Database\Factories\Facilities\RoomFactory;
use Database\Factories\Facilities\StockMovementFactory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * DEVELOPMENT / TESTING dummy data ONLY — do NOT run against production.
 *
 * Idempotent by design:
 *  - Rooms / Assets / Maintenance / Inventory are keyed on deterministic codes
 *    and are only inserted when the code does not already exist (firstOrCreate
 *    semantics via existence guard). Existing rows (user/production data) are
 *    NEVER modified or deleted.
 *  - StockMovements have no natural key, so they are only inserted when the
 *    stock_movements table is still empty.
 *
 * Run with: php artisan db:seed --class=FacilitiesSeeder
 */
class FacilitiesSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's Facilities dummy data.
     */
    public function run(): void
    {
        $rooms = collect($this->rooms());

        $this->seedRooms();
        $this->seedAssets();
        $this->seedMaintenance();
        $this->seedInventory();
        $this->seedStockMovements();

        $this->command?->info(
            sprintf(
                'Facilities seeded: %d rooms, %d assets, %d maintenance, %d inventory, %d stock movements.',
                Room::count(),
                Asset::count(),
                Maintenance::count(),
                Inventory::count(),
                StockMovement::count(),
            ),
        );
    }

    // ---------------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------------

    /**
     * Idempotent create-by-code using the matching factory.
     */
    private function firstCreateByCode(
        \Illuminate\Database\Eloquent\Factories\Factory $factory,
        string $model,
        string $code,
        array $attributes,
    ): \Illuminate\Database\Eloquent\Model {
        if ($model::where('code', $code)->exists()) {
            return $model::where('code', $code)->first();
        }

        return $factory->create($attributes);
    }

    private function roomIdByCode(string $code): ?int
    {
        return Room::where('code', $code)->value('id');
    }

    private function assetIdByCode(string $code): ?int
    {
        return Asset::where('code', $code)->value('id');
    }

    private function inventoryIdByCode(string $code): ?int
    {
        return Inventory::where('code', $code)->value('id');
    }

    // ---------------------------------------------------------------------
    // Phase 1 — Rooms
    // ---------------------------------------------------------------------

    private function seedRooms(): void
    {
        foreach ($this->rooms() as $room) {
            $this->firstCreateByCode(new RoomFactory, Room::class, $room['code'], $room);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function rooms(): array
    {
        return [
            ['code' => 'R-001', 'name' => 'Ruang Kelas 1A', 'capacity' => 32, 'location' => 'Lantai 1, Timur', 'has_computer' => false, 'status' => 'active'],
            ['code' => 'R-002', 'name' => 'Ruang Kelas 1B', 'capacity' => 32, 'location' => 'Lantai 1, Timur', 'has_computer' => false, 'status' => 'active'],
            ['code' => 'R-003', 'name' => 'Ruang Kelas 2A', 'capacity' => 34, 'location' => 'Lantai 1, Barat', 'has_computer' => false, 'status' => 'active'],
            ['code' => 'R-004', 'name' => 'Ruang Kelas 2B', 'capacity' => 34, 'location' => 'Lantai 1, Barat', 'has_computer' => false, 'status' => 'inactive'],
            ['code' => 'R-005', 'name' => 'Laboratorium Komputer', 'capacity' => 30, 'location' => 'Lantai 2, Tengah', 'has_computer' => true, 'status' => 'active'],
            ['code' => 'R-006', 'name' => 'Laboratorium IPA', 'capacity' => 28, 'location' => 'Lantai 2, Utara', 'has_computer' => false, 'status' => 'active'],
            ['code' => 'R-007', 'name' => 'Perpustakaan', 'capacity' => 40, 'location' => 'Lantai 1, Utara', 'has_computer' => false, 'status' => 'active'],
            ['code' => 'R-008', 'name' => 'Ruang Guru', 'capacity' => 24, 'location' => 'Lantai 2, Selatan', 'has_computer' => true, 'status' => 'active'],
            ['code' => 'R-009', 'name' => 'Ruang Kepala Sekolah', 'capacity' => 6, 'location' => 'Lantai 2, Selatan', 'has_computer' => true, 'status' => 'active'],
            ['code' => 'R-010', 'name' => 'Ruang Tata Usaha', 'capacity' => 8, 'location' => 'Lantai 1, Selatan', 'has_computer' => true, 'status' => 'active'],
            ['code' => 'R-011', 'name' => 'Ruang UKS', 'capacity' => 4, 'location' => 'Lantai 2, Timur', 'has_computer' => false, 'status' => 'inactive'],
            ['code' => 'R-012', 'name' => 'Aula', 'capacity' => 120, 'location' => 'Lantai 1, Tengah', 'has_computer' => false, 'status' => 'active'],
        ];
    }

    // ---------------------------------------------------------------------
    // Phase 2 — Assets
    // ---------------------------------------------------------------------

    private function seedAssets(): void
    {
        foreach ($this->assets() as $asset) {
            $asset['room_id'] = $asset['room_code'] ? $this->roomIdByCode($asset['room_code']) : null;
            unset($asset['room_code']);

            $this->firstCreateByCode(new AssetFactory, Asset::class, $asset['code'], $asset);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function assets(): array
    {
        return [
            ['code' => 'AST-001', 'name' => 'Laptop Acer TravelMate', 'description' => 'Laptop untuk guru, dilengkapi webcam dan port HDMI.', 'category' => 'electronics', 'quantity' => 12, 'condition' => 'good', 'location' => 'Rak Lab', 'room_code' => 'R-008', 'purchase_date' => '2023-01-15', 'purchase_price' => 8500000, 'status' => 'active'],
            ['code' => 'AST-002', 'name' => 'Desktop Komputer Lab', 'description' => 'PC unit untuk laboratorium komputer.', 'category' => 'electronics', 'quantity' => 20, 'condition' => 'fair', 'location' => 'Rak Lab', 'room_code' => 'R-005', 'purchase_date' => '2021-07-20', 'purchase_price' => 6000000, 'status' => 'active'],
            ['code' => 'AST-003', 'name' => 'Monitor LED 21 inch', 'description' => 'Monitor standar untuk workstation lab.', 'category' => 'electronics', 'quantity' => 20, 'condition' => 'good', 'location' => 'Rak Lab', 'room_code' => 'R-005', 'purchase_date' => '2021-07-20', 'purchase_price' => 2100000, 'status' => 'active'],
            ['code' => 'AST-004', 'name' => 'Proyektor LCD Epson', 'description' => 'Proyektor untuk pembelajaran multimedia.', 'category' => 'electronics', 'quantity' => 6, 'condition' => 'good', 'location' => 'Rak Lab', 'room_code' => 'R-005', 'purchase_date' => '2022-02-10', 'purchase_price' => 4200000, 'status' => 'active'],
            ['code' => 'AST-005', 'name' => 'Printer Laserjet', 'description' => 'Printer untuk kebutuhan administrasi, sebagian tidak berfungsi.', 'category' => 'electronics', 'quantity' => 3, 'condition' => 'damaged', 'location' => 'Meja Operasional', 'room_code' => 'R-010', 'purchase_date' => '2019-11-05', 'purchase_price' => 3200000, 'status' => 'active'],
            ['code' => 'AST-006', 'name' => 'Scanner Dokumen', 'description' => 'Scanner untuk digitalisasi arsip surat.', 'category' => 'electronics', 'quantity' => 1, 'condition' => 'fair', 'location' => 'Meja Operasional', 'room_code' => 'R-010', 'purchase_date' => '2018-05-22', 'purchase_price' => 1500000, 'status' => 'inactive'],
            ['code' => 'AST-007', 'name' => 'Air Conditioner 2 PK', 'description' => 'Pendingin ruangan untuk lab komputer.', 'category' => 'electronics', 'quantity' => 4, 'condition' => 'good', 'location' => 'Dinding Lab', 'room_code' => 'R-005', 'purchase_date' => '2020-06-01', 'purchase_price' => 5500000, 'status' => 'active'],
            ['code' => 'AST-008', 'name' => 'Air Conditioner 1 PK', 'description' => 'Pendingin ruangan untuk ruang kelas.', 'category' => 'electronics', 'quantity' => 8, 'condition' => 'poor', 'location' => 'Dinding Kelas', 'room_code' => 'R-001', 'purchase_date' => '2017-09-15', 'purchase_price' => 4300000, 'status' => 'active'],
            ['code' => 'AST-009', 'name' => 'Sound System Aktif', 'description' => 'Speaker aktif dan mixer untuk acara di aula.', 'category' => 'electronics', 'quantity' => 2, 'condition' => 'good', 'location' => 'Panggung Aula', 'room_code' => 'R-012', 'purchase_date' => '2021-03-08', 'purchase_price' => 7800000, 'status' => 'active'],
            ['code' => 'AST-010', 'name' => 'Televisi LED 55 inch', 'description' => 'Televisi untuk keperluan acara dan informasi.', 'category' => 'electronics', 'quantity' => 2, 'condition' => 'fair', 'location' => 'Panggung Aula', 'room_code' => 'R-012', 'purchase_date' => '2020-12-18', 'purchase_price' => 6900000, 'status' => 'active'],
            ['code' => 'AST-011', 'name' => 'CCTV Kamera IP', 'description' => 'Kamera pengawas yang tersebar di seluruh area sekolah.', 'category' => 'electronics', 'quantity' => 8, 'condition' => 'good', 'location' => 'Seluruh area', 'room_code' => null, 'purchase_date' => '2022-08-11', 'purchase_price' => 1100000, 'status' => 'active'],
            ['code' => 'AST-012', 'name' => 'Router WiFi', 'description' => 'Router utama jaringan nirkabel sekolah.', 'category' => 'electronics', 'quantity' => 5, 'condition' => 'good', 'location' => 'Ruang Server', 'room_code' => 'R-005', 'purchase_date' => '2023-04-19', 'purchase_price' => 950000, 'status' => 'active'],
            ['code' => 'AST-013', 'name' => 'Mikroskop Binokuler', 'description' => 'Mikroskop untuk praktikum biologi.', 'category' => 'lab_equipment', 'quantity' => 6, 'condition' => 'good', 'location' => 'Lemari Alat', 'room_code' => 'R-006', 'purchase_date' => '2020-02-25', 'purchase_price' => 3800000, 'status' => 'active'],
            ['code' => 'AST-014', 'name' => 'Kit Percobaan IPA', 'description' => 'Set alat percobaan fisika dan kimia dasar.', 'category' => 'lab_equipment', 'quantity' => 15, 'condition' => 'fair', 'location' => 'Lemari Alat', 'room_code' => 'R-006', 'purchase_date' => '2019-03-14', 'purchase_price' => 1500000, 'status' => 'active'],
            ['code' => 'AST-015', 'name' => 'Meja Siswa', 'description' => 'Meja kayu untuk siswa, tipe 2 kursi.', 'category' => 'furniture', 'quantity' => 40, 'condition' => 'good', 'location' => 'Ruang Kelas', 'room_code' => 'R-001', 'purchase_date' => '2022-07-01', 'purchase_price' => 850000, 'status' => 'active'],
            ['code' => 'AST-016', 'name' => 'Kursi Siswa', 'description' => 'Kursi besi dengan sandaran untuk siswa.', 'category' => 'furniture', 'quantity' => 60, 'condition' => 'good', 'location' => 'Ruang Kelas', 'room_code' => 'R-001', 'purchase_date' => '2022-07-01', 'purchase_price' => 320000, 'status' => 'active'],
            ['code' => 'AST-017', 'name' => 'Meja Guru', 'description' => 'Meja kerja guru, beberapa cat mengelupas.', 'category' => 'furniture', 'quantity' => 16, 'condition' => 'fair', 'location' => 'Ruang Guru', 'room_code' => 'R-008', 'purchase_date' => '2018-01-20', 'purchase_price' => 1250000, 'status' => 'active'],
            ['code' => 'AST-018', 'name' => 'Kursi Guru', 'description' => 'Kursi kerja beroda untuk guru dan staf.', 'category' => 'furniture', 'quantity' => 24, 'condition' => 'good', 'location' => 'Ruang Guru', 'room_code' => 'R-008', 'purchase_date' => '2021-05-17', 'purchase_price' => 750000, 'status' => 'active'],
            ['code' => 'AST-019', 'name' => 'Lemari Arsip', 'description' => 'Lemari besi untuk penyimpanan dokumen.', 'category' => 'furniture', 'quantity' => 6, 'condition' => 'fair', 'location' => 'Sudut Ruangan', 'room_code' => 'R-010', 'purchase_date' => '2017-10-02', 'purchase_price' => 1350000, 'status' => 'active'],
            ['code' => 'AST-020', 'name' => 'Meja Laboratorium', 'description' => 'Meja praktikum tahan air untuk lab IPA.', 'category' => 'furniture', 'quantity' => 8, 'condition' => 'good', 'location' => 'Tengah Lab', 'room_code' => 'R-006', 'purchase_date' => '2020-02-25', 'purchase_price' => 2300000, 'status' => 'active'],
            ['code' => 'AST-021', 'name' => 'Papan Tulis Putih', 'description' => 'Whiteboard 120x240 cm.', 'category' => 'teaching_aids', 'quantity' => 12, 'condition' => 'good', 'location' => 'Dinding Depan', 'room_code' => 'R-003', 'purchase_date' => '2022-07-01', 'purchase_price' => 620000, 'status' => 'active'],
            ['code' => 'AST-022', 'name' => 'Globe Edukasi', 'description' => 'Globe untuk pelajaran geografi.', 'category' => 'teaching_aids', 'quantity' => 4, 'condition' => 'fair', 'location' => 'Rak Referensi', 'room_code' => 'R-007', 'purchase_date' => '2016-04-12', 'purchase_price' => 480000, 'status' => 'active'],
            ['code' => 'AST-023', 'name' => 'Bola Sepak', 'description' => 'Bola sepak ukuran standar untuk ekstrakurikuler.', 'category' => 'sports', 'quantity' => 10, 'condition' => 'good', 'location' => 'Gudang Olahraga', 'room_code' => null, 'purchase_date' => '2023-02-06', 'purchase_price' => 150000, 'status' => 'active'],
            ['code' => 'AST-024', 'name' => 'Raket Bulu Tangkis', 'description' => 'Raket untuk latihan bulu tangkis, sebagian dengan senar kendor.', 'category' => 'sports', 'quantity' => 8, 'condition' => 'damaged', 'location' => 'Gudang Olahraga', 'room_code' => null, 'purchase_date' => '2015-08-30', 'purchase_price' => 180000, 'status' => 'inactive'],
        ];
    }

    // ---------------------------------------------------------------------
    // Phase 3 — Maintenance
    // ---------------------------------------------------------------------

    private function seedMaintenance(): void
    {
        foreach ($this->maintenance() as $job) {
            $job['asset_id'] = $job['asset_code'] ? $this->assetIdByCode($job['asset_code']) : null;
            $job['room_id'] = $job['room_code'] ? $this->roomIdByCode($job['room_code']) : null;
            unset($job['asset_code'], $job['room_code']);

            $this->firstCreateByCode(new MaintenanceFactory, Maintenance::class, $job['code'], $job);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function maintenance(): array
    {
        $today = Carbon::today();

        return [
            [
                'code' => 'MNT-001',
                'title' => 'Pemeriksaan rutin laptop lab komputer',
                'description' => 'Cek kondisi fisik dan kinerja semua laptop guru.',
                'asset_code' => 'AST-001',
                'room_code' => 'R-005',
                'reported_by' => 'Kepala Lab',
                'maintenance_type' => 'preventive',
                'priority' => 'medium',
                'status' => 'completed',
                'scheduled_date' => (string) $today->copy()->subDays(20),
                'started_date' => (string) $today->copy()->subDays(19),
                'completed_date' => (string) $today->copy()->subDays(18),
                'estimated_cost' => 0,
                'actual_cost' => 0,
                'notes' => 'Semua unit diperiksa secara berkala.',
                'resolution' => 'Semua laptop dalam kondisi baik.',
            ],
            [
                'code' => 'MNT-002',
                'title' => 'Servis proyektor LCD',
                'description' => 'Gambar proyektor mulai redup dan tidak fokus.',
                'asset_code' => 'AST-004',
                'room_code' => 'R-005',
                'reported_by' => 'Bu Sari',
                'maintenance_type' => 'corrective',
                'priority' => 'high',
                'status' => 'completed',
                'scheduled_date' => (string) $today->copy()->subDays(30),
                'started_date' => (string) $today->copy()->subDays(28),
                'completed_date' => (string) $today->copy()->subDays(25),
                'estimated_cost' => 350000,
                'actual_cost' => 400000,
                'notes' => 'Lampu proyektor sudah lebih dari 2 tahun dipakai.',
                'resolution' => 'Lampu proyektor diganti, tampilan kembali normal.',
            ],
            [
                'code' => 'MNT-003',
                'title' => 'Perbaikan AC ruang kelas 1A',
                'description' => 'AC tidak dingin dan keluar bunyi berisik.',
                'asset_code' => 'AST-008',
                'room_code' => 'R-001',
                'reported_by' => 'Guru Kelas 1A',
                'maintenance_type' => 'corrective',
                'priority' => 'urgent',
                'status' => 'in_progress',
                'scheduled_date' => (string) $today->copy()->subDays(5),
                'started_date' => (string) $today->copy()->subDays(3),
                'completed_date' => null,
                'estimated_cost' => 600000,
                'actual_cost' => null,
                'notes' => 'Terduga kompresor bermasalah.',
                'resolution' => null,
            ],
            [
                'code' => 'MNT-004',
                'title' => 'Perbaikan printer TU',
                'description' => 'Printer sering macet saat mencetak surat.',
                'asset_code' => 'AST-005',
                'room_code' => 'R-010',
                'reported_by' => 'Staf TU',
                'maintenance_type' => 'emergency',
                'priority' => 'high',
                'status' => 'in_progress',
                'scheduled_date' => (string) $today->copy()->subDays(2),
                'started_date' => (string) $today->copy()->subDays(1),
                'completed_date' => null,
                'estimated_cost' => 250000,
                'actual_cost' => null,
                'notes' => 'Terjadi paper jam berulang.',
                'resolution' => null,
            ],
            [
                'code' => 'MNT-005',
                'title' => 'Pembersihan komputer lab komputer',
                'description' => 'Pembersihan debu CPU dan periferal.',
                'asset_code' => 'AST-002',
                'room_code' => 'R-005',
                'reported_by' => 'Kepala Lab',
                'maintenance_type' => 'preventive',
                'priority' => 'low',
                'status' => 'completed',
                'scheduled_date' => (string) $today->copy()->subDays(15),
                'started_date' => (string) $today->copy()->subDays(15),
                'completed_date' => (string) $today->copy()->subDays(14),
                'estimated_cost' => 0,
                'actual_cost' => 0,
                'notes' => null,
                'resolution' => 'Semua unit dibersihkan dan dites menyala.',
            ],
            [
                'code' => 'MNT-006',
                'title' => 'Perbaikan kursi siswa kelas 1A',
                'description' => 'Beberapa kursi longgar pada bautnya.',
                'asset_code' => 'AST-016',
                'room_code' => 'R-001',
                'reported_by' => 'Guru Kelas 1A',
                'maintenance_type' => 'corrective',
                'priority' => 'low',
                'status' => 'pending',
                'scheduled_date' => (string) $today->copy()->addDays(3),
                'started_date' => null,
                'completed_date' => null,
                'estimated_cost' => 150000,
                'actual_cost' => null,
                'notes' => 'Diagendakan setelah jam pelajaran selesai.',
                'resolution' => null,
            ],
            [
                'code' => 'MNT-007',
                'title' => 'Pengecekan CCTV seluruh sekolah',
                'description' => 'Inspeksi fungsi rekam semua kamera pengawas.',
                'asset_code' => 'AST-011',
                'room_code' => null,
                'reported_by' => 'Kepala Sekolah',
                'maintenance_type' => 'inspection',
                'priority' => 'medium',
                'status' => 'pending',
                'scheduled_date' => (string) $today->copy()->addDays(7),
                'started_date' => null,
                'completed_date' => null,
                'estimated_cost' => 0,
                'actual_cost' => null,
                'notes' => null,
                'resolution' => null,
            ],
            [
                'code' => 'MNT-008',
                'title' => 'Servis sound system aula',
                'description' => 'Suara speaker kiri terputus-putus.',
                'asset_code' => 'AST-009',
                'room_code' => 'R-012',
                'reported_by' => 'Panitia Acara',
                'maintenance_type' => 'preventive',
                'priority' => 'medium',
                'status' => 'completed',
                'scheduled_date' => (string) $today->copy()->subDays(40),
                'started_date' => (string) $today->copy()->subDays(40),
                'completed_date' => (string) $today->copy()->subDays(39),
                'estimated_cost' => 400000,
                'actual_cost' => 450000,
                'notes' => null,
                'resolution' => 'Amplifier diperbaiki dan kabel RCA diganti.',
            ],
            [
                'code' => 'MNT-009',
                'title' => 'Kalibrasi mikroskop lab IPA',
                'description' => 'Kalibrasi pembesaran lensa mikroskop.',
                'asset_code' => 'AST-013',
                'room_code' => 'R-006',
                'reported_by' => 'Guru IPA',
                'maintenance_type' => 'inspection',
                'priority' => 'low',
                'status' => 'completed',
                'scheduled_date' => (string) $today->copy()->subDays(60),
                'started_date' => (string) $today->copy()->subDays(58),
                'completed_date' => (string) $today->copy()->subDays(55),
                'estimated_cost' => 500000,
                'actual_cost' => 500000,
                'notes' => null,
                'resolution' => 'Semua lensa dikalibrasi oleh teknisi vendor.',
            ],
            [
                'code' => 'MNT-010',
                'title' => 'Penggantian lampu koridor',
                'description' => 'Beberapa lampu LED koridor lantai 2 padam.',
                'asset_code' => null,
                'room_code' => null,
                'reported_by' => 'Staf TU',
                'maintenance_type' => 'corrective',
                'priority' => 'medium',
                'status' => 'cancelled',
                'scheduled_date' => (string) $today->copy()->subDays(12),
                'started_date' => null,
                'completed_date' => null,
                'estimated_cost' => null,
                'actual_cost' => null,
                'notes' => 'Ditunda karena stok lampu belum tersedia.',
                'resolution' => null,
            ],
            [
                'code' => 'MNT-011',
                'title' => 'Perawatan meja laboratorium IPA',
                'description' => 'Penggantian lapisan permukaan meja praktikum.',
                'asset_code' => 'AST-020',
                'room_code' => 'R-006',
                'reported_by' => 'Kepala Lab',
                'maintenance_type' => 'preventive',
                'priority' => 'medium',
                'status' => 'in_progress',
                'scheduled_date' => (string) $today->copy()->subDays(7),
                'started_date' => (string) $today->copy()->subDays(6),
                'completed_date' => null,
                'estimated_cost' => 300000,
                'actual_cost' => null,
                'notes' => 'Sedang menunggu material lamina.',
                'resolution' => null,
            ],
            [
                'code' => 'MNT-012',
                'title' => 'Perbaikan televisi LED aula',
                'description' => 'Layar tidak menyala saat acara berlangsung.',
                'asset_code' => 'AST-010',
                'room_code' => 'R-012',
                'reported_by' => 'Panitia Acara',
                'maintenance_type' => 'emergency',
                'priority' => 'urgent',
                'status' => 'pending',
                'scheduled_date' => (string) $today->copy()->addDays(1),
                'started_date' => null,
                'completed_date' => null,
                'estimated_cost' => 1200000,
                'actual_cost' => null,
                'notes' => 'Menunggu teknisi vendor elektronik.',
                'resolution' => null,
            ],
            [
                'code' => 'MNT-013',
                'title' => 'Pembersihan AC ruang TU',
                'description' => 'Pembersihan filter dan servis ringan AC.',
                'asset_code' => 'AST-007',
                'room_code' => 'R-010',
                'reported_by' => 'Staf TU',
                'maintenance_type' => 'preventive',
                'priority' => 'low',
                'status' => 'completed',
                'scheduled_date' => (string) $today->copy()->subDays(25),
                'started_date' => (string) $today->copy()->subDays(25),
                'completed_date' => (string) $today->copy()->subDays(24),
                'estimated_cost' => 0,
                'actual_cost' => 0,
                'notes' => null,
                'resolution' => 'Filter AC dibersihkan dan freon dicek.',
            ],
            [
                'code' => 'MNT-014',
                'title' => 'Perbaikan meja guru ruang guru',
                'description' => 'Satu meja guru rangkanya bengkok.',
                'asset_code' => 'AST-017',
                'room_code' => 'R-008',
                'reported_by' => 'Guru',
                'maintenance_type' => 'corrective',
                'priority' => 'medium',
                'status' => 'pending',
                'scheduled_date' => (string) $today->copy()->addDays(5),
                'started_date' => null,
                'completed_date' => null,
                'estimated_cost' => 200000,
                'actual_cost' => null,
                'notes' => null,
                'resolution' => null,
            ],
            [
                'code' => 'MNT-015',
                'title' => 'Servis router WiFi lab komputer',
                'description' => 'Koneksi WiFi lab sering terputus.',
                'asset_code' => 'AST-012',
                'room_code' => 'R-005',
                'reported_by' => 'Kepala Lab',
                'maintenance_type' => 'corrective',
                'priority' => 'high',
                'status' => 'completed',
                'scheduled_date' => (string) $today->copy()->subDays(10),
                'started_date' => (string) $today->copy()->subDays(9),
                'completed_date' => (string) $today->copy()->subDays(9),
                'estimated_cost' => 200000,
                'actual_cost' => 250000,
                'notes' => null,
                'resolution' => 'Konfigurasi router di-reset dan firmware diperbarui.',
            ],
            [
                'code' => 'MNT-016',
                'title' => 'Inspeksi papan tulis ruang kelas 2A',
                'description' => 'Pengecekan kondisi permukaan whiteboard.',
                'asset_code' => 'AST-021',
                'room_code' => 'R-003',
                'reported_by' => 'Guru Kelas 2A',
                'maintenance_type' => 'inspection',
                'priority' => 'low',
                'status' => 'cancelled',
                'scheduled_date' => (string) $today->copy()->subDays(35),
                'started_date' => null,
                'completed_date' => null,
                'estimated_cost' => null,
                'actual_cost' => null,
                'notes' => 'Papan tulis masih layak, inspeksi ditutup.',
                'resolution' => null,
            ],
        ];
    }

    // ---------------------------------------------------------------------
    // Phase 4 — Inventory
    // ---------------------------------------------------------------------

    private function seedInventory(): void
    {
        foreach ($this->inventory() as $item) {
            $item['room_id'] = $item['room_code'] ? $this->roomIdByCode($item['room_code']) : null;
            unset($item['room_code']);

            $this->firstCreateByCode(new InventoryFactory, Inventory::class, $item['code'], $item);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function inventory(): array
    {
        return [
            ['code' => 'INV-001', 'name' => 'Kertas HVS A4 70gsm', 'description' => 'Kertas fotokopi standar ukuran A4.', 'category' => 'stationery', 'unit' => 'rim', 'quantity' => 24, 'minimum_stock' => 10, 'location' => 'Rak A1', 'room_code' => 'R-010', 'status' => 'active'],
            ['code' => 'INV-002', 'name' => 'Pulpen Standard AE7', 'description' => 'Pulpen bolpoin tinta hitam.', 'category' => 'stationery', 'unit' => 'pcs', 'quantity' => 120, 'minimum_stock' => 50, 'location' => 'Rak A2', 'room_code' => 'R-010', 'status' => 'active'],
            ['code' => 'INV-003', 'name' => 'Pensil 2B', 'description' => 'Pensil kayu untuk ujian dan pemakaian harian.', 'category' => 'stationery', 'unit' => 'pcs', 'quantity' => 60, 'minimum_stock' => 40, 'location' => 'Rak A2', 'room_code' => null, 'status' => 'active'],
            ['code' => 'INV-004', 'name' => 'Spidol Whiteboard', 'description' => 'Spidol hitam untuk papan tulis.', 'category' => 'stationery', 'unit' => 'pcs', 'quantity' => 6, 'minimum_stock' => 12, 'location' => 'Kotak Media', 'room_code' => 'R-003', 'status' => 'active'],
            ['code' => 'INV-005', 'name' => 'Penghapus Whiteboard', 'description' => 'Penghapus papan tulis putih.', 'category' => 'stationery', 'unit' => 'pcs', 'quantity' => 4, 'minimum_stock' => 5, 'location' => 'Kotak Media', 'room_code' => 'R-003', 'status' => 'active'],
            ['code' => 'INV-006', 'name' => 'Map Folder Plastik', 'description' => 'Map transparan untuk arsip dokumen.', 'category' => 'office_supplies', 'unit' => 'pcs', 'quantity' => 80, 'minimum_stock' => 30, 'location' => 'Rak B1', 'room_code' => 'R-010', 'status' => 'active'],
            ['code' => 'INV-007', 'name' => 'Tinta Printer Epson 003', 'description' => 'Botol tinta refill warna hitam.', 'category' => 'office_supplies', 'unit' => 'botol', 'quantity' => 3, 'minimum_stock' => 5, 'location' => 'Rak B1', 'room_code' => 'R-010', 'status' => 'active'],
            ['code' => 'INV-008', 'name' => 'Kabel HDMI 3 meter', 'description' => 'Kabel HDMI untuk proyektor dan televisi.', 'category' => 'electronics_supplies', 'unit' => 'pcs', 'quantity' => 10, 'minimum_stock' => 4, 'location' => 'Rak B2', 'room_code' => 'R-005', 'status' => 'active'],
            ['code' => 'INV-009', 'name' => 'Mouse USB', 'description' => 'Mouse optik kabel untuk komputer lab.', 'category' => 'electronics_supplies', 'unit' => 'pcs', 'quantity' => 6, 'minimum_stock' => 10, 'location' => 'Kotak Perangkat', 'room_code' => 'R-005', 'status' => 'active'],
            ['code' => 'INV-010', 'name' => 'Keyboard USB', 'description' => 'Keyboard standar untuk komputer lab.', 'category' => 'electronics_supplies', 'unit' => 'pcs', 'quantity' => 8, 'minimum_stock' => 10, 'location' => 'Kotak Perangkat', 'room_code' => 'R-005', 'status' => 'active'],
            ['code' => 'INV-011', 'name' => 'Flashdisk 16GB', 'description' => 'Flashdisk untuk media pembelajaran.', 'category' => 'electronics_supplies', 'unit' => 'pcs', 'quantity' => 15, 'minimum_stock' => 5, 'location' => 'Rak B2', 'room_code' => 'R-010', 'status' => 'active'],
            ['code' => 'INV-012', 'name' => 'Baterai AA', 'description' => 'Baterai alkaline untuk remote dan mouse wireless.', 'category' => 'electronics_supplies', 'unit' => 'pcs', 'quantity' => 60, 'minimum_stock' => 24, 'location' => 'Laci Logistik', 'room_code' => null, 'status' => 'active'],
            ['code' => 'INV-013', 'name' => 'Lampu LED 12 watt', 'description' => 'Lampu LED untuk perawatan gedung.', 'category' => 'electronics_supplies', 'unit' => 'pcs', 'quantity' => 20, 'minimum_stock' => 10, 'location' => 'Gudang Sarana', 'room_code' => null, 'status' => 'active'],
            ['code' => 'INV-014', 'name' => 'Kabel LAN UTP Cat6', 'description' => 'Kabel jaringan untuk laboratorium komputer.', 'category' => 'electronics_supplies', 'unit' => 'meter', 'quantity' => 150, 'minimum_stock' => 50, 'location' => 'Gulungan Rak B2', 'room_code' => 'R-005', 'status' => 'active'],
            ['code' => 'INV-015', 'name' => 'Konektor RJ45', 'description' => 'Konektor ujung kabel jaringan.', 'category' => 'electronics_supplies', 'unit' => 'pcs', 'quantity' => 40, 'minimum_stock' => 20, 'location' => 'Kotak Perangkat', 'room_code' => 'R-005', 'status' => 'active'],
            ['code' => 'INV-016', 'name' => 'Sabun Cair Cuci Tangan', 'description' => 'Sabun cair untuk fasilitas UKS dan toilet.', 'category' => 'cleaning', 'unit' => 'botol', 'quantity' => 9, 'minimum_stock' => 12, 'location' => 'Gudang Kebersihan', 'room_code' => 'R-011', 'status' => 'active'],
            ['code' => 'INV-017', 'name' => 'Tisu Basah', 'description' => 'Tisu basah untuk kebutuhan UKS.', 'category' => 'cleaning', 'unit' => 'box', 'quantity' => 24, 'minimum_stock' => 8, 'location' => 'Gudang Kebersihan', 'room_code' => 'R-011', 'status' => 'active'],
            ['code' => 'INV-018', 'name' => 'Pengaduk Kaca Laboratorium', 'description' => 'Batangan pengaduk untuk praktikum.', 'category' => 'lab_supplies', 'unit' => 'pcs', 'quantity' => 10, 'minimum_stock' => 6, 'location' => 'Lemari Alat', 'room_code' => 'R-006', 'status' => 'active'],
            ['code' => 'INV-019', 'name' => 'Serbet Mikro', 'description' => 'Kain lap bersih untuk peralatan lab.', 'category' => 'lab_supplies', 'unit' => 'pcs', 'quantity' => 5, 'minimum_stock' => 8, 'location' => 'Lemari Alat', 'room_code' => 'R-006', 'status' => 'active'],
            ['code' => 'INV-020', 'name' => 'Kertas Folio Bergaris', 'description' => 'Kertas folio untuk kebutuhan perpustakaan.', 'category' => 'stationery', 'unit' => 'rim', 'quantity' => 18, 'minimum_stock' => 10, 'location' => 'Rak Referensi', 'room_code' => 'R-007', 'status' => 'active'],
        ];
    }

    // ---------------------------------------------------------------------
    // Phase 5 — Stock Movements (only when the table is still empty)
    // ---------------------------------------------------------------------

    private function seedStockMovements(): void
    {
        if (StockMovement::count() > 0) {
            return;
        }

        $creators = ['Bendahara Sekolah', 'Kepala TU', 'Petugas Gudang', 'Admin Sarpras'];
        $offsetDays = 140;

        foreach ($this->stockMovements() as $entry) {
            $inventoryId = $this->inventoryIdByCode($entry['inventory_code']);
            if ($inventoryId === null) {
                continue;
            }

            $running = 0;
            $day = 0;

            foreach ($entry['movements'] as $movement) {
                $delta = $this->deltaFor($movement);
                $running += $delta;

                if ($running < 0) {
                    throw new \RuntimeException(
                        sprintf(
                            'Stock movement would produce negative stock for %s (running = %d).',
                            $entry['inventory_code'],
                            $running,
                        ),
                    );
                }

                $this->createMovement($inventoryId, $movement, $day, $offsetDays, $creators);
                $day++;
            }

            Inventory::where('id', $inventoryId)->update(['quantity' => $running]);
        }
    }

    private function deltaFor(array $movement): int
    {
        return match ($movement['type']) {
            'stock_in' => $movement['qty'],
            'stock_out' => -$movement['qty'],
            'adjustment' => $movement['adjustment_type'] === 'decrease'
                ? -$movement['qty']
                : $movement['qty'],
        };
    }

    private function createMovement(
        int $inventoryId,
        array $movement,
        int $day,
        int $offsetDays,
        array $creators,
    ): void {
        $createdAt = Carbon::now()->subDays($offsetDays - $day);

        $base = new StockMovementFactory;

        $factory = match ($movement['type']) {
            'stock_in' => $base->stockIn($movement['qty'], $inventoryId),
            'stock_out' => $base->stockOut($movement['qty'], $inventoryId),
            'adjustment' => $movement['adjustment_type'] === 'decrease'
                ? $base->adjustmentDecrease($movement['qty'], $inventoryId)
                : $base->adjustmentIncrease($movement['qty'], $inventoryId),
        };

        $factory->create([
            'notes' => $movement['notes'],
            'created_by' => $creators[$day % count($creators)],
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function stockMovements(): array
    {
        return [
            ['inventory_code' => 'INV-001', 'movements' => [
                ['type' => 'stock_in', 'qty' => 30, 'notes' => 'Pembelian kertas HVS semester baru'],
                ['type' => 'stock_out', 'qty' => 6, 'notes' => 'Distribusi ke ruang kelas 1A dan 1B'],
            ]],
            ['inventory_code' => 'INV-002', 'movements' => [
                ['type' => 'stock_in', 'qty' => 150, 'notes' => 'Pembelian pulpen untuk ATK tahun ajaran'],
                ['type' => 'stock_out', 'qty' => 30, 'notes' => 'Distribusi ke ruang guru'],
            ]],
            ['inventory_code' => 'INV-003', 'movements' => [
                ['type' => 'stock_in', 'qty' => 80, 'notes' => 'Pembelian pensil untuk ujian'],
                ['type' => 'stock_out', 'qty' => 20, 'notes' => 'Distribusi ke ruang kelas'],
            ]],
            ['inventory_code' => 'INV-004', 'movements' => [
                ['type' => 'stock_in', 'qty' => 10, 'notes' => 'Pembelian spidol whiteboard'],
                ['type' => 'stock_out', 'qty' => 4, 'notes' => 'Dipakai di ruang kelas 2A'],
            ]],
            ['inventory_code' => 'INV-005', 'movements' => [
                ['type' => 'stock_in', 'qty' => 5, 'notes' => 'Pembelian penghapus whiteboard'],
                ['type' => 'stock_out', 'qty' => 1, 'notes' => 'Penggantian penghapus yang hilang'],
            ]],
            ['inventory_code' => 'INV-006', 'movements' => [
                ['type' => 'stock_in', 'qty' => 50, 'notes' => 'Pembelian map folder'],
                ['type' => 'stock_out', 'qty' => 20, 'notes' => 'Distribusi arsip dokumen TU'],
                ['type' => 'stock_in', 'qty' => 50, 'notes' => 'Re-stok map folder'],
            ]],
            ['inventory_code' => 'INV-007', 'movements' => [
                ['type' => 'stock_in', 'qty' => 6, 'notes' => 'Pembelian tinta printer'],
                ['type' => 'stock_out', 'qty' => 3, 'notes' => 'Pemakaian pencetakan laporan'],
            ]],
            ['inventory_code' => 'INV-008', 'movements' => [
                ['type' => 'stock_in', 'qty' => 20, 'notes' => 'Pembelian kabel HDMI'],
                ['type' => 'stock_out', 'qty' => 10, 'notes' => 'Pemasangan di ruang kelas multimedia'],
            ]],
            ['inventory_code' => 'INV-009', 'movements' => [
                ['type' => 'stock_in', 'qty' => 10, 'notes' => 'Pembelian mouse USB'],
                ['type' => 'stock_out', 'qty' => 4, 'notes' => 'Penggantian mouse rusak lab'],
            ]],
            ['inventory_code' => 'INV-010', 'movements' => [
                ['type' => 'stock_in', 'qty' => 5, 'notes' => 'Pembelian keyboard USB'],
                ['type' => 'adjustment', 'qty' => 3, 'adjustment_type' => 'increase', 'notes' => 'Stok dihitung ulang, sisa gudang ditemukan'],
            ]],
            ['inventory_code' => 'INV-011', 'movements' => [
                ['type' => 'stock_in', 'qty' => 10, 'notes' => 'Pembelian flashdisk pertama'],
                ['type' => 'stock_in', 'qty' => 10, 'notes' => 'Pembelian flashdisk tambahan'],
                ['type' => 'stock_out', 'qty' => 5, 'notes' => 'Distribusi media pembelajaran'],
            ]],
            ['inventory_code' => 'INV-012', 'movements' => [
                ['type' => 'stock_in', 'qty' => 100, 'notes' => 'Pembelian baterai AA'],
                ['type' => 'stock_out', 'qty' => 40, 'notes' => 'Pemakaian remote AC dan mouse wireless'],
            ]],
            ['inventory_code' => 'INV-013', 'movements' => [
                ['type' => 'stock_in', 'qty' => 30, 'notes' => 'Pembelian lampu LED'],
                ['type' => 'stock_out', 'qty' => 10, 'notes' => 'Penggantian lampu koridor'],
            ]],
            ['inventory_code' => 'INV-014', 'movements' => [
                ['type' => 'stock_in', 'qty' => 120, 'notes' => 'Pembelian kabel LAN roll 305 m'],
                ['type' => 'stock_out', 'qty' => 30, 'notes' => 'Instalasi jaringan lab komputer'],
                ['type' => 'stock_in', 'qty' => 60, 'notes' => 'Re-stok kabel LAN'],
            ]],
            ['inventory_code' => 'INV-015', 'movements' => [
                ['type' => 'stock_in', 'qty' => 60, 'notes' => 'Pembelian konektor RJ45'],
                ['type' => 'stock_out', 'qty' => 20, 'notes' => 'Terminasi kabel jaringan lab'],
            ]],
            ['inventory_code' => 'INV-016', 'movements' => [
                ['type' => 'stock_in', 'qty' => 10, 'notes' => 'Pembelian sabun cair'],
                ['type' => 'stock_out', 'qty' => 1, 'notes' => 'Pemakaian bulanan UKS'],
            ]],
            ['inventory_code' => 'INV-017', 'movements' => [
                ['type' => 'stock_in', 'qty' => 30, 'notes' => 'Pembelian tisu basah'],
                ['type' => 'stock_out', 'qty' => 6, 'notes' => 'Distribusi ke ruang UKS dan guru'],
            ]],
            ['inventory_code' => 'INV-018', 'movements' => [
                ['type' => 'stock_in', 'qty' => 15, 'notes' => 'Pembelian pengaduk kaca'],
                ['type' => 'stock_out', 'qty' => 5, 'notes' => 'Pemakaian praktikum IPA'],
            ]],
            ['inventory_code' => 'INV-019', 'movements' => [
                ['type' => 'stock_in', 'qty' => 8, 'notes' => 'Pembelian serbet mikro'],
                ['type' => 'stock_out', 'qty' => 3, 'notes' => 'Serbet hilang saat praktikum'],
            ]],
            ['inventory_code' => 'INV-020', 'movements' => [
                ['type' => 'stock_in', 'qty' => 20, 'notes' => 'Pembelian kertas folio'],
                ['type' => 'adjustment', 'qty' => 2, 'adjustment_type' => 'decrease', 'notes' => 'Selisih stok fisik dengan catatan, kertas rusak'],
            ]],
        ];
    }
}