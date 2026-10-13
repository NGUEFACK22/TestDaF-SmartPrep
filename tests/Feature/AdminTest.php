<?php

namespace Tests\Feature;

use App\Jobs\GenerateChallengeQuestions;
use App\Models\AiChallenge;
use App\Models\ModellTest;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\LevelTrackSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
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

    public function test_admin_ai_overview_loads(): void
    {
        AiChallenge::create([
            'user_id' => $this->admin->id,
            'modell_test_id' => $this->firstTest()->id,
            'skill' => 'lesen',
            'status' => 'failed',
            'level' => 'C1',
            'weak_snapshot' => [],
            'error' => 'IA momentanément indisponible (erreur 429).',
            'requested_at' => now(),
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.ai.index'))
            ->assertOk()
            ->assertSee('Suivi des générations IA', false)
            ->assertSee('Relancer', false);
    }

    public function test_admin_can_retry_failed_generation(): void
    {
        Queue::fake();

        $challenge = AiChallenge::create([
            'user_id' => $this->admin->id,
            'modell_test_id' => $this->firstTest()->id,
            'skill' => 'lesen',
            'status' => 'failed',
            'level' => 'C1',
            'weak_snapshot' => [],
            'error' => 'Génération incomplète (8/20).',
            'requested_at' => now()->subHour(),
            'completed_at' => now()->subHour(),
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.ai.retry', $challenge))
            ->assertRedirect();

        $challenge->refresh();
        $this->assertSame('generating', $challenge->status);
        $this->assertNull($challenge->error);
        $this->assertNull($challenge->completed_at);
        Queue::assertPushed(GenerateChallengeQuestions::class, fn ($job) => $job->challengeId === $challenge->id);
    }

    public function test_candidate_cannot_access_admin_ai(): void
    {
        $user = User::factory()->create(['role_id' => Role::where('slug', 'candidate')->first()->id]);

        $this->actingAs($user)->get(route('admin.ai.index'))->assertForbidden();
    }

    private function firstTest(): ModellTest
    {
        return ModellTest::where('number', 1)->first() ?? ModellTest::first();
    }
}
