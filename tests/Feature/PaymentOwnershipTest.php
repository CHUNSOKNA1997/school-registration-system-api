<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PaymentOwnershipTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_can_only_access_own_payment(): void
    {
        $studentUserA = $this->createStudentUser('a@example.test');
        $studentUserB = $this->createStudentUser('b@example.test');

        $studentA = $this->createStudentProfile($studentUserA, 'A');
        $studentB = $this->createStudentProfile($studentUserB, 'B');

        $paymentA = $this->createPayment($studentA, 'PA');
        $paymentB = $this->createPayment($studentB, 'PB');

        Sanctum::actingAs($studentUserA);

        $own = $this->getJson('/api/v1/payments/' . $paymentA->uuid);
        $own
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.payment_uuid', $paymentA->uuid);

        $other = $this->getJson('/api/v1/payments/' . $paymentB->uuid);
        $other
            ->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthorized access to payment',
            ]);
    }

    private function createStudentUser(string $email): User
    {
        return User::create([
            'name' => 'Student User',
            'email' => $email,
            'password' => 'password123',
            'is_admin' => false,
            'account_type' => 'student',
            'is_active' => true,
        ]);
    }

    private function createStudentProfile(User $user, string $suffix): Student
    {
        return Student::create([
            'uuid' => (string) Str::uuid(),
            'student_code' => '2026-00' . $suffix,
            'first_name' => 'Student',
            'last_name' => $suffix,
            'date_of_birth' => now()->subYears(12)->toDateString(),
            'gender' => 'male',
            'student_type' => 'regular',
            'phone' => '01200000' . ord($suffix),
            'email' => strtolower($suffix) . '.student@example.test',
            'parent_name' => 'Parent ' . $suffix,
            'parent_phone' => '09900000' . ord($suffix),
            'shift' => 'morning',
            'registration_date' => now()->toDateString(),
            'academic_year' => '2025-2026',
            'status' => 'active',
            'created_by' => $user->id,
            'user_id' => $user->id,
        ]);
    }

    private function createPayment(Student $student, string $suffix): Payment
    {
        return Payment::create([
            'uuid' => (string) Str::uuid(),
            'payment_code' => 'PAY-20260227-00' . $suffix,
            'student_id' => $student->id,
            'academic_year' => '2025-2026',
            'amount' => 500.00,
            'discount_amount' => 0.00,
            'paid_amount' => 0.00,
            'balance' => 500.00,
            'payment_type' => 'tuition',
            'payment_period' => 'monthly',
            'payment_method' => 'cash',
            'due_date' => now()->addMonth()->toDateString(),
            'status' => 'pending',
        ]);
    }
}
