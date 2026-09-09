<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\SubscriptionPlan;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SubscriptionPlanEndpointTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['mauzo360.channel_token' => 'test-channel']);
    }

    public function test_super_user_can_create_subscription_plan_with_fixed_30_day_duration(): void
    {
        $admin = $this->superUser();

        $response = $this->postJson('/api/v2/baseApp/create-subscription-plan', [
            'title' => 'Starter',
            'amount' => 15000,
            'description' => 'For small shops',
            'durationDays' => 90,
        ], $this->headers($admin));

        $response
            ->assertOk()
            ->assertJsonPath('statusCode', 1)
            ->assertJsonPath('message', 'success')
            ->assertJsonPath('data.title', 'Starter')
            ->assertJsonPath('data.amount', 15000)
            ->assertJsonPath('data.description', 'For small shops')
            ->assertJsonPath('data.durationDays', 30);

        $this->assertDatabaseHas('subscription_plans', [
            'title' => 'Starter',
            'amount' => 15000,
            'description' => 'For small shops',
            'duration_days' => 30,
        ]);
    }

    public function test_super_user_can_update_subscription_plan_without_changing_duration(): void
    {
        $admin = $this->superUser();
        $plan = SubscriptionPlan::factory()->create([
            'title' => 'Starter',
            'amount' => 15000,
            'duration_days' => 7,
        ]);

        $response = $this->putJson('/api/v2/baseApp/subscription-plans/'.$plan->id, [
            'title' => 'Business',
            'amount' => 25000,
            'description' => 'For growing teams',
            'durationDays' => 365,
        ], $this->headers($admin));

        $response
            ->assertOk()
            ->assertJsonPath('statusCode', 1)
            ->assertJsonPath('message', 'success')
            ->assertJsonPath('data.title', 'Business')
            ->assertJsonPath('data.amount', 25000)
            ->assertJsonPath('data.description', 'For growing teams')
            ->assertJsonPath('data.durationDays', 30);

        $this->assertDatabaseHas('subscription_plans', [
            'id' => $plan->id,
            'title' => 'Business',
            'amount' => 25000,
            'description' => 'For growing teams',
            'duration_days' => 30,
        ]);
    }

    public function test_super_user_can_delete_subscription_plan(): void
    {
        $admin = $this->superUser();
        $plan = SubscriptionPlan::factory()->create();

        $response = $this->postJson('/api/v2/baseApp/delete-subscription-plan', [
            'planId' => $plan->id,
        ], $this->headers($admin));

        $response
            ->assertOk()
            ->assertJsonPath('statusCode', 1)
            ->assertJsonPath('message', 'success');

        $this->assertSoftDeleted('subscription_plans', [
            'id' => $plan->id,
        ]);
    }

    public function test_list_returns_subscription_plans_for_interface(): void
    {
        $admin = $this->superUser();
        SubscriptionPlan::factory()->create([
            'title' => 'Starter',
            'amount' => 15000,
            'duration_days' => 30,
            'created_at' => now()->subMinute(),
        ]);
        SubscriptionPlan::factory()->create([
            'title' => 'Business',
            'amount' => 25000,
            'duration_days' => 30,
            'created_at' => now(),
        ]);

        $response = $this->getJson('/api/v2/baseApp/subscription-plans', $this->headers($admin));

        $response
            ->assertOk()
            ->assertJsonPath('statusCode', 1)
            ->assertJsonPath('message', 'success')
            ->assertJsonPath('data.0.title', 'Business')
            ->assertJsonPath('data.0.durationDays', 30)
            ->assertJsonPath('data.1.title', 'Starter')
            ->assertJsonPath('data.1.durationDays', 30);
    }

    public function test_returns_422_when_required_plan_fields_are_missing(): void
    {
        $admin = $this->superUser();

        $response = $this->postJson('/api/v2/baseApp/create-subscription-plan', [
            'description' => 'Missing title and amount',
        ], $this->headers($admin));

        $response
            ->assertUnprocessable()
            ->assertJsonPath('statusCode', 0)
            ->assertJsonPath('message', 'The title field is required.');

        $this->assertDatabaseMissing('subscription_plans', [
            'description' => 'Missing title and amount',
        ]);
    }

    public function test_regular_user_cannot_manage_subscription_plans(): void
    {
        $admin = Admin::factory()->create([
            'secret_key' => 'owner-token-'.Str::random(10),
            'super_user' => 0,
        ]);

        $response = $this->postJson('/api/v2/baseApp/create-subscription-plan', [
            'title' => 'Starter',
            'amount' => 15000,
            'description' => 'For small shops',
        ], $this->headers($admin));

        $response
            ->assertOk()
            ->assertJsonPath('statusCode', 0)
            ->assertJsonPath('message', 'Fail you dont have access to view this resource');

        $this->assertDatabaseMissing('subscription_plans', [
            'title' => 'Starter',
        ]);
    }

    private function superUser(): Admin
    {
        return Admin::factory()->create([
            'secret_key' => 'super-token-'.Str::random(10),
            'super_user' => 1,
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function headers(Admin $admin): array
    {
        return [
            'X-Api-Key' => 'test-channel',
            'token' => (string) $admin->secret_key,
            'username' => (string) $admin->username,
        ];
    }
}
