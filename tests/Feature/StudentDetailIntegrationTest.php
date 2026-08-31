<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Students\Student;
use App\Models\Students\StudentParent;
use App\Models\Students\Guardian;
use App\Models\System\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class StudentDetailIntegrationTest extends TestCase
{
    /**
     * Test student can have parent relationship.
     */
    public function test_student_can_have_parent_relationship(): void
    {
        $student = Student::with('parent')->first();
        
        $this->assertNotNull($student);
        
        if ($student->parent) {
            $this->assertInstanceOf(StudentParent::class, $student->parent);
            $this->assertEquals($student->id, $student->parent->student_id);
        }
    }

    /**
     * Test student can have guardians relationship.
     */
    public function test_student_can_have_guardians_relationship(): void
    {
        $student = Student::with('guardians')->first();
        
        $this->assertNotNull($student);
        
        foreach ($student->guardians as $guardian) {
            $this->assertInstanceOf(Guardian::class, $guardian);
            $this->assertEquals($student->id, $guardian->student_id);
        }
    }

    /**
     * Test student detail API endpoint returns data.
     */
    public function test_student_detail_api_returns_data(): void
    {
        $user = User::first();
        $student = Student::first();
        
        $this->assertNotNull($student, 'No students found in database');
        
        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/students/{$student->id}");
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'nisn',
                    'nis',
                    'name',
                    'gender',
                    'birth_place',
                    'birth_date',
                    'address',
                ]
            ]);
    }

    /**
     * Test student detail API includes parent when loaded.
     */
    public function test_student_detail_api_includes_parent(): void
    {
        $user = User::first();
        $student = Student::whereHas('parent')->first();
        
        if (!$student) {
            $this->markTestSkipped('No students with parent found');
        }
        
        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/students/{$student->id}");
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'parent' => [
                        'id',
                        'student_id',
                        'father_name',
                        'mother_name',
                    ]
                ]
            ]);
    }

    /**
     * Test student detail API includes guardians when loaded.
     */
    public function test_student_detail_api_includes_guardians(): void
    {
        $user = User::first();
        $student = Student::whereHas('guardians')->first();
        
        if (!$student) {
            $this->markTestSkipped('No students with guardians found');
        }
        
        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/students/{$student->id}");
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'guardians' => [
                        '*' => [
                            'id',
                            'student_id',
                            'name',
                            'relation',
                        ]
                    ]
                ]
            ]);
    }

    /**
     * Test student without parent can be accessed.
     */
    public function test_student_without_parent_can_be_accessed(): void
    {
        $user = User::first();
        $student = Student::doesntHave('parent')->first();
        
        if (!$student) {
            $this->markTestSkipped('All students have parents');
        }
        
        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/students/{$student->id}");
        
        $response->assertStatus(200);
        $this->assertNull($response->json('data.parent'));
    }

    /**
     * Test student without guardians can be accessed.
     */
    public function test_student_without_guardians_can_be_accessed(): void
    {
        $user = User::first();
        $student = Student::doesntHave('guardians')->first();
        
        if (!$student) {
            $this->markTestSkipped('All students have guardians');
        }
        
        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/students/{$student->id}");
        
        $response->assertStatus(200);
        $this->assertEmpty($response->json('data.guardians'));
    }

    /**
     * Test parent relationship returns correct data.
     */
    public function test_parent_relationship_returns_correct_data(): void
    {
        $student = Student::with('parent')->whereHas('parent')->first();
        
        if (!$student) {
            $this->markTestSkipped('No students with parent found');
        }
        
        $this->assertNotNull($student->parent);
        $this->assertNotEmpty($student->parent->father_name);
        $this->assertNotEmpty($student->parent->mother_name);
    }

    /**
     * Test guardians relationship returns correct data.
     */
    public function test_guardians_relationship_returns_correct_data(): void
    {
        $student = Student::with('guardians')->whereHas('guardians')->first();
        
        if (!$student) {
            $this->markTestSkipped('No students with guardians found');
        }
        
        $this->assertGreaterThan(0, $student->guardians->count());
        
        foreach ($student->guardians as $guardian) {
            $this->assertNotEmpty($guardian->name);
            $this->assertContains($guardian->relation, [
                'ayah', 'ibu', 'kakek', 'nenek', 'paman', 'bibi', 'lainnya'
            ]);
        }
    }
}
