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
 * Lettres affichées : toujours dans l'ordre (a, b, c, d de haut en bas) ;
 * la bonne réponse porte la lettre de la position où son contenu a atterri
 * (brassage par graine). Elle varie d'une question à l'autre et d'une
 * tentative à l'autre, la correction utilisant les mêmes lettres affichées.
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

    public function test_letters_in_order_and_correct_letters_vary(): void
    {
        $engine = app(ExamService::class);
        $seenByQuestion = [];

        for ($round = 0; $round < 3; $round++) {
            $attempt = $engine->startAttempt($this->user, $this->test);
            $this->assertSame($round, (int) $attempt->label_rotation);

            $ae = $attempt->attemptExercises()->orderBy('position')->first();
            $roundCorrect = [];

            foreach ($ae->formQuestions() as $question) {
                // Lettres affichées : toujours dans l'ordre (a, b, c, d).
                $labels = array_column($engine->displayOptionsForAttempt($question, $attempt), 'label');
                $sorted = $labels;
                sort($sorted);
                $this->assertSame($sorted, $labels, 'Question #'.$question->id.' : lettres désordonnées.');

                $shown = $engine->displayedCorrectLabels($question, $attempt);
                $this->assertNotEmpty($shown);
                $roundCorrect[] = $shown[0];
                $seenByQuestion[$question->id][] = $shown[0];
            }

            // Diversité intra-tentative : les réponses justes portent des
            // lettres différentes (pas toutes a, pas toutes la même).
            if (count($roundCorrect) >= 3) {
                $this->assertGreaterThan(
                    1,
                    count(array_unique($roundCorrect)),
                    'Tour '.$round.' : toutes les bonnes réponses portent la même lettre.'
                );
            }

            $this->playDisplayedPerfect($attempt->fresh());
            $this->assertSame('completed', $attempt->fresh()->status->value);
        }

        // D'une tentative à l'autre, au moins une question revue change de lettre.
        $reviewed = $changed = 0;
        foreach ($seenByQuestion as $qid => $letters) {
            if (count($letters) < 2) {
                continue;
            }
            $reviewed++;
            if (count(array_unique($letters)) > 1) {
                $changed++;
            }
        }

        $this->assertGreaterThan(0, $reviewed, 'Aucune question revue sur 3 tours ?');
        $this->assertGreaterThan(0, $changed, 'Les lettres doivent changer entre les tours.');
        fwrite(STDERR, "\n[rotation] {$changed}/{$reviewed} questions revues ont changé de lettre\n");
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
