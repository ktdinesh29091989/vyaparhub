<?php

namespace Tests\Feature;

use App\Models\PlanGrant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminGrantProTest extends TestCase
{
    use RefreshDatabase;

    // `is_admin`/`plan`/`plan_expires_at` are deliberately excluded from User::$fillable,
    // so User::factory()->create([...]) silently drops them — must forceFill() after create.
    private function makeAdmin(): User
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();

        return $admin;
    }

    public function test_admin_can_grant_pro_to_a_free_user(): void
    {
        $admin = $this->makeAdmin();
        $target = User::factory()->create();

        $response = $this->actingAs($admin)->post(route('admin.users.grant-pro', $target), [
            'days' => 30,
            'plan_type' => 'monthly',
            'reason' => 'Bank transfer',
        ]);

        $response->assertRedirect();

        $target->refresh();
        $this->assertSame('pro', $target->plan);
        $this->assertSame('monthly', $target->plan_type);
        $this->assertEqualsWithDelta(now()->addDays(30)->timestamp, $target->plan_expires_at->timestamp, 5);

        $grant = PlanGrant::first();
        $this->assertNotNull($grant);
        $this->assertSame($target->id, $grant->user_id);
        $this->assertSame($admin->id, $grant->granted_by);
        $this->assertSame('free', $grant->previous_plan);
        $this->assertNull($grant->previous_expires_at);
        $this->assertSame('pro', $grant->new_plan);
        $this->assertSame(30, $grant->days_granted);
        $this->assertSame('Bank transfer', $grant->reason);
    }

    public function test_admin_extend_adds_days_on_top_of_existing_expiry_instead_of_resetting(): void
    {
        $admin = $this->makeAdmin();
        $target = User::factory()->create();
        $farFutureExpiry = now()->addDays(200);
        $target->forceFill(['plan' => 'pro', 'plan_expires_at' => $farFutureExpiry, 'plan_type' => 'annual'])->save();

        $this->actingAs($admin)->post(route('admin.users.grant-pro', $target), [
            'days' => 30,
            'plan_type' => 'monthly',
            'reason' => 'Goodwill extension',
        ]);

        $target->refresh();
        // Must be old expiry + 30 days, NOT now() + 30 days.
        $this->assertEqualsWithDelta($farFutureExpiry->copy()->addDays(30)->timestamp, $target->plan_expires_at->timestamp, 5);

        $grant = PlanGrant::first();
        $this->assertSame('pro', $grant->previous_plan);
        $this->assertEqualsWithDelta($farFutureExpiry->timestamp, $grant->previous_expires_at->timestamp, 5);
    }

    public function test_non_admin_cannot_grant_pro(): void
    {
        $user = User::factory()->create();
        $target = User::factory()->create();

        $response = $this->actingAs($user)->post(route('admin.users.grant-pro', $target), [
            'days' => 30,
            'plan_type' => 'monthly',
            'reason' => 'Bank transfer',
        ]);

        $response->assertForbidden();
        $this->assertSame('free', $target->fresh()->plan);
        $this->assertSame(0, PlanGrant::count());
    }

    public function test_reason_is_required(): void
    {
        $admin = $this->makeAdmin();
        $target = User::factory()->create();

        $response = $this->actingAs($admin)->post(route('admin.users.grant-pro', $target), [
            'days' => 30,
            'plan_type' => 'monthly',
        ]);

        $response->assertSessionHasErrors('reason');
        $this->assertSame('free', $target->fresh()->plan);
        $this->assertSame(0, PlanGrant::count());
    }

    public function test_grants_page_lists_and_filters_by_user(): void
    {
        $admin = $this->makeAdmin();
        $targetA = User::factory()->create(['name' => 'Target A']);
        $targetB = User::factory()->create(['name' => 'Target B']);

        $this->actingAs($admin)->post(route('admin.users.grant-pro', $targetA), [
            'days' => 30, 'plan_type' => 'monthly', 'reason' => 'Bank transfer',
        ]);
        $this->actingAs($admin)->post(route('admin.users.grant-pro', $targetB), [
            'days' => 60, 'plan_type' => 'annual', 'reason' => 'Support compensation',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.grants'));
        $response->assertOk();
        $response->assertSee('Target A');
        $response->assertSee('Target B');

        $filtered = $this->actingAs($admin)->get(route('admin.grants', ['user_id' => $targetA->id]));
        $filtered->assertOk();
        $filtered->assertSee('Target A');
        $filtered->assertDontSee('Target B');
    }
}
