<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StudentRegistrationPaymentPlanTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_uses_monthly_plan_by_default(): void
    {
        $this->authenticateUser();

        $response = $this->postJson('/api/v1/students', $this->studentPayload());
        $response->assertStatus(201);

        $studentId = (int) $response->json('data.id');
        $payment = Payment::where('student_id', $studentId)->firstOrFail();

        $this->assertSame('monthly', $payment->payment_period->value);
        $this->assertSame('500.00', number_format((float) $payment->amount, 2, '.', ''));
        $this->assertSame('0.00', number_format((float) $payment->discount_amount, 2, '.', ''));
        $this->assertSame('500.00', number_format((float) $payment->balance, 2, '.', ''));
    }

    public function test_it_supports_half_year_plan(): void
    {
        $this->authenticateUser();

        $response = $this->postJson('/api/v1/students', $this->studentPayload([
            'email' => 'halfyear@example.test',
            'phone' => '012300001',
            'parent_phone' => '099000001',
            'payment_plan' => 'half_year',
        ]));
        $response->assertStatus(201);

        $studentId = (int) $response->json('data.id');
        $payment = Payment::where('student_id', $studentId)->firstOrFail();

        $this->assertSame('monthly', $payment->payment_period->value);
        $this->assertSame('3000.00', number_format((float) $payment->amount, 2, '.', ''));
        $this->assertSame('0.00', number_format((float) $payment->discount_amount, 2, '.', ''));
        $this->assertSame('3000.00', number_format((float) $payment->balance, 2, '.', ''));
    }

    public function test_it_supports_yearly_plan_with_discount(): void
    {
        $this->authenticateUser();

        $response = $this->postJson('/api/v1/students', $this->studentPayload([
            'email' => 'yearly@example.test',
            'phone' => '012300002',
            'parent_phone' => '099000002',
            'payment_plan' => 'yearly',
        ]));
        $response->assertStatus(201);

        $studentId = (int) $response->json('data.id');
        $payment = Payment::where('student_id', $studentId)->firstOrFail();

        $this->assertSame('yearly', $payment->payment_period->value);
        $this->assertSame('6000.00', number_format((float) $payment->amount, 2, '.', ''));
        $this->assertSame('600.00', number_format((float) $payment->discount_amount, 2, '.', ''));
        $this->assertSame('5400.00', number_format((float) $payment->balance, 2, '.', ''));
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
}
