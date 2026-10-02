<?php

namespace Tests\Feature;

use App\Models\ModellTest;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\ModellTestSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([
            RoleSeeder::class,
            AdminUserSeeder::class,
            SettingSeeder::class,
            ModellTestSeeder::class,
        ]);
        $this->admin = User::where('email', 'admin@testdaf.local')->first();
    }

    public function test_admin_dashboard_loads(): void
    {
        $this->actingAs($this->admin)->get(route('admin.dashboard'))->assertOk();
    }

    public function test_admin_can_update_ai_settings(): void
    {
        $this->actingAs($this->admin)->put(route('admin.settings.update'), [
            'ai_enabled' => '1',
            'ai_provider' => 'gemini',
            'ai_model' => 'gemini-1.5-flash',
            'max_ai_requests' => 50,
        ])->assertRedirect();

        $this->assertTrue((bool) Setting::get('ai_enabled'));
        $this->assertSame(50, Setting::get('max_ai_requests'));
    }

    public function test_admin_can_publish_modelltest(): void
    {
        $test = ModellTest::where('number', 1)->first();

        $this->actingAs($this->admin)->put(route('admin.modelltests.update', $test), [
            'number' => 1,
            'title' => 'Modelltest 1 — Mise à jour',
            'difficulty' => 'B2',
            'status' => 'published',
        ])->assertRedirect();

        $this->assertSame('published', $test->refresh()->status);
    }

    public function test_candidate_cannot_access_admin_users(): void
    {
        $user = User::factory()->create(['role_id' => Role::where('slug', 'candidate')->first()->id]);

        $this->actingAs($user)->get(route('admin.users.index'))->assertForbidden();
    }
}
