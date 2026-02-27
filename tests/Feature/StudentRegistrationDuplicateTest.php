<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StudentRegistrationDuplicateTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_rejects_duplicate_student_identity_registration(): void
    {
        $this->authenticateUser();

        $payload = $this->studentPayload([
            'email' => null,
            'phone' => null,
        ]);

        $first = $this->postJson('/api/v1/students', $payload);
        $first->assertStatus(201);

        $second = $this->postJson('/api/v1/students', $payload);
        $second
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Student is already registered with the same name, date of birth, and parent phone',
            ]);
    }

    public function test_it_rejects_duplicate_student_email_registration(): void
    {
        $this->authenticateUser();

        $first = $this->postJson('/api/v1/students', $this->studentPayload([
            'first_name' => 'Sokha',
            'last_name' => 'Kim',
            'parent_phone' => '099999991',
            'email' => 'same@student.test',
            'phone' => '012000001',
        ]));
        $first->assertStatus(201);

        $second = $this->postJson('/api/v1/students', $this->studentPayload([
            'first_name' => 'Dara',
            'last_name' => 'Chan',
            'parent_phone' => '099999992',
            'email' => 'same@student.test',
            'phone' => '012000002',
        ]));

        $second
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    private function studentPayload(array $overrides = []): array
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
            'phone' => '012345678',
            'email' => 'student@example.test',
            'current_address' => 'Phnom Penh',
            'permanent_address' => 'Kandal',
            'parent_name' => 'Chan Vanna',
            'parent_phone' => '099999999',
            'parent_occupation' => 'Teacher',
            'emergency_contact' => '011111111',
            'emergency_contact_relationship' => 'Aunt',
            'class_id' => null,
            'shift' => 'morning',
            'registration_date' => now()->toDateString(),
            'academic_year' => '2025-2026',
            'previous_school' => 'ABC School',
            'photo' => null,
            'documents' => [],
            'notes' => null,
        ], $overrides);
    }

    private function authenticateUser(): void
    {
        $user = User::create([
            'name' => 'Test Staff',
            'email' => 'staff+' . uniqid() . '@test.local',
            'password' => 'password123',
            'is_admin' => false,
            'is_active' => true,
        ]);

        Sanctum::actingAs($user);
    }
}
