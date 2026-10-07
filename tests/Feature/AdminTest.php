<?php

namespace Tests\Feature;

use App\Models\ModellTest;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\LevelTrackSeeder;
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
            LevelTrackSeeder::class,
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

    public function test_admin_can_create_qcm_exercise(): void
    {
        $this->actingAs($this->admin)->post(route('admin.exercises.store'), [
            'skill' => 'lesen',
            'type' => 'multiple_choice',
            'title' => 'Exercice QCM A1',
            'level' => 'A1',
            'difficulty' => 'A1',
            'duration_seconds' => 300,
            'status' => 'draft',
        ])->assertRedirect();

        $this->assertDatabaseHas('exercises', ['title' => 'Exercice QCM A1', 'difficulty' => 'A1']);
    }

    public function test_candidate_cannot_access_admin_users(): void
    {
        $user = User::factory()->create(['role_id' => Role::where('slug', 'candidate')->first()->id]);

        $this->actingAs($user)->get(route('admin.users.index'))->assertForbidden();
    }
}
