<?php

namespace Tests\Feature;

use App\Models\Attempt;
use App\Models\ModellTest;
use App\Models\Role;
use App\Models\User;
use App\Services\Exam\AnswerService;
use App\Services\Exam\ExamService;
use App\Services\Exam\ScoringService;
use Database\Seeders\ModellTestSeeder;
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

        $this->seed([RoleSeeder::class, SettingSeeder::class, ModellTestSeeder::class]);

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
            app(AnswerService::class)->save($attempt, $ae, $question, $question->correct_answer);
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

    public function test_preparation_page_shows_c1_methodology_per_skill(): void
    {
        $this->actingAs($this->user)->get(route('preparation.show', 'lesen'))
            ->assertOk()
            ->assertSee('LESEN — méthode C1')
            ->assertSee('Progression C1 — 6 niveaux')
            ->assertSee('Mode examen');

        $this->actingAs($this->user)->get(route('preparation.show', 'schreiben'))
            ->assertOk()
            ->assertSee('SCHREIBEN — méthode C1')
            ->assertSee('minimum 200 mots');
    }

    public function test_preparation_page_shows_speaking_time_targets(): void
    {
        $response = $this->actingAs($this->user)->get(route('preparation.show', 'sprechen'));

        $response->assertOk()
            ->assertSee('SPRECHEN — méthode C1')
            ->assertSee('00:45 de parole')   // Rat geben : 45 s
            ->assertSee('02:30 de parole');  // Thema präsentieren : 2 min 30
    }
}