<?php

namespace Tests\Unit;

use App\Models\Teacher;
use PHPUnit\Framework\TestCase;

class TeacherModelTest extends TestCase
{
    private Teacher $teacher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->teacher = new Teacher();
    }

    public function test_teacher_uses_correct_table(): void
    {
        $this->assertEquals('teachers', $this->teacher->getTable());
    }

    public function test_teacher_primary_key_is_id(): void
    {
        $this->assertEquals('id', $this->teacher->getKeyName());
    }

    public function test_teacher_is_incrementing(): void
    {
        $this->assertTrue($this->teacher->getIncrementing());
    }

    public function test_teacher_key_type_is_int(): void
    {
        $this->assertEquals('int', $this->teacher->getKeyType());
    }

    public function test_teacher_has_soft_deletes(): void
    {
        $this->assertTrue(in_array(
            \Illuminate\Database\Eloquent\SoftDeletes::class,
            class_uses_recursive(Teacher::class)
        ));
    }

    public function test_teacher_fillable_matches_legacy_schema(): void
    {
        $expected = [
            'user_id',
            'teacher_code',
            'nip',
            'full_name',
            'prefix_title',
            'suffix_title',
            'phone',
            'email',
            'last_education',
            'major',
            'employment_status',
            'join_date',
            'photo',
            'is_active',
            'address',
            'gender',
            'birth_place',
            'birth_date',
            'religion',
        ];

        $this->assertEquals($expected, $this->teacher->getFillable());
    }

    public function test_teacher_casts_include_is_active_as_boolean(): void
    {
        $casts = $this->teacher->getCasts();
        $this->assertArrayHasKey('is_active', $casts);
        $this->assertEquals('boolean', $casts['is_active']);
    }

    public function test_teacher_casts_include_join_date(): void
    {
        $casts = $this->teacher->getCasts();
        $this->assertArrayHasKey('join_date', $casts);
        $this->assertEquals('date', $casts['join_date']);
    }

    public function test_teacher_casts_include_birth_date(): void
    {
        $casts = $this->teacher->getCasts();
        $this->assertArrayHasKey('birth_date', $casts);
        $this->assertEquals('date', $casts['birth_date']);
    }

    public function test_teacher_has_user_relationship(): void
    {
        $this->assertTrue(method_exists($this->teacher, 'user'));
    }

    public function test_teacher_has_classes_relationship(): void
    {
        $this->assertTrue(method_exists($this->teacher, 'classes'));
    }

    public function test_teacher_has_class_subjects_relationship(): void
    {
        $this->assertTrue(method_exists($this->teacher, 'classSubjects'));
    }

    public function test_teacher_uses_has_api_tokens_trait(): void
    {
        $this->assertFalse(in_array(
            \Laravel\Sanctum\HasApiTokens::class,
            class_uses_recursive(Teacher::class)
        ));
    }
}
