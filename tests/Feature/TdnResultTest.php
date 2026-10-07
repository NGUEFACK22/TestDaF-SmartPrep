<?php

namespace Tests\Feature;

use App\Models\Attempt;
use App\Models\ModellTest;
use App\Models\Role;
use App\Models\User;
use App\Services\Exam\AnswerService;
use App\Services\Exam\ExamService;
use App\Services\Exam\ScoringService;
use Database\Seeders\LevelTrackSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TdnResultTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private ModellTest $test;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([RoleSeeder::class, SettingSeeder::class, LevelTrackSeeder::class]);

        $role = Role::where('slug', 'candidate')->first();
        $this->user = User::factory()->create(['role_id' => $role->id]);
        $this->test = ModellTest::where('number', 1)->first();
    }

    /** Termine la première tâche en la répondant correctement, puis agrège. */
    private function finishFirstExerciseWithScore(): Attempt
    {
        $engine = app(ExamService::class);
        $attempt = $engine->startAttempt($this->user, $this->test);

        $first = $attempt->attemptExercises()->orderBy('position')->first();
        $ae = $engine->startExercise($attempt, $first->exercise_id);

        foreach ($ae->formQuestions() as $question) {
            app(AnswerService::class)->save($attempt, $ae->fresh(), $question, $question->correct_answer);
            $engine->advanceQuestion($attempt, $ae->fresh());
        }

        $engine->completeExercise($attempt, $ae->exercise_id);

        app(ScoringService::class)->computeResults($attempt);

        return $attempt->refresh();
    }

    public function test_results_page_shows_points20_and_estimated_tdn_per_skill(): void
    {
        $attempt = $this->finishFirstExerciseWithScore();

        $reading = $attempt->results->firstWhere('skill', 'lesen');
        $this->assertNotNull($reading);
        $this->assertSame('TDN 5', $reading->tdn);
        $this->assertEqualsWithDelta(20.0, (float) $reading->points20, 0.01);

        $response = $this->actingAs($this->user)->get(route('results.show', $attempt));

        $response->assertOk()
            ->assertSee('TDN estimé')
            ->assertSee('TDN 5')
            ->assertSee('/ 20');
    }

    public function test_results_page_explains_the_0_to_20_scale_and_c1_target(): void
    {
        $attempt = $this->finishFirstExerciseWithScore();

        $response = $this->actingAs($this->user)->get(route('results.show', $attempt));

        $response->assertOk()
            ->assertSee('0–20')
            ->assertSee('16–20 points');
    }

    public function test_preparation_page_lists_levels_with_bank_and_ia(): void
    {
        $response = $this->actingAs($this->user)->get(route('preparation.index'));

        $response->assertOk();

        foreach (['A1', 'A2', 'B1', 'B2', 'C1', 'C2'] as $level) {
            $response->assertSee($level);
        }

        $response->assertSee('Banque QCM', false);
        $response->assertSee('IA inédite', false);
        $response->assertSee('Espace Élite', false);
    }
}