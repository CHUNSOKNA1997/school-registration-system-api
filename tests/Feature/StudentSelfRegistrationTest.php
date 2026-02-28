<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StudentSelfRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_account_can_self_register_once(): void
    {
        $user = User::create([
            'name' => 'Student Account',
            'email' => 'student.self@example.test',
            'password' => 'password123',
            'is_admin' => false,
            'account_type' => 'student',
            'is_active' => true,
        ]);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/student-registrations', $this->payload());
        $response
            ->assertStatus(201)
            ->assertJsonPath('data.user_id', $user->id);

        $this->assertDatabaseHas('students', [
            'user_id' => $user->id,
            'email' => 'student.profile@example.test',
        ]);

        $second = $this->postJson('/api/v1/student-registrations', $this->payload([
            'email' => 'student.profile2@example.test',
            'phone' => '012333445',
            'parent_phone' => '099222334',
        ]));
        $second
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Student profile already exists for this account',
            ]);
    }

    public function test_student_account_cannot_use_staff_student_create_endpoint(): void
    {
        $user = User::create([
            'name' => 'Student Account',
            'email' => 'student.blocked@example.test',
            'password' => 'password123',
            'is_admin' => false,
            'account_type' => 'student',
            'is_active' => true,
        ]);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/students', $this->payload([
            'email' => 'blocked@example.test',
            'phone' => '012333446',
            'parent_phone' => '099222335',
        ]));

        $response
            ->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Use POST /api/v1/student-registrations for student self-registration',
            ]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'Sophea',
            'last_name' => 'Chan',
            'khmer_name' => null,
            'date_of_birth' => now()->subYears(12)->toDateString(),
            'place_of_birth' => 'Phnom Penh',
            'gender' => 'female',
            'student_type' => 'regular',
            'nationality' => 'Cambodian',
            'phone' => '012333444',
            'email' => 'student.profile@example.test',
            'current_address' => 'Phnom Penh',
            'parent_name' => 'Chan Vanna',
            'parent_phone' => '099222333',
            'parent_occupation' => 'Teacher',
            'emergency_contact' => '011111111',
            'emergency_contact_relationship' => 'Aunt',
            'class_id' => null,
            'shift' => 'morning',
            'academic_year' => '2025-2026',
            'payment_plan' => 'monthly',
            'previous_school' => 'ABC School',
            'photo' => null,
            'documents' => [],
            'notes' => null,
        ], $overrides);
    }
}
