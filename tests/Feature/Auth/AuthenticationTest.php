<?php

namespace Tests\Feature\Auth;

use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('style="color-scheme: light"', false);
        $response->assertSee("document.documentElement.classList.remove('dark');", false);
        $response->assertDontSee('prefers-color-scheme: dark', false);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $admin = User::factory()->admin()->create();
        $admin->roles()->attach(Role::where('name', 'admin')->firstOrFail());

        $this->post('/login', [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard', absolute: false));

        $this->post('/logout');

        $employeeUser = User::factory()->create();
        $employeeUser->roles()->attach(Role::where('name', 'employee')->firstOrFail());

        Employee::create([
            'user_id' => $employeeUser->id,
            'employee_code' => 'EMP-001',
            'name' => $employeeUser->name,
            'employment_status' => 'active',
            'basic_salary' => 5_000_000,
        ]);

        $this->post('/login', [
            'email' => $employeeUser->email,
            'password' => 'password',
        ])->assertRedirect(route('attendance.index', absolute: false));

        $this->assertAuthenticated();
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect(route('home', absolute: false));
    }

    public function test_login_without_remember_does_not_set_remember_cookie(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect();

        $this->assertAuthenticated();

        $response->assertCookieMissing(
            Auth::guard()->getRecallerName()
        );
    }

    public function test_login_with_remember_sets_remember_token_and_cookie(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
            'remember' => 'on',
        ])->assertRedirect();

        $this->assertAuthenticated();
        $this->assertNotNull($user->fresh()->remember_token);
        $response->assertCookie(Auth::guard()->getRecallerName());
    }

    public function test_logout_after_remember_login_invalidates_remember_token(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
            'remember' => 'on',
        ]);

        $rememberToken = $user->fresh()->remember_token;
        $this->assertNotNull($rememberToken);

        $this->post('/logout');

        $this->assertGuest();
        $this->assertNotSame($rememberToken, $user->fresh()->remember_token);
    }

    public function test_remember_login_redirects_admin_and_employee_correctly(): void
    {
        $admin = User::factory()->admin()->create();
        $admin->roles()->attach(Role::where('name', 'admin')->firstOrFail());

        $this->post('/login', [
            'email' => $admin->email,
            'password' => 'password',
            'remember' => true,
        ])->assertRedirect(route('dashboard', absolute: false));

        $this->post('/logout');

        $employeeUser = User::factory()->create();
        $employeeUser->roles()->attach(Role::where('name', 'employee')->firstOrFail());

        Employee::create([
            'user_id' => $employeeUser->id,
            'employee_code' => 'EMP-002',
            'name' => $employeeUser->name,
            'employment_status' => 'active',
            'basic_salary' => 5_000_000,
        ]);

        $this->post('/login', [
            'email' => $employeeUser->email,
            'password' => 'password',
            'remember' => true,
        ])->assertRedirect(route('attendance.index', absolute: false));
    }
}
