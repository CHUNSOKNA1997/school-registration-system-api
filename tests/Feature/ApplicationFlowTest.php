<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\StudentApplication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApplicationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_submit_application(): void
    {
        $response = $this->postJson('/api/v1/applications', $this->applicationPayload());

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'payment_pending');

        $applicationUuid = $response->json('data.application_uuid');
        $this->assertNotEmpty($applicationUuid);

        $this->assertDatabaseHas('student_applications', [
            'uuid' => $applicationUuid,
            'status' => 'payment_pending',
            'personal_email' => 'applicant@example.test',
        ]);
    }

    public function test_paid_application_provisions_and_activates_student_account(): void
    {
        $create = $this->postJson('/api/v1/applications', $this->applicationPayload());
        $create->assertStatus(201);

        $applicationUuid = $create->json('data.application_uuid');
        $application = StudentApplication::where('uuid', $applicationUuid)->with('payment')->firstOrFail();

        Payment::where('id', $application->payment_id)->update([
            'status' => 'paid',
            'paid_at' => now(),
            'payment_date' => now()->toDateString(),
        ]);

        $status = $this->getJson('/api/v1/applications/' . $applicationUuid . '/payment-status');
        $status->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.payment_status', 'paid')
            ->assertJsonPath('data.application_status', 'account_created');

        $application->refresh();
        $this->assertNotNull($application->user_id);
        $this->assertNotNull($application->school_email);

        $knownToken = 'known-activation-token';
        $application->update([
            'activation_token_hash' => hash('sha256', $knownToken),
            'activation_token_expires_at' => now()->addHours(24),
        ]);

        $activate = $this->postJson('/api/v1/student-accounts/activate', [
            'token' => $knownToken,
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ]);

        $activate->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.school_email', $application->school_email);

        $login = $this->postJson('/api/v1/sessions', [
            'email' => $application->school_email,
            'password' => 'new-password-123',
        ]);

        $login->assertOk()
            ->assertJsonPath('success', true);
    }

    private function applicationPayload(array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'Sokna',
            'last_name' => 'Chun',
            'khmer_name' => null,
            'date_of_birth' => now()->subYears(12)->toDateString(),
            'place_of_birth' => 'Phnom Penh',
            'gender' => 'female',
            'student_type' => 'regular',
            'nationality' => 'Cambodian',
            'phone' => '012111222',
            'email' => 'applicant@example.test',
            'current_address' => 'Phnom Penh',
            'parent_name' => 'Parent Name',
            'parent_phone' => '099111222',
            'parent_occupation' => 'Teacher',
            'emergency_contact' => '011222333',
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
