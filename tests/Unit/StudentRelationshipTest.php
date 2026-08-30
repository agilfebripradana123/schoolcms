<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Students\Student;
use App\Models\Students\StudentParent;
use App\Models\Students\Guardian;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class StudentRelationshipTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Test Student model has parent relationship defined.
     */
    public function test_student_has_parent_relationship_defined(): void
    {
        $student = new Student();
        
        $this->assertTrue(
            method_exists($student, 'parent'),
            'Student model should have parent() method'
        );
    }

    /**
     * Test Student model has guardians relationship defined.
     */
    public function test_student_has_guardians_relationship_defined(): void
    {
        $student = new Student();
        
        $this->assertTrue(
            method_exists($student, 'guardians'),
            'Student model should have guardians() method'
        );
    }

    /**
     * Test parent relationship returns HasOne instance.
     */
    public function test_parent_relationship_is_has_one(): void
    {
        $student = new Student();
        $relation = $student->parent();
        
        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\HasOne::class,
            $relation,
            'parent() should return HasOne relationship'
        );
    }

    /**
     * Test guardians relationship returns HasMany instance.
     */
    public function test_guardians_relationship_is_has_many(): void
    {
        $student = new Student();
        $relation = $student->guardians();
        
        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\HasMany::class,
            $relation,
            'guardians() should return HasMany relationship'
        );
    }

    /**
     * Test parent relationship uses correct foreign key.
     */
    public function test_parent_relationship_uses_correct_foreign_key(): void
    {
        $student = new Student();
        $relation = $student->parent();
        
        $this->assertEquals(
            'student_id',
            $relation->getForeignKeyName(),
            'Parent relationship should use student_id as foreign key'
        );
    }

    /**
     * Test guardians relationship uses correct foreign key.
     */
    public function test_guardians_relationship_uses_correct_foreign_key(): void
    {
        $student = new Student();
        $relation = $student->guardians();
        
        $this->assertEquals(
            'student_id',
            $relation->getForeignKeyName(),
            'Guardians relationship should use student_id as foreign key'
        );
    }

    /**
     * Test parent relationship returns StudentParent model.
     */
    public function test_parent_relationship_returns_student_parent_model(): void
    {
        $student = new Student();
        $relation = $student->parent();
        
        $this->assertEquals(
            StudentParent::class,
            get_class($relation->getRelated()),
            'Parent relationship should return StudentParent model'
        );
    }

    /**
     * Test guardians relationship returns Guardian model.
     */
    public function test_guardians_relationship_returns_guardian_model(): void
    {
        $student = new Student();
        $relation = $student->guardians();
        
        $this->assertEquals(
            Guardian::class,
            get_class($relation->getRelated()),
            'Guardians relationship should return Guardian model'
        );
    }

    /**
     * Test StudentParent has student relationship.
     */
    public function test_student_parent_has_student_relationship(): void
    {
        $parent = new StudentParent();
        
        $this->assertTrue(
            method_exists($parent, 'student'),
            'StudentParent model should have student() method'
        );
        
        $relation = $parent->student();
        
        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\BelongsTo::class,
            $relation,
            'student() should return BelongsTo relationship'
        );
    }

    /**
     * Test Guardian has student relationship.
     */
    public function test_guardian_has_student_relationship(): void
    {
        $guardian = new Guardian();
        
        $this->assertTrue(
            method_exists($guardian, 'student'),
            'Guardian model should have student() method'
        );
        
        $relation = $guardian->student();
        
        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\BelongsTo::class,
            $relation,
            'student() should return BelongsTo relationship'
        );
    }
}
