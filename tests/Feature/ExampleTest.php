<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\AdminAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_the_login_form(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirectToRoute('login');

        $this->withoutVite()->get(route('login'))->assertOk()->assertViewIs('auth.login');
    }

    public function test_admin_can_log_in_and_out(): void
    {
        $this->freezeTime();
        $this->seed(AdminAccessSeeder::class);
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::where('name', 'admin')->firstOrFail());

        $response = $this->post(route('login.store'), [
            'email' => $admin->email,
            'password' => 'password',
            'remember' => true,
        ]);
        $recallerName = Auth::guard()->getRecallerName();
        $response->assertRedirectToRoute('admin.dashboard')->assertCookie($recallerName);
        $this->assertSame(now()->addDays(7)->timestamp, $response->getCookie($recallerName)->getExpiresTime());

        $this->assertAuthenticatedAs($admin);
        $this->assertNotNull($admin->fresh()->last_login_at);

        $this->withoutVite()->get(route('admin.dashboard'))->assertOk()
            ->assertViewIs('admin.dashboard')
            ->assertSee('Belum ada data operasional');

        $this->post(route('logout'))->assertRedirectToRoute('login');
        $this->assertGuest();
        $this->get(route('admin.dashboard'))->assertRedirectToRoute('login');
    }

    public function test_user_without_dashboard_permission_cannot_access_admin(): void
    {
        $user = User::factory()->create();

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->actingAs($user)->get(route('admin.dashboard'))->assertForbidden();
    }

    public function test_suspended_admin_cannot_log_in_or_open_the_dashboard(): void
    {
        $this->seed(AdminAccessSeeder::class);
        $admin = User::factory()->create(['status' => 'suspended']);
        $admin->roles()->attach(Role::where('name', 'admin')->firstOrFail());

        $this->post(route('login.store'), [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertForbidden();
    }

    public function test_login_limit_does_not_block_a_different_account_on_the_same_ip(): void
    {
        $this->seed(AdminAccessSeeder::class);
        $role = Role::where('name', 'admin')->firstOrFail();
        [$admin, $colleague] = User::factory()->count(2)->create();
        $admin->roles()->attach($role);
        $colleague->roles()->attach($role);
        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.10']);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post(route('login.store'), [
                'email' => $admin->email,
                'password' => 'incorrect',
            ])->assertSessionHasErrors('email');
        }

        $this->post(route('login.store'), [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->post(route('login.store'), [
            'email' => $colleague->email,
            'password' => 'password',
        ])->assertRedirectToRoute('admin.dashboard');
        $this->assertAuthenticatedAs($colleague);
    }

    public function test_login_limit_applies_to_the_same_account_across_ips(): void
    {
        $this->freezeTime();
        $this->seed(AdminAccessSeeder::class);
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::where('name', 'admin')->firstOrFail());

        foreach (['192.0.2.10', '192.0.2.11'] as $ip) {
            $this->withServerVariables(['REMOTE_ADDR' => $ip]);

            for ($attempt = 0; $attempt < 5; $attempt++) {
                $this->post(route('login.store'), [
                    'email' => $admin->email,
                    'password' => 'incorrect',
                ])->assertSessionHasErrors('email');
            }
        }

        $credentials = ['email' => $admin->email, 'password' => 'password'];
        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.12'])
            ->post(route('login.store'), $credentials)->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->travel(61)->seconds();
        $this->post(route('login.store'), $credentials)->assertRedirectToRoute('admin.dashboard');
        $this->assertAuthenticatedAs($admin);
    }

    public function test_long_email_fits_in_database_cache_keys(): void
    {
        config(['cache.limiter' => 'database']);
        $email = str_repeat('a', 64).'@'.str_repeat('b', 60).'.'.str_repeat('c', 60).'.'.str_repeat('d', 60).'.test';

        $this->post(route('login.store'), [
            'email' => $email,
            'password' => 'incorrect',
        ])->assertSessionHasErrors('email');

        $keys = DB::table('cache')->pluck('key');
        $this->assertCount(4, $keys);

        foreach ($keys as $key) {
            $this->assertLessThanOrEqual(255, strlen($key));
        }
    }
}
