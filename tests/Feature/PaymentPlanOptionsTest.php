<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PaymentPlanOptionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_payment_plan_options_for_authenticated_user(): void
    {
        $user = User::create([
            'name' => 'Test Staff',
            'email' => 'staff+' . uniqid() . '@test.local',
            'password' => 'password123',
            'is_admin' => false,
            'is_active' => true,
        ]);
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/payment-plans');

        $response
            ->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'default_plan' => 'monthly',
                    'currency' => 'USD',
                    'monthly_tuition' => '500.00',
                ],
            ]);

        $plans = $response->json('data.plans');
        $this->assertIsArray($plans);
        $this->assertCount(3, $plans);

        $monthly = collect($plans)->firstWhere('code', 'monthly');
        $halfYear = collect($plans)->firstWhere('code', 'half_year');
        $yearly = collect($plans)->firstWhere('code', 'yearly');

        $this->assertSame('500.00', $monthly['amount']);
        $this->assertSame('500.00', $monthly['payable_amount']);

        $this->assertSame('3000.00', $halfYear['amount']);
        $this->assertSame('3000.00', $halfYear['payable_amount']);

        $this->assertSame('6000.00', $yearly['amount']);
        $this->assertSame('600.00', $yearly['discount_amount']);
        $this->assertSame('5400.00', $yearly['payable_amount']);
    }
}
