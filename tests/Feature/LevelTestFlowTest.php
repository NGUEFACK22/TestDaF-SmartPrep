<?php

namespace Tests\Feature;

use App\Enums\AttemptStatus;
use App\Models\Attempt;
use App\Models\ModellTest;
use App\Models\Role;
use App\Models\User;
use App\Services\Exam\AnswerService;
use App\Services\Exam\ExamService;
use Database\Seeders\HoerenDemoSeeder;
use Database\Seeders\LevelTestSeeder;
use Database\Seeders\ModellTestSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * LevelTestFlowTest — flux « Préparation → niveau C1 → test de positionnement ».
 *
 * Le test C1 (LevelTestSeeder) contient deux parties :
 * - Hörverstehen (FIXE) : les 2 grandes tâches audio officielles ;
 * - Leseverstehen (DYNAMIQUE) : texte + forme de 4 QCM tirée par tentative.
 */
class LevelTestFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RoleSeeder::class,
            SettingSeeder::class,
            ModellTestSeeder::class,
            HoerenDemoSeeder::class,
            LevelTestSeeder::class,
        ]);

        $role = Role::where('slug', 'candidate')->first();
        $this->user = User::factory()->create(['role_id' => $role->id]);
    }

    private function c1Test(): ModellTest
    {
        return ModellTest::where('number', LevelTestSeeder::C1_TEST_NUMBER)->firstOrFail();
    }

    private function candidate(): User
    {
        $role = Role::where('slug', 'candidate')->first();

        return User::factory()->create(['role_id' => $role->id]);
    }

    public function test_preparation_page_lists_levels_with_direct_c1_start(): void
    {
        $response = $this->actingAs($this->user)->get(route('preparation.index'));

        $response->assertOk();

        foreach (['A1', 'A2', 'B1', 'B2', 'C1', 'C2'] as $level) {
            $response->assertSee($level);
        }

        $response->assertSee('Commencer le test C1');
        $response->assertSee('À venir');
        $response->assertSee('Réviser par compétence');
    }

    public function test_starting_the_c1_level_creates_an_attempt_with_two_parts(): void
    {
        $response = $this->actingAs($this->user)->post(route('preparation.start', 'C1'));

        $response->assertRedirect();

        $attempt = Attempt::latest('id')->first();
        $this->assertNotNull($attempt);
        $this->assertSame($this->c1Test()->id, $attempt->modell_test_id);

        $skills = $attempt->attemptExercises()->orderBy('position')->get()
            ->map(fn ($ae) => $ae->exercise->skill->value)
            ->all();

        // Deux parties : d'abord l'audio (2 tâches), puis le texte.
        $this->assertSame(['hoeren', 'hoeren', 'lesen'], $skills);
    }

    public function test_unknown_levels_cannot_start_a_test(): void
    {
        $this->actingAs($this->user)
            ->post(route('preparation.start', 'A1'))
            ->assertNotFound();
    }

    public function test_audio_part_is_fixed_and_text_part_is_a_four_question_form(): void
    {
        $engine = app(ExamService::class);
        $other = $this->candidate();

        $first = $engine->startAttempt($this->user, $this->c1Test());
        $second = $engine->startAttempt($other, $this->c1Test());

        $audioIds1 = $first->attemptExercises
            ->filter(fn ($ae) => $ae->exercise->skill->value === 'hoeren')
            ->flatMap(fn ($ae) => $ae->formQuestions()->pluck('id'))
            ->values();
        $audioIds2 = $second->attemptExercises
            ->filter(fn ($ae) => $ae->exercise->skill->value === 'hoeren')
            ->flatMap(fn ($ae) => $ae->formQuestions()->pluck('id'))
            ->values();

        // Partie audio FIXE : mêmes questions pour les deux candidats
        // (5 QCM de la démo 1 + 1 grande question de la démo 7).
        $this->assertCount(6, $audioIds1);
        $this->assertSame($audioIds1->all(), $audioIds2->all());

        // Partie texte : forme de 4 questions tirées du pool (8 au total).
        $reading = $first->attemptExercises
            ->first(fn ($ae) => $ae->exercise->skill->value === 'lesen');

        $this->assertSame(8, $reading->exercise->questions()->count());
        $this->assertCount(4, $reading->formQuestions());

        // Minuteur par partie actif sur la partie texte (questions chronométrées).
        $this->assertNotNull($reading->exercise->duration_seconds);
        $this->assertTrue($reading->formQuestions()->every(
            fn ($q) => $q->time_limit_seconds > 0
        ));
    }

    public function test_full_c1_run_scores_each_part_and_the_results_page_shows_improvements(): void
    {
        $engine = app(ExamService::class);
        $answers = app(AnswerService::class);

        $attempt = $engine->startAttempt($this->user, $this->c1Test());

        foreach ($attempt->attemptExercises()->orderBy('position')->get() as $task) {
            $engine->startExercise($attempt->fresh(), $task->exercise_id);
            $task = $task->fresh();

            $outcome = null;

            foreach ($task->formQuestions() as $question) {
                $answers->save($attempt->fresh(), $task->fresh(), $question, (array) $question->correct_answer);
                $outcome = $engine->advanceQuestion($attempt->fresh(), $task->fresh())['outcome'];
            }

            // La dernière question clôture la tâche (ou le test pour la fin).
            $this->assertContains($outcome, ['exercise', 'finished']);
        }

        $attempt = $attempt->fresh();
        $this->assertSame(AttemptStatus::Completed, $attempt->status);

        // Score par partie : Hörverstehen (6 points) + Leseverstehen (4 points).
        $results = $attempt->results()->get()->keyBy(fn ($r) => $r->skill->value);
        $this->assertArrayHasKey('hoeren', $results);
        $this->assertArrayHasKey('lesen', $results);
        $this->assertEquals(6.0, (float) $results['hoeren']->max_points);
        $this->assertEquals(4.0, (float) $results['lesen']->max_points);
        $this->assertEquals(6.0, (float) $results['hoeren']->points);
        $this->assertEquals(4.0, (float) $results['lesen']->points);

        // Page résultats : score par partie + résumé des points à améliorer.
        $this->actingAs($this->user)
            ->get(route('results.show', $attempt))
            ->assertOk()
            ->assertSee('Score par partie')
            ->assertSee('Points à améliorer');
    }
}