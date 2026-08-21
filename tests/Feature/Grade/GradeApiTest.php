<?php

namespace Tests\Feature\Grade;

use App\Models\ClassSubject;
use App\Models\Grade;
use App\Models\Role;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class GradeApiTest extends TestCase
{
    private int $classId;
    private int $subjectId;
    private int $studentId;
    private int $classSubjectId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app['config']->set('database.default', 'mysql');
        $this->app['config']->set('database.connections.mysql', [
            'driver' => 'mysql',
            'host' => '127.0.0.1',
            'port' => '3306',
            'database' => 'schoolcms_db',
            'username' => 'root',
            'password' => 'root',
            'unix_socket' => '',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_general_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => false,
            'engine' => null,
        ]);

        $this->app['db']->purge('mysql');

        $this->cleanupTestGrades();
        $this->cleanupTestStudents();
        $this->cleanupTestClassSubjects();

        $this->setupTestData();
    }

    protected function tearDown(): void
    {
        $this->cleanupTestGrades();
        $this->cleanupTestStudents();
        $this->cleanupTestClassSubjects();
        parent::tearDown();
    }

    // ─── Setup Helpers ─────────────────────────────────────────

    private function setupTestData(): void
    {
        $this->classId = SchoolClass::whereNull('deleted_at')->orderBy('id')->first()->id;
        $this->subjectId = Subject::whereNull('deleted_at')->orderBy('id')->first()->id;

        $tempStudent = $this->createTestStudent(['class_id' => $this->classId]);
        $this->studentId = $tempStudent->id;

        $tempCs = ClassSubject::create([
            'class_id' => $this->classId,
            'subject_id' => $this->subjectId,
            'teacher_id' => null,
        ]);
        $this->classSubjectId = $tempCs->id;
    }

    // ─── Auth Helpers ──────────────────────────────────────────

    private function authenticate(): void
    {
        $user = User::first();
        Sanctum::actingAs($user);
    }

    private function authenticateAsAdmin(): void
    {
        $adminRole = Role::where('name', 'Admin')->first();
        $user = User::where('role_id', $adminRole->id)->first();
        Sanctum::actingAs($user);
    }

    private function authenticateAsAdministrator(): void
    {
        $adminRole = Role::where('name', 'Administrator')->first();
        $user = User::where('role_id', $adminRole->id)->first();
        Sanctum::actingAs($user);
    }

    private function authenticateAsGuru(): void
    {
        $guruRole = Role::where('name', 'Guru')->first();
        $user = User::where('role_id', $guruRole->id)->first();
        Sanctum::actingAs($user);
    }

    private function authenticateAsSiswa(): void
    {
        $siswaRole = Role::where('name', 'Siswa')->first();
        $user = $this->createTestUser($siswaRole->id, 'siswa');
        Sanctum::actingAs($user);
    }

    // ─── Data Helpers ──────────────────────────────────────────

    private function createTestUser(int $roleId, string $prefix = 'test'): User
    {
        return User::create([
            'username' => $prefix . '_' . mt_rand(100000, 999999),
            'name' => 'Test User Grade ' . $prefix,
            'email' => $prefix . '.' . mt_rand(100000, 999999) . '@test.local',
            'password' => 'password',
            'is_active' => true,
            'role_id' => $roleId,
        ]);
    }

    private function createTestStudent(array $overrides = []): Student
    {
        $defaults = [
            'nisn' => 'GN-' . str_pad(mt_rand(100000, 999999), 6, '0', STR_PAD_LEFT),
            'nis' => 'SN-' . str_pad(mt_rand(100000, 999999), 6, '0', STR_PAD_LEFT),
            'name' => 'Test Student Grade',
            'gender' => 'L',
            'birth_place' => 'Test City',
            'birth_date' => '2008-01-01',
            'address' => 'Test Address',
        ];

        return Student::create(array_merge($defaults, $overrides));
    }

    private function getSecondClassId(): int
    {
        return SchoolClass::whereNull('deleted_at')->orderBy('id')->skip(1)->first()->id;
    }

    private function getSecondSubjectId(): int
    {
        return Subject::whereNull('deleted_at')->orderBy('id')->skip(1)->first()->id;
    }

    private function createTestClassSubject(int $classId, int $subjectId): ClassSubject
    {
        return ClassSubject::create([
            'class_id' => $classId,
            'subject_id' => $subjectId,
            'teacher_id' => null,
        ]);
    }

    private function createTestGrade(array $overrides = []): Grade
    {
        $defaults = [
            'student_id' => $this->studentId,
            'subject_id' => $this->subjectId,
            'class_id' => $this->classId,
            'type' => 'tugas',
            'score' => 85.50,
            'semester' => '1',
            'academic_year' => '2026/2027',
        ];

        return Grade::create(array_merge($defaults, $overrides));
    }

    // ─── Cleanup Helpers ───────────────────────────────────────

    private function cleanupTestGrades(): void
    {
        DB::connection('mysql')->table('grades')
            ->where('id', '>', 0)
            ->delete();
    }

    private function cleanupTestStudents(): void
    {
        Student::where('nisn', 'like', 'GN-%')->forceDelete();
        Student::where('nis', 'like', 'SN-%')->forceDelete();
    }

    private function cleanupTestClassSubjects(): void
    {
        DB::connection('mysql')->table('class_subjects')
            ->where('id', '>', 0)
            ->delete();
    }

    // ─── Authentication Tests ──────────────────────────────────

    public function test_unauthenticated_access_returns_401(): void
    {
        $response = $this->getJson('/api/grades');
        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_access_index(): void
    {
        $this->authenticate();
        $response = $this->getJson('/api/grades');
        $response->assertStatus(200);
    }

    public function test_authenticated_user_can_access_show(): void
    {
        $this->authenticate();
        $grade = $this->createTestGrade();
        $response = $this->getJson("/api/grades/{$grade->id}");
        $response->assertStatus(200);
    }

    // ─── Index Tests ───────────────────────────────────────────

    public function test_index_returns_200(): void
    {
        $this->authenticate();
        $response = $this->getJson('/api/grades');
        $response->assertStatus(200);
    }

    public function test_index_returns_json(): void
    {
        $this->authenticate();
        $response = $this->getJson('/api/grades');
        $response->assertHeader('Content-Type', 'application/json');
    }

    public function test_index_response_structure(): void
    {
        $this->authenticate();
        $response = $this->getJson('/api/grades');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'data',
            'meta' => [
                'current_page',
                'per_page',
                'total',
                'last_page',
            ],
        ]);
        $response->assertJson([
            'success' => true,
            'message' => 'Grades retrieved successfully',
        ]);
    }

    public function test_index_returns_grades(): void
    {
        $this->authenticate();
        $this->createTestGrade();
        $response = $this->getJson('/api/grades');
        $response->assertStatus(200);
        $this->assertNotEmpty($response->json('data'));
    }

    public function test_index_includes_eager_loaded_relations(): void
    {
        $this->authenticate();
        $this->createTestGrade();
        $response = $this->getJson('/api/grades');
        $response->assertStatus(200);
        $first = $response->json('data')[0];
        $this->assertArrayHasKey('student', $first);
        $this->assertArrayHasKey('subject', $first);
        $this->assertArrayHasKey('class', $first);
    }

    // ─── Pagination Tests ──────────────────────────────────────

    public function test_pagination_default_per_page(): void
    {
        $this->authenticate();
        $response = $this->getJson('/api/grades');
        $response->assertStatus(200);
        $meta = $response->json('meta');
        $this->assertEquals(1, $meta['current_page']);
        $this->assertEquals(10, $meta['per_page']);
        $this->assertIsInt($meta['total']);
    }

    public function test_pagination_per_page_works(): void
    {
        $this->authenticate();
        $response = $this->getJson('/api/grades?per_page=5');
        $response->assertStatus(200);
        $meta = $response->json('meta');
        $this->assertEquals(5, $meta['per_page']);
    }

    public function test_pagination_invalid_per_page_rejected(): void
    {
        $this->authenticate();
        $response = $this->getJson('/api/grades?per_page=-1');
        $response->assertStatus(422);
    }

    public function test_pagination_excessive_per_page_rejected(): void
    {
        $this->authenticate();
        $response = $this->getJson('/api/grades?per_page=101');
        $response->assertStatus(422);
    }

    // ─── Filter Tests ──────────────────────────────────────────

    public function test_filter_by_student_id(): void
    {
        $this->authenticate();
        $this->createTestGrade();
        $response = $this->getJson("/api/grades?student_id={$this->studentId}");
        $response->assertStatus(200);
        foreach ($response->json('data') as $item) {
            $this->assertEquals($this->studentId, $item['student_id']);
        }
    }

    public function test_filter_by_subject_id(): void
    {
        $this->authenticate();
        $this->createTestGrade();
        $response = $this->getJson("/api/grades?subject_id={$this->subjectId}");
        $response->assertStatus(200);
        foreach ($response->json('data') as $item) {
            $this->assertEquals($this->subjectId, $item['subject_id']);
        }
    }

    public function test_filter_by_class_id(): void
    {
        $this->authenticate();
        $this->createTestGrade();
        $response = $this->getJson("/api/grades?class_id={$this->classId}");
        $response->assertStatus(200);
        foreach ($response->json('data') as $item) {
            $this->assertEquals($this->classId, $item['class_id']);
        }
    }

    public function test_filter_by_type(): void
    {
        $this->authenticate();
        $this->createTestGrade(['type' => 'uts']);
        $response = $this->getJson('/api/grades?type=uts');
        $response->assertStatus(200);
        foreach ($response->json('data') as $item) {
            $this->assertEquals('uts', $item['type']);
        }
    }

    public function test_filter_by_semester(): void
    {
        $this->authenticate();
        $this->createTestGrade();
        $response = $this->getJson('/api/grades?semester=1');
        $response->assertStatus(200);
        foreach ($response->json('data') as $item) {
            $this->assertEquals('1', $item['semester']);
        }
    }

    public function test_filter_by_academic_year(): void
    {
        $this->authenticate();
        $this->createTestGrade();
        $response = $this->getJson('/api/grades?academic_year=2026/2027');
        $response->assertStatus(200);
        foreach ($response->json('data') as $item) {
            $this->assertEquals('2026/2027', $item['academic_year']);
        }
    }

    // ─── Show Tests ────────────────────────────────────────────

    public function test_show_returns_200(): void
    {
        $this->authenticate();
        $grade = $this->createTestGrade();
        $response = $this->getJson("/api/grades/{$grade->id}");
        $response->assertStatus(200);
    }

    public function test_show_returns_correct_data(): void
    {
        $this->authenticate();
        $grade = $this->createTestGrade();
        $response = $this->getJson("/api/grades/{$grade->id}");
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'id' => $grade->id,
                'student_id' => $this->studentId,
                'subject_id' => $this->subjectId,
                'class_id' => $this->classId,
                'type' => 'tugas',
                'score' => '85.50',
                'semester' => '1',
                'academic_year' => '2026/2027',
            ],
        ]);
    }

    public function test_show_includes_eager_loaded_relations(): void
    {
        $this->authenticate();
        $grade = $this->createTestGrade();
        $response = $this->getJson("/api/grades/{$grade->id}");
        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertArrayHasKey('student', $data);
        $this->assertArrayHasKey('subject', $data);
        $this->assertArrayHasKey('class', $data);
    }

    public function test_show_nonexistent_returns_404(): void
    {
        $this->authenticate();
        $response = $this->getJson('/api/grades/99999');
        $response->assertStatus(404);
        $response->assertJson([
            'success' => false,
            'message' => 'Grade not found',
        ]);
    }

    // ─── Store Tests ───────────────────────────────────────────

    public function test_admin_can_store_grade(): void
    {
        $this->authenticateAsAdmin();
        $response = $this->postJson('/api/grades', [
            'student_id' => $this->studentId,
            'subject_id' => $this->subjectId,
            'class_id' => $this->classId,
            'type' => 'tugas',
            'score' => 85.50,
            'semester' => '1',
            'academic_year' => '2026/2027',
        ]);
        $response->assertStatus(201);
    }

    public function test_administrator_can_store_grade(): void
    {
        $this->authenticateAsAdministrator();
        $response = $this->postJson('/api/grades', [
            'student_id' => $this->studentId,
            'subject_id' => $this->subjectId,
            'class_id' => $this->classId,
            'type' => 'uts',
            'score' => 90,
            'semester' => '1',
            'academic_year' => '2026/2027',
        ]);
        $response->assertStatus(201);
    }

    public function test_guru_cannot_store_grade(): void
    {
        $this->authenticateAsGuru();
        $response = $this->postJson('/api/grades', [
            'student_id' => $this->studentId,
            'subject_id' => $this->subjectId,
            'class_id' => $this->classId,
            'type' => 'tugas',
            'score' => 85,
            'semester' => '1',
            'academic_year' => '2026/2027',
        ]);
        $response->assertStatus(403);
    }

    public function test_siswa_cannot_store_grade(): void
    {
        $this->authenticateAsSiswa();
        $response = $this->postJson('/api/grades', [
            'student_id' => $this->studentId,
            'subject_id' => $this->subjectId,
            'class_id' => $this->classId,
            'type' => 'tugas',
            'score' => 85,
            'semester' => '1',
            'academic_year' => '2026/2027',
        ]);
        $response->assertStatus(403);
    }

    public function test_store_returns_created_status(): void
    {
        $this->authenticateAsAdmin();
        $response = $this->postJson('/api/grades', [
            'student_id' => $this->studentId,
            'subject_id' => $this->subjectId,
            'class_id' => $this->classId,
            'type' => 'tugas',
            'score' => 85,
            'semester' => '1',
            'academic_year' => '2026/2027',
        ]);
        $response->assertStatus(201);
    }

    public function test_store_creates_database_record(): void
    {
        $this->authenticateAsAdmin();
        $this->postJson('/api/grades', [
            'student_id' => $this->studentId,
            'subject_id' => $this->subjectId,
            'class_id' => $this->classId,
            'type' => 'tugas',
            'score' => 85,
            'semester' => '1',
            'academic_year' => '2026/2027',
        ])->assertStatus(201);

        $this->assertDatabaseHas('grades', [
            'student_id' => $this->studentId,
            'subject_id' => $this->subjectId,
            'class_id' => $this->classId,
            'type' => 'tugas',
            'semester' => '1',
            'academic_year' => '2026/2027',
        ], 'mysql');
    }

    public function test_store_returns_eager_loaded_relations(): void
    {
        $this->authenticateAsAdmin();
        $response = $this->postJson('/api/grades', [
            'student_id' => $this->studentId,
            'subject_id' => $this->subjectId,
            'class_id' => $this->classId,
            'type' => 'tugas',
            'score' => 85,
            'semester' => '1',
            'academic_year' => '2026/2027',
        ]);
        $response->assertStatus(201);
        $data = $response->json('data');
        $this->assertArrayHasKey('student', $data);
        $this->assertArrayHasKey('subject', $data);
        $this->assertArrayHasKey('class', $data);
    }

    // ─── Store Validation Tests ────────────────────────────────

    public function test_store_requires_student_id(): void
    {
        $this->authenticateAsAdmin();
        $response = $this->postJson('/api/grades', [
            'subject_id' => $this->subjectId,
            'class_id' => $this->classId,
            'type' => 'tugas',
            'score' => 85,
            'semester' => '1',
            'academic_year' => '2026/2027',
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['student_id']);
    }

    public function test_store_requires_subject_id(): void
    {
        $this->authenticateAsAdmin();
        $response = $this->postJson('/api/grades', [
            'student_id' => $this->studentId,
            'class_id' => $this->classId,
            'type' => 'tugas',
            'score' => 85,
            'semester' => '1',
            'academic_year' => '2026/2027',
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['subject_id']);
    }

    public function test_store_requires_class_id(): void
    {
        $this->authenticateAsAdmin();
        $response = $this->postJson('/api/grades', [
            'student_id' => $this->studentId,
            'subject_id' => $this->subjectId,
            'type' => 'tugas',
            'score' => 85,
            'semester' => '1',
            'academic_year' => '2026/2027',
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['class_id']);
    }

    public function test_store_requires_type(): void
    {
        $this->authenticateAsAdmin();
        $response = $this->postJson('/api/grades', [
            'student_id' => $this->studentId,
            'subject_id' => $this->subjectId,
            'class_id' => $this->classId,
            'score' => 85,
            'semester' => '1',
            'academic_year' => '2026/2027',
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['type']);
    }

    public function test_store_requires_score(): void
    {
        $this->authenticateAsAdmin();
        $response = $this->postJson('/api/grades', [
            'student_id' => $this->studentId,
            'subject_id' => $this->subjectId,
            'class_id' => $this->classId,
            'type' => 'tugas',
            'semester' => '1',
            'academic_year' => '2026/2027',
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['score']);
    }

    public function test_store_requires_semester(): void
    {
        $this->authenticateAsAdmin();
        $response = $this->postJson('/api/grades', [
            'student_id' => $this->studentId,
            'subject_id' => $this->subjectId,
            'class_id' => $this->classId,
            'type' => 'tugas',
            'score' => 85,
            'academic_year' => '2026/2027',
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['semester']);
    }

    public function test_store_requires_academic_year(): void
    {
        $this->authenticateAsAdmin();
        $response = $this->postJson('/api/grades', [
            'student_id' => $this->studentId,
            'subject_id' => $this->subjectId,
            'class_id' => $this->classId,
            'type' => 'tugas',
            'score' => 85,
            'semester' => '1',
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['academic_year']);
    }

    public function test_store_rejects_invalid_student_id(): void
    {
        $this->authenticateAsAdmin();
        $response = $this->postJson('/api/grades', [
            'student_id' => 99999,
            'subject_id' => $this->subjectId,
            'class_id' => $this->classId,
            'type' => 'tugas',
            'score' => 85,
            'semester' => '1',
            'academic_year' => '2026/2027',
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['student_id']);
    }

    public function test_store_rejects_invalid_subject_id(): void
    {
        $this->authenticateAsAdmin();
        $response = $this->postJson('/api/grades', [
            'student_id' => $this->studentId,
            'subject_id' => 99999,
            'class_id' => $this->classId,
            'type' => 'tugas',
            'score' => 85,
            'semester' => '1',
            'academic_year' => '2026/2027',
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['subject_id']);
    }

    public function test_store_rejects_invalid_class_id(): void
    {
        $this->authenticateAsAdmin();
        $response = $this->postJson('/api/grades', [
            'student_id' => $this->studentId,
            'subject_id' => $this->subjectId,
            'class_id' => 99999,
            'type' => 'tugas',
            'score' => 85,
            'semester' => '1',
            'academic_year' => '2026/2027',
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['class_id']);
    }

    public function test_store_rejects_invalid_type(): void
    {
        $this->authenticateAsAdmin();
        $response = $this->postJson('/api/grades', [
            'student_id' => $this->studentId,
            'subject_id' => $this->subjectId,
            'class_id' => $this->classId,
            'type' => 'invalid',
            'score' => 85,
            'semester' => '1',
            'academic_year' => '2026/2027',
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['type']);
    }

    public function test_store_rejects_score_below_0(): void
    {
        $this->authenticateAsAdmin();
        $response = $this->postJson('/api/grades', [
            'student_id' => $this->studentId,
            'subject_id' => $this->subjectId,
            'class_id' => $this->classId,
            'type' => 'tugas',
            'score' => -1,
            'semester' => '1',
            'academic_year' => '2026/2027',
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['score']);
    }

    public function test_store_rejects_score_above_100(): void
    {
        $this->authenticateAsAdmin();
        $response = $this->postJson('/api/grades', [
            'student_id' => $this->studentId,
            'subject_id' => $this->subjectId,
            'class_id' => $this->classId,
            'type' => 'tugas',
            'score' => 101,
            'semester' => '1',
            'academic_year' => '2026/2027',
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['score']);
    }

    public function test_store_accepts_score_0(): void
    {
        $this->authenticateAsAdmin();
        $response = $this->postJson('/api/grades', [
            'student_id' => $this->studentId,
            'subject_id' => $this->subjectId,
            'class_id' => $this->classId,
            'type' => 'tugas',
            'score' => 0,
            'semester' => '1',
            'academic_year' => '2026/2027',
        ]);
        $response->assertStatus(201);
    }

    public function test_store_accepts_score_100(): void
    {
        $this->authenticateAsAdmin();
        $response = $this->postJson('/api/grades', [
            'student_id' => $this->studentId,
            'subject_id' => $this->subjectId,
            'class_id' => $this->classId,
            'type' => 'tugas',
            'score' => 100,
            'semester' => '1',
            'academic_year' => '2026/2027',
        ]);
        $response->assertStatus(201);
    }

    public function test_store_rejects_invalid_semester(): void
    {
        $this->authenticateAsAdmin();
        $response = $this->postJson('/api/grades', [
            'student_id' => $this->studentId,
            'subject_id' => $this->subjectId,
            'class_id' => $this->classId,
            'type' => 'tugas',
            'score' => 85,
            'semester' => '3',
            'academic_year' => '2026/2027',
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['semester']);
    }

    public function test_store_rejects_invalid_academic_year(): void
    {
        $this->authenticateAsAdmin();
        $response = $this->postJson('/api/grades', [
            'student_id' => $this->studentId,
            'subject_id' => $this->subjectId,
            'class_id' => $this->classId,
            'type' => 'tugas',
            'score' => 85,
            'semester' => '1',
            'academic_year' => '2026-2027',
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['academic_year']);
    }

    public function test_store_rejects_string_student_id(): void
    {
        $this->authenticateAsAdmin();
        $response = $this->postJson('/api/grades', [
            'student_id' => 'abc',
            'subject_id' => $this->subjectId,
            'class_id' => $this->classId,
            'type' => 'tugas',
            'score' => 85,
            'semester' => '1',
            'academic_year' => '2026/2027',
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['student_id']);
    }

    // ─── Business Rule Tests ───────────────────────────────────

    public function test_store_rejects_student_without_class(): void
    {
        $this->authenticateAsAdmin();
        $noClassStudent = $this->createTestStudent(['class_id' => null]);

        $response = $this->postJson('/api/grades', [
            'student_id' => $noClassStudent->id,
            'subject_id' => $this->subjectId,
            'class_id' => $this->classId,
            'type' => 'tugas',
            'score' => 85,
            'semester' => '1',
            'academic_year' => '2026/2027',
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['student_id']);
    }

    public function test_store_rejects_student_in_different_class(): void
    {
        $this->authenticateAsAdmin();
        $otherClassId = $this->getSecondClassId();
        $otherClassStudent = $this->createTestStudent(['class_id' => $otherClassId]);

        $response = $this->postJson('/api/grades', [
            'student_id' => $otherClassStudent->id,
            'subject_id' => $this->subjectId,
            'class_id' => $this->classId,
            'type' => 'tugas',
            'score' => 85,
            'semester' => '1',
            'academic_year' => '2026/2027',
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['student_id']);
    }

    public function test_store_rejects_subject_not_assigned_to_class(): void
    {
        $this->authenticateAsAdmin();
        $otherSubjectId = $this->getSecondSubjectId();

        $response = $this->postJson('/api/grades', [
            'student_id' => $this->studentId,
            'subject_id' => $otherSubjectId,
            'class_id' => $this->classId,
            'type' => 'tugas',
            'score' => 85,
            'semester' => '1',
            'academic_year' => '2026/2027',
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['subject_id']);
    }

    public function test_store_accepts_valid_student_class_subject(): void
    {
        $this->authenticateAsAdmin();
        $response = $this->postJson('/api/grades', [
            'student_id' => $this->studentId,
            'subject_id' => $this->subjectId,
            'class_id' => $this->classId,
            'type' => 'tugas',
            'score' => 85,
            'semester' => '1',
            'academic_year' => '2026/2027',
        ]);
        $response->assertStatus(201);
    }

    // ─── Duplicate Tests ───────────────────────────────────────

    public function test_store_rejects_duplicate_grade(): void
    {
        $this->authenticateAsAdmin();
        $this->createTestGrade();

        $response = $this->postJson('/api/grades', [
            'student_id' => $this->studentId,
            'subject_id' => $this->subjectId,
            'class_id' => $this->classId,
            'type' => 'tugas',
            'score' => 90,
            'semester' => '1',
            'academic_year' => '2026/2027',
        ]);
        $response->assertStatus(422);
    }

    public function test_store_allows_same_student_different_type(): void
    {
        $this->authenticateAsAdmin();
        $this->createTestGrade(['type' => 'tugas']);

        $response = $this->postJson('/api/grades', [
            'student_id' => $this->studentId,
            'subject_id' => $this->subjectId,
            'class_id' => $this->classId,
            'type' => 'uts',
            'score' => 90,
            'semester' => '1',
            'academic_year' => '2026/2027',
        ]);
        $response->assertStatus(201);
    }

    public function test_store_allows_same_student_different_semester(): void
    {
        $this->authenticateAsAdmin();
        $this->createTestGrade(['semester' => '1']);

        $response = $this->postJson('/api/grades', [
            'student_id' => $this->studentId,
            'subject_id' => $this->subjectId,
            'class_id' => $this->classId,
            'type' => 'tugas',
            'score' => 90,
            'semester' => '2',
            'academic_year' => '2026/2027',
        ]);
        $response->assertStatus(201);
    }

    public function test_store_allows_same_student_different_academic_year(): void
    {
        $this->authenticateAsAdmin();
        $this->createTestGrade(['academic_year' => '2025/2026']);

        $response = $this->postJson('/api/grades', [
            'student_id' => $this->studentId,
            'subject_id' => $this->subjectId,
            'class_id' => $this->classId,
            'type' => 'tugas',
            'score' => 90,
            'semester' => '1',
            'academic_year' => '2026/2027',
        ]);
        $response->assertStatus(201);
    }

    // ─── Update Tests ──────────────────────────────────────────

    public function test_admin_can_update_grade(): void
    {
        $this->authenticateAsAdmin();
        $grade = $this->createTestGrade();
        $response = $this->putJson("/api/grades/{$grade->id}", [
            'score' => 95,
        ]);
        $response->assertStatus(200);
    }

    public function test_administrator_can_update_grade(): void
    {
        $this->authenticateAsAdministrator();
        $grade = $this->createTestGrade();
        $response = $this->putJson("/api/grades/{$grade->id}", [
            'score' => 95,
        ]);
        $response->assertStatus(200);
    }

    public function test_guru_cannot_update_grade(): void
    {
        $this->authenticateAsGuru();
        $grade = $this->createTestGrade();
        $response = $this->putJson("/api/grades/{$grade->id}", [
            'score' => 95,
        ]);
        $response->assertStatus(403);
    }

    public function test_siswa_cannot_update_grade(): void
    {
        $this->authenticateAsSiswa();
        $grade = $this->createTestGrade();
        $response = $this->putJson("/api/grades/{$grade->id}", [
            'score' => 95,
        ]);
        $response->assertStatus(403);
    }

    public function test_guru_cannot_patch_grade(): void
    {
        $this->authenticateAsGuru();
        $grade = $this->createTestGrade();
        $response = $this->patchJson("/api/grades/{$grade->id}", [
            'score' => 95,
        ]);
        $response->assertStatus(403);
    }

    public function test_siswa_cannot_patch_grade(): void
    {
        $this->authenticateAsSiswa();
        $grade = $this->createTestGrade();
        $response = $this->patchJson("/api/grades/{$grade->id}", [
            'score' => 95,
        ]);
        $response->assertStatus(403);
    }

    public function test_update_changes_score(): void
    {
        $this->authenticateAsAdmin();
        $grade = $this->createTestGrade(['score' => 70]);
        $response = $this->putJson("/api/grades/{$grade->id}", [
            'score' => 95,
        ]);
        $response->assertStatus(200);
        $this->assertEquals('95.00', $response->json('data.score'));
    }

    public function test_update_nonexistent_returns_404(): void
    {
        $this->authenticateAsAdmin();
        $response = $this->putJson('/api/grades/99999', [
            'score' => 95,
        ]);
        $response->assertStatus(404);
    }

    public function test_update_returns_eager_loaded_relations(): void
    {
        $this->authenticateAsAdmin();
        $grade = $this->createTestGrade();
        $response = $this->putJson("/api/grades/{$grade->id}", [
            'score' => 95,
        ]);
        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertArrayHasKey('student', $data);
        $this->assertArrayHasKey('subject', $data);
        $this->assertArrayHasKey('class', $data);
    }

    public function test_update_rejects_score_above_100(): void
    {
        $this->authenticateAsAdmin();
        $grade = $this->createTestGrade();
        $response = $this->putJson("/api/grades/{$grade->id}", [
            'score' => 101,
        ]);
        $response->assertStatus(422);
    }

    // ─── Destroy Tests ─────────────────────────────────────────

    public function test_admin_can_delete_grade(): void
    {
        $this->authenticateAsAdmin();
        $grade = $this->createTestGrade();
        $response = $this->deleteJson("/api/grades/{$grade->id}");
        $response->assertStatus(200);
    }

    public function test_administrator_can_delete_grade(): void
    {
        $this->authenticateAsAdministrator();
        $grade = $this->createTestGrade();
        $response = $this->deleteJson("/api/grades/{$grade->id}");
        $response->assertStatus(200);
    }

    public function test_guru_cannot_delete_grade(): void
    {
        $this->authenticateAsGuru();
        $grade = $this->createTestGrade();
        $response = $this->deleteJson("/api/grades/{$grade->id}");
        $response->assertStatus(403);
    }

    public function test_siswa_cannot_delete_grade(): void
    {
        $this->authenticateAsSiswa();
        $grade = $this->createTestGrade();
        $response = $this->deleteJson("/api/grades/{$grade->id}");
        $response->assertStatus(403);
    }

    public function test_delete_removes_from_database(): void
    {
        $this->authenticateAsAdmin();
        $grade = $this->createTestGrade();
        $this->deleteJson("/api/grades/{$grade->id}")->assertStatus(200);
        $this->assertDatabaseMissing('grades', ['id' => $grade->id], 'mysql');
    }

    public function test_delete_nonexistent_returns_404(): void
    {
        $this->authenticateAsAdmin();
        $response = $this->deleteJson('/api/grades/99999');
        $response->assertStatus(404);
    }

    // ─── Database Integrity Tests ──────────────────────────────

    public function test_delete_preserves_student(): void
    {
        $this->authenticateAsAdmin();
        $grade = $this->createTestGrade();
        $this->deleteJson("/api/grades/{$grade->id}")->assertStatus(200);
        $this->assertDatabaseHas('students', ['id' => $this->studentId], 'mysql');
    }

    public function test_delete_preserves_subject(): void
    {
        $this->authenticateAsAdmin();
        $grade = $this->createTestGrade();
        $this->deleteJson("/api/grades/{$grade->id}")->assertStatus(200);
        $this->assertDatabaseHas('subjects', ['id' => $this->subjectId], 'mysql');
    }

    public function test_delete_preserves_class(): void
    {
        $this->authenticateAsAdmin();
        $grade = $this->createTestGrade();
        $this->deleteJson("/api/grades/{$grade->id}")->assertStatus(200);
        $this->assertDatabaseHas('classes', ['id' => $this->classId], 'mysql');
    }

    public function test_delete_preserves_class_subject(): void
    {
        $this->authenticateAsAdmin();
        $grade = $this->createTestGrade();
        $this->deleteJson("/api/grades/{$grade->id}")->assertStatus(200);
        $this->assertDatabaseHas('class_subjects', ['id' => $this->classSubjectId], 'mysql');
    }

    // ─── IDOR Tests ────────────────────────────────────────────

    public function test_idor_show_returns_404(): void
    {
        $this->authenticate();
        $response = $this->getJson('/api/grades/99999');
        $response->assertStatus(404);
    }

    public function test_idor_update_returns_404(): void
    {
        $this->authenticateAsAdmin();
        $response = $this->putJson('/api/grades/99999', ['score' => 90]);
        $response->assertStatus(404);
    }

    public function test_idor_delete_returns_404(): void
    {
        $this->authenticateAsAdmin();
        $response = $this->deleteJson('/api/grades/99999');
        $response->assertStatus(404);
    }

    // ─── Mass Assignment Tests ─────────────────────────────────

    public function test_store_ignores_id_field(): void
    {
        $this->authenticateAsAdmin();
        $response = $this->postJson('/api/grades', [
            'id' => 99999,
            'student_id' => $this->studentId,
            'subject_id' => $this->subjectId,
            'class_id' => $this->classId,
            'type' => 'tugas',
            'score' => 85,
            'semester' => '1',
            'academic_year' => '2026/2027',
        ]);
        $response->assertStatus(201);
        $this->assertNotEquals(99999, $response->json('data.id'));
    }

    public function test_store_ignores_created_at(): void
    {
        $this->authenticateAsAdmin();
        $response = $this->postJson('/api/grades', [
            'student_id' => $this->studentId,
            'subject_id' => $this->subjectId,
            'class_id' => $this->classId,
            'type' => 'tugas',
            'score' => 85,
            'semester' => '1',
            'academic_year' => '2026/2027',
            'created_at' => '2000-01-01 00:00:00',
        ]);
        $response->assertStatus(201);
        $this->assertNotEquals('2000-01-01T00:00:00.000000Z', $response->json('data.created_at'));
    }

    // ─── Sensitive Field Tests ─────────────────────────────────

    public function test_index_does_not_expose_password(): void
    {
        $this->authenticate();
        $this->createTestGrade();
        $response = $this->getJson('/api/grades');
        $response->assertStatus(200);
        foreach ($response->json('data') as $item) {
            $this->assertArrayNotHasKey('password', $item['student'] ?? []);
        }
    }
}
