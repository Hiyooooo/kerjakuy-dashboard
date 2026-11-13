<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InitialAdminSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_initial_admin_can_log_in_and_reseeding_preserves_the_existing_password(): void
    {
        config([
            'admin.initial_email' => 'initial-admin@example.test',
            'admin.initial_password' => 'initial-test-password',
        ]);
        $this->seed();
        config(['admin.initial_password' => 'different-test-password']);
        $this->seed();

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('roles', 3);

        $this->post(route('login.store'), [
            'email' => 'initial-admin@example.test',
            'password' => 'initial-test-password',
        ])->assertRedirectToRoute('admin.dashboard');

        $this->assertAuthenticatedAs(User::where('email', 'initial-admin@example.test')->firstOrFail());
        $this->withoutVite()->get(route('admin.accounts.index'))->assertOk();
    }

    public function test_seeding_does_not_promote_an_existing_account(): void
    {
        $account = User::factory()->create();
        config(['admin.initial_email' => $account->email, 'admin.initial_password' => 'initial-test-password']);

        try {
            $this->seed();
            $this->fail('Seeding must reject an existing non-admin account.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('not an active Super Admin', $exception->getMessage());
        }

        $this->assertSame('attendance_admin', $account->fresh()->role->code);
    }
}
