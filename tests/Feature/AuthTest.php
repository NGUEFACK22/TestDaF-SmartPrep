<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\ModellTestSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, SettingSeeder::class, ModellTestSeeder::class]);
    }

    public function test_guest_can_register_and_becomes_candidate(): void
    {
        $response = $this->post(route('register.store'), [
            'name' => 'Marie Dupont',
            'email' => 'marie@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();

        $user = User::where('email', 'marie@example.com')->first();
        $this->assertSame('candidate', $user->role->slug);
    }

    public function test_user_can_login_and_logout(): void
    {
        $role = Role::where('slug', 'candidate')->first();
        User::factory()->create(['email' => 'login@example.com', 'role_id' => $role->id]);

        $this->post(route('login.attempt'), [
            'email' => 'login@example.com',
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticated();

        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_admin_area_requires_admin_role(): void
    {
        $candidateRole = Role::where('slug', 'candidate')->first();
        $user = User::factory()->create(['role_id' => $candidateRole->id]);

        $this->actingAs($user)->get(route('admin.dashboard'))->assertForbidden();
    }
}
