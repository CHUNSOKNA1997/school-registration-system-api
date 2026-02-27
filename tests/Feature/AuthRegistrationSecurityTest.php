<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthRegistrationSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_registration_cannot_create_admin_account(): void
    {
        $response = $this->postJson('/api/v1/registrations', [
            'name' => 'Student User',
            'email' => 'student.user@example.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'phone' => '012345678',
            'is_admin' => true,
        ]);

        $response
            ->assertStatus(201)
            ->assertJsonPath('data.user.is_admin', false)
            ->assertJsonPath('data.user.account_type', 'student');

        $user = User::where('email', 'student.user@example.test')->firstOrFail();
        $this->assertFalse((bool) $user->is_admin);
        $this->assertSame('student', $user->account_type);
    }
}
