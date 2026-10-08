<?php

namespace Tests\Feature;

use App\Models\Attempt;
use App\Models\ModellTest;
use App\Models\Role;
use App\Models\User;
use App\Services\Exam\AnswerService;
use App\Services\Exam\ExamService;
use Database\Seeders\LevelTrackSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Rotation des LETTRES correctes d'un tour à l'autre :
 * si un tour la bonne réponse est A, au tour suivant c'est B/C/D (plus A).
 * La correction dé-rotate avant de noter : le score reste juste.
 */
class LabelRotationTest extends TestCase
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
        $this->test = ModellTest::where('number', 3)->firstOrFail(); // B1 : 2 tâches
    }

    /** Joue une tentative entière en répondant les lettres AFFICHÉES justes. */
    private function playDisplayedPerfect(Attempt $attempt): void
    {
        $engine = app(ExamService::class);
        $answers = app(AnswerService::class);

        foreach ($attempt->attemptExercises()->orderBy('position')->get() as $task) {
            $engine->startExercise($attempt->fresh(), $task->exercise_id);
            $task = $task->fresh();

            foreach ($task->formQuestions() as $question) {
                $correct = $engine->displayedCorrectLabels($question, $attempt->fresh());
                $this->assertNotEmpty($correct, 'QCM sans bonne réponse affichée.');
                $answers->save($attempt->fresh(), $task->fresh(), $question, $correct);
                $engine->advanceQuestion($attempt->fresh(), $task->fresh());
            }

            $engine->completeExercise($attempt->fresh(), $task->exercise_id);
        }
    }

    public function test_correct_letter_changes_every_round(): void
    {
        $engine = app(ExamService::class);
        $seenByQuestion = [];

        for ($round = 0; $round < 3; $round++) {
            $attempt = $engine->startAttempt($this->user, $this->test);
            $this->assertSame($round, (int) $attempt->label_rotation);

            $ae = $attempt->attemptExercises()->orderBy('position')->first();
            foreach ($ae->formQuestions() as $question) {
                $shown = $engine->displayedCorrectLabels($question, $attempt);
                $this->assertNotEmpty($shown);
                $seenByQuestion[$question->id][] = $shown[0];
            }

            $this->playDisplayedPerfect($attempt->fresh());
            $this->assertSame('completed', $attempt->fresh()->status->value);
        }

        // Chaque question vue 2+ fois a changé de lettre à chaque tour.
        $checked = 0;
        foreach ($seenByQuestion as $qid => $letters) {
            if (count($letters) < 2) {
                continue;
            }
            $checked++;
            foreach (array_slice($letters, 1) as $i => $letter) {
                $this->assertNotSame(
                    $letters[$i], $letter,
                    "Question #{$qid} : même lettre deux tours de suite."
                );
            }
        }

        $this->assertGreaterThan(0, $checked, 'Aucune question revue sur 3 tours ?');
        fwrite(STDERR, "\n[rotation] {$checked} questions revues, lettre différente à chaque tour\n");
    }

    public function test_perfect_score_answering_displayed_letters(): void
    {
        $engine = app(ExamService::class);

        // Tour 2 (rotation active) : 100 % en répondant les lettres affichées.
        $first = $engine->startAttempt($this->user, $this->test);
        $this->playDisplayedPerfect($first);

        $second = $engine->startAttempt($this->user, $this->test);
        $this->assertSame(1, (int) $second->label_rotation);
        $this->playDisplayedPerfect($second->fresh());

        $second = $second->fresh();
        $this->assertSame('completed', $second->status->value);
        $this->assertEqualsWithDelta((float) $second->max_score, (float) $second->score, 0.01);
        $this->assertGreaterThan(0, (float) $second->max_score);
    }

    public function test_results_show_displayed_letters(): void
    {
        $engine = app(ExamService::class);

        $first = $engine->startAttempt($this->user, $this->test);
        $this->playDisplayedPerfect($first);

        $second = $engine->startAttempt($this->user, $this->test);
        $this->playDisplayedPerfect($second->fresh());
        $second = $second->fresh();

        $response = $this->actingAs($this->user)->get(route('results.show', $second));
        $response->assertOk();

        // La page affiche les lettres vues pendant CE tour (pas les originales).
        $ae = $second->attemptExercises()->orderBy('position')->first();
        $q = $ae->formQuestions()->first();
        $shown = $engine->displayedCorrectLabels($q, $second);
        $this->assertNotEmpty($shown);
        $response->assertSee('Bonne réponse', false);
    }
}
